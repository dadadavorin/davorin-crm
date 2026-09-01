# ADR-0006: Inertia instead of a separate REST API

## Status

Accepted · 2026-09-01.

## Context

The stack is Laravel plus a React frontend. Two shapes were on the table: a
Laravel JSON API consumed by a separately-built React SPA (with an OpenAPI
contract and generated client types), or Inertia, where Laravel controllers
return page components directly and the frontend never talks to a general
JSON API at all.

A REST API is the right shape when the frontend is a genuinely separate
client — a mobile app, a third-party integration, multiple frontends against
one backend. None of that applies here: this is one team building one
Laravel application with one React frontend, deployed as a single unit. Under
those conditions a REST layer is pure overhead — a contract to keep in sync,
a generation step to fail silently when it drifts, and a duplicate copy of
every validation rule (once in the Form Request, once in whatever schema
generates the client types).

## Decision

Inertia end to end. Controllers return `Inertia::render(...)` with props built
directly from Eloquent models; the frontend renders whatever page component
the controller named. No OpenAPI document, no generated TypeScript client, no
`api/` route file for CRUD.

**One deliberate exception: the kanban move endpoint.** A drag-and-drop
reorder needs a response the frontend can branch on synchronously — succeeded,
or rejected with a specific reason — to drive an optimistic-update revert.
Inertia's request lifecycle does not give that: a `ValidationException` or a
domain rejection thrown inside an Inertia request produces a **302 redirect
back with flashed session errors**, not a status code and a body the
in-flight `fetch` call can inspect. So `POST /api/v1/boards/{entity}/{id}/move`
is a plain JSON route, hit with `fetch`/axios outside the Inertia lifecycle,
returning `204` on success or a `422` `application/problem+json` body on
rejection. It is the one place in the application with a real JSON API
contract, and it exists for this one reason.

## Alternatives considered

### Full REST API + generated TypeScript client

Rejected. A single-team, single-frontend, single-deployment application gets
no benefit from the contract layer, only its cost: an export step, a drift
check, and a second definition of every validation rule.

### Inertia everywhere, including the board move

Considered and rejected specifically because of how Inertia's exception
handling works. A `ValidationException` thrown during an Inertia request is
turned into a redirect with flashed errors — correct for a form submission,
useless for a drag handler that needs to inspect a status code on the
response it just received and revert an optimistic UI update. Forcing the
move endpoint through Inertia would mean parsing a redirect Location and
session flash data from a `fetch` call, which is more fragile than just
giving that one endpoint a real JSON contract.

## Consequences

Adding CRUD screens is cheap: a controller, a Form Request, an
`Inertia::render` call, no parallel API surface to keep in sync. The cost is
concentrated in one place — if a second Laravel-external client ever needs to
integrate with this application (a mobile app, a partner integration), it has
no API to call except the one board-move route, and building a general one at
that point means introducing the OpenAPI/contract machinery this ADR
deliberately avoided.

## Superseded reasoning

N/A — first version of this decision.
