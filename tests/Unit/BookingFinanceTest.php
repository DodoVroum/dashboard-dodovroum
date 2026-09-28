<?php

namespace Tests\Unit;

use App\Support\BookingFinance;
use PHPUnit\Framework\TestCase;

/**
 * Le dashboard ne fait qu'additionner les montants calculés par l'API
 * (booking.finance), avec deux déclencheurs :
 * - propriétaire (90 %) : en attente, puis réalisé le jour de la remise des clés ;
 * - DodoVroum (volume 100 %, commission 10 %) : dès la confirmation du propriétaire.
 */
class BookingFinanceTest extends TestCase
{
    /** Réservation de 100 000 telle que renvoyée par l'API. */
    private function booking(string $ownerState, ?string $ownerRealizedAt, string $platformState, ?string $platformRealizedAt): array
    {
        return [
            'totalPrice' => 100000,
            'finance' => [
                'bookingValue' => 100000,
                'commission' => 10000,
                'ownerRevenue' => 90000,
                'onlinePaid' => 30000,
                'remainingOnSite' => 70000,
                'ownerState' => $ownerState,
                'ownerRealizedAt' => $ownerRealizedAt,
                'platformState' => $platformState,
                'platformRealizedAt' => $platformRealizedAt,
            ],
        ];
    }

    public function test_payee_non_confirmee_proprietaire_en_attente_dodovroum_rien(): void
    {
        $bookings = [$this->booking('PENDING', null, 'NONE', null)];

        $this->assertSame(0.0, BookingFinance::ownerRealized($bookings));
        $this->assertSame(90000.0, BookingFinance::ownerPending($bookings));
        $this->assertSame(['bookingValue' => 0.0, 'commission' => 0.0], BookingFinance::platformRealized($bookings));
    }

    public function test_confirmee_cles_non_remises_proprietaire_en_attente_dodovroum_compte(): void
    {
        $bookings = [$this->booking('PENDING', null, 'REALIZED', '2026-09-20T10:00:00.000Z')];

        $this->assertSame(0.0, BookingFinance::ownerRealized($bookings));
        $this->assertSame(90000.0, BookingFinance::ownerPending($bookings));
        $this->assertSame(['bookingValue' => 100000.0, 'commission' => 10000.0], BookingFinance::platformRealized($bookings));
    }

    public function test_cles_remises_proprietaire_realise(): void
    {
        $bookings = [$this->booking('REALIZED', '2026-10-01T10:00:00.000Z', 'REALIZED', '2026-10-01T10:00:00.000Z')];

        $this->assertSame(90000.0, BookingFinance::ownerRealized($bookings));
        $this->assertSame(0.0, BookingFinance::ownerPending($bookings));
        $this->assertSame(['bookingValue' => 100000.0, 'commission' => 10000.0], BookingFinance::platformRealized($bookings));
    }

    public function test_annulee_ne_compte_nulle_part(): void
    {
        $bookings = [$this->booking('NONE', null, 'NONE', null)];

        $this->assertSame(0.0, BookingFinance::ownerRealized($bookings));
        $this->assertSame(0.0, BookingFinance::ownerPending($bookings));
        $this->assertSame(['bookingValue' => 0.0, 'commission' => 0.0], BookingFinance::platformRealized($bookings));
    }

    public function test_mois_proprietaire_remise_des_cles_mois_dodovroum_confirmation(): void
    {
        // Confirmée le 25 septembre, séjour fin septembre, clés remises le 1er octobre.
        $bookings = [$this->booking('REALIZED', '2026-10-01T09:00:00.000Z', 'REALIZED', '2026-09-25T09:00:00.000Z')];
        $sep = [new \DateTimeImmutable('2026-09-01T00:00:00Z'), new \DateTimeImmutable('2026-10-01T00:00:00Z')];
        $oct = [new \DateTimeImmutable('2026-10-01T00:00:00Z'), new \DateTimeImmutable('2026-11-01T00:00:00Z')];

        $this->assertSame(0.0, BookingFinance::ownerRealized($bookings, ...$sep));
        $this->assertSame(90000.0, BookingFinance::ownerRealized($bookings, ...$oct));
        $this->assertSame(10000.0, BookingFinance::platformRealized($bookings, ...$sep)['commission']);
        $this->assertSame(0.0, BookingFinance::platformRealized($bookings, ...$oct)['commission']);
    }

    public function test_reservation_sans_donnees_financieres_api_ignoree(): void
    {
        $this->assertSame(0.0, BookingFinance::ownerRealized([['totalPrice' => 100000, 'status' => 'confirmed']]));
    }

    public function test_resume_api_par_defaut_a_zero(): void
    {
        $summary = BookingFinance::fromSummary(null);

        $this->assertSame(['realized' => 0.0, 'realizedMonth' => 0.0, 'pending' => 0.0], $summary['owner']);
        $this->assertSame(['bookingValue' => 0.0, 'commission' => 0.0], $summary['platform']['realized']);
    }
}
