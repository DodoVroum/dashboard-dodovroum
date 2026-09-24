<?php

namespace Tests\Feature;

use App\Models\ApiUser;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Page Réservations propriétaire : « Revenus du mois » et « Revenus totaux »
 * affichent l'argent réellement encaissé (GET /api/stats), jamais la somme des
 * totalPrice des réservations.
 */
class OwnerBookingRevenueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);

        Auth::login(new ApiUser([
            'id' => 'owner-1',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'token' => 'fake-owner-token',
        ]));
    }

    /** Deux réservations de 50 000 ce mois-ci : une confirmée non payée, une avec acompte. */
    private function bookings(): array
    {
        $thisMonth = now()->format('Y-m').'-15T00:00:00.000Z';

        return [
            ['id' => 'bk-unpaid', 'status' => 'confirmee', 'totalPrice' => 50000, 'totalPaid' => 0,
                'startDate' => $thisMonth, 'endDate' => $thisMonth, 'createdAt' => $thisMonth],
            ['id' => 'bk-deposit', 'status' => 'paid', 'totalPrice' => 50000, 'totalPaid' => 15000,
                'startDate' => $thisMonth, 'endDate' => $thisMonth, 'createdAt' => $thisMonth],
        ];
    }

    public static function statsPayloads(): array
    {
        $stats = ['totalBookings' => 2, 'totalRevenue' => 15000, 'monthRevenue' => 15000];

        return [
            'objet nu (réponse actuelle de GET /api/stats)' => [$stats],
            'enveloppe { success, data }' => [['success' => true, 'data' => $stats]],
        ];
    }

    /** @dataProvider statsPayloads */
    public function test_les_revenus_viennent_des_paiements_encaisses_et_non_du_total_price(array $statsPayload): void
    {
        Http::fake([
            '*/bookings/my-properties-bookings*' => Http::response($this->bookings()),
            '*/stats*' => Http::response($statsPayload),
            '*' => Http::response([]),
        ]);

        $this->get('/owner/bookings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Owner/Bookings/Index')
                ->where('stats.totalBookings', 2)
                ->where('stats.totalRevenue', 15000)   // et non 100 000 (somme des totalPrice)
                ->where('stats.monthRevenue', 15000));

        // Périmètre : jeton du propriétaire connecté, aucun identifiant dans l'URL.
        Http::assertSent(fn (Request $request) => str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/stats')
            && $request->hasHeader('Authorization', 'Bearer fake-owner-token'));
    }

    public function test_api_stats_indisponible_affiche_zero_plutot_que_le_prix_des_reservations(): void
    {
        Http::fake([
            '*/bookings/my-properties-bookings*' => Http::response($this->bookings()),
            '*/stats*' => Http::response(['message' => 'Erreur'], 500),
            '*' => Http::response([]),
        ]);

        $this->get('/owner/bookings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.totalRevenue', 0)
                ->where('stats.monthRevenue', 0));
    }
}
