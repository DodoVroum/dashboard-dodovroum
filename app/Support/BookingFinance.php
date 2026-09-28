<?php

namespace App\Support;

/**
 * Revenus DodoVroum calculés par l'API NestJS (src/stats/booking-finance.ts).
 *
 * Aucune règle métier ici : l'API fournit, pour chaque réservation
 * (`booking.finance`, réponses propriétaire / admin) et en agrégé (GET /stats,
 * GET /admin/stats → `finance`) :
 *   volume = 100 % du totalPrice · commission DodoVroum = 10 % · propriétaire = 90 %
 * avec deux déclencheurs :
 *   - propriétaire : ownerState PENDING (payée ou confirmée) → REALIZED le jour de la
 *     remise des clés (ownerRealizedAt) ;
 *   - DodoVroum : platformState REALIZED dès la confirmation du propriétaire
 *     (platformRealizedAt) ; pas de notion « en attente ».
 * Le dashboard se contente d'additionner ces valeurs pour un sous-ensemble
 * (un bien, une période), afin qu'une réservation ait les mêmes montants partout.
 */
final class BookingFinance
{
    private static function finance(array $booking): array
    {
        return is_array($booking['finance'] ?? null) ? $booking['finance'] : [];
    }

    private static function amount(array $booking, string $field): float
    {
        return (float) (self::finance($booking)[$field] ?? 0);
    }

    private static function date(array $booking, string $field): ?\DateTimeImmutable
    {
        $raw = self::finance($booking)[$field] ?? null;
        if (! $raw) {
            return null;
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }

    private static function inPeriod(?\DateTimeImmutable $at, ?\DateTimeInterface $from, ?\DateTimeInterface $to): bool
    {
        if (! $from && ! $to) {
            return true;
        }

        return $at && (! $from || $at >= $from) && (! $to || $at < $to);
    }

    // --- Propriétaire (90 %) --------------------------------------------------

    public static function isOwnerRealized(array $booking): bool
    {
        return (self::finance($booking)['ownerState'] ?? null) === 'REALIZED';
    }

    /** Jour de la remise des clés (comptabilisation du revenu propriétaire), sinon null. */
    public static function ownerRealizedAt(array $booking): ?\DateTimeImmutable
    {
        return self::date($booking, 'ownerRealizedAt');
    }

    public static function ownerRevenue(array $booking): float
    {
        return self::amount($booking, 'ownerRevenue');
    }

    /** Revenu propriétaire réalisé (clés remises), éventuellement remises dans [$from, $to[. */
    public static function ownerRealized(iterable $bookings, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): float
    {
        $total = 0.0;
        foreach ($bookings as $booking) {
            if (is_array($booking) && self::isOwnerRealized($booking)
                && self::inPeriod(self::ownerRealizedAt($booking), $from, $to)) {
                $total += self::ownerRevenue($booking);
            }
        }

        return $total;
    }

    /** Revenu propriétaire en attente (payée ou confirmée, clés pas encore remises). */
    public static function ownerPending(iterable $bookings): float
    {
        $total = 0.0;
        foreach ($bookings as $booking) {
            if (is_array($booking) && (self::finance($booking)['ownerState'] ?? null) === 'PENDING') {
                $total += self::ownerRevenue($booking);
            }
        }

        return $total;
    }

    // --- DodoVroum (volume 100 %, commission 10 %) --------------------------

    public static function isPlatformRealized(array $booking): bool
    {
        return (self::finance($booking)['platformState'] ?? null) === 'REALIZED';
    }

    /** Date de la confirmation du propriétaire (comptabilisation DodoVroum), sinon null. */
    public static function platformRealizedAt(array $booking): ?\DateTimeImmutable
    {
        return self::date($booking, 'platformRealizedAt');
    }

    public static function commission(array $booking): float
    {
        return self::amount($booking, 'commission');
    }

    /**
     * Volume et commission des réservations confirmées, éventuellement
     * confirmées dans [$from, $to[.
     *
     * @return array{bookingValue: float, commission: float}
     */
    public static function platformRealized(iterable $bookings, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $total = ['bookingValue' => 0.0, 'commission' => 0.0];
        foreach ($bookings as $booking) {
            if (is_array($booking) && self::isPlatformRealized($booking)
                && self::inPeriod(self::platformRealizedAt($booking), $from, $to)) {
                $total['bookingValue'] += self::amount($booking, 'bookingValue');
                $total['commission'] += self::commission($booking);
            }
        }

        return $total;
    }

    // --- Périodes et résumés API --------------------------------------------

    /** Premier jour du mois courant, 00:00 UTC (même borne que l'API). */
    public static function monthStart(?\DateTimeInterface $now = null): \DateTimeImmutable
    {
        $now = \DateTimeImmutable::createFromInterface($now ?? new \DateTimeImmutable('now'))
            ->setTimezone(new \DateTimeZone('UTC'));

        return $now->setDate((int) $now->format('Y'), (int) $now->format('m'), 1)->setTime(0, 0);
    }

    /** Début du jour courant, 00:00 UTC. */
    public static function dayStart(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today', new \DateTimeZone('UTC'));
    }

    /** Résumé API (GET /stats, GET /admin/stats → finance), 0 par défaut. */
    public static function fromSummary(?array $finance): array
    {
        $owner = is_array($finance['owner'] ?? null) ? $finance['owner'] : [];
        $platform = is_array($finance['platform'] ?? null) ? $finance['platform'] : [];
        $amounts = fn ($values) => [
            'bookingValue' => (float) ($values['bookingValue'] ?? 0),
            'commission' => (float) ($values['commission'] ?? 0),
        ];

        return [
            'owner' => [
                'realized' => (float) ($owner['realized'] ?? 0),
                'realizedMonth' => (float) ($owner['realizedMonth'] ?? 0),
                'pending' => (float) ($owner['pending'] ?? 0),
            ],
            'platform' => [
                'realized' => $amounts($platform['realized'] ?? []),
                'realizedMonth' => $amounts($platform['realizedMonth'] ?? []),
            ],
        ];
    }
}
