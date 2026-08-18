# BBMS Modernisation Masterplan

Status: proposed
Audit date: 17 August 2026
Repository baseline: `d686bf6d40ea39dd0493d787d4982909e3330fb2` (1 April 2024)
Scope: Laravel, PHP and JavaScript dependencies, tests, application code, data safety, security, delivery, and operations

> **Approved strategy update — 18 August 2026:** The client selected direct implementation from Phase 5 using a clean Laravel 13 skeleton. The fragile hosted system will remain unchanged and serve only as a read-only behavioural/data reference until cutover. The approved foundation uses the official Inertia 3/Vue 3/TypeScript starter kit for the member application, Filament for privileged administration, Spatie Laravel Permission with Laravel policies, Pest 5, and Brick/Money with integer-pence persistence. The execution plan is [LARAVEL_13_IMPLEMENTATION_PLAN.md](LARAVEL_13_IMPLEMENTATION_PLAN.md); it supersedes earlier sequencing and stack recommendations.

## Executive summary

BBMS is a working, business-critical membership system built on Laravel 5.1.46 and a 2015-era frontend. It manages money, membership entitlement, physical access, equipment charging, personal data, and scheduled billing. The upgrade must therefore preserve behaviour before it improves design.

The recommended destination is:

- Laravel 13.x, supported with security fixes until 17 March 2028.
- PHP 8.4, supported with security fixes until 31 December 2028.
- Pest 5 as the authored PHP test stack.
- Node.js 24 LTS, Vite, and one package manager.
- Inertia 3, Vue 3, TypeScript, Tailwind 4, and the official Laravel Vue starter-kit conventions.
- Filament for privileged administration, with Spatie Laravel Permission and Laravel policies as the authorization boundary.
- Reproducible local, CI, staging, and production environments.
- Supported payment, storage, mail, logging, and realtime integrations.

Do not attempt an in-place `composer update` on the hosted application. Following the approved client decision, development begins with a clean Laravel 13 skeleton on an isolated branch; the legacy application remains unchanged and is used only as a read-only behavioural and data reference. Required behaviour is rebuilt as tested vertical slices while retaining the database and externally visible contracts where necessary. Cut over only after automated parity checks, a production-like rehearsal, and a tested rollback. See the detailed [Laravel 13 clean-skeleton implementation plan](LARAVEL_13_IMPLEMENTATION_PLAN.md).

The programme is complete when the application is on supported runtimes, all critical journeys are tested, every production integration is either upgraded or explicitly retired, deployments are repeatable, and the old runtime can be removed.

## 1. Audit snapshot

### 1.1 Repository size and shape

The audit excluded `vendor` and `node_modules`, neither of which is installed in the current checkout.

| Area | Finding |
|---|---:|
| Tracked files | 632 |
| Application PHP files | 209 |
| PHP files across app, database, and tests | 323 |
| Approximate PHP lines across app, database, and tests | 41,758, including generated Codeception helpers |
| Controllers | 48 |
| Eloquent entities | 20 |
| Repository classes | 17 |
| Explicit route declarations | 88 |
| Migrations | 44, dated 2014–2018 |
| Blade views | 92 / about 4,774 lines |
| Source JavaScript | 27 files / about 1,182 lines |
| Codeception scenario files | 37: 4 API, 27 functional, 6 unit |
| PHPUnit-style test classes | 11 application test classes plus the base test case |
| JavaScript tests | 2 Jasmine/Karma test files |

Major functional areas found in code are:

- signup, login, password reset, profiles, member status, and role-based access;
- subscriptions, member credit, expenses, cash payments, Stripe, PayPal, and two generations of GoCardless handling;
- RFID key fobs, door/access-control endpoints, ACS nodes, equipment access, usage logs, and automatic charges;
- inductions, storage boxes, proposals/voting, notifications, group email, and policies;
- scheduled membership checks, billing, balances, proposal results, equipment fees, and device health checks;
- S3-hosted member/equipment/expense/CCTV images, Pusher realtime events, email, Rollbar, Clockwork, Slack remnants, Swagger 2 output, and Envoyer heartbeat URLs.

### 1.2 Runtime and dependency baseline

Product direction recorded on 18 August 2026 requires parity for every inventoried legacy capability. Historical direct-debit migration, PayPal payments/donations, CCTV capture, Discord status notifications, Swagger/API documentation, and secure log viewing are the final tranche: begin them only after all other parity work is accepted. Preserve their business outcomes with supported implementations rather than retaining obsolete packages or unsafe protocols.

| Concern | Current repository state | Modernisation implication |
|---|---|---|
| PHP | No Composer platform constraint; Travis used PHP 7.1 | Reproduce the exact legacy runtime first; target PHP 8.4 |
| Laravel | `v5.1.46` | Unsupported; move to Laravel 13 via a clean skeleton transplant |
| Composer | Lock file from the Composer 1 era; minimum stability is `dev` | Normalise metadata, add platform requirements, audit, and regenerate deterministically |
| PHPUnit | `4.8.36` | Convert valuable scenarios to Pest 5 syntax and current Laravel assertions |
| Codeception | `2.3.6` plus generated helpers committed to the repository | Port valuable scenarios to Laravel/Pest and remove generated sources |
| PHPSpec | Config and package exist, but no specs were found | Remove unless a missing external spec suite is discovered |
| Frontend | Gulp 3, Laravel Elixir 2, Browserify, Babel 5, LESS | Replace build pipeline with Vite |
| UI libraries | React 0.13, Backbone 1.2, jQuery 2.1, Bootstrap 3, Select2 3 | Do not upgrade these mechanically; replace the small widget layer deliberately |
| CI | Travis only, with obsolete services and coverage paths | Replace with a maintained CI workflow and production-like database service |
| Deployment | README references Forge/DigitalOcean; no deploy definition is tracked | Inventory the real platform and codify build, release, worker, scheduler, and rollback steps |

The local host currently has PHP 8.4.22, Composer 2.10.1, Node 25.2.1, and npm 11.7.0. Node 25 is end-of-life and must not become the project target. Node 24 is the current LTS line.

### 1.3 Code and architecture findings

- Controllers, Eloquent models, repositories, helpers, observers, event handlers, and custom validators share business logic. The most important rules are not isolated behind stable application services.
- `User` is a 416-line active record containing relationships, presentation choices, authorisation, membership state changes, and persistence.
- `PaymentRepository` is 365 lines and mixes querying, state transitions, event dispatch, and balance/subscription coordination.
- Some controllers are similarly large: `AccountController` is 344 lines and `PaymentController` is 315 lines.
- Static facades are pervasive: the audit found 65 `Input::` references, 88 `Request::` references, and 55 `Auth::` references. This raises framework migration cost and makes focused unit tests harder.
- Authorisation is split between route middleware, controller middleware, `User::findWithPermission()`, and role checks in models. There are no policies or gates providing a single auditable rule set.
- Domain states and categories are strings spread through code and database rows (`active`, `suspended`, `payment-warning`, payment sources/statuses/reasons). Typographical drift is possible.
- Legacy string events such as `payment.create` coexist with event classes. Side effects are hard to trace and are sometimes synchronous inside persistence flows.
- No database transactions were found in application code, including payment and balance transitions that update multiple records and emit events.
- Currency storage is inconsistent: `payments` uses SQL `DOUBLE`, while several other money fields use integers. Floating-point values must not be silently reinterpreted during migration.
- The migrations define very few foreign keys or composite uniqueness constraints. Application behaviour currently carries much of the referential-integrity burden.
- Migrations include both schema changes and data/role mutations. Fresh-database construction and upgrading a real historical database are separate behaviours and both need tests.
- A tracked `database/dump.sql` contains schema and insert statements. Confirm that it contains no real personal or secret data, replace it with intentional factories/seeders or a sanitised snapshot, and remove it from history if necessary.

### 1.4 Test findings

There is useful behavioural coverage, especially around roles, membership, payments, subscriptions, ACS endpoints, equipment, expenses, proposals, and storage. It is fragmented across old PHPUnit, Codeception API/functional/unit suites, and two Karma/Jasmine tests.

Known weaknesses are:

- dependencies are absent, so no suite currently runs in this checkout;
- the PHPUnit base case migrates and rolls back per test rather than using modern database test traits;
- SQLite is configured for PHPUnit while Codeception expects MySQL, allowing database-specific differences;
- PHPUnit method discovery relies heavily on old `@test` docblocks;
- some tests and assertions are commented out, including ACS boot behaviour and an entire GoCardless API scenario;
- test helpers generated by Codeception are committed and account for a large portion of reported PHP lines;
- external integrations are not consistently faked at a clear adapter boundary;
- time, webhooks, duplicate delivery, concurrency, and scheduled-command idempotency are not sufficiently controlled;
- there is no visible coverage threshold, mutation testing, static analysis, browser smoke suite, or contract-test strategy.

### 1.5 Security and operational findings

These are findings to verify and address, not claims that the running production system is exploitable:

1. Global CSRF middleware is commented out in `app/Http/Kernel.php`. It must be restored, with narrow exclusions only for verified machine/webhook endpoints. Laravel 13 additionally checks request origin in its request-forgery middleware.
2. Logout is a `GET` route. It must become a CSRF-protected `POST` action.
3. `.env.example` and `.travis.yml` contain a fixed encryption key. Determine whether it was ever used outside disposable tests, rotate affected credentials through an explicit key/session migration plan, and never ship a real default key.
4. API keys are stored and rendered as plaintext for ACS devices. Introduce hashed-at-rest credentials or scoped tokens, one-time display, rotation, revocation, audit records, rate limits, and replay protection.
5. Several access-control, camera, and webhook endpoints are intentionally outside member authentication. Each needs a documented trust model, payload validation, authentication/signature verification, replay window, rate limit, safe logging, and contract tests.
6. GoCardless signature comparison uses `!=`; use the supported SDK verification path or constant-time `hash_equals` after validating required headers and payload shape.
7. The application uses legacy Stripe Checkout/Charges, PayPal Merchant SDK/IPN, and old GoCardless clients. Payment modernisation is a compliance and correctness workstream, not just a package bump.
8. Hard-coded Pusher identifiers, analytics identifiers, site URLs, heartbeat URLs, S3 URL construction, and external scripts should move to configuration or be retired. Apply a content security policy after enumerating required origins.
9. Production-only browser monitoring code and old Swagger UI assets are committed directly. Replace with supported packages/build outputs and establish a policy that generated/vendor assets are not hand-maintained.
10. Debug tooling is in production requirements/providers. Clockwork and similar tools must be development-only and disabled by default outside local environments.

## 2. Target state

### 2.1 Supported platform

- Laravel `^13.0`.
- PHP `~8.4.0` initially. Re-evaluate PHP 8.5 only after the Laravel 13 cutover is stable; do not combine those changes.
- Composer 2 with `composer.lock`, authoritative production autoloading, and no `minimum-stability: dev` unless a documented dependency requires it.
- Pest `^5.0` as the single authored PHP test style; PHPUnit is transitive infrastructure only.
- Node.js 24 LTS, pinned in a version file and CI.
- Inertia `^3.0`, Vue `^3.5`, TypeScript, Tailwind CSS `^4.1`, Vite, and the Laravel Vite/Wayfinder plugins; npm is the selected package manager.
- A supported MySQL/MariaDB version selected after inspecting production version, collation, SQL mode, table engines, and zero-date/data compatibility.

Laravel 13 requires PHP 8.3–8.5 and receives security fixes until March 2028. PHP 8.4 remains security-supported until December 2028, which makes this pair a conservative target as of this plan.

### 2.2 Application shape

- Keep Laravel routes, controllers, validation, policies, and server-side data loading authoritative. Inertia renders the public/member Vue application; Filament renders the privileged administration panel within its Livewire boundary.
- Preserve existing URLs, database identifiers, membership/payment semantics, and device contracts unless a versioned migration explicitly changes them.
- Put HTTP concerns in controllers and form requests, authorisation in policies/gates, workflows in application actions/services, provider calls behind interfaces, and persistence rules close to models/query objects.
- Introduce typed enums/value objects for statuses, payment reasons/sources, currency amounts, and identifiers after tests lock current semantics.
- Use first-party Laravel capabilities before community packages: validation, notifications, HTTP client, filesystem, mail, logging, events, queues, rate limiting, and Vite.
- Keep provider-specific payloads out of the core domain. Translate webhooks into internal commands/events at an adapter boundary.
- Use explicit database transactions and idempotency keys for financial, entitlement, and access-control state changes.

### 2.3 Quality gates

Every merge to the modernisation branch must pass:

1. dependency installation from lock files;
2. code formatting;
3. static analysis at the agreed level, ratcheted upward rather than disabled;
4. Pest unit, feature, integration, and contract suites;
5. frontend lint/type checks where JavaScript remains;
6. production frontend build;
7. dependency vulnerability audits;
8. migration tests from both an empty database and a sanitised production-like snapshot;
9. a small browser smoke suite on critical journeys;
10. secret scanning and a check that generated/vendor assets are not accidentally committed.

## 3. Delivery strategy

### 3.1 Recommended strategy: characterise, decouple, transplant, cut over

> **Decision:** Begin at the clean-skeleton stage. Steps below that would modify or install tooling into the legacy application are no longer prerequisites. Discovery and characterisation still occur, but against source, sanitised snapshots, provider/device fixtures, and approved read-only observations. Execution is defined in [LARAVEL_13_IMPLEMENTATION_PLAN.md](LARAVEL_13_IMPLEMENTATION_PLAN.md).

Laravel 5.1 to 13 crosses twelve major framework boundaries, several PHP generations, a new application bootstrap model, authentication and testing changes, and many abandoned packages. Repeatedly upgrading the same old skeleton would spend substantial effort preserving obsolete integration code only to replace it later.

Use this strategy:

1. Freeze and reproduce the current application in an isolated legacy toolchain.
2. Capture behaviour with tests and production-safe observations.
3. Fix urgent security issues that can be changed safely on the legacy branch.
4. Create narrow adapters around external providers and high-risk workflows.
5. Generate a clean Laravel 13 application skeleton in a dedicated branch or temporary comparison directory.
6. Port configuration and business slices into that skeleton, preserving route names, database tables, IDs, and public contracts.
7. Run legacy and modern test oracles against the same sanitised scenarios.
8. Rehearse data migration and deploy to staging.
9. Cut over with a tested rollback, then retire the legacy runtime.

This is a framework transplant, not a rewrite: existing behaviour and data remain the acceptance contract. If the team chooses sequential in-place upgrades instead, it must move one major at a time (`5.1 -> 5.2 -> ... -> 13`), run the full suite at every boundary, and commit each boundary separately. Do not skip official upgrade guides during an in-place route.

### 3.2 Branch and change discipline

- Create a signed/annotated baseline tag and a modernisation branch after the legacy suite is green.
- Keep commits small and reversible: environment, tests, adapter, framework port, and behaviour change should not be mixed.
- Use one vertical slice per pull request where practical: route, request, policy, action, persistence, view/API response, and tests.
- No opportunistic product redesign during compatibility work. Record it in a post-upgrade backlog.
- Maintain a `MODERNISATION_DECISIONS.md` or ADR directory for target runtime, database, UI approach, payment-provider decisions, and cutover topology.
- Maintain a route/command/integration parity ledger showing legacy status, modern status, tests, owner, and cutover evidence.

## 4. Phased programme

### Phase 0 — Ownership, backups, and production discovery

Goal: know what is actually running and make recovery possible before touching it.

Work:

- Name a technical owner and a business owner for membership, finance, and physical access.
- Inventory production PHP, web server, OS, database engine/version/SQL mode, cron, queue workers, file storage, DNS/TLS, mail, cache/session, secrets, Forge/Envoyer configuration, backups, and monitoring.
- Inventory live use of Stripe, PayPal, GoCardless legacy and current APIs, Pusher, S3, Rollbar, Slack/Discord, CCTV, Spark, ACS generations, Swagger, log viewing, and heartbeat endpoints. Record the supported replacement and compatibility evidence for each required capability.
- Export route, schedule, and schema metadata from production without exporting personal data into the repository.
- Verify encrypted data/cookies and determine whether the committed example key was ever used. Plan key rotation rather than changing it blindly.
- Prove database and object-storage backups restore into an isolated environment. Record restore time and rollback commands.
- Establish a maintenance/cutover communication plan for members, finance, and physical access operators.

Exit gate:

- A production inventory exists; every external integration has an owner and disposition.
- A sanitised database snapshot exists outside Git and can be restored automatically.
- Recovery and rollback have been rehearsed.

### Phase 1 — Reproducible legacy baseline

Goal: make the known-good system and its tests repeatable before altering dependencies.

Work:

- Build an isolated legacy container/image using the last runtime known to work. Start with PHP 7.1 because that is the recorded Travis target; pin the exact patch after testing compatibility with the lock file.
- Install required extensions and services explicitly. Do not weaken the host machine or production to accommodate obsolete dependencies.
- Install from the existing Composer and Yarn lock files using compatible historical tooling inside the container.
- Document one-command setup, database reset, asset build, test, scheduler, and local mail/storage fakes.
- Replace the empty 57-byte Codeception dump with deterministic migrations/seed data, or explain and test its intended role.
- Capture the current route list, schedule, rendered critical pages, response status/body contracts, schema, and representative business outcomes as baseline artefacts.
- Run all suites separately and classify failures as environment, flaky, obsolete, or real. Do not call the baseline green by deleting failing tests.
- Add a temporary legacy CI job. Pin its base image by digest and isolate it from public traffic because its dependencies are unsupported.

Exit gate:

- A clean checkout can build and run the legacy application and every retained test with documented commands.
- Baseline failures are zero or explicitly quarantined with an owner, reason, and replacement test.
- Route, schema, schedule, and critical-page snapshots are stored as CI artefacts.

### Phase 2 — Characterisation and contract safety net

Goal: protect the behaviours whose accidental change could affect money, access, or members.

Add tests in this priority order:

1. Payment webhook authentication, duplicate delivery, out-of-order delivery, replay, unknown member/payment, partial payment, refund, failure, retry, and provider timeout.
2. Subscription-charge creation, payment allocation, expiry extension, cancellation, rejoin, suspension, warning, honorary status, and balance calculation at month boundaries and leap days.
3. Access-control decisions for unknown, inactive, banned, uninducted, untrusted, lost-fob, duplicate-fob, offline-node, and valid-member cases.
4. Equipment-session start/stop, overlapping sessions, missing stop events, fee calculation, and command reruns.
5. Signup, login, password reset, email confirmation, profile privacy, role escalation attempts, and cross-account access.
6. Expense approval/decline, storage deposits, induction approval, proposal voting/deadlines, and notification delivery.
7. Scheduled commands run twice without double billing or corrupting state.

Testing rules:

- Use a production-equivalent MySQL/MariaDB service for integration tests; SQLite can remain only for genuinely database-agnostic unit tests.
- Freeze time in date-sensitive tests and use explicit GBP minor-unit fixtures.
- Fake provider HTTP, storage, mail, events, queues, notifications, and broadcasts at adapter boundaries.
- Add contract fixtures for inbound/outbound ACS and payment-provider payloads, with secrets and personal data removed.
- Use factories/builders with named states such as `active`, `suspended`, `financeRole`, `trusted`, and `withMandate`.
- Add a minimal browser suite for signup/login, member self-service, finance payment view, induction, and a representative admin action.
- Measure coverage by critical workflow first. A global percentage is secondary and must not reward generated or trivial code.

Exit gate:

- All critical workflows have happy-path and failure-path coverage.
- Tests demonstrate idempotency for billing, payment webhooks, and equipment/access events.
- The same contract fixtures can be run against legacy and modern implementations.

### Phase 3 — Immediate security containment

Goal: reduce current exposure without waiting for the full framework migration.

Work:

- Re-enable CSRF protection and add explicit, documented exclusions only for authenticated webhook/device routes. Add a test for every exclusion.
- Change logout from GET to POST and update views/tests.
- Validate GoCardless signatures using constant-time comparison or the supported SDK. Reject malformed JSON, missing headers, stale/replayed events, and unsupported event types safely.
- Threat-model all unauthenticated routes in `app/Http/routes.php`, especially `access-control/*`, `acs`, `acs/spark`, camera endpoints, PayPal IPN, GoCardless webhook, password reset, and generated API docs.
- Add rate limiting and structured audit logs without recording tokens, card/provider secrets, emergency contacts, or unnecessary payloads.
- Rotate exposed/shared credentials, remove usable defaults, and add secret scanning. Treat history rewriting as a separate coordinated decision because clones and deployments may rely on the history.
- Hide ACS credentials in views and begin a versioned rotation scheme. Store only a hash for newly issued credentials where device protocol permits it.
- Disable or protect debug/log/API documentation endpoints in production.
- Add standard secure-cookie, HTTPS, HSTS, frame, referrer, content-type, and CSP headers after testing external assets.
- Review file uploads for size, MIME/content validation, decompression bombs, SVG/script content, private storage, random names, and authorised downloads.

Exit gate:

- State-changing browser routes reject missing/invalid CSRF tokens.
- Every public machine endpoint has tested authentication, validation, rate limiting, and replay/idempotency behaviour.
- No active secret is committed or displayed after initial issuance.

### Phase 4 — Dependency triage and adapter extraction

Goal: stop obsolete third-party APIs from leaking through the application before the framework move.

| Current package/capability | Observed use | Planned action |
|---|---|---|
| `illuminate/html` 5.0 | Form/HTML facades across Blade; custom sortable helpers | Replace public/member forms with Inertia Vue components and privileged administration forms/tables with Filament; retain a small tested pagination/sort helper where the member UI needs it |
| `rap2hpoutre/laravel-log-viewer` | One admin route | Replace in the final parity tranche with secure operator log access backed by the selected central log platform; do not expose unrestricted raw logs |
| `nuovo/spreadsheet-reader` | HSBC statement import | Put parsing behind `StatementReader`; replace with a supported CSV/XLSX library after real fixture tests |
| Intervention Image 2 | profile, equipment, expense, CCTV images | Upgrade behind an `ImageProcessor` adapter; preserve orientation, crop, encoding, and size with golden-file tests |
| PayPal Merchant SDK | IPN subscriptions/donations | Replace in the final parity tranche with a supported PayPal Checkout/webhook integration while preserving payment/donation outcomes and readable history |
| `laracasts/presenter` | Seven presenter classes/traits | Replace member-facing presentation with Inertia resources/props and Vue components, administration presentation with Filament resources/tables where applicable, and shared domain formatting with model casts/accessors or dedicated formatters |
| Stripe PHP 1.x / legacy Checkout | token to `Stripe_Charge::create` | Move to a current Stripe SDK and server-created Checkout Session or PaymentIntent with signed webhooks and idempotency |
| `michelf/php-markdown` | policies, proposals, equipment help | Upgrade or replace behind a Markdown renderer; define trusted/untrusted HTML sanitisation policy |
| `jenssegers/rollbar` | provider plus old browser snippet | Replace with the supported Rollbar Laravel integration or the chosen error platform |
| `maknz/slack` | provider; application calls mostly commented | Remove if unused; otherwise use a maintained notification/webhook adapter |
| `sybio/gif-creator` | CCTV GIF generation | Replace behind a supported media adapter in the final parity tranche while preserving CCTV capture outcomes and applying approved privacy/retention controls |
| Flysystem/S3 v1 | member/equipment/expense/CCTV objects | Move through Laravel filesystem APIs to current Flysystem; test visibility, URLs, metadata, and existing keys |
| Pusher PHP 2 / JS 2.2 | private member notifications and realtime activity | Replace through supported Laravel broadcasting/Echo; move IDs/options to environment config and preserve session-authorised realtime behaviour |
| `arthurguy/notifications` | form flash/error API throughout views | Replace with Laravel session flash data, validation errors, and notifications |
| Clockwork | provider in production requirements | Move to `require-dev`, upgrade, and register only locally, or remove |
| Swagger PHP 2 and committed UI | ACS annotations and docs route | Move to OpenAPI 3 with a supported generator/UI, or replace with a checked-in API contract generated in CI |
| GoCardless Pro 1.x | mandates, payments, webhooks | Upgrade via a provider adapter and official current SDK; reconcile API resource/status changes |
| Doctrine DBAL 2.5 | migration support | Remove if Laravel/schema operations no longer require it; otherwise use a compatible current version |
| Whoops 1 | custom exception rendering | Remove custom integration and use Laravel's supported error rendering |
| Faker legacy package | factories | Replace with `fakerphp/faker` or Laravel factory defaults |

For each adapter, record current calls, provider API version, retry/timeouts, idempotency key, webhook verification, logging/redaction, sandbox tests, and a kill switch. Do not update a money or door-access SDK without provider sandbox/contract tests.

Exit gate:

- No controller or domain workflow calls a payment, messaging, image, or device SDK directly.
- Every package has a documented `upgrade`, `replace`, or `remove` disposition.
- Unused packages/providers/configuration are removed with a passing suite.

### Phase 5 — Laravel 13 skeleton and framework transplant

Goal: boot the existing product on a clean, supported framework without changing product behaviour.

Work order:

1. Generate a clean Laravel 13 skeleton using PHP 8.4 and compare every skeleton file; do not copy old `bootstrap`, `config`, or exception-handler files wholesale.
2. Set Composer metadata, `BB\\` or a deliberately chosen `App\\` namespace, platform/extensions, stable dependencies, scripts, and production autoload settings.
3. Port environment/configuration using modern names: `APP_KEY`, `DB_CONNECTION`, `FILESYSTEM_DISK`, `QUEUE_CONNECTION`, mail, cache, session, broadcast, trusted proxies/hosts, and service credentials. Use `env()` only in config files.
4. Port models and schema mappings before controllers. Explicitly preserve nonstandard table names such as `subscription_charge`, `equipment_log`, `user_address`, and `access_log`.
5. Port authentication while preserving existing password hashes and remember-token semantics. Use modern password reset, email verification decisions, session regeneration, and login throttling.
6. Split routes into `routes/web.php`, `routes/api.php`, and versioned machine/provider route groups. Preserve names and paths through parity tests.
7. Replace route/controller string references, old middleware registration, old exception signatures, legacy request/input facades, old event dispatch, mail APIs, helpers, factories, and model date/mutator APIs.
8. Import legacy roles and assignments into Spatie Laravel Permission, then port authorization to policies/gates; leave temporary compatibility shims only where covered and scheduled for removal.
9. Port console commands and schedules. Replace inline HTTP heartbeat callbacks with supported scheduler hooks/monitoring and test timezone/overlap/on-one-server behaviour.
10. Port views and mailables without redesigning pages. Add compatibility tests for escaping and links.
11. Port one business slice at a time in this order: read-only/public pages; authentication/profile; roles/inductions/storage/proposals; equipment; access control; expenses/credit; subscriptions/payments; scheduled billing.

At each slice:

- run legacy characterisation fixtures against both implementations;
- compare route, response, database, emitted-event, mail/notification, and provider-request outcomes;
- record deliberate differences;
- ensure rollback does not require reversing an unsafe data migration.

Exit gate:

- Laravel 13 boots under PHP 8.4 with no legacy framework files silently overriding skeleton defaults.
- Route, command, schedule, config, and schema parity ledgers are complete.
- The complete retained suite passes on the production-equivalent database.
- No unsupported compatibility package is required to boot the application.

### Phase 6 — Test-stack consolidation

Goal: make the safety net fast, understandable, and maintainable.

Work:

- Port PHPUnit 4 tests to Pest 5 closures, datasets, expectations, and current Laravel response/database/event assertions.
- Port valuable Codeception scenarios to Laravel feature/integration tests. Preserve API contract intent; do not mechanically translate generated helper calls.
- Delete Codeception, its generated tester classes, suite YAML, dump, and package only after a parity checklist proves every valuable scenario has moved or been consciously retired.
- Remove PHPSpec and configuration if the production-discovery phase confirms no external specs.
- Replace Karma/Jasmine tests as widgets are migrated. Use a current DOM/unit runner only where JavaScript logic remains; prefer browser tests for cross-layer behaviour.
- Add PHPStan/Larastan with a baseline, then ratchet by directory and forbid new baseline entries.
- Add Laravel Pint and consistent EditorConfig rules. Apply mechanical formatting separately from behaviour changes.
- Add architecture tests for dependency direction and forbidden direct provider SDK/facade usage in the core workflow layer.

Suggested suite split:

- `Unit`: value objects, status transitions, money/date calculations, parsers.
- `Feature`: routes, validation, policies, views/JSON, commands.
- `Integration`: real database constraints/transactions and adapter persistence.
- `Contract`: recorded, sanitised provider/device payloads and outbound requests.
- `Browser`: a small set of revenue/access/member smoke journeys.

Exit gate:

- `composer test` is the single documented PHP entry point.
- There is one source of truth for every retained scenario and no generated test code in Git.
- CI reports suite duration, flaky retries (normally zero), coverage of critical workflows, and static-analysis changes.

### Phase 7 — Frontend and asset pipeline

Goal: replace the unsupported build and browser stack without turning the framework upgrade into a redesign.

Approved boundary: the official Laravel Inertia 3 + Vue 3 + TypeScript starter kit with Tailwind 4 and shadcn-vue components owns public/member journeys. Filament owns privileged administration. The source JavaScript is only about 1,182 lines; rebuild retained member workflows as tested Vue pages/components and retained administrative workflows as policy-protected Filament resources/pages rather than carrying forward React 0.13, Backbone, or Bootstrap 3.

Work:

- Pin Node 24 LTS and choose npm; remove `yarn.lock` only in the same change that introduces the replacement lock file.
- Use the starter kit's Vite, Inertia, Vue, TypeScript, Wayfinder, Tailwind, and component conventions. Build member assets from the single Inertia root layout; keep Filament/Livewire assets and components inside the administration boundary.
- Use Filament resources, pages, actions, and widgets only for privileged administration; share the Fortify user identity and enforce Spatie-backed Laravel policies.
- Port LESS to supported Sass/CSS or plain CSS. Preserve visual behaviour before restyling.
- Inventory and replace the widget set: payment form, notification table/count, expense list/modal/count, filterable payment table, feedback/snackbar, date picker, and Select2 fields.
- Replace legacy Stripe Checkout first because it is a payment-flow migration, not merely UI work.
- Remove React 0.13, Backbone, Babel 5, Browserify, Elixir, Gulp 3, jQuery 2, old Bootstrap, old Select2, Karma, and old browser launchers after their last consumer is gone.
- Stop committing built `public/js/bundle.js`, `public/css/main.css`, and third-party Swagger assets unless the deployment model explicitly requires artefacts; generate them in CI/release builds.
- Add accessibility keyboard/focus/error tests and responsive visual smoke checks for critical pages.
- Add CSP nonces/SRI or self-hosted assets as appropriate; remove obsolete Google JSAPI, Google+, old analytics, and protocol-relative script URLs.

Exit gate:

- `npm ci`, lint/test, and `npm run build` work from a clean checkout on Node 24.
- No unsupported browser package or hand-maintained vendor bundle remains.
- Critical UI journeys pass browser and accessibility smoke checks.

### Phase 8 — Code and domain modernisation

Goal: improve maintainability after compatibility is proven, without destabilising the cutover.

Work in small vertical slices:

- Introduce PHP strict types for new/refactored modules, scalar/return types, constructor property promotion where useful, `final` by default for leaf services, and readonly value objects where appropriate.
- Replace string statuses with backed enums while keeping database values stable. Candidate enums: member status, payment status/source/reason, equipment-log status, proposal status, and notification type.
- Use `Brick\Money\Money` for arithmetic and domain/API boundaries. Construct GBP values from minor units with `Money::ofMinor()`, persist signed integers in explicitly named `_pence` columns, and never use binary floats. Migrate legacy `DOUBLE` columns only in a separately rehearsed and reconciled data change.
- Replace `User::findWithPermission()` and model-level `Auth` access with Spatie-backed policies and explicit current-user inputs.
- Extract application actions such as `RecordPayment`, `ApplyPaymentToSubscription`, `ChangeMemberStatus`, `AuthoriseAccess`, `StartEquipmentSession`, and `ApproveExpense`.
- Put multi-record transitions inside database transactions. Dispatch external side effects after commit and make handlers retry-safe.
- Replace legacy string events with typed events carrying immutable IDs/value data rather than live models when queueing.
- Reduce repository abstractions that merely proxy Eloquent; retain query/service objects where they express a real cohesive concept.
- Replace custom validators/exceptions with form requests, rule objects, domain exceptions, and standard validation responses.
- Add PHPDoc only where types or domain meaning cannot be expressed in PHP. Remove stale annotations and dead/deprecated methods after usage checks.
- Fix naming and historical typos through compatibility aliases/migrations where external contracts are involved (`polices`, `secondry`, `Accese`, singular table names).

Exit gate:

- Critical workflows have a clear application entry point and transaction boundary.
- Provider SDKs and Laravel HTTP/session globals do not leak into domain calculations.
- Static analysis reaches the agreed target with no new baseline debt.

### Phase 9 — Database and data integrity

Goal: make historical data safe on the modern runtime and strengthen integrity without surprise downtime.

Work:

- Compare migrations, `database/dump.sql`, and production schema. Record drift before changing anything.
- Add schema tests for every nonstandard table/column/index relied on by code.
- Build a sanitised production-like fixture that includes old/null/inconsistent states, high IDs, duplicate candidates, zero dates, mixed collations, and every membership/payment state.
- Run fresh install, legacy snapshot upgrade, rollback-safe migration, and backup restore in CI/staging.
- Add missing indexes based on real query plans and production metrics, not guesses.
- Add foreign keys and uniqueness constraints only after orphan/duplicate reports are reviewed and cleanup is reversible.
- Plan a zero-downtime path for expensive changes: additive nullable column, dual read/write if needed, chunked backfill with checkpoints, verify, switch reads, then remove old column later.
- Convert money values to integer minor units only after reconciling every row and provider calculation. Store currency explicitly if anything other than GBP is possible.
- Add immutable provider event/inbox records with unique provider event IDs to guarantee webhook idempotency and support reconciliation.
- Define retention and deletion policies for emergency contacts, member photos, access logs, audit logs, CCTV, payment metadata, and uploaded receipts.

Exit gate:

- Production schema matches a documented, testable migration history.
- Integrity reports are clean or have accepted exceptions.
- All data migrations are restartable, observable, and rehearsed on production-scale data.

### Phase 10 — Delivery, observability, and operations

Goal: make the supported application boring to build, deploy, monitor, and restore.

Work:

- Replace Travis with the repository's chosen maintained CI platform.
- CI matrix: PHP 8.4, production-equivalent database, Node 24, locked dependency installs, tests, static analysis, format check, frontend build, Composer audit, npm audit policy, secret scan, and migration tests.
- Produce immutable build artefacts/images once and promote the same artefact through staging and production.
- Codify deployment order: maintenance/readiness decision, database backup, additive migrations, app release, cache/config/routes/views, worker restart, scheduler verification, smoke tests, and monitoring checkpoint.
- Use real queue workers for slow/retryable mail, notification, image, and provider side effects where appropriate; configure timeout, backoff, attempts, failed-job alerts, and idempotency.
- Add health/readiness endpoints that do not expose secrets. Monitor scheduler freshness, queue age/failures, payment reconciliation, webhook reject rates, ACS/device heartbeat, access-denial anomalies, mail failures, storage errors, and application exceptions.
- Use structured logs with request/correlation IDs and provider event IDs. Redact secrets and personal data centrally.
- Add operational runbooks for failed billing, duplicate/missing webhook, stuck subscription charge, door/device outage, queue backlog, credential rotation, restore, deploy rollback, and application-key rotation.
- Schedule automated dependency update pull requests and a monthly supported-version/security review.

Exit gate:

- A staging deploy and rollback are automated and repeatable.
- Alerts have an owner, tested trigger, and runbook.
- Backup restore, scheduler, workers, and provider sandbox checks pass before production cutover.

### Phase 11 — Rehearsal, cutover, and legacy retirement

Goal: switch safely and prove the old runtime is no longer needed.

Rehearsal:

- Restore a recent sanitised production snapshot into staging.
- Run all migrations and record runtime/locks/table growth.
- Reconcile member counts/statuses, roles, balances, charges, payments, active mandates/subscriptions, key fobs, storage boxes, open equipment sessions, proposals, and object-store references before and after.
- Run provider sandbox webhooks and ACS/device contract tests.
- Run load/smoke tests for login, member list, finance pages, webhook bursts, access checks, scheduled billing, and image retrieval.
- Practise rollback at each point, including the point after migrations and after new webhook endpoints receive events.

Cutover:

1. Announce and begin the agreed change window.
2. Pause schedulers/workers and provider event consumption if required; prevent concurrent billing.
3. Take verified database/object-store backups and record the release/database positions.
4. Deploy the already-tested artefact and run only rehearsed migrations.
5. Start workers/scheduler, update provider webhook destinations if needed, and run automated smoke/reconciliation checks.
6. Monitor elevated error, payment, webhook, access, queue, and database dashboards.
7. Make an explicit go/rollback decision within the pre-agreed window.

Retirement:

- Keep the old artefact/configuration available for the agreed rollback period, but prevent it processing jobs or webhooks concurrently.
- After the stability period, revoke legacy credentials, remove old webhook endpoints/runtime/images, archive required logs, and update runbooks.
- Close the parity ledger and move intentional product/architecture changes into a separate roadmap.

Exit gate:

- Two complete billing/scheduler cycles and the agreed stability period pass with reconciled results.
- No production traffic, worker, cron, or provider webhook targets the legacy runtime.
- Legacy credentials and unsupported images are revoked/removed.

## 5. Proposed implementation epics

These are independently trackable outcomes, not one giant upgrade ticket:

| Epic | Outcome | Depends on |
|---|---|---|
| M01 Production inventory and recovery | Running topology known; restore and rollback proven | None |
| M02 Legacy build | Clean checkout builds/tests in an isolated pinned environment | M01 |
| M03 Critical characterisation | Money, access, membership, devices, and schedules protected | M02 |
| M04 Security containment | CSRF, logout, secrets, public endpoints, keys, uploads hardened | M02–M03 |
| M05 Provider adapters | Payment/device/storage/media/realtime SDKs isolated | M03 |
| M06 Laravel 13 foundation | Clean skeleton, PHP 8.4, config, CI, database connection | M01–M03 |
| M07 Identity and member slice | Auth, accounts, profiles, Spatie roles/permissions, policies, Filament admin boundary | M04, M06 |
| M08 Community operations slice | Inductions, groups, storage, proposals, notifications | M07 |
| M09 Equipment and access slice | ACS, fobs, equipment sessions/fees, device contracts | M05–M08 |
| M10 Finance slice | Expenses, credit, subscriptions, payments, reconciliation | M05–M08 |
| M11 Scheduler and async slice | Commands, queues, retry/idempotency, monitoring | M09–M10 |
| M12 Test consolidation | Pest 5 only; static analysis and browser smoke suite | M07–M11 |
| M13 Frontend/admin UI | Inertia Vue member pages, Filament administration, and supported widget replacements | M06–M10 |
| M14 Data integrity | Schema drift resolved; constraints/indexes/money plan | M03, M10 |
| M15 Delivery and operations | Immutable deploy, alerts, runbooks, restore | M06, M11, M14 |
| M16 Cutover and retirement | Production on modern stack; legacy revoked | All prior |

Each epic should be split into deployable tracer bullets. A ticket is complete only when it includes tests, observability, migration/rollback notes, documentation, and evidence for its exit gate.

## 6. Risk register

| Risk | Impact | Mitigation / evidence required |
|---|---|---|
| Undocumented production-only behaviour/configuration | Critical | Phase 0 inventory; compare real routes/schema/cron/provider settings |
| Incorrect balance or subscription calculation | Critical | Characterisation, integer-money plan, transactions, reconciliation reports |
| Duplicate/out-of-order provider events | Critical | Durable event inbox, unique event IDs, idempotent actions, replay tests |
| Door/equipment access outage | Critical | Contract fixtures, staged device testing, fallback/manual-access runbook |
| Authentication/session/key migration logs members out or corrupts encrypted data | High | Preserve password hashes; explicit APP_KEY/session plan; rehearsal |
| Historical schema differs from migrations | High | Schema diff and snapshot-upgrade CI before framework port |
| Old provider API is no longer testable | High | Confirm provider support early; sandbox/recorded contracts; migration owner |
| Package replacement changes output or storage paths | High | Golden files and URL/object-key compatibility tests |
| SQLite tests mask MySQL behaviour | High | Make production-equivalent database mandatory in CI |
| Framework and UI rewrites interact | High | Keep each Inertia Vue port as a tested vertical slice; preserve workflow and visual intent before redesign |
| Long-lived branch diverges from production fixes | High | Small PRs, forward-port policy, frequent integration, parity ledger |
| Data cleanup/constraints lock large tables | High | Query production size/plans; additive/chunked migrations; rehearse duration |
| Unsupported legacy toolchain is exposed | Medium | Container isolation, no public traffic, shortest practical lifetime |
| Team cannot explain old business rules | Medium | Business owners approve examples and reconciliation outcomes |

## 7. Definition of done

The modernisation is not complete merely because the homepage renders on Laravel 13. All of the following must be true:

- Laravel 13/PHP 8.4/Node 24 are pinned, supported, and reproducible.
- `composer install`, `composer test`, static analysis, formatting, `npm ci`, and `npm run build` pass from a clean checkout and in CI.
- No Laravel 5 runtime, obsolete build pipeline, Codeception-generated code, or abandoned package is required in production.
- Critical financial, membership, access, device, and scheduler behaviours have automated happy/failure/idempotency tests.
- CSRF is enabled, logout is POST, public machine routes have explicit authentication/replay/rate-limit controls, and no active secrets are committed/displayed.
- Payment integrations use supported provider flows and pass sandbox plus reconciliation checks.
- Production schema upgrades from a real sanitised snapshot and fresh databases migrate successfully.
- Multi-record financial/access transitions use explicit transactions and safe after-commit side effects.
- Route, schedule, provider, schema, and data parity ledgers are complete with every difference approved.
- Staging deployment, production cutover, rollback, backup restore, worker restart, and scheduler verification are documented and rehearsed.
- Monitoring and runbooks cover payments, queues, scheduler, devices/access, storage, mail, and application errors.
- The agreed stability period and at least two billing cycles complete with reconciled results.
- Legacy web processes, workers, schedules, provider webhooks, credentials, and images are retired.

## 8. Decisions required before implementation

These questions should be answered during Phase 0; none should be guessed from this repository alone:

1. What exact commit, PHP version, database version, and infrastructure are in production?
2. Which production configurations and clients exercise both GoCardless generations, PayPal, Stripe, Pusher, Discord, CCTV/GIF, Spark, Swagger, log viewing, Rollbar, and Clockwork? Their parity disposition is required; this evidence defines contracts, migration data, and supported replacements.
3. Is `database/dump.sql` synthetic, and has any real member or credential data ever been committed?
4. Was the example/CI encryption key ever used for production or persistent staging data?
5. Which API/device clients cannot be upgraded simultaneously, and what version-negotiation window is required?
6. Is brief maintenance acceptable for cutover, or is dual-running/routing required?
7. What are the retention and privacy requirements for member, payment, access, emergency-contact, expense, and CCTV data?
8. Which legacy visual details may change while member workflows are rebuilt in Inertia Vue and administrative workflows are rebuilt in Filament?
9. Which CI/deployment/monitoring platforms are organisational standards?
10. Who signs off reconciled money, membership entitlement, and physical-access results?

## 9. Reference material

Current target decisions should be rechecked when implementation begins:

- [Laravel 13 release notes and support policy](https://laravel.com/docs/13.x/releases)
- [Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade)
- [Laravel 13 deployment guidance](https://laravel.com/docs/13.x/deployment)
- [Laravel 13 request-forgery protection](https://laravel.com/docs/13.x/csrf)
- [Laravel 13 Vite integration](https://laravel.com/docs/13.x/vite)
- [PHP supported versions](https://www.php.net/supported-versions.php)
- [Node.js release schedule](https://nodejs.org/en/about/previous-releases)
- [Pest installation and upgrade guidance](https://pestphp.com/docs/installation)
- [Laravel Vue starter kit](https://github.com/laravel/vue-starter-kit)
- [Brick/Money](https://github.com/brick/money)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v8/introduction)
- [Filament 5](https://filamentphp.com/docs/5.x/getting-started)
- [Stripe legacy Checkout migration guide](https://docs.stripe.com/payments/checkout/migration)

When executing an in-place framework step or porting a legacy behaviour, consult every intervening official Laravel upgrade guide and compare the corresponding `laravel/laravel` skeleton. The short Laravel 12-to-13 estimate does not apply to this Laravel 5.1 application.
