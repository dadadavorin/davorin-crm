# ADR-0005: Plain soft delete, no cascade, no restore UI

## Status

Accepted · 2026-09-01.

## Context

Companies, contacts, deals and quotes reference each other: a deal
requires a company, a quote requires a deal, a deal may reference a
primary contact. Deleting a record that other live records still depend on
raises a real question — cascade the delete through every dependent, or
refuse it. Cascading is dangerous here specifically because a **sent
quote** is meant to be an immutable historical document (its own line
items, customer block and tax rate are snapshotted precisely so a later
change elsewhere can't alter it); a company delete that cascades through
contacts, deals and quotes would make a sent quote disappear because
something three relations away was deleted, silently defeating that
immutability guarantee.

## Decision

Plain Eloquent `SoftDeletes` on all four entities. No cascade, no restore
UI, no recycle bin. Deleting a record that other live records still point
to is refused outright, with a message naming what blocks it
(`RecordHasDependentsException`, carrying the blocking counts) rather than
silently cascading or silently orphaning. The asymmetry is deliberate: a
company with live contacts or deals cannot be deleted, because
`deals.company_id` is required; deleting a contact instead nulls the
now-dangling `primary_contact_id` on any deal that referenced it, because
that column is nullable by design — a person outliving their employer at
the company is a normal event, not an error state. Soft delete itself
exists purely as a data-safety net against accidental deletion, recoverable
by direct database access; it is not exposed as a user-facing feature.

## Alternatives considered

### Hard delete

Rejected. Unrecoverable from a user mistake the moment it happens, and
leaves no trace for a "why is this data gone" question later.

### Cascading soft delete

Rejected. Deleting a parent would silently delete every dependent,
including a sent quote — the one document in the system that's supposed to
survive changes to the records it was built from. Nothing in the
assignment calls for cascade, and the value it would add (fewer blocked
deletes) is smaller than the guarantee it would break.

### A restore UI / recycle bin

Rejected. Nothing in the assignment calls for it, and it roughly doubles
the surface soft delete would otherwise need — a policy for who can
restore, a browse-trashed-records screen — for a feature that isn't
requested. The soft-deleted rows already exist in the database if this is
ever needed later; adding the UI on top is additive, not a schema change.

## Consequences

Deleting a record with live dependents always requires either removing the
dependents first or accepting that it can't be deleted yet — occasionally
more friction for the user, but nothing is ever silently lost or silently
broken elsewhere. Recovering an accidentally deleted record currently needs
direct database access; if a restore UI is ever needed, the data it would
operate on is already there.

## Superseded reasoning

N/A — first version of this decision.
