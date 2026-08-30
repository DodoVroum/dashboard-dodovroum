<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erreur d'upload/traitement d'image dont le message est déjà rédigé pour
 * être affiché tel quel au propriétaire (contrairement à DodoVroumApiException,
 * qui porte des erreurs internes API).
 */
class ImageUploadException extends RuntimeException
{
    public function __construct(string $userMessage, int $statusCode = 422)
    {
        parent::__construct($userMessage, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    public static function rawFormatNotSupported(): self
    {
        return new self(
            "Ce format RAW n'est pas pris en charge. Veuillez convertir votre photo en JPG avant de l'importer.",
            422
        );
    }

    public static function heicNotConverted(): self
    {
        return new self(
            "Ce fichier HEIC n'a pas pu être converti automatiquement par votre navigateur. Réessayez, ou convertissez la photo en JPG avant de l'importer.",
            422
        );
    }

    public static function unsupportedFormat(): self
    {
        return new self(
            'Format d\'image non pris en charge. Formats acceptés : JPG, PNG, HEIC.',
            422
        );
    }

    public static function tooLarge(int $maxMegabytes): self
    {
        return new self(
            "Le fichier est trop volumineux (maximum {$maxMegabytes} Mo). Réduisez sa taille avant de l'importer.",
            422
        );
    }

    public static function processingFailed(): self
    {
        return new self(
            'Impossible de traiter cette image. Réessayez avec un autre fichier.',
            500
        );
    }
}
