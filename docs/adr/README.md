# Architecture Decision Records

One file per contested decision, with the alternatives that were considered
and rejected.

| #                                                      | Decision                               | Why it mattered                                                                                                                                      |
| ------------------------------------------------------ | -------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| [0006](0006-inertia-instead-of-a-separate-rest-api.md) | Inertia instead of a separate REST API | Avoids an OpenAPI contract and generated client for a single-team, single-frontend app; the board move endpoint is the one deliberate JSON exception |

Decisions **not** given an ADR, because they were conventional rather than
contested: the framework, the frontend build tool, Docker Compose for local
development, and the hosting provider. Each is explained in the README where
it is relevant to running the project.
