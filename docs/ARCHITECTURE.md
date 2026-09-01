# Architecture

A CRM for companies, contacts, deals and quotes: a single Laravel application
serving an Inertia + React frontend, with one JSON API exception for the
kanban board (see [ADR-0006](adr/0006-inertia-instead-of-a-separate-rest-api.md)).

This document is filled in as the application is built. Each section below is
owned by the task named in it.

## Layering

_Filled in alongside the domain foundations: `Money`, `EmailAddress`, the
status-enum contract, and the exception-to-response mapping._

## Request lifecycle

_Filled in alongside the domain foundations: how a request reaches a
controller, how domain exceptions become an Inertia redirect or a
`problem+json` response, and why the board move endpoint is the one route
that skips Inertia entirely._

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
