# ADR-0004: Query-count assertions instead of `EXPLAIN` planner tests

## Status

Accepted · 2026-09-01.

## Context

The board endpoints are the part of this application most exposed to a
silent N+1: four boards, each rendering cards that pull in relations
(company, contact names on a deal card, for example), where a missing
eager load turns one query into one-per-card. An `EXPLAIN`-based test —
asserting a specific index is used, or that a particular plan node is
absent — would guard the query _plan_, but the plan Postgres picks depends
on table size and planner statistics: a query that uses an index seek
against a populated table can use a sequential scan against an empty test
table, and vice versa. That makes an `EXPLAIN` assertion brittle against
exactly the kind of environment difference (test data volume, Postgres
version) that has nothing to do with the regression it's meant to catch.

## Decision

Query-count assertions instead: a query listener records how many queries a
request issues, and the test asserts a fixed number regardless of how many
cards are on the board. This directly tests the failure mode that matters
here — a missing eager load turning into one query per row — without
depending on planner internals that vary between environments.

## Alternatives considered

### `EXPLAIN`-plan assertions (index usage, plan node type)

Rejected. Brittle against local and CI data shape for reasons unrelated to
the regression being guarded against, and indirect: what actually causes a
production slowdown from an N+1 is the _number_ of queries issued, and a
query-count assertion tests that directly instead of inferring it from a
plan.

## Consequences

A genuinely missing index — correct query count, but a slow plan on a
populated table — is not caught by this suite; only manual review or
production monitoring would catch that. That gap is accepted given the
application's bounded table sizes (offset pagination at 25/page, board
columns capped at 50 — ADR-0003): the data volumes in play are small enough
that a missing index is a latency problem, not a page-doesn't-load problem,
and the query-count guarantee is the one that actually protects the board
from becoming unusable as card counts grow.

## Superseded reasoning

N/A — first version of this decision.
