<?php

namespace Tests\Feature;

use App\Models\ApiUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Page Revenus propriétaire : graphique sur une année civile (janvier →
 * décembre), revenus réalisés au mois de la remise des clés, années
 * précédentes consultables.
 */
class OwnerRevenueChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        Carbon::setTestNow('2026-09-28 10:00:00');

        Auth::login(new ApiUser([
            'id' => 'owner-1',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'token' => 'fake-owner-token',
        ]));

        $booking = fn (string $id, string $realizedAt) => [
            'id' => $id,
            'status' => 'terminee',
            'totalPrice' => 100000,
            'residence' => ['id' => 'res-1', 'proprietaireId' => 'owner-1'],
            'finance' => [
                'bookingValue' => 100000, 'commission' => 10000, 'ownerRevenue' => 90000,
                'ownerState' => 'REALIZED', 'ownerRealizedAt' => $realizedAt,
                'platformState' => 'REALIZED', 'platformRealizedAt' => $realizedAt,
            ],
        ];

        Http::fake([
            '*/bookings*' => Http::response([
                $booking('bk-2025', '2025-03-10T10:00:00.000Z'),
                $booking('bk-2026', '2026-09-05T10:00:00.000Z'),
            ]),
            '*' => Http::response([]),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_annee_en_cours_par_defaut_douze_mois_et_annees_disponibles(): void
    {
        $this->get('/owner/revenue')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.chartYear', 2026)
                ->where('stats.availableYears', [2026, 2025])
                ->has('stats.chartData', 12)
                ->where('stats.chartData.0.month', 'Janv.')
                ->where('stats.chartData.8.total', 90000) // septembre 2026
                ->where('stats.chartData.2.total', 0)     // mars 2026 : rien
                ->where('stats.chartYearTotal', 90000));
    }

    public function test_annee_precedente(): void
    {
        $this->get('/owner/revenue?year=2025')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.chartYear', 2025)
                ->has('stats.chartData', 12)
                ->where('stats.chartData.2.total', 90000) // mars 2025
                ->where('stats.chartData.8.total', 0)
                ->where('stats.chartYearTotal', 90000)
                ->where('stats.totalRevenue', 180000)); // cartes : total toutes années
    }

    public function test_annee_future_ramenee_a_l_annee_en_cours(): void
    {
        $this->get('/owner/revenue?year=2030')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('stats.chartYear', 2026));
    }
}
