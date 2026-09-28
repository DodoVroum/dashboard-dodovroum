<?php

namespace Tests\Feature;

use App\Models\ApiUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Page Revenus admin : commissions DodoVroum sur une année civile (janvier →
 * décembre), au mois de la confirmation du propriétaire ; années précédentes
 * consultables et exportables en CSV.
 */
class AdminRevenueChartTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        Carbon::setTestNow('2026-09-28 10:00:00');

        Auth::login(new ApiUser([
            'id' => 'admin-1',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'token' => 'fake-admin-token',
        ]));

        $booking = fn (string $id, string $confirmedAt) => [
            'id' => $id,
            'status' => 'terminee',
            'totalPrice' => 100000,
            'finance' => [
                'bookingValue' => 100000, 'commission' => 10000, 'ownerRevenue' => 90000,
                'ownerState' => 'REALIZED', 'ownerRealizedAt' => $confirmedAt,
                'platformState' => 'REALIZED', 'platformRealizedAt' => $confirmedAt,
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

    public function test_annee_en_cours_par_defaut_douze_mois(): void
    {
        $this->get('/admin/revenue')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.chartYear', 2026)
                ->where('stats.availableYears', [2026, 2025])
                ->has('stats.chartData', 12)
                ->where('stats.chartData.8.total', 10000) // septembre 2026
                ->where('stats.chartYearTotal', 10000));
    }

    public function test_annee_precedente(): void
    {
        $this->get('/admin/revenue?year=2025')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.chartYear', 2025)
                ->where('stats.chartData.2.total', 10000) // mars 2025
                ->where('stats.chartYearTotal', 10000)
                ->where('stats.totalRevenue', 20000)); // carte : toutes années
    }

    public function test_export_csv_de_l_annee_choisie(): void
    {
        $response = $this->get('/admin/revenue/export.csv?year=2025');

        $response->assertOk();
        $response->assertDownload('commissions-dodovroum-2025.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Mars;10000', $csv);
        $this->assertStringContainsString('"Total 2025";10000', $csv);
    }
}
