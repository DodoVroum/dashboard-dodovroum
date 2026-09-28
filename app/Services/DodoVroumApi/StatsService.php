<?php

namespace App\Services\DodoVroumApi;

use App\Services\DodoVroumApi\BaseApiService;
use App\Support\BookingFinance;
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
     * Statistiques du propriétaire connecté (GET /stats, scope JWT), telles que
     * renvoyées par l'API.
     *
     * @param string|null $ownerId Conservé pour compatibilité des appelants ; non envoyé à l'API.
     * @return array|null null si l'API est indisponible
     */
    public function getOwnerStats(?string $ownerId = null): ?array
    {
        return $this->fetchStatsObject('stats');
    }

    /**
     * Revenus du propriétaire connecté (GET /stats → finance) : réalisé,
     * réalisé du mois, en attente (voir App\Support\BookingFinance).
     */
    public function getOwnerFinance(): ?array
    {
        $stats = $this->fetchStatsObject('stats');

        return is_array($stats['finance'] ?? null) ? BookingFinance::fromSummary($stats['finance']) : null;
    }

    /** Volume / commission de la plateforme (GET /admin/stats → finance, admin). */
    public function getAdminFinance(): ?array
    {
        $stats = $this->fetchStatsObject('admin/stats');

        return is_array($stats['finance'] ?? null) ? BookingFinance::fromSummary($stats['finance']) : null;
    }

    /**
     * GET d'un objet de statistiques. Ces endpoints renvoient un objet sans `id`
     * (nu ou { success, data: {...} }) : il ne peut pas passer par get() /
     * ApiResponseNormalizer::data(), qui ne conserve que des listes d'entités
     * identifiées (objet nu → [objet], enveloppe → tableau vide).
     */
    private function fetchStatsObject(string $endpoint): ?array
    {
        $token = $this->getAuthToken();
        if (! $token) {
            return null;
        }

        try {
            $response = Http::timeout(10)->withToken($token)->acceptJson()->get("{$this->baseUrl}/{$endpoint}");
        } catch (\Throwable $e) {
            Log::warning("GET /{$endpoint} injoignable", ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning("GET /{$endpoint} en échec", ['status' => $response->status()]);

            return null;
        }

        $payload = $response->json();
        $stats = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        return is_array($stats) && ! array_is_list($stats) ? $stats : null;
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

