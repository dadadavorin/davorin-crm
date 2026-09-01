# ADR-0002: Money as integer minor units, single currency

## Status

Accepted · 2026-09-01.

## Context

Money stored as a decimal column and manipulated with floating-point
arithmetic in PHP is a recurring source of a specific failure: a total
computed one way (in the database, via `SUM()`) and the same total
recomputed another way (in PHP, by adding decimals or floats) disagree by a
fraction of a cent, for reasons that don't reproduce reliably in isolation.
The quotes feature needs the opposite guarantee — a stored total that
provably equals a fresh recomputation from its line items, every time.

Multi-currency support was also never a requirement: every quote in this
CRM is issued and paid in the same currency.

## Decision

Every money column is a `bigint` storing integer minor units (cents),
wrapped by a `final readonly Money` value object with an Eloquent cast
(`MoneyCast`). Currency is a single class constant, `Money::CURRENCY =
'EUR'` — no currency column exists anywhere in the schema.

Exactly one rounding operation exists in the whole codebase:
`Money::percentage()`, used once per quote to apply the snapshotted tax
rate to the subtotal, half-up to the nearest minor unit, computed with
exact integer arithmetic (never floats). Every other `Money` operation —
`add`, `subtract`, `multiplyBy` a whole-number quantity — is exact integer
arithmetic with no rounding at all.

## Alternatives considered

### `decimal` columns with float arithmetic in PHP

Rejected. Floating-point representation error accumulates across additions,
so a total built by summing many line items in PHP can silently disagree
with the same total computed by the database, or with itself if
recomputed a second time.

### A currency column on every money-bearing table

Rejected. Nothing in the assignment calls for multi-currency, and a
currency column without exchange-rate history or a currency-aware rounding
rule doesn't actually deliver multi-currency support — it's a placeholder
that adds a column to every table for a feature that isn't there. Adding it
later, when it is actually needed, is one migration; carrying it now is
dead weight.

## Consequences

Every money comparison and sum in the codebase is exact and reproducible,
so the quote totals invariant test (stored total equals a fresh
recomputation) can assert exact equality with no tolerance fudge-factor.
The cost lands if a second currency is ever required: every `Money`-typed
column gains a sibling currency column, and `Money::percentage()` —
currently the one rounding point in the system — needs a currency-aware
rounding rule, since not every currency has two decimal places.

## Superseded reasoning

N/A — first version of this decision.
