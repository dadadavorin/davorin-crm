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
  _Validity_ is decided by a value object. `EmailAddress`
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

The first resource, and the pattern every later entity copies.

- **Backend**: `CompanyController` is an explicit resource controller — no
  shared base controller (a deliberate choice; duplication across the four
  entities is removed one layer down, in shared Actions and React
  primitives, not by inheriting a generic `ResourceController`). Three
  single-purpose actions (`CreateCompany`, `UpdateCompany`, `DeleteCompany`)
  hold the business rules; `StoreCompanyRequest`/`UpdateCompanyRequest`
  validate shape only, and `App\Rules\ValidEmailAddress` is the bridge that
  lets `EmailAddress` (the value object that decides validity) fail as an
  ordinary field error instead of an uncaught exception when a form submits
  a malformed address.
- **The dependent-check is real but currently empty.** `Company::dependentCounts()`
  returns `[]` today; `DeleteCompany` already refuses a delete whenever it
  isn't, via `RecordHasDependentsException` (ADR-0005). Contacts (T6) and
  deals (T7) add entries to that method — the refusal path itself needs no
  new code.
- **Reads are never scoped by owner** — `CompanyPolicy::viewAny`/`view` are
  unconditional; only `delete` checks owner-or-admin. Every later policy
  follows the same shape.
- **Search** goes through `Company::scopeSearch()`, an `ILIKE` with an
  explicit `ESCAPE` clause so a literal `%` or `_` typed into the search box
  matches only itself.
- **Pagination is offset-based**, 25 per page — see
  [ADR-0003](adr/0003-offset-pagination-instead-of-keyset.md).
- **Frontend**: four shared primitives are introduced here and reused by
  every later entity — `<ResourceTable>` (column-config-driven, sortable,
  empty state), `<ResourceForm>` (field-config-driven, inline errors),
  `<StatusBadge>` (presentation only; each entity maps its own enum to a
  badge variant, e.g. `companyStatusVariant`), and `<ConfirmDelete>` (a
  dialog wrapping `router.delete`, so a `RecordHasDependentsException`
  refusal surfaces as a flashed error instead of a silent failure).

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

One engine, shared by all four entities: each contributes only its status
enum, a `HasBoardStatus` implementation on its model, a card component and a
policy. Nothing board-specific lives outside `app/Board/`.

- **`HasBoardStatus`** (`app/Board/HasBoardStatus.php`) is the model-side
  contract: which enum holds the status (`boardStatusEnum()`), which column
  it lives in (`boardStatusColumn()` — `status` for most entities, `stage`
  for deals), which relations a card needs eager-loaded
  (`boardCardRelations()`), and how to render one card
  (`toBoardCard()`). `BoardBuilder` and `MoveCardAction` depend on this
  contract only, never on a concrete entity. The enum side of the same
  contract is `App\Enums\Concerns\BoardStatus`, which every board-driven
  status enum implements alongside `HasTransitions`.
- **`BoardBuilder`** (`app/Board/BoardBuilder.php`) turns a model class into
  one column per enum case, ordered by `boardOrder()`. It owns the eager
  loading: one query for every live row plus one query per relation in
  `boardCardRelations()` — a fixed count regardless of how many cards exist,
  never one query per row. Each column is capped at 50 cards with a
  `has_more` count past the cap.
- **`MoveCardAction`** (`app/Board/MoveCardAction.php`) resolves the target
  status, checks the transition is legal (or a same-column reorder), and
  computes the new `position` as the midpoint of the two neighbour ids the
  request names — rebalancing the column first if the gap between them has
  shrunk too far to bisect safely. All of it runs inside one transaction
  with every row it touches locked. See the class's own docblock for the
  full diagram and the rebalance threshold's reasoning.
- **`BoardEntityRegistry`** (`app/Board/BoardEntityRegistry.php`) is the
  single place mapping a board move URL's `{entity}` segment to the model
  class it moves — the only file a later entity's board needs to touch
  beyond its own enum, card component and policy.
- **The move endpoint**, `POST /api/v1/boards/{entity}/{id}/move`, is the
  JSON surface named in ADR-0006: session-authenticated through the `web`
  middleware group like the rest of the app, but never Inertia, so a
  rejection comes back as a real `422` `problem+json` body a `fetch` call
  can branch on instead of a redirect with flashed errors. It returns `204`
  on success.
- **The board page itself is a plain Inertia route** (`GET
/companies/board`, one per entity) — only the move is special. The
  controller calls `BoardBuilder` and renders the board component with its
  columns as props, the same as any other page.
- **`<KanbanBoard>`** (`resources/js/components/kanban-board.tsx`) is the
  one React component every board reuses, generic over its card type. Drag
  state lives in `useKanbanBoard`
  (`resources/js/hooks/use-kanban-board.ts`): a drop is applied to local
  state immediately, posted to the move endpoint, and — on a rejection —
  reverted back to what it was before the drop, with the server's reason
  shown as a toast.

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
