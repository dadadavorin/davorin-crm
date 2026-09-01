# ADR-0003: Offset pagination instead of keyset, with bounded result sets

## Status

Accepted · 2026-09-01.

## Context

Keyset pagination (a `WHERE (sort_col, id) > (?, ?)` predicate carrying a
cursor forward) exists to solve two problems that don't apply here: pages
skipping or repeating rows as data changes underneath a long-lived page walk,
and `OFFSET` degrading linearly on a table large enough that skipping N rows
to reach page N is itself expensive. Both problems are a function of table
size and session length. Every list in this application — companies,
contacts, deals, quotes — is a single user's CRM data, not a public dataset,
and the assignment's own scope (demo data in the dozens per entity, a working
CRM rather than a data warehouse) means these tables stay small for the life
of the project. A keyset cursor also can't do what an index page actually
needs: jump to an arbitrary page number, or show "page 3 of 12" from a UI
control the reviewer clicks directly, without the extra work of encoding and
decoding an opaque cursor token through the frontend and back.

## Decision

Every index page uses Laravel's offset paginator (`Model::paginate(25)`).
Board columns are capped at 50 cards, with a `has_more` count past the cap
rather than a paginated board (ADR-0004 depends on this bound too: query-count
assertions replace `EXPLAIN` tests specifically because the tables involved
stay small enough that a missing index is a latency problem, not a
page-doesn't-load problem). Nothing in this application returns an unbounded
result set.

## Alternatives considered

### Keyset pagination

Rejected. Solves a skip/repeat correctness problem and an `OFFSET` cost
problem that both require a large, actively-written table to manifest;
neither applies at this data volume. It would also cost the "jump to page N"
UI a reviewer expects from an index table, in exchange for a guarantee this
application doesn't need.

## Consequences

`OFFSET`'s well-known cost — the database still has to walk and discard the
skipped rows — is accepted, and it is the reason for the bound: at 25 rows a
page and no entity expected to reach thousands of records, that walk stays
cheap. The row count that should trigger revisiting this decision: an entity
index page routinely paginating past roughly page 100 (2,500+ rows), where
`OFFSET`'s linear cost starts being felt on a reviewer's click. Nothing in
this project's scope gets there.

## Superseded reasoning

N/A — first version of this decision.
