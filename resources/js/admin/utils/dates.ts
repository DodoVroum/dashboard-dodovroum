/**
 * Utilitaires de gestion des dates — DodoVroum Dashboard
 *
 * Fuseau de référence : Côte d'Ivoire = Africa/Abidjan = UTC+0 toute l'année
 * (pas de changement d'heure, identique à GMT/UTC).
 *
 * Règles fondamentales appliquées ici :
 *  1. Ne JAMAIS utiliser `new Date('YYYY-MM-DD')` → JavaScript l'interprète
 *     comme minuit UTC, ce qui peut décaler l'affichage d'un jour dans les
 *     fuseaux UTC- (et dans tout navigateur non configuré en UTC+0).
 *  2. Toujours passer `timeZone: TZ` à Intl.DateTimeFormat pour garantir
 *     l'affichage correct quel que soit le fuseau du navigateur de l'admin.
 *  3. Les dates envoyées à l'API NestJS sont au format YYYY-MM-DD (plain date,
 *     sans partie horaire ni timezone) : le backend fait l'interprétation.
 */

/** Fuseau horaire canonique de la Côte d'Ivoire */
export const CI_TIMEZONE = 'Africa/Abidjan';

/** Locale française pour tous les affichages */
const LOCALE = 'fr-FR';

// ---------------------------------------------------------------------------
// Parsers
// ---------------------------------------------------------------------------

/**
 * Parse une chaîne de date venant de l'API NestJS en objet Date UTC.
 *
 * - Format YYYY-MM-DD  → converti en minuit UTC (= minuit CI puisque CI=UTC+0)
 * - Format ISO complet → passé directement au constructeur Date
 * - null / undefined   → retourne null
 *
 * L'objet Date résultant est toujours correct pour un formatage via
 * `Intl.DateTimeFormat` avec `timeZone: 'Africa/Abidjan'`.
 */
export function parseApiDate(value: string | null | undefined): Date | null {
  if (!value) return null;

  // Date seule : YYYY-MM-DD (éventuellement suivie de T...)
  const plain = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (plain) {
    const d = new Date(Date.UTC(+plain[1], +plain[2] - 1, +plain[3]));
    return isNaN(d.getTime()) ? null : d;
  }

  // ISO complet (avec heure et offset) : laisser le navigateur parser
  const d = new Date(value);
  return isNaN(d.getTime()) ? null : d;
}

// ---------------------------------------------------------------------------
// Formateurs — affichage
// ---------------------------------------------------------------------------

/**
 * Formate une date en texte lisible (fr-FR, fuseau CI).
 *
 * @example
 * formatDate('2026-05-19')          // → "19 mai 2026"
 * formatDate('2026-05-19', 'short') // → "19 mai"
 */
export function formatDate(
  value: string | null | undefined,
  style: 'long' | 'short' | 'numeric' = 'long',
): string {
  const d = parseApiDate(value);
  if (!d) return '—';

  const opts: Intl.DateTimeFormatOptions = {
    timeZone: CI_TIMEZONE,
    day: 'numeric',
    month: style === 'numeric' ? '2-digit' : 'short',
    ...(style === 'long' || style === 'numeric' ? { year: 'numeric' } : {}),
  };

  if (style === 'long') opts.month = 'long';

  return new Intl.DateTimeFormat(LOCALE, opts).format(d);
}

/**
 * Formate une plage de dates (start – end).
 * Omet l'année du début si elle est identique à celle de la fin.
 *
 * @example
 * formatDateRange('2026-01-03', '2026-03-15') // → "3 janv. – 15 mars 2026"
 * formatDateRange('2025-12-20', '2026-01-05') // → "20 déc. 2025 – 5 janv. 2026"
 */
export function formatDateRange(
  startValue: string | null | undefined,
  endValue: string | null | undefined,
): string {
  const start = parseApiDate(startValue);
  const end   = parseApiDate(endValue);

  if (!start && !end) return '—';

  const fmt = (d: Date, opts: Intl.DateTimeFormatOptions) =>
    new Intl.DateTimeFormat(LOCALE, { timeZone: CI_TIMEZONE, ...opts }).format(d);

  const getYear = (d: Date) =>
    new Intl.DateTimeFormat('en', { year: 'numeric', timeZone: CI_TIMEZONE }).format(d);

  if (!end && start) {
    return fmt(start, { day: 'numeric', month: 'short', year: 'numeric' });
  }
  if (!start && end) {
    return fmt(end, { day: 'numeric', month: 'short', year: 'numeric' });
  }

  const sameYear = getYear(start!) === getYear(end!);

  const startStr = fmt(start!, {
    day: 'numeric',
    month: 'short',
    ...(!sameYear ? { year: 'numeric' } : {}),
  });
  const endStr = fmt(end!, { day: 'numeric', month: 'short', year: 'numeric' });

  return `${startStr} – ${endStr}`;
}

/**
 * Formate un timestamp (ex: createdAt, updatedAt) avec heure.
 *
 * @example
 * formatDateTime('2026-05-19T14:32:00Z') // → "19 mai 2026 à 14:32"
 */
export function formatDateTime(value: string | null | undefined): string {
  const d = parseApiDate(value);
  if (!d) return '—';
  return new Intl.DateTimeFormat(LOCALE, {
    timeZone: CI_TIMEZONE,
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(d);
}

// ---------------------------------------------------------------------------
// Date du jour en CI
// ---------------------------------------------------------------------------

/**
 * Retourne la date d'aujourd'hui en Côte d'Ivoire au format YYYY-MM-DD.
 * Utilise 'en-CA' car ce locale produit exactement ce format avec Intl.
 *
 * Utilisation type :
 *  - Valeur min des <input type="date"> ("pas avant aujourd'hui")
 *  - Valeur par défaut de startDate dans les formulaires
 */
export function todayCI(): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone: CI_TIMEZONE }).format(new Date());
}

/**
 * Retourne une date YYYY-MM-DD à +N jours par rapport à aujourd'hui CI.
 *
 * @example
 * futureDateCI(365) // → "2027-05-27" (1 an plus tard)
 */
export function futureDateCI(days: number): string {
  const base = new Date(); // maintenant en UTC
  base.setUTCDate(base.getUTCDate() + days); // CI = UTC+0 → UTC date = CI date
  return new Intl.DateTimeFormat('en-CA', { timeZone: CI_TIMEZONE }).format(base);
}

// ---------------------------------------------------------------------------
// Conversions pour l'API
// ---------------------------------------------------------------------------

/**
 * Extrait la partie YYYY-MM-DD d'une valeur ISO retournée par NestJS.
 * Utilisé pour pré-remplir les <input type="date">.
 *
 * @example
 * toInputDate('2026-05-19T00:00:00.000Z') // → "2026-05-19"
 * toInputDate('2026-05-19')               // → "2026-05-19"
 */
export function toInputDate(value: string | null | undefined): string {
  if (!value) return '';
  // Extraire YYYY-MM-DD, que la valeur soit une date seule ou un ISO complet
  const m = value.match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : '';
}

// ---------------------------------------------------------------------------
// Comparaisons (fallback — préférer offer.statut du backend)
// ---------------------------------------------------------------------------

/**
 * Vérifie si une date est strictement dans le passé en heure CI.
 *
 * ⚠️  FALLBACK UNIQUEMENT — pour les offres combinées, utiliser offer.statut
 * (ACTIVE / EXPIREE / INACTIVE) retourné par le backend NestJS qui fait
 * l'autorité sur le statut. Cette fonction est utile pour les réservations
 * et autres entités qui n'ont pas de champ statut explicite.
 */
export function isDatePast(value: string | null | undefined): boolean {
  if (!value) return false;
  const dateStr = new Intl.DateTimeFormat('en-CA', { timeZone: CI_TIMEZONE })
    .format(parseApiDate(value) ?? new Date(NaN));
  return !!dateStr && dateStr < todayCI();
}

/**
 * Vérifie si une date est aujourd'hui ou dans le futur (CI).
 */
export function isDateActiveOrFuture(value: string | null | undefined): boolean {
  if (!value) return false;
  return !isDatePast(value);
}
