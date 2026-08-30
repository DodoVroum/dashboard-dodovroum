<?php

namespace App\Console\Commands;

use App\Services\ImageProcessingService;
use App\Services\LegacyImageOptimizationService;
use Illuminate\Console\Command;

/**
 * Rétro-optimise les images de résidences déjà stockées avant la mise en place
 * du pipeline d'upload optimisé : redimensionnement, correction EXIF, recompression.
 *
 * Ne modifie JAMAIS le nom de fichier ni les références en base — chaque image est
 * remplacée sur le serveur en conservant exactement son URL actuelle. L'original
 * est sauvegardé automatiquement côté API avant tout écrasement (voir
 * DodoVroum-backend: PUT /upload/replace/{category}/{filename}).
 */
class OptimizeResidenceImages extends Command
{
    protected $signature = 'images:optimize
                            {--dry-run : Analyse et affiche les gains estimés sans modifier aucun fichier}
                            {--limit= : Nombre maximum d\'images à analyser}
                            {--force : Retraite même une image déjà jugée suffisamment optimisée}';

    protected $description = "Redimensionne et recompresse les anciennes images de résidences (1920px max, même format, aucune référence en base modifiée)";

    public function handle(LegacyImageOptimizationService $optimizer, ImageProcessingService $imageProcessing): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $limitOption = $this->option('limit');
        $limit = $limitOption !== null ? (int) $limitOption : null;

        if ($dryRun) {
            $this->warn('Mode DRY-RUN : analyse uniquement, aucun fichier ne sera modifié.');
        } else {
            $this->warn('⚠️  Mode RÉEL : les images correspondantes seront réellement remplacées sur le serveur.');
            $this->line('   (une sauvegarde de chaque original est créée automatiquement côté API avant écrasement)');
            if (!$this->confirm('Confirmer le lancement en mode réel ?', false)) {
                $this->info('Annulé — aucune modification effectuée.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('Récupération de la liste des résidences...');

        $stats = [
            'analysees' => 0,
            'optimisees' => 0,
            'ignorees' => 0,
            'erreurs' => 0,
            'poids_avant' => 0,
            'poids_apres' => 0,
        ];

        foreach ($optimizer->iterateResidenceImages() as $item) {
            if ($limit !== null && $stats['analysees'] >= $limit) {
                break;
            }
            $stats['analysees']++;

            $target = $optimizer->parseCategoryAndFilename($item['url']);
            if ($target === null) {
                $stats['ignorees']++;
                $this->line("  <fg=gray>[ignoré] {$item['url']} (hors schéma de nommage, non ciblable en sécurité)</>");
                continue;
            }

            $content = $optimizer->downloadImage($item['url']);
            if ($content === null) {
                $stats['erreurs']++;
                $this->error("  [erreur] Téléchargement impossible : {$item['url']}");
                continue;
            }

            $originalSize = strlen($content);

            if ($optimizer->shouldSkip($content, $force)) {
                $stats['ignorees']++;
                $stats['poids_avant'] += $originalSize;
                $stats['poids_apres'] += $originalSize;
                continue;
            }

            $result = $imageProcessing->reoptimizeInPlace($content);
            if ($result === null) {
                $stats['erreurs']++;
                $this->error("  [erreur] Image non décodable (corrompue ou format non supporté) : {$item['url']}");
                continue;
            }

            $newSize = strlen($result['content']);

            // Une recompression qui n'apporte aucun gain n'est jamais appliquée.
            if ($newSize >= $originalSize) {
                $stats['ignorees']++;
                $stats['poids_avant'] += $originalSize;
                $stats['poids_apres'] += $originalSize;
                continue;
            }

            $stats['poids_avant'] += $originalSize;
            $stats['poids_apres'] += $newSize;

            $reduction = round((1 - $newSize / $originalSize) * 100, 1);
            $this->line(sprintf(
                '  [optimisé] %s — %s : %s → %s (-%s%%)',
                $item['residenceTitle'],
                $target['filename'],
                $this->formatBytes($originalSize),
                $this->formatBytes($newSize),
                $reduction
            ));

            if ($dryRun) {
                $stats['optimisees']++;
                continue;
            }

            try {
                $mime = $optimizer->mimeForExtension(pathinfo($target['filename'], PATHINFO_EXTENSION));
                $optimizer->replaceRemoteFile($target['category'], $target['filename'], $result['content'], $mime);
                $stats['optimisees']++;
            } catch (\Throwable $e) {
                $stats['erreurs']++;
                $this->error("  [erreur] Remplacement distant échoué pour {$target['filename']} : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $reductionPct = $stats['poids_avant'] > 0
            ? round((1 - $stats['poids_apres'] / $stats['poids_avant']) * 100, 1)
            : 0;

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Images analysées', $stats['analysees']],
                ['Images optimisées', $stats['optimisees']],
                ['Images ignorées', $stats['ignorees']],
                ['Erreurs', $stats['erreurs']],
                ['Poids total avant', $this->formatBytes($stats['poids_avant'])],
                ['Poids total après', $this->formatBytes($stats['poids_apres'])],
                ['Espace économisé', $this->formatBytes($stats['poids_avant'] - $stats['poids_apres'])],
                ['Réduction', "{$reductionPct}%"],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry-run terminé — relancez sans --dry-run pour appliquer réellement ces changements.');
        }

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return "{$bytes} o";
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' Ko';
        }
        return round($bytes / (1024 * 1024), 2) . ' Mo';
    }
}
