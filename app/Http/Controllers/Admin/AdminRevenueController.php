<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DodoVroumApiService;
use App\Support\BookingFinance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminRevenueController extends Controller
{
    private const MONTH_LABELS = ['Janv.', 'Févr.', 'Mars', 'Avr.', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];

    public function __construct(
        protected DodoVroumApiService $apiService
    ) {
    }

    /**
     * Afficher la page des revenus avec statistiques détaillées pour l'admin
     */
    public function index(Request $request): Response
    {
        try {
            $stats = $this->fetchAdminRevenueStats($this->chartYear($request));
        } catch (\Exception $e) {
            Log::error('Erreur récupération données revenus admin', ['error' => $e->getMessage()]);

            $stats = $this->getDefaultStats();
        }

        // Log pour déboguer
        \Log::info('Admin Revenue Stats', [
            'totalRevenue' => $stats['totalRevenue'],
            'revenueThisMonth' => $stats['revenueThisMonth'] ?? 0,
            'totalBookings' => $stats['totalBookings'],
            'chartDataCount' => count($stats['chartData']),
            'chartData' => $stats['chartData'],
        ]);

        return Inertia::render('Admin/Revenue', [
            'stats' => $stats,
        ]);
    }

    /**
     * Export CSV des commissions de l'année affichée dans le graphique (?year=).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $year = $this->chartYear($request);
        try {
            $stats = $this->fetchAdminRevenueStats($year);
        } catch (\Exception $e) {
            Log::error('Erreur export CSV revenus admin', ['error' => $e->getMessage()]);
            $stats = $this->getDefaultStats();
        }

        $filename = 'commissions-dodovroum-'.$year.'.csv';

        return response()->streamDownload(function () use ($stats) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Mois '.($stats['chartYear'] ?? ''), 'Commissions DodoVroum (10 %) — FCFA'], ';');
            foreach ($stats['chartData'] ?? [] as $row) {
                fputcsv($out, [$row['month'] ?? '', $row['total'] ?? 0], ';');
            }
            fputcsv($out, []);
            fputcsv($out, ['Total '.($stats['chartYear'] ?? ''), $stats['chartYearTotal'] ?? 0], ';');
            fputcsv($out, ['Total toutes années', $stats['totalRevenue'] ?? 0], ';');
            fputcsv($out, ['Réservations comptabilisées (toutes années)', $stats['totalBookings'] ?? 0], ';');
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @throws \Throwable
     */
    /** Année affichée dans le graphique : année en cours par défaut, jamais dans le futur. */
    private function chartYear(Request $request): int
    {
        $year = min((int) $request->query('year', (string) Carbon::now()->year), Carbon::now()->year);

        return max($year, 2000);
    }

    private function fetchAdminRevenueStats(int $year): array
    {
        $allResidences = $this->apiService->getResidences([]);
        $allVehicles = $this->apiService->getVehicles([]);
        $allBookings = $this->apiService->getBookings([]);

        return $this->calculateRevenueStats($allResidences, $allVehicles, $allBookings, $year);
    }

    /**
     * Commissions agrégées : uniquement réservations éligibles, montants non négatifs, dates sécurisées (Carbon).
     */
    private function calculateRevenueStats(array $residences, array $vehicles, array $bookings, int $year): array
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

        // Graphique : les 12 mois (janvier → décembre) de l'année demandée.
        $chartBuckets = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartBuckets[sprintf('%04d-%02d', $year, $m)] = 0.0;
        }
        // Années proposées : année en cours + années ayant des commissions.
        $yearsWithRevenue = [$now->year => true];

        $totalRevenue = 0.0;
        $revenueThisMonth = 0.0;
        $revenueLastMonth = 0.0;
        $bookingsThisMonth = 0;
        $bookingsLastMonth = 0;
        $totalNights = 0;
        $eligibleCount = 0;

        foreach ($bookings as $booking) {
            // Commission DodoVroum : réservations confirmées par le propriétaire.
            if (! is_array($booking) || ! BookingFinance::isPlatformRealized($booking)) {
                continue;
            }

            $eligibleCount++;

            // Commission DodoVroum (10 % du totalPrice), calculée par l'API.
            $commission = BookingFinance::commission($booking);
            $totalRevenue += $commission;

            // Comptabilisée au mois de la confirmation du propriétaire.
            $realizedAt = BookingFinance::platformRealizedAt($booking);
            if ($realizedAt) {
                $realizedAt = Carbon::instance($realizedAt)->utc();
                $monthKey = $realizedAt->format('Y-m');
                $yearsWithRevenue[(int) $realizedAt->format('Y')] = true;
                if (array_key_exists($monthKey, $chartBuckets)) {
                    $chartBuckets[$monthKey] += $commission;
                }
                if ($realizedAt->month === $currentMonth && $realizedAt->year === $currentYear) {
                    $revenueThisMonth += $commission;
                    $bookingsThisMonth++;
                } elseif ($monthKey === $lastMonth->format('Y-m')) {
                    $revenueLastMonth += $commission;
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

        Log::info('AdminRevenueController - Réservations éligibles pour CA', [
            'eligible_count' => $eligibleCount,
        ]);

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
        foreach (array_values($chartBuckets) as $index => $total) {
            $chartDataArray[] = [
                'month' => self::MONTH_LABELS[$index],
                'total' => (int) round(max(0.0, $total)),
            ];
        }
        $availableYears = array_keys($yearsWithRevenue + [$year => true]);
        rsort($availableYears);

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
            'chartYear' => $year,
            'chartYearTotal' => (int) round(array_sum(array_column($chartDataArray, 'total'))),
            'availableYears' => $availableYears,
            // Volume des réservations confirmées (100 % du totalPrice).
            'volumeRealized' => BookingFinance::platformRealized($bookings)['bookingValue'],
            'volumeRealizedThisMonth' => BookingFinance::platformRealized($bookings, BookingFinance::monthStart())['bookingValue'],
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
            'chartYear' => Carbon::now()->year,
            'chartYearTotal' => 0,
            'availableYears' => [Carbon::now()->year],
            'volumeRealized' => 0,
            'volumeRealizedThisMonth' => 0,
        ];
    }
}

