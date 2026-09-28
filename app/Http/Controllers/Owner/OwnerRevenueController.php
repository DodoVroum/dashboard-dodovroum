<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\HasProprietaireId;
use App\Services\BookingOwnerScopeService;
use App\Services\DodoVroumApiService;
use App\Support\BookingFinance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OwnerRevenueController extends Controller
{
    use HasProprietaireId;

    public function __construct(
        protected DodoVroumApiService $apiService,
        protected BookingOwnerScopeService $bookingOwnerScopeService
    ) {
    }

    /**
     * Afficher la page des revenus avec statistiques détaillées
     */
    public function index(): Response
    {
        $user = auth()->user();

        try {
            // Récupérer le proprietaireId réel depuis les données utilisateur
            $proprietaireId = $this->getProprietaireId($user);
            
            if (!$proprietaireId) {
                Log::error('Impossible de récupérer le proprietaireId pour la page revenus', [
                    'user_id' => $user->getAuthIdentifier(),
                ]);
                
                return Inertia::render('Owner/Revenue', [
                    'stats' => $this->getDefaultStats(),
                ]);
            }
            
            // Réservations du propriétaire : les montants (revenu propriétaire réalisé / en
            // attente) sont ceux calculés par l'API (booking.finance) ; ce contrôleur ne
            // fait que les regrouper par mois de remise des clés pour le graphique.
            $apiFilters = [];
            if (is_numeric($proprietaireId)) {
                $apiFilters['proprietaireId'] = (int) $proprietaireId;
            } else {
                $apiFilters['proprietaireId'] = $proprietaireId;
            }
            
            // Récupérer les données nécessaires
            $allResidences = $this->apiService->getResidences($apiFilters);
            $allVehicles = $this->apiService->getVehicles($apiFilters);
            $allBookings = $this->apiService->getBookings($apiFilters);
            
            // Double vérification côté serveur pour les résidences
            $residences = [];
            foreach ($allResidences as $residence) {
                $residenceProprietaireId = $residence['proprietaireId'] ?? $residence['proprietaire_id'] ?? $residence['ownerId'] ?? $residence['owner_id'] ?? null;
                if ($residenceProprietaireId && (
                    (string) $residenceProprietaireId === (string) $proprietaireId ||
                    (int) $residenceProprietaireId === (int) $proprietaireId
                )) {
                    $residences[] = $residence;
                }
            }
            
            // Double vérification côté serveur pour les véhicules
            $vehicles = [];
            foreach ($allVehicles as $vehicle) {
                $vehicleProprietaireId = $vehicle['proprietaireId'] ?? $vehicle['proprietaire_id'] ?? $vehicle['ownerId'] ?? $vehicle['owner_id'] ?? null;
                if ($vehicleProprietaireId && (
                    (string) $vehicleProprietaireId === (string) $proprietaireId ||
                    (int) $vehicleProprietaireId === (int) $proprietaireId
                )) {
                    $vehicles[] = $vehicle;
                }
            }
            
            // Double vérification côté serveur pour les réservations
            $bookings = [];
            foreach ($allBookings as $booking) {
                $bookingProprietaireId = $this->bookingOwnerScopeService->resolveOwnerIdForBooking($booking);
                if ($this->bookingOwnerScopeService->matchesProprietaire($bookingProprietaireId, $proprietaireId)) {
                    $bookings[] = $booking;
                }
            }
            
            // Calculer les statistiques de revenus
            $stats = $this->calculateRevenueStats($residences, $vehicles, $bookings);
            
        } catch (\Exception $e) {
            Log::error('Erreur récupération données revenus propriétaire', ['error' => $e->getMessage()]);
            
            $stats = $this->getDefaultStats();
        }

        return Inertia::render('Owner/Revenue', [
            'stats' => $stats,
        ]);
    }

    /**
     * Revenus propriétaire (90 %) : même logique « safe » que l’admin (éligibilité, max(0), Carbon).
     */
    private function calculateRevenueStats(array $residences, array $vehicles, array $bookings): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        $activeProperties = 0;
        foreach ($residences as $residence) {
            $isActive = $residence['isActive'] ?? $residence['is_active'] ?? $residence['available'] ?? true;
            if ($isActive === true || $isActive === 'true' || $isActive === 1) {
                $activeProperties++;
            }
        }
        foreach ($vehicles as $vehicle) {
            $isAvailable = $vehicle['available'] ?? $vehicle['isAvailable'] ?? $vehicle['is_available'] ?? true;
            if ($isAvailable === true || $isAvailable === 'true' || $isAvailable === 1) {
                $activeProperties++;
            }
        }

        $chartBuckets = [];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $months[] = $monthDate->format('M');
            $chartBuckets[$monthDate->format('Y-m')] = 0.0;
        }

        $totalRevenue = 0.0;
        $revenueThisMonth = 0.0;
        $revenueLastMonth = 0.0;
        $bookingsThisMonth = 0;
        $bookingsLastMonth = 0;
        $totalNights = 0;
        $eligibleCount = 0;

        foreach ($bookings as $booking) {
            // Revenu réalisé uniquement : clés remises.
            if (! is_array($booking) || ! BookingFinance::isOwnerRealized($booking)) {
                continue;
            }

            $eligibleCount++;

            // Revenu propriétaire (90 % du totalPrice), calculé par l'API.
            $ownerPayment = BookingFinance::ownerRevenue($booking);
            $totalRevenue += $ownerPayment;

            // Comptabilisé au mois de la remise des clés.
            $realizedAt = BookingFinance::ownerRealizedAt($booking);
            if ($realizedAt) {
                $realizedAt = Carbon::instance($realizedAt)->utc();
                $monthKey = $realizedAt->format('Y-m');
                if (array_key_exists($monthKey, $chartBuckets)) {
                    $chartBuckets[$monthKey] += $ownerPayment;
                }
                if ($realizedAt->month === $currentMonth && $realizedAt->year === $currentYear) {
                    $revenueThisMonth += $ownerPayment;
                    $bookingsThisMonth++;
                } elseif ($monthKey === $lastMonth->format('Y-m')) {
                    $revenueLastMonth += $ownerPayment;
                    $bookingsLastMonth++;
                }
            }

            $startDate = $booking['startDate'] ?? $booking['start_date'] ?? null;
            $endDate = $booking['endDate'] ?? $booking['end_date'] ?? null;
            if ($startDate && $endDate) {
                try {
                    $start = Carbon::parse($startDate);
                    $end = Carbon::parse($endDate);
                    $totalNights += max(0, $start->diffInDays($end));
                } catch (\Throwable) {
                }
            }
        }

        $occupationRate = 0.0;
        if ($activeProperties > 0) {
            $maxNights = $activeProperties * 30;
            if ($maxNights > 0) {
                $occupationRate = min(100.0, round(($totalNights / $maxNights) * 100, 1));
            }
        }

        $revenueTrend = 0.0;
        if ($revenueLastMonth > 0) {
            $revenueTrend = round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1);
        }

        $bookingsTrend = 0.0;
        if ($bookingsLastMonth > 0) {
            $bookingsTrend = round((($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100, 1);
        }

        $chartDataArray = [];
        foreach ($months as $index => $month) {
            $monthKey = $now->copy()->subMonths(5 - $index)->format('Y-m');
            $chartDataArray[] = [
                'month' => $month,
                'total' => (int) round(max(0.0, $chartBuckets[$monthKey] ?? 0.0)),
            ];
        }

        return [
            'totalRevenue' => round(max(0.0, $totalRevenue), 2),
            'revenueThisMonth' => round(max(0.0, $revenueThisMonth), 2),
            'totalBookings' => $eligibleCount,
            'occupationRate' => $occupationRate,
            'activeProperties' => $activeProperties,
            'trends' => [
                'totalRevenue' => $revenueTrend,
                'bookings' => $bookingsTrend,
                'occupation' => 0,
                'properties' => 0,
            ],
            'chartData' => $chartDataArray,
            // Payée et/ou confirmée, en attente de la remise des clés.
            'pendingRevenue' => BookingFinance::ownerPending($bookings),
        ];
    }

    /**
     * Retourner des statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats(): array
    {
        return [
            'totalRevenue' => 0,
            'revenueThisMonth' => 0,
            'totalBookings' => 0,
            'occupationRate' => 0,
            'activeProperties' => 0,
            'trends' => [
                'totalRevenue' => 0,
                'bookings' => 0,
                'occupation' => 0,
                'properties' => 0,
            ],
            'chartData' => [],
            'pendingRevenue' => 0,
        ];
    }
}

