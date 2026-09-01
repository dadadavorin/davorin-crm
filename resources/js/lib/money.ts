const CURRENCY_FORMATTER = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR',
});

/**
 * Formats integer minor units for display — presentation only, never fed
 * back into a calculation (`App\Support\Money` owns those, server-side).
 */
export function formatMoney(minorUnits: number | null): string {
    if (minorUnits === null) {
        return '—';
    }

    return CURRENCY_FORMATTER.format(minorUnits / 100);
}

/**
 * The inverse of the server's `Money::fromDecimalString()` — exact integer
 * arithmetic, mirrored here only to prefill a create/edit form's text
 * input, never used for a stored amount.
 */
export function moneyToInputValue(
    minorUnits: number | null,
): string | undefined {
    if (minorUnits === null) {
        return undefined;
    }

    const negative = minorUnits < 0;
    const absolute = Math.abs(minorUnits);
    const whole = Math.trunc(absolute / 100);
    const fraction = absolute % 100;

    return `${negative ? '-' : ''}${whole}.${String(fraction).padStart(2, '0')}`;
}
