# Architecture

A CRM for companies, contacts, deals and quotes: a single Laravel application
serving an Inertia + React frontend, with one JSON API exception for the
kanban board (see [ADR-0006](adr/0006-inertia-instead-of-a-separate-rest-api.md)).

This document is filled in as the application is built. Each section below is
owned by the task named in it.

## Layering

Dependencies point inward, but there is no framework-free domain package —
see [ADR-0001](adr/0001-eloquent-first-layering-no-repository-interfaces.md)
for why. Eloquent models are the persistence layer directly:

```
Http (controllers, Form Requests) → Actions (one use case per class) → Eloquent models
```

- **Form Requests validate shape only** — present, string, max length.
  *Validity* is decided by a value object. `EmailAddress`
  (`app/Support/EmailAddress.php`) is the model: it normalizes (lowercase,
  trim) and validates in its constructor, and normalization happens in
  exactly one place — every write to a normalized column goes through it,
  which is what lets the partial unique index on `contacts.email` (T6) rely
  on a single canonical form.
- **Money** (`app/Support/Money.php`) is a `final readonly` value object
  wrapping integer minor units, with `MoneyCast` bridging it to and from an
  Eloquent `bigint` column. See
  [ADR-0002](adr/0002-money-as-minor-units-single-currency.md) for why
  money is minor units in a single currency, and the class itself for the
  rounding pipeline.
- **Status transitions** are owned by the enum, not by the controller or
  action that changes one. Every status-holding backed enum uses the
  `HasTransitions` trait (`app/Enums/Concerns/HasTransitions.php`), which
  supplies `canTransitionTo()` and `isTerminal()` from the enum's own
  `allowedTransitions()`, so a transition can never be checked one way in
  one place and a different way somewhere else.
- **One place maps domain exceptions to HTTP responses.** `ExceptionMap`
  (`app/Exceptions/ExceptionMap.php`) is the single table from exception
  class to HTTP status and message key; see Request lifecycle below for how
  it's used.

## Request lifecycle

Every domain-level rejection — a business rule refusing an action, as
opposed to a framework error — extends `DomainException`
(`app/Exceptions/DomainException.php`). `RecordHasDependentsException` is
the first concrete case: a delete refused because live records still
depend on it (ADR-0005).

`ExceptionMap` is the single table mapping a `DomainException` subclass to
an HTTP status and a message key. Two renderers read it, and the exception
handler (`bootstrap/app.php`) chooses between them based on whether the
request wants JSON:

```
DomainException thrown
        │
        ▼
 wants JSON? ──── no ──→ InertiaExceptionRenderer
        │                 redirect()->back()->withErrors([...])
        │                 302, errors flashed to the session
       yes
        │
        ▼
 ProblemJsonExceptionRenderer
 application/problem+json body (RFC 9457), status from ExceptionMap
```

This split exists because Inertia and a plain JSON client need genuinely
different responses to the same rejection. An Inertia request already
gets this behavior for free from Laravel's own `ValidationException`
handling — a redirect back with errors flashed, not a 422 body — and the
`DomainException` renderer gives every domain-level rejection the same
shape, not just validation failures.

The one route that deliberately skips this Inertia path entirely is the
board move endpoint, `POST /api/v1/boards/{entity}/{id}/move`
(T5). A drag-and-drop reorder needs a response the frontend can branch on
synchronously — succeeded, or rejected with a specific reason — to drive an
optimistic-update revert, and Inertia's redirect-with-flash behavior can't
give that to an in-flight `fetch` call. So that one route is plain JSON in
both directions, and always renders through `ProblemJsonExceptionRenderer`
regardless of what the request's `Accept` header says. See
[ADR-0006](adr/0006-inertia-instead-of-a-separate-rest-api.md) for the full
reasoning.

## Entities

### Companies

_Filled in once the first resource — controller, policy, actions, screens —
exists._

### Contacts

_Filled in once the second resource exists, noting anywhere the shared
primitives from Companies had to change to fit._

### Deals

_Filled in once Deals exists, including the terminal-stage guard and the
asymmetric delete behavior between companies and contacts._

### Quotes

_Filled in once Quotes exists: the snapshot model (line items, customer
block, tax rate), immutability from `Sent` onward, and quote numbering._

## The board engine

_Filled in once the kanban engine and the first board (Companies) exist:
`HasBoardStatus`, `BoardBuilder`'s eager loading, `MoveCardAction`'s
transaction and rebalance strategy, and the JSON move endpoint._

## Money and totals pipeline

_Filled in once `Money` and `RecalculateQuoteTotals` exist: the rounding
rule, and how a quote's stored totals stay provably equal to a fresh
recomputation from its line items._

## Snapshot and delete model

_Filled in once Quotes and the delete-refusal behavior across all four
entities exist: what gets snapshotted, when, and why plain `SoftDeletes`
without cascade or restore was chosen over the alternatives._

## PDF export

_Filled in once quote PDF export exists: the render pipeline, the DejaVu font
registration, and why nothing in the template is resolved live._

## Deployment topology

_Filled in at the production deploy: the single-container nginx + php-fpm
shape, the injected `PORT`, and how migrations run as a release step._
