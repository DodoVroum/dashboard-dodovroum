<?php

namespace App\Services;

use App\Services\DodoVroumApi\AuthService;
use App\Services\DodoVroumApi\ResidenceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Orchestre la rétro-optimisation des images de résidences déjà stockées
 * (commande `images:optimize`). N'écrit jamais en base : chaque fichier est
 * remplacé sur le serveur NestJS en conservant exactement son nom actuel,
 * via l'endpoint admin PUT /upload/replace/{category}/{filename}.
 */
class LegacyImageOptimizationService
{
    // Une image déjà sous ce poids est considérée comme suffisamment optimisée
    // et n'est jamais retraitée (sauf --force).
    private const SKIP_MAX_BYTES = 300 * 1024;

    // Une image déjà à une largeur raisonnable ET pas trop lourde est également
    // laissée telle quelle : évite de recompresser à répétition (perte de qualité
    // cumulative) une image déjà correcte.
    private const SKIP_MAX_BYTES_IF_SMALL_DIMENSIONS = 800 * 1024;
    private const SKIP_MAX_WIDTH = 1920;

    private const GENERATED_FILENAME_PATTERN =
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.(jpg|jpeg|png|webp|gif)$/i';

    public function __construct(
        private ResidenceService $residenceService,
        private AuthService $authService,
    ) {}

    /**
     * @return \Generator<array{residenceId: string, residenceTitle: string, url: string}>
     */
    public function iterateResidenceImages(): \Generator
    {
        $residences = $this->residenceService->allMapped();

        foreach ($residences as $residence) {
            $id = $residence['id'] ?? null;
            $images = $residence['images'] ?? [];

            if (!$id || !is_array($images)) {
                continue;
            }

            foreach ($images as $url) {
                if (!is_string($url) || trim($url) === '') {
                    continue;
                }

                yield [
                    'residenceId' => (string) $id,
                    'residenceTitle' => $residence['title'] ?? $residence['name'] ?? (string) $id,
                    'url' => $url,
                ];
            }
        }
    }

    /**
     * Extrait {category}/{filename} d'une URL de stockage, uniquement si le nom de
     * fichier suit exactement le schéma généré par l'upload (uuid.ext). Toute URL
     * hors de ce schéma (image par défaut, ancien format, URL malformée) n'est
     * volontairement pas ciblable : on ne devine jamais un chemin sur le serveur.
     *
     * @return array{category: string, filename: string}|null
     */
    public function parseCategoryAndFilename(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        if (!preg_match('#/storage/([a-z0-9_-]+)/([^/]+)$#i', $path, $matches)) {
            return null;
        }

        [, $category, $filename] = $matches;

        if (!preg_match(self::GENERATED_FILENAME_PATTERN, $filename)) {
            return null;
        }

        return ['category' => strtolower($category), 'filename' => $filename];
    }

    /**
     * Certaines images legacy sont référencées en base sous l'ancien domaine
     * `dodovroum.com`, qui ne sert plus `/storage` (404) — seul le sous-domaine
     * `api.dodovroum.com` sert effectivement les fichiers. Même correctif que
     * `resources/js/admin/utils/imageUrl.ts` côté frontend.
     */
    private function normalizeDownloadUrl(string $url): string
    {
        return preg_replace('#^(https?://)(www\.)?dodovroum\.com/#i', '$1api.dodovroum.com/', $url) ?? $url;
    }

    public function downloadImage(string $url): ?string
    {
        $url = $this->normalizeDownloadUrl($url);

        try {
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning('Téléchargement d\'image legacy échoué', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function shouldSkip(string $binaryContent, bool $force): bool
    {
        if ($force) {
            return false;
        }

        $size = strlen($binaryContent);
        if ($size <= self::SKIP_MAX_BYTES) {
            return true;
        }

        $info = @getimagesizefromstring($binaryContent);
        if ($info !== false && $info[0] <= self::SKIP_MAX_WIDTH && $size <= self::SKIP_MAX_BYTES_IF_SMALL_DIMENSIONS) {
            return true;
        }

        return false;
    }

    /**
     * Remplace le fichier existant sur le serveur NestJS. Lève une exception si
     * l'appel échoue — l'appelant est responsable de compter l'échec et de
     * continuer avec l'image suivante.
     */
    public function replaceRemoteFile(string $category, string $filename, string $content, string $mime): array
    {
        $token = $this->authService->getAccessToken();
        if (!$token) {
            throw new \RuntimeException("Impossible d'obtenir un token d'authentification admin.");
        }

        $apiBaseUrl = config('services.dodovroum.api_url_local', config('services.dodovroum.api_url'));
        $url = rtrim($apiBaseUrl, '/') . "/upload/replace/{$category}/{$filename}";

        $response = Http::timeout(30)
            ->withToken($token)
            ->attach('file', $content, $filename, ['Content-Type' => $mime])
            ->put($url);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Échec du remplacement distant (HTTP {$response->status()}) : " . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    public function mimeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
