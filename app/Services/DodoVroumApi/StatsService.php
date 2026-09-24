<?php

namespace App\Services\DodoVroumApi;

use App\Services\DodoVroumApi\BaseApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service pour récupérer les statistiques depuis l'API NestJS
 *
 * Appelle GET {DODOVROUM_API_URL}/stats (ex. https://…/api/stats). Aucun ownerId dans l’URL :
 * le périmètre est déduit du JWT (Bearer) côté NestJS.
 */
class StatsService extends BaseApiService
{
    /**
     * Récupérer les statistiques du propriétaire connecté (scope JWT).
     *
     * @param string|null $ownerId Conservé pour compatibilité des appelants ; non envoyé à l’API.
     * @return array|null Retourne null si l'endpoint n'existe pas (404), pour permettre le fallback
     */
    public function getOwnerStats(?string $ownerId = null): ?array
    {
        try {
            $response = $this->get('stats');

            // Si l'endpoint retourne un tableau vide (404 géré par BaseApiService),
            // on retourne null pour indiquer que l'endpoint n'existe pas encore
            if (empty($response)) {
                Log::info('Endpoint stats non disponible (404), fallback sur calcul local');
                return null;
            }
            
            if ($response && is_array($response)) {
                Log::info('Stats récupérées depuis l\'API NestJS', [
                    'has_data' => !empty($response),
                    'keys' => array_keys($response),
                ]);
                return $response;
            }
            
            Log::warning('Réponse API stats invalide', [
                'response' => $response,
                'response_type' => gettype($response),
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            // Si c'est une erreur 404, c'est normal (endpoint pas encore implémenté)
            if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Not Found')) {
                Log::info('Endpoint stats non trouvé, fallback sur calcul local', [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
            
            Log::error('Erreur lors de la récupération des stats depuis l\'API', [
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Revenus encaissés du propriétaire connecté (scope JWT) : paiements COMPLETED
     * non marqués à rembourser ; monthRevenue selon la date d'encaissement.
     *
     * GET /stats renvoie un objet sans `id` ({ success, data: {...} }) : il ne peut
     * pas passer par get() / ApiResponseNormalizer::data(), qui ne conserve que des
     * listes d'entités identifiées et renverrait un tableau vide.
     *
     * @return array{totalRevenue: float, monthRevenue: float}|null null si indisponible
     */
    public function getOwnerRevenue(): ?array
    {
        $token = $this->getAuthToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::timeout(10)->withToken($token)->acceptJson()->get("{$this->baseUrl}/stats");
        } catch (\Throwable $e) {
            Log::warning('GET /stats injoignable (revenus encaissés)', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('GET /stats en échec (revenus encaissés)', ['status' => $response->status()]);

            return null;
        }

        $payload = $response->json();
        $stats = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        if (! is_array($stats) || ! isset($stats['totalRevenue'], $stats['monthRevenue'])) {
            Log::warning('GET /stats sans totalRevenue / monthRevenue', [
                'keys' => is_array($stats) ? array_keys($stats) : null,
            ]);

            return null;
        }

        return [
            'totalRevenue' => (float) $stats['totalRevenue'],
            'monthRevenue' => (float) $stats['monthRevenue'],
        ];
    }

    /**
     * Retourner des statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats(): array
    {
        return [
            'totalRevenue' => 0,
            'revenueTrend' => 0,
            'totalBookings' => 0,
            'bookingsTrend' => 0,
            'occupationRate' => 0,
            'occupationTrend' => 0,
            'activeProperties' => 0,
            'propertiesTrend' => 0,
            'chartData' => [],
        ];
    }
}

