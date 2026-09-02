/**
 * The suite runs against the real Compose stack, whose database persists
 * across local runs — every record created here needs a name unlikely to
 * collide with a previous run or with the seeded demo data.
 */
export function uniqueSuffix(): string {
    return `${Date.now()}-${Math.floor(Math.random() * 100_000)}`;
}
