<?php

namespace App\Services;

use App\Exceptions\ImageUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Normalise toute image entrante (résidences, véhicules, profil, offres combo)
 * avant qu'elle ne soit transmise à l'API NestJS : redimensionnement, ré-orientation
 * EXIF et compression en WebP. Sert de filet de sécurité serveur même quand le
 * navigateur a déjà fait ce travail côté client.
 *
 * Limite connue : le serveur ne tourne qu'avec GD (pas d'Imagick/libheif), qui ne
 * sait pas décoder le HEIC ni les formats RAW (DNG, CR2, NEF, ARW...). Le HEIC doit
 * donc déjà être converti côté navigateur avant d'arriver ici ; le RAW n'est jamais
 * pris en charge et doit être rejeté avec un message clair.
 */
class ImageProcessingService
{
    private const MAX_WIDTH = 1920;
    private const RECOMPRESS_QUALITY = 82; // JPEG/WebP, ~80-85% visé
    private const PNG_COMPRESSION_LEVEL = 6; // 0 (aucune) à 9 (max), 6 = compromis standard GD
    private const MAX_ORIGINAL_SIZE_BYTES = 20 * 1024 * 1024; // 20MB avant traitement

    private const RAW_EXTENSIONS = [
        'dng', 'raw', 'cr2', 'cr3', 'nef', 'arw', 'raf', 'orf', 'rw2', 'pef', 'srw',
    ];

    private const RAW_MIME_MARKERS = [
        'x-adobe-dng', 'x-canon-cr2', 'x-canon-cr3', 'x-nikon-nef', 'x-sony-arw',
        'x-fuji-raf', 'x-olympus-orf', 'x-panasonic-rw2', 'x-pentax-pef', 'x-samsung-srw',
    ];

    private const HEIC_EXTENSIONS = ['heic', 'heif'];

    /**
     * @return array{content: string, filename: string, mime: string}
     */
    public function process(UploadedFile $file): array
    {
        $this->guardAgainstUnsupportedFormats($file);

        if ($file->getSize() > self::MAX_ORIGINAL_SIZE_BYTES) {
            $maxMb = (int) (self::MAX_ORIGINAL_SIZE_BYTES / 1024 / 1024);
            throw ImageUploadException::tooLarge($maxMb);
        }

        $path = $file->getRealPath();
        $imageInfo = @getimagesize($path);

        if ($imageInfo === false) {
            throw ImageUploadException::unsupportedFormat();
        }

        $image = $this->createGdImage($imageInfo[2], $path);

        try {
            $image = $this->applyExifOrientation($image, $imageInfo[2], $path);
            $image = $this->resizeIfNeeded($image);

            $content = $this->encodeToWebp($image);
        } catch (ImageUploadException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Échec du traitement GD de l\'image', [
                'error' => $e->getMessage(),
                'original_name' => $file->getClientOriginalName(),
            ]);
            throw ImageUploadException::processingFailed();
        } finally {
            imagedestroy($image);
        }

        return [
            'content' => $content,
            'filename' => Str::uuid() . '.webp',
            'mime' => 'image/webp',
        ];
    }

    private function guardAgainstUnsupportedFormats(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        // getMimeType() sniffe le contenu réel du fichier (fileinfo), pas seulement
        // le Content-Type déclaré par le navigateur — nécessaire car un RAW peut
        // arriver avec un MIME générique ou absent selon le navigateur/OS.
        $mimeType = strtolower((string) $file->getMimeType());

        $looksLikeRaw = in_array($extension, self::RAW_EXTENSIONS, true);
        foreach (self::RAW_MIME_MARKERS as $marker) {
            if (str_contains($mimeType, $marker)) {
                $looksLikeRaw = true;
                break;
            }
        }
        // Le DNG est un TIFF déguisé : libmagic le rapporte souvent comme image/tiff.
        if ($mimeType === 'image/tiff' || in_array($extension, ['tif', 'tiff'], true)) {
            $looksLikeRaw = true;
        }

        if ($looksLikeRaw) {
            throw ImageUploadException::rawFormatNotSupported();
        }

        if (in_array($extension, self::HEIC_EXTENSIONS, true) || in_array($mimeType, ['image/heic', 'image/heif'], true)) {
            throw ImageUploadException::heicNotConverted();
        }
    }

    /**
     * @param resource|\GdImage $imageResource
     */
    private function resizeIfNeeded(\GdImage $imageResource): \GdImage
    {
        $width = imagesx($imageResource);
        $height = imagesy($imageResource);

        if ($width <= self::MAX_WIDTH) {
            return $imageResource;
        }

        // IMG_BICUBIC échoue silencieusement sur certaines versions de GD une fois
        // imagesavealpha()/imagealphablending() activés (nécessaires pour préserver
        // la transparence) ; IMG_BILINEAR_FIXED est fiable dans ce cas et donne un
        // résultat de qualité équivalente pour une réduction de taille.
        $resized = imagescale($imageResource, self::MAX_WIDTH, -1, IMG_BILINEAR_FIXED);

        if ($resized === false) {
            throw ImageUploadException::processingFailed();
        }

        imagedestroy($imageResource);

        return $resized;
    }

    private function createGdImage(int $imageType, string $path): \GdImage
    {
        $image = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            throw ImageUploadException::unsupportedFormat();
        }

        // Préserver la transparence (PNG/GIF/WebP) lors de la ré-encodage en WebP.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Les iPhone/appareils photo écrivent l'orientation réelle dans l'EXIF plutôt
     * que de faire pivoter les pixels : sans ce correctif, les photos redimensionnées
     * apparaissent couchées ou inversées une fois affichées côté mobile.
     */
    private function applyExifOrientation(\GdImage $image, int $imageType, string $path): \GdImage
    {
        if ($imageType !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = $exif['Orientation'] ?? 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated === null) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }

    private function encodeToWebp(\GdImage $image): string
    {
        ob_start();
        $success = imagewebp($image, null, self::RECOMPRESS_QUALITY);
        $content = ob_get_clean();

        if (!$success || $content === false) {
            throw ImageUploadException::processingFailed();
        }

        return $content;
    }

    /**
     * Redimensionne/recompresse une image DÉJÀ EN LIGNE en conservant son format
     * d'origine (jamais de conversion, jamais de renommage) : utilisé par la
     * commande de rétro-optimisation des images legacy (images:optimize), qui ne
     * doit jamais changer une URL déjà référencée en base.
     *
     * @return array{content: string, width: int, height: int}|null null si le contenu
     *         n'est pas une image décodable par GD (corrompu, ou format non supporté
     *         comme un RAW qui aurait échappé à l'ancienne validation).
     */
    public function reoptimizeInPlace(string $binaryContent): ?array
    {
        $imageInfo = @getimagesizefromstring($binaryContent);
        if ($imageInfo === false) {
            return null;
        }

        $imageType = $imageInfo[2];
        $tmpPath = tempnam(sys_get_temp_dir(), 'legacy_img_');
        file_put_contents($tmpPath, $binaryContent);

        $image = null;

        try {
            $image = $this->createGdImage($imageType, $tmpPath);
            $image = $this->applyExifOrientation($image, $imageType, $tmpPath);
            $image = $this->resizeIfNeeded($image);

            $content = $this->encodeToSameFormat($image, $imageType);

            return [
                'content' => $content,
                'width' => imagesx($image),
                'height' => imagesy($image),
            ];
        } catch (\Throwable $e) {
            Log::error('Échec du retraitement en place d\'une image legacy', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } finally {
            if ($image !== null) {
                imagedestroy($image);
            }
            @unlink($tmpPath);
        }
    }

    private function encodeToSameFormat(\GdImage $image, int $imageType): string
    {
        ob_start();
        $success = match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($image, null, self::RECOMPRESS_QUALITY),
            IMAGETYPE_PNG => imagepng($image, null, self::PNG_COMPRESSION_LEVEL),
            IMAGETYPE_GIF => imagegif($image),
            IMAGETYPE_WEBP => imagewebp($image, null, self::RECOMPRESS_QUALITY),
            default => false,
        };
        $content = ob_get_clean();

        if (!$success || $content === false) {
            throw ImageUploadException::processingFailed();
        }

        return $content;
    }
}
