# ADR-0007: In-container Laravel scheduler instead of a Railway cron service

## Status

Accepted · 2026-09-02.

## Context

`quotes:expire` moves a `Sent` quote to `Expired` once its `valid_until` date
has passed. It has to run once a day somewhere, and the production container
has no system cron — the base image is Alpine, the container runs as a
single foreground process under Docker's signal handling, and nothing else in
the deployment currently needs a second scheduled process.

Two shapes were on the table: give the command a Railway **cron service** of
its own (the same image, `deploy.cronSchedule` in its `railway.json`, started
with `php artisan quotes:expire` instead of the web command), or run
Laravel's own scheduler loop inside the existing web container.

## Decision

`php artisan schedule:work` runs backgrounded in the same container as
php-fpm and nginx, started from `docker/prod/entrypoint.sh`. It sleeps until
each due minute and dispatches whatever `routes/console.php` has scheduled —
currently just the daily `quotes:expire` call. Docker still sends its signals
to nginx in the foreground; the scheduler and php-fpm are both backgrounded
processes inside the same container, the same relationship php-fpm already
has to nginx.

## Alternatives considered

### A dedicated Railway cron service

Rejected for this deployment's scale. A second service means a second thing
to configure with the database connection variables, a second place a
misconfiguration can hide, and a second bill line — all to run one `UPDATE`
a day. `DEPLOY-RAILWAY.md` §1 is explicit that the whole point of this
deployment is one service, one origin; a cron service for a single low-stakes
daily job works against that without buying anything a bigger, multi-job
scheduler would actually need.

## Consequences

Adding a second scheduled command is free — one more line in
`routes/console.php`, nothing to provision. The cost is concentrated the
other way: if the schedule ever needs a job with its own resource profile
(heavier than a single `UPDATE`, or one that shouldn't restart when the web
process does), splitting it into its own Railway service is the natural next
step, and this ADR is what gets superseded when that happens.

## Superseded reasoning

N/A — first version of this decision.
