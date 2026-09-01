# Laravel Cloud release and rollback

This runbook is intentionally dormant until a Laravel Cloud staging environment is approved and provisioned. It contains no organisation, application, environment, domain, credential, or production database identifier.

## One-time staging setup

1. Confirm the Laravel Cloud organisation, European region, monthly budget/spending limit, staging instance sizes, and named release owner.
2. Confirm current CLI syntax with `cloud <command> -h`; run every non-interactive command with `-n` and JSON output where supported.
3. Authenticate with `cloud auth -n`, create or select the application, and bind repository defaults with `cloud repo:config <application> --organization=<organization> -n`.
4. Connect `charlielangridge/bbmembershipsystem` and create a non-production environment tracking the approved deployment branch. Select PHP 8.4 and Node 24.
5. Attach a Laravel MySQL 8.4 database and Laravel Valkey cache. Cloud supplies their credentials; never copy them into Git.
6. Configure encrypted environment values through Laravel Cloud secrets. At minimum set `APP_ENV=staging`, `APP_DEBUG=false`, `SESSION_DRIVER=redis`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=redis`, `LOG_CHANNEL=stack`, `LOG_STACK=stderr`, `LOG_LEVEL=info`, and the Flare key/settings.
7. Add dedicated worker compute running `php artisan queue:work --sleep=1 --tries=3 --timeout=90 --max-time=3600`.
8. Before member uploads are exercised, attach Laravel Object Storage, install the Laravel-supported S3 Flysystem adapter, select the private S3 disk, and prove authorised temporary access.

## Build and deploy contract

Use a build command equivalent to:

```shell
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Use a deploy command equivalent to:

```shell
php artisan migrate --force
php artisan optimize
```

Laravel Cloud performs zero-downtime replacement and gracefully reloads long-running services. Push-to-deploy must remain off until the environment has demonstrated that CI gates the exact commit; after that, configure a protected deploy hook or approved branch policy.

## Staging release proof

1. Record the candidate commit SHA and ensure GitHub CI is green for that exact SHA.
2. Trigger the first release with `cloud ship -n`, or an existing environment release with `cloud deploy <application> <environment> -n`, only after checking command help.
3. Always monitor to a terminal state with `cloud deploy:monitor -n`.
4. Verify `/up` returns success and an `X-Request-ID` header without configuration data.
5. Run migrations/status checks through the Cloud command facility, then exercise authentication, a queued no-op smoke job, cache/session persistence, mail delivery, and a deliberately captured non-sensitive Flare test error.
6. Record deployment ID, commit, timestamps, checks, and approver in the release evidence. Do not paste secret-bearing output.

## Application rollback

1. Stop promotion and announce the rollback using the recorded release owner and incident channel.
2. Redeploy the last known-good commit from Laravel Cloud. Monitor the deployment to a terminal state and repeat `/up` plus smoke checks.
3. Do not reverse a destructive database migration in place. Use expand/contract migrations so the prior release remains compatible; restore a Cloud snapshot into a new cluster and reattach only under the separately approved data-recovery procedure.
4. Reconcile queued jobs before resuming workers. Never purge a queue as part of a routine rollback.
5. Record the failed and restored deployment IDs, database state, queue state, impact window, and follow-up action.

## Production gate

Production provisioning remains blocked until the production schema/version, data volume, storage requirements, recovery objectives, retention, region, sizing, Flare project, mail provider, and cutover ownership are known. A successful staging deployment and rollback using this runbook is required before Phase 1 infrastructure evidence can be signed off.
