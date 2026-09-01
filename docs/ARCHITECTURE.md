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

The second resource. It reuses every shared primitive from Companies
unchanged — `<ResourceTable>`, `<ResourceForm>`, `<StatusBadge>`,
`<ConfirmDelete>`, `HasBoardStatus`, `BoardBuilder` and `MoveCardAction` — so
nothing here required bending the abstractions T4 and T5 introduced.

- **`contacts.email` has a partial unique index**, `WHERE deleted_at IS
NULL`, so a soft-deleted contact's address can be reused while a live
  duplicate is still rejected. Per `CONVENTIONS.md` §4 this is never a
  check-then-insert: `CreateContact` and `UpdateContact` attempt the write
  and translate a SQLSTATE `23505` into `DuplicateEmailException`, registered
  in `ExceptionMap` like every other domain exception. The write itself runs
  inside `DB::transaction()` so a unique-index violation is confined to a
  savepoint instead of aborting a transaction a caller may have opened
  around the action.
- **`company_id` is nullable** — a contact with no company renders correctly
  everywhere (index, detail, board) rather than being required to belong to
  one.
- **The delete asymmetry with companies is deliberate (ADR-0005).**
  `DeleteCompany` refuses when live contacts exist —
  `Company::dependentCounts()` now has a real `contacts` entry, counted from
  the `contacts()` relation. `DeleteContact`, by contrast, is never refused:
  nothing depends on a contact yet, and once deals exist (T7),
  `deals.primary_contact_id` is nullable by design, so a contact's removal
  nulls it there instead of being blocked.
- **The company detail page lists its live contacts**, each linking to that
  contact's own detail page — a plain query scoped to `company_id`, not a
  new shared primitive.
- `ContactStatus`: `New → Active → Inactive`, and `Inactive → Active`. No
  terminal state, the same shape as `CompanyStatus`.

### Deals

The third resource, and the first with money and a terminal state. Reuses
every shared primitive from Companies and Contacts unchanged.

- **`DealStage`**: `New → Qualified → Proposal → Negotiation → Won|Lost`.
  `Won` and `Lost` are terminal — `allowedTransitions()` returns nothing for
  either, so `MoveCardAction::canTransitionTo()` already refuses every drag
  out of them without any deal-specific code in the board engine. Reopening
  a terminal deal to `Negotiation` is a deliberate exception to that same
  enum's transition graph, not a transition it allows: `Deal::booted()`
  registers a `saving` listener that permits a `stage` write off a terminal
  value only when the target is `Negotiation`, and rejects every other one —
  including a direct `save()` that bypasses `UpdateDealRequest` and
  `MoveCardAction` entirely. `ReopenDeal` is the one named action that
  performs that write; the board can never reach it because `canTransitionTo()`
  still says no, which is what keeps "reachable only from the detail page,
  never a drag" true without a second guard.
- **Money**: `value_minor` is nullable — a deal's estimated value is often
  unknown before qualification — cast through `MoneyCast` (ADR-0002). The
  create/edit form collects it as a decimal string (`"1500.50"`); `Money::
fromDecimalString()`/`toDecimalString()` are the exact, non-rounding
  boundary conversions between that string and minor units, validated by
  `App\Rules\ValidMoneyAmount` the same way `ValidEmailAddress` bridges
  `EmailAddress` into a Form Request.
- **`company_id` is a required FK**; `primary_contact_id` is nullable and,
  when set, is validated against the deal's own `company_id` by
  `App\Rules\ContactBelongsToCompany` — a contact from a different company
  would misrepresent who the deal is actually with.
- **The delete asymmetry extends one level further (ADR-0005).**
  `Company::dependentCounts()` now has a real `deals` entry alongside
  `contacts`, so a company with live deals cannot be deleted. `DeleteContact`
  nulls `primary_contact_id` on every live deal that named it, in one bulk
  update inside the same transaction as the soft delete — never blocked,
  the same shape as the contacts/companies asymmetry it extends.
- **Company and contact detail pages list their deals** — the company's via
  `deals.company_id`, the contact's via `deals.primary_contact_id` — plain
  scoped queries, not a new shared primitive.
- `quotes.deal_id` is a required FK, so `Deal::dependentCounts()` blocks a
  deal with live quotes the same way `Company::dependentCounts()` blocks a
  company above — `DeleteDeal` refuses via `RecordHasDependentsException`,
  naming the count.
- **The deal-board shortcut** (T9) puts a "Create quote" action on every deal
  card: it opens a small dialog for the two fields a quote doesn't otherwise
  default for itself (`valid_until`, `tax_rate`) and, on submit, creates a
  linked `Draft` quote without leaving the board. `CreateQuoteForDeal` is a
  thin wrapper around `CreateQuote` (T8) — it supplies the deal, today as the
  issue date and an empty item set, then delegates numbering, the customer
  block snapshot and totals to the exact same mechanics a standalone create
  uses. It never writes to the deal itself. The deal detail page lists its
  quotes the same way the company and contact pages list theirs above — a
  plain query scoped to `deal_id`.

### Quotes

The fourth resource, and the most complex: a document with its own line
items, a stored numbering sequence, and a freeze that locks most of it once
it leaves `Draft`. Reuses the shared frontend primitives from Companies and
the board engine from T5, contributing its own `QuoteStatus` enum, card
component and policy like every other entity.

- **`quotes.number`** comes from a Postgres sequence, `quote_number_seq`,
  formatted in PHP as `Q-{year}-{0000}` (`GenerateQuoteNumber`). The
  sequence already guarantees a unique value; the unique index on `number`
  is a backstop, not the primary mechanism — `CreateQuote` translates a
  `23505` into a retry with a fresh sequence value rather than checking
  existence first, per `CONVENTIONS.md` §4.
- **`QuoteStatus`**: `Draft → Sent → Accepted|Rejected|Expired`. `Accepted`,
  `Rejected` and `Expired` are terminal in the enum's own transition graph,
  the same shape as `DealStage`'s `Won`/`Lost` (T7) — the board
  (`MoveCardAction::canTransitionTo()`) refuses every drag out of them.
  `Expired` is set by the `quotes:expire` scheduled command
  (`app/Console/Commands/ExpireQuotes.php`, run daily) once `valid_until`
  has passed, never derived at render time, so the board and every listing
  can trust the stored value directly. Reopening a terminal quote to `Sent`
  is a deliberate exception `Quote::booted()` carves out for itself
  (`ReopenQuote`), not a transition the enum allows — the same pattern as
  `Deal::booted()`'s reopen-to-`Negotiation` carve-out. The same guard also
  refuses `Sent` reverting to `Draft` directly, since nothing else here
  would stop it.
- **The immutability freeze** is the other half of `Quote::booted()`: once a
  quote's _persisted_ status is no longer `Draft`, any write touching
  `tax_rate`, the three money columns, or the four `bill_to_*` snapshot
  columns is rejected with `QuoteNotEditableException` — including a direct
  `save()` that bypasses `UpdateQuote` entirely. `QuoteItem::booted()`
  mirrors this from the item side: once the parent quote is not `Draft`, no
  item create, update or delete is accepted either, checked with a fresh
  query against the quote's current status rather than through a
  `belongsTo` relation that might already be cached from before the quote
  itself transitioned elsewhere in the same request. `notes`, `terms` and
  `status` itself are never frozen — only line items, money and the
  customer block are.
- **`ReplaceQuoteItems`** rewrites a quote's entire item set on every write
  (both create and update) rather than diffing individual rows — line items
  are always edited as one set from the inline editor, and a full replace
  keeps `sort_order` trivially correct. It refuses outright once the quote
  is not `Draft`, which is what actually stops the bulk `delete()` a
  replace starts with (a bulk relation `delete()`, unlike a single model's
  own `delete()`, does not fire `QuoteItem::booted()`'s per-row guard).
- **Standalone create is the only creation path in this task** — the create
  form has a deal picker (`deal_id` is required); the deal-board shortcut
  (T9) is an additional entry point onto the same mechanics, not a
  different one.
- **CRUD** follows the same shape as the other three entities, with one
  addition: the edit form's line-item table and `tax_rate` field are
  disabled once the quote is no longer `Draft`, matching what
  `Quote::booted()` would reject anyway — the UI mirrors the guard rather
  than working around it.

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

`Money` (`app/Support/Money.php`) wraps integer minor units; every operation
except one is exact integer arithmetic with no rounding at all — `add`,
`subtract`, `multiplyBy` a whole-number quantity. The single rounding point
in the entire codebase is `Money::percentage()`, half-up to the nearest
minor unit, computed with exact integer arithmetic rather than floats (see
ADR-0002 and the class's own docblock for the full pipeline diagram).

`RecalculateQuoteTotals` (`app/Actions/Quote/RecalculateQuoteTotals.php`) is
the one place that pipeline is applied to a document: it sums every item's
`line_total_minor` into a subtotal, applies the quote's snapshotted
`tax_rate` once at the document level to get the tax, and adds the two for
the total. It runs once per request, after the whole item set has been
written, inside the same transaction as that write (`CreateQuote`,
`UpdateQuote`) — never per item, never speculatively. It only sets
attributes; the caller folds the save into whichever single `save()` call
also carries the rest of that request's changes, so `Quote::booted()`'s
freeze guard — which reads the status as it stood _before_ the write —
never sees an intermediate state that doesn't actually exist.

This is what makes the totals-invariant test load-bearing rather than
incidental: because nothing recomputes a total anywhere else, and the only
rounding happens once, in one place, a stored `subtotal_minor`/`tax_minor`/
`total_minor` can be asserted exactly equal to a fresh recomputation from
`items()` and `tax_rate` — no tolerance, no drift.

## Snapshot and delete model

Three things get frozen onto a quote, all at write time, all read back
verbatim rather than resolved live: the **line items** (`QuoteItem`'s
`description` and `unit_price_minor`, never joined from a product
catalogue), the **customer block** (`bill_to_company_name`,
`bill_to_address`, `bill_to_contact_name`, `bill_to_contact_email`, derived
from the deal's company and primary contact by `SnapshotCustomerBlock` at
creation), and the **tax rate** (`tax_rate`, used by every later totals
recomputation instead of whatever rate is current elsewhere). A company
rename, a contact's email change, or a later change to whatever default tax
rate a new quote offers must never alter a quote that already captured its
own values — that is the entire reason a snapshot exists instead of a
foreign key plus a join. `Quote::booted()` and `QuoteItem::booted()` enforce
that nothing can un-freeze these fields once the quote leaves `Draft`,
covered in the Quotes section above.

Every entity uses plain `SoftDeletes` with no cascade, no batch ids, no
restore UI (ADR-0005) — soft delete is a data-safety net, not a
user-facing feature, so a mistaken delete is recoverable by direct database
access but never advertised as reversible in the product itself. The
delete-refusal behavior is asymmetric by design, following one rule
consistently: a **required** foreign key blocks the referenced record's
deletion, a **nullable** one nulls out instead.

- `deals.company_id` and `quotes.deal_id` are required, so
  `Company::dependentCounts()` blocks a company with live contacts or
  deals, and `Deal::dependentCounts()` blocks a deal with live quotes —
  both refuse via `RecordHasDependentsException`, naming what blocks them.
- `deals.primary_contact_id` is nullable, so `DeleteContact` nulls it on
  every live deal that named it instead of being refused — a person
  outliving their employer is normal, and nothing downstream requires a
  contact to exist.
- Nothing has a foreign key onto a quote, so `DeleteQuote` is never
  refused — the asymmetry doesn't extend a fourth level because nothing
  yet depends on a quote the way a deal depends on nothing and a company
  depends on everything below it.

## PDF export

_Filled in once quote PDF export exists: the render pipeline, the DejaVu font
registration, and why nothing in the template is resolved live._

## Deployment topology

_Filled in at the production deploy: the single-container nginx + php-fpm
shape, the injected `PORT`, and how migrations run as a release step._
