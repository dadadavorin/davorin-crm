# Davorin CRM

A CRM for tracking companies, contacts, deals and quotes through a kanban
board per entity, with quote line items, PDF export and an audit-friendly
snapshot model. Single-team, single-tenant: every authenticated user sees
every record.

## Stack

- **Backend**: PHP 8.4, Laravel 13, PostgreSQL 18
- **Frontend**: Inertia 3 + React 19 + TypeScript, Tailwind 4 + shadcn/ui
- **PDF export**: `barryvdh/laravel-dompdf`, pure PHP — no headless browser
  in the image
- **Local development**: Docker Compose
- **Testing**: Pest (backend), Vitest (frontend components)

There is no separate REST API or generated API client — Inertia serves
props directly from controllers into React pages. The one deliberate
exception is the kanban board's move endpoint, a plain JSON route; see
[ADR-0006](docs/adr/0006-inertia-instead-of-a-separate-rest-api.md) for why.

## Quick start

```bash
cp .env.example .env
docker compose up
```

The app is served at **http://localhost:8080**. The dev container installs
dependencies, generates an application key, runs migrations and seeds demo
data automatically on first boot — later restarts skip whatever step is
already done, so nothing re-runs against a volume that already has it.

Sign in with the seeded admin account (credentials in `.env.example`,
overridable via `SEED_ADMIN_EMAIL`/`SEED_ADMIN_PASSWORD` before the first
boot):

```
admin@davorincrm.test / password
```

A second, non-admin account (`member@davorincrm.test / password`) is seeded
alongside it to exercise the read-everyone/delete-owner-or-admin policy
shape. The seeded data spans every status and stage on all four boards —
twelve companies, thirty contacts, twenty deals and ten quotes, Croatian
names throughout, all owned by the admin account — so the first thing either
account sees is a populated CRM, not an empty one.

## Checks

```bash
composer check   # Pint (lint) + Larastan (static analysis) + Pest (tests)
npm run check    # tsc --noEmit + ESLint
npm run test     # Vitest
```

Run these inside the `app` container (`docker compose exec app ...`) or
locally against a Postgres instance matching `.env`'s `DB_*` values.

## Project layout

```
app/
  Actions/       One class per use case (CreateCompany, MoveCardAction, ...)
  Board/         The kanban engine shared by all four entities
  Enums/         Status/stage enums, each owning its own transition graph
  Exceptions/    Domain exceptions and the two renderers that map them
  Http/          Controllers and Form Requests
  Models/        Eloquent models — the persistence layer directly, no
                 repository layer in between
  Policies/      Authorization, one per entity
  Services/      QuotePdfRenderer and other framework-facing services
  Support/       Money, EmailAddress and other framework-free value objects
database/
  factories/     Model factories used by both tests and the seeders
  migrations/
  seeders/       DatabaseSeeder plus one seeder per entity
docs/
  ARCHITECTURE.md  How the pieces fit together
  adr/             Architecture decision records — see below
resources/js/    Inertia pages, shared React primitives, the kanban board
tests/
  Unit/          Value objects, enums, policies
  Feature/       Endpoints, error paths, invariants
docker/          The dev image, entrypoint and nginx config
```

## Architecture decisions

[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) describes how the pieces fit
together — layering, the board engine, the money and totals pipeline, the
snapshot and delete model, PDF export.

Contested decisions each get an ADR under [`docs/adr/`](docs/adr/), with the
alternatives that were considered and rejected. Conventional choices — the
framework, the frontend build tool, Docker Compose for local development —
don't get one; they're explained above, where they're relevant to running
the project.
