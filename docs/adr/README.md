# Architecture Decision Records

One file per contested decision, with the alternatives that were considered
and rejected.

| # | Decision | Why it mattered |
| --- | --- | --- |
| [0001](0001-eloquent-first-layering-no-repository-interfaces.md) | Eloquent-first layering, no repository interfaces | Avoids a repository/fake pair per entity for a single-backend, single-team app |
| [0002](0002-money-as-minor-units-single-currency.md) | Money as integer minor units, single currency | Keeps every money sum exact and reproducible; the quote totals invariant test depends on it |
| [0004](0004-query-count-assertions-instead-of-explain.md) | Query-count assertions instead of `EXPLAIN` tests | Guards against N+1 on the boards without depending on planner internals that vary between environments |
| [0005](0005-soft-delete-without-cascade-or-restore-ui.md) | Plain soft delete, no cascade, no restore UI | Protects a sent quote's immutability; refuses a delete with live dependents by name instead of cascading through them |
| [0006](0006-inertia-instead-of-a-separate-rest-api.md) | Inertia instead of a separate REST API | Avoids an OpenAPI contract and generated client for a single-team, single-frontend app; the board move endpoint is the one deliberate JSON exception |

Decisions **not** given an ADR, because they were conventional rather than
contested: the framework, the frontend build tool, Docker Compose for local
development, and the hosting provider. Each is explained in the README where
it is relevant to running the project.
