# ADR-0001: Eloquent-first layering, no repository interfaces or in-memory fakes

## Status

Accepted · 2026-09-01.

## Context

A common layering for a Laravel application puts a framework-free domain
package behind repository interfaces, with Eloquent as one interchangeable
implementation and an in-memory fake as another, so unit tests can run
against the domain without a database. That shape earns its cost when a
second persistence backend is a real possibility, or when the domain logic
is complex enough to be worth testing in complete isolation from the
framework.

Neither is true here. This is a single Laravel application, one team, one
Postgres database, for the life of the project. There is no second backend
to swap in, and the business rules for four CRUD-shaped entities (companies,
contacts, deals, quotes) are not complex enough to need isolation from
Eloquent to stay testable — they are mostly shape validation, status
transitions and a handful of delete-time invariants.

## Decision

Eloquent models are the persistence layer directly, with no repository
interface in front of them. Business rules live in single-purpose Action
classes (`CreateCompany`, `DeleteCompany`, and so on — one use case per
class), invoked from controllers. Form Requests validate shape only
(present, string, max length); a value object decides what is *valid*.
Backed enums own their own status-transition rules via `HasTransitions`.
Policies gate authorization on every resource. No framework-free domain
layer, no repository interfaces, no in-memory fakes.

## Alternatives considered

### Repository interfaces + in-memory fakes (full hexagonal)

Rejected. This roughly doubles the file count per entity — a real Eloquent
repository, an interface, and an in-memory fake kept behaviorally identical
to the real one — for a swap target (a second persistence backend) that
will never be exercised. The in-memory fake is also its own maintenance
burden: it has to be kept in sync with every query the real implementation
grows, and a drifted fake turns the unit suite that runs against it into
theatre rather than a real guarantee.

## Consequences

Adding an entity is cheap: one migration, one model, one enum, a handful of
Action classes, and Form Requests — matching the CRUD-heavy shape of this
application. The cost lands if a second persistence backend, or a
message-based domain model, is ever needed: introducing an interface at
that point means touching every Action class that currently calls Eloquent
directly, where the repository-interface alternative would have made that a
one-file change. That trade is deliberate for a single-backend, single-team
application.

## Superseded reasoning

N/A — first version of this decision.
