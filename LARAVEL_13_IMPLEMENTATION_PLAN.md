# Laravel 13 Clean-Skeleton Implementation Plan

Status: implementation started; clean foundation installed
Decision date: 17 August 2026
Parent plan: [MODERNISATION_MASTERPLAN.md](MODERNISATION_MASTERPLAN.md)
Starting point: Phase 5, a clean Laravel 13 skeleton followed by a controlled port of required behaviour

Canonical repository: [charlielangridge/bbmembershipsystem](https://github.com/charlielangridge/bbmembershipsystem)

### Foundation checkpoint — 17 August 2026

Completed on branch `modernisation/laravel-13`:

- replaced the legacy working tree with the official Laravel 13 Inertia Vue starter-kit structure while retaining the legacy commit in Git history;
- installed and locked Inertia Laravel 3, Vue 3, TypeScript, Tailwind 4, Fortify, Wayfinder, Pest 5, Brick/Money, Larastan, Pint, and Laravel Boost;
- finalised the official Fortify authentication implementation with registration, password reset, email verification, password confirmation, 2FA, and passkeys, removing the one-time Chisel installer;
- converted all starter-kit PHP tests to Pest syntax;
- installed Boost guidelines, repository skills, and MCP configuration;
- added a GitHub Actions quality gate for dependency audits, formatting, static analysis, frontend checks/build, and Pest;
- verified Pest, Larastan, Pint, Vue type checking, ESLint, Prettier, and the production Vite build.

This is the agreed pause point. No legacy business logic, schema, integrations, or production data have been ported yet.

## 1. Decision and intent

The client has chosen rapid new development on a clean Laravel 13 application over modifying the fragile hosted Laravel 5.1 system.

The existing system is now treated as:

- the source of business rules that must be understood;
- a behavioural reference for screens, routes, reports, commands, and device responses;
- the current system of record until the cutover;
- a source of production-safe schema and integration facts;
- read-only except for emergency production fixes handled outside this programme.

It is not the base onto which Laravel, PHP, packages, tests, or frontend tooling will be upgraded. No modernisation commit should be deployed to the old application. The new system will be built independently, tested with sanitised copies of legacy data, rehearsed in staging, and switched into service only when its release gates pass.

This plan supersedes the assumption in the parent masterplan that Phases 1–4 would modify or stabilise the legacy code before Phase 5. The useful discovery, contract, security, and integration work from those phases is retained, but it will be implemented in the new application or performed as read-only investigation.

## 2. Outcomes and non-goals

### 2.1 Required outcomes

- A clean Laravel 13 application at the repository root, running PHP 8.4.
- Existing production member and operational data remains usable without lossy transformation.
- Required member, administrator, finance, access-control, equipment, payment, and scheduled workflows are available in the new application.
- Live URLs and machine contracts remain compatible where clients cannot be upgraded at cutover; incompatible contracts are versioned and have an explicit migration window.
- Every financial, access, membership, and scheduled workflow has automated acceptance coverage.
- Provider integrations use supported APIs/SDKs, signed webhooks, idempotency, and reconciliation.
- A repeatable staging deployment, production cutover, and rollback exist before production is changed.
- The legacy application remains deployable from its frozen tag/branch during the agreed rollback window, but cannot process jobs or webhooks concurrently after cutover.

### 2.2 Non-goals for the first production release

- A wholesale visual redesign or new brand.
- Changing membership policy, pricing, member statuses, voting rules, or access policy unless the client approves a separately documented change.
- Renaming historical database tables/columns merely to match Laravel conventions.
- Replacing working infrastructure without a support, security, or delivery need.
- Adding features that do not exist in the agreed release scope.
- Running the old and new applications as concurrent writers to the same business tables.
- Perfectly reproducing unused, obsolete, or explicitly retired features.

### 2.3 Delivery principle

Build a new product implementation, not a line-by-line port. Preserve business outcomes and external contracts, but use current Laravel structures and supported provider APIs. Copying a legacy class is allowed only after its behaviour is described by tests and its dependencies have been replaced with new interfaces.

## 3. Safety boundary around the hosted system

The modernisation team may use the hosted system only for approved read-only investigation:

- view pages using dedicated non-privileged test accounts;
- export route/configuration metadata that contains no secrets;
- run read-only, reviewed database queries against a replica or exported snapshot;
- inspect scheduler, worker, provider, storage, and deployment configuration with credentials redacted;
- compare visible outcomes using synthetic records where production policy permits.

The team must not:

- run Composer/npm upgrades on the host;
- change its framework configuration, middleware, routes, database schema, or dependencies;
- point development or staging webhooks at production provider accounts;
- run scheduled commands or billing commands manually in production;
- use production personal data in local development or CI;
- share the production database as a writable database between old and new applications.

Emergency legacy fixes remain possible, but they follow the existing production process and must be logged in the parity ledger so the new implementation receives the equivalent correction.

## 4. Repository and branch model

### 4.1 Recommended Git topology

1. Tag the approved legacy commit as `legacy-production-baseline-YYYY-MM-DD` after confirming which commit production actually runs.
2. Preserve the existing production branch for emergency legacy releases.
3. Create `modernisation/laravel-13` from the confirmed baseline so history remains connected.
4. On that branch, replace the application at the repository root with a fresh Laravel 13 skeleton. Do not commit the new app under a permanent `modern/` subdirectory.
5. Keep a second local Git worktree checked out at the legacy tag when developers need side-by-side source comparison.
6. Protect the production and modernisation branches; require CI and review on both.
7. Merge or rename the modernisation branch to the normal production branch only after cutover acceptance. Keep the legacy tag permanently.

Why this topology:

- Laravel, Composer, Artisan, Vite, deployment tools, and IDEs work from a conventional repository root.
- New skeleton files cannot accidentally inherit legacy bootstrap/configuration.
- Legacy source stays available through Git without being packaged or deployed with the new application.
- Production remains untouched until an explicit deployment points to the new branch/release.

### 4.2 Foundation change sequence

Keep later concerns in reviewable commits. The initial clean bootstrap was committed atomically as `0da3671` after the stack decisions were approved; it contains no copied legacy business logic and is the audit reference for the new application.

1. Record the approved architecture decision and legacy baseline metadata.
2. Remove legacy runtime sources from the modernisation branch while retaining planning documents and intentional domain fixtures.
3. Add an unmodified Laravel 13 skeleton generated by the official installer.
4. Set PHP 8.4, Composer, Node 24, npm, extension, and database constraints.
5. Add environment templates and local container/development setup.
6. Add CI with the empty skeleton's tests, Pint, Larastan baseline policy, audits, and Vite build.
7. Record Laravel conventions and add the first behaviour through normal framework locations.
8. Add the legacy parity ledgers and implementation backlog.

Do not combine foundation work with copied legacy models/controllers. Commit `0da3671` is the clean-foundation audit reference for every later change.

### 4.3 Repository layout

Keep Laravel's generated structure intact. Do not pre-create a domain-layer hierarchy or move framework concepts away from their conventional locations. Add directories only when a real implementation needs them, preferably through the relevant Artisan generator.

```text
app/
  Console/Commands/
  Enums/
  Events/
  Http/
    Controllers/
    Middleware/
    Requests/
  Jobs/
  Listeners/
  Mail/
  Models/
  Notifications/
  Policies/
  Providers/
  Rules/
  Services/
config/
database/
  factories/
  migrations/
  schema/
  seeders/
docs/
  adr/
  parity/
  runbooks/
resources/
  css/
  js/
  views/
routes/
tests/
  Feature/
  Unit/
```

Use standard Laravel concepts first: Eloquent models, form requests, policies, controllers, jobs, events/listeners, notifications, commands, casts, and validation rules. Put cohesive business workflows or external-provider adapters under `app/Services` only when no first-party framework location describes their responsibility. Organise `tests/Feature` and `tests/Unit` into feature subdirectories when volume justifies it; contract and integration tests remain feature tests unless they genuinely require a distinct runner or configuration.

Laravel Boost will be installed immediately after the skeleton and its generated guidance will be consulted before adding application structure or business logic.

## 5. Target technical baseline

| Concern | Initial target | Rule |
|---|---|---|
| Framework | Laravel `^13.0` | Track current patch releases |
| PHP | `~8.4.0` | PHP 8.5 is a later, separate change |
| Authentication | Laravel Fortify | Fortify owns login, registration, password reset, email verification, password confirmation, 2FA, and passkeys; do not port legacy auth controllers |
| Tests | Pest `^5.0` | Pest is the only authored PHP test style; PHPUnit remains an implementation dependency only |
| Database | Production-compatible supported MySQL/MariaDB | Exact engine/version decided from production inventory |
| Frontend build | Vite + Laravel Vite plugin | No Gulp, Elixir, Browserify, or committed bundles |
| Node | 24 LTS | Pin locally and in CI; use npm |
| Server UI | Inertia `^3.0` + Vue `^3.5` + TypeScript | Use Laravel routes/controllers with pages under `resources/js/pages` |
| Browser behaviour | Vue Composition API | Reuse starter-kit components and Wayfinder-generated route functions |
| CSS | Tailwind CSS `^4.1` + shadcn-vue components | Preserve workflows before visual redesign |
| Static analysis | Larastan/PHPStan | Start with no new baseline debt; ratchet upward |
| Formatting | Laravel Pint | Formatting-only commits where broad |
| Queues | Database/Redis as selected operationally | All queued handlers idempotent |
| Storage | Laravel filesystem with S3/local disks | Existing object keys remain readable |
| Time | UTC persistence; explicit display timezone | Freeze time in tests |
| Money | Brick/Money with GBP integer pence persistence | Never persist or calculate with binary floats; name new columns with a `_pence` suffix |

## 6. Architecture rules for the new build

### 6.1 HTTP and authorisation

- Controllers translate HTTP requests into application calls and responses; they do not calculate balances or membership eligibility.
- Form Request objects own input validation and normalisation.
- Policies and gates own user-facing authorisation. Device/provider endpoints use explicit authentication middleware.
- Browser routes, machine/device routes, and provider webhooks live in separate route files/groups with distinct middleware.
- Browser state changes use POST/PUT/PATCH/DELETE and request-forgery protection. Logout is POST.
- Route names and externally consumed paths are captured in `docs/parity/routes.md`.

### 6.2 Application and domain logic

- Each business operation has one named entry point, such as `RegisterMember`, `ApproveInduction`, `AuthoriseDoorAccess`, `RecordProviderPayment`, or `CalculateEquipmentCharge`.
- Business state changes are expressed with enums and value objects while their persisted legacy strings remain stable for the first release.
- Multi-record changes run inside a database transaction.
- External messages are queued/dispatched after commit.
- Handlers accept identifiers and immutable data rather than relying on serialised live Eloquent models.
- Retries, duplicate webhooks, and duplicate scheduled runs must produce the same correct result.

### 6.3 Persistence

- Eloquent models map explicitly to legacy table names, primary keys, timestamps, casts, and relationships.
- Models may contain relationship/cast/query behaviour; cross-aggregate workflows belong in application actions.
- Query objects are used for complex reports and lists. Generic repositories that merely mirror Eloquent are not recreated.
- No schema “cleanup” is bundled into the first working slice.
- Raw SQL must be parameterised, explained, and covered using the production database engine.

### 6.4 External providers

Every provider is behind a small application-owned interface. For example:

```php
interface PaymentGateway
{
    public function beginPayment(BeginPaymentData $payment): PaymentRedirect;
    public function parseWebhook(VerifiedWebhookRequest $request): ProviderEvent;
}
```

Interfaces describe BBMS needs, not the complete provider SDK. Adapters own authentication, timeouts, retries, API versions, idempotency headers, payload translation, and redacted logging.

### 6.5 Observability

- Every request receives a correlation ID.
- Provider events retain provider event IDs and processing status without storing unnecessary secret/personal payloads.
- Financial/access decisions emit structured audit events with actor, subject, decision, reason, time, and correlation ID.
- Metrics distinguish rejected authentication, validation failures, duplicate events, provider failures, and application defects.

## 7. Source-of-truth and parity artefacts

Create these before broad feature implementation:

| Artefact | Contents | Owner/sign-off |
|---|---|---|
| `docs/parity/features.md` | Every current feature marked required, changed, or retired | Client/product owner |
| `docs/parity/routes.md` | Method, path, route name, audience, auth, response, replacement | Technical owner |
| `docs/parity/schema.md` | Tables, keys, columns, types, nullability, indexes, unusual values | Data owner |
| `docs/parity/statuses.md` | Member/payment/equipment/proposal states and legal transitions | Product + finance/access owners |
| `docs/parity/commands.md` | Command, schedule, timezone, overlaps, inputs, outputs, idempotency | Operations owner |
| `docs/parity/integrations.md` | Provider/API version, credentials, endpoints, payloads, disposition | Integration owner |
| `docs/parity/screens.md` | Required page content/actions/permissions; screenshots with synthetic data | Product owner |
| `docs/parity/reconciliation.md` | Counts and totals used before/after migration and cutover | Finance/data owners |

Evidence may come from legacy source, sanitised data, provider dashboards, deployment configuration, or client interviews. Every fact records its source and date. Unresolved contradictions become decisions, not silent guesses.

## 8. Implementation sequence

### Milestone 0 — Scope and production facts

Goal: decide what the new release must contain without changing production.

Tasks:

- Confirm the commit and infrastructure currently deployed.
- Complete the feature/integration disposition: `required`, `changed`, or `retired`.
- Identify all device clients and versions that call access/ACS endpoints.
- Identify active payment methods and provider API/webhook versions.
- Obtain a structure-only schema export and an encrypted, sanitised production-like data snapshot through the approved data process.
- Define cutover downtime tolerance and rollback period.
- Assign business sign-off for membership, finance, and physical access.
- Approve the first-release UI approach.

Acceptance:

- The parity artefacts have named owners.
- No P0/P1 feature or integration has an unknown disposition.
- Snapshot handling and data access meet client privacy requirements.
- Cutover and rollback constraints are recorded in an ADR.

### Milestone 1 — Clean foundation

Goal: a deployable empty Laravel 13 application with all engineering controls working.

Tasks:

- Apply the branch/commit sequence in section 4.
- Configure PHP extensions, MySQL/MariaDB, mail catcher, local S3-compatible storage or fakes, cache/session, and queue worker.
- Add `.env.example` with names only and safe non-secret defaults.
- Add `/up`/readiness behaviour suitable for the target platform without leaking configuration.
- Add CI jobs for Composer install/audit, Pest, Pint, Larastan, npm install/audit policy, frontend type/lint checks, and Vite production build.
- Add protected logging defaults, correlation IDs, and exception reporting adapter.
- Add ADRs for branch topology, database strategy, frontend approach, queue backend, and error monitoring.
- Demonstrate staging deployment of the empty skeleton and rollback to its prior release.

Acceptance:

- A clean checkout reaches a green build with documented commands.
- CI and staging use PHP 8.4, Node 24, and the selected database engine.
- No secret or production identifier exists in Git.
- The empty release deploys and rolls back automatically.

### Milestone 2 — Legacy database compatibility layer

Goal: the new application can read a restored legacy snapshot safely and fresh test databases can reproduce its structure.

Tasks:

- Compare production schema, tracked migrations, and `database/dump.sql`; document drift.
- Create a structure-only schema baseline under `database/schema/` for new development/test databases. Do not copy production rows.
- Retain the legacy migration history as documentation, not executable Laravel 13 migrations.
- Create only new, forward migrations after the agreed baseline timestamp.
- Map all 20 legacy entities to explicit Laravel 13 models, beginning with users, roles, profiles, addresses, payments, subscription charges, key fobs, equipment, and equipment logs.
- Add casts for dates, booleans, JSON/serialised fields, and values whose legacy representation is surprising.
- Build factories with named legacy states.
- Add read-only smoke tests against the sanitised snapshot.
- Add reconciliation queries for row counts, orphan relationships, duplicate provider IDs, invalid statuses, null/zero dates, and money totals.
- Prevent the new application from running writes against any database identified as production until the cutover deployment is explicitly enabled.

Acceptance:

- Fresh CI databases and restored snapshots both boot and pass schema/model tests.
- No framework boot or model read mutates legacy data.
- Every table used by a P0/P1 feature has an explicit model/schema mapping.
- Baseline reconciliation results are recorded and reviewed.

### Milestone 3 — Identity, roles, and member read model

Goal: establish authentication and the permissions foundation used by every later slice.

Tasks:

- Use Fortify as the authoritative authentication backend; do not port the legacy Laravel 5 authentication controllers or traits.
- Map legacy users into Fortify-compatible accounts while preserving existing password hashes; prove representative hashes authenticate without resets.
- Map the legacy verification state into `email_verified_at`, and leave 2FA/passkeys unenrolled until each member opts in.
- Verify secure logout, session regeneration, login throttling, registration, password reset, email verification, password confirmation, 2FA, and passkey flows.
- Map roles and role membership; implement policies for self, member, communications, finance, and admin access.
- Implement current-user account view and a read-only admin member view.
- Implement member directory privacy rules and profile-photo authorisation.
- Implement member status enums/transitions as read behaviour first.
- Add audit events for login, password reset, permission denial, and privileged member access without logging credentials or emergency-contact content.
- Add feature/browser tests for login, reset, role boundaries, account isolation, inactive/banned users, and session expiry.

Acceptance:

- Existing accounts can authenticate from the snapshot.
- A member cannot read or change another member's protected data.
- Finance/comms/admin permissions match the approved role matrix.
- Security tests cover CSRF, session fixation, brute-force throttling, and open redirects.

### Milestone 4 — Member lifecycle and self-service

Goal: replace the core member-facing experience independently of payments.

Tasks:

- Implement signup, member details, address, emergency contact, profile, privacy, and photo upload/approval.
- Implement membership statuses and approved transitions: setting-up, active, payment-warning, suspended, leaving, on-hold, left, honorary.
- Implement rules agreement, induction workflow, member rejoin/cancel request, group/role display, and notifications required for these flows.
- Render current policies through a safe Markdown pipeline.
- Preserve existing S3 object keys or provide a tested fallback resolver for legacy photos.
- Replace the custom notification facade with Laravel validation errors, flash messages, mail, and notifications.
- Add accessibility checks for forms, errors, focus, keyboard operation, and responsive layouts.

Acceptance:

- A synthetic new member can register and reach the expected pre-payment state.
- An existing member can view/edit only permitted data and images.
- Admin/comms induction and photo actions match the approved workflow.
- State-transition tests reject illegal transitions and record the actor/reason.

### Milestone 5 — Access control, fobs, and device contracts

Goal: make physical access independently testable before any device is pointed at the new system.

Tasks:

- Document the decision table for door access, device access, trusted/key-holder status, induction, suspension, ban, lost fob, and unknown identifiers.
- Implement `AuthoriseAccess` as a pure decision service plus a transactional activity/audit recorder.
- Implement key-fob administration with uniqueness, lost/revoked state, rotation, and actor audit.
- Implement versioned ACS/device authentication. Prefer hashed credentials, scopes, expiration/rotation, one-time display, rate limits, and replay protection.
- Reproduce required legacy response shapes for clients that cannot upgrade at cutover.
- Implement node boot, heartbeat, status, activity start/update/stop, and queued-command behaviour that is confirmed as live.
- Create a device simulator from sanitised request/response fixtures.
- Run hardware staging tests on representative door/equipment clients without contacting production.
- Create manual-access and new-system-outage runbooks.

Acceptance:

- The approved access decision matrix passes as unit and contract tests.
- Duplicate/replayed device messages do not duplicate sessions or charges.
- Representative hardware passes a staging soak test.
- No device secret is stored or displayed in recoverable plaintext unless an approved hardware constraint is documented.

### Milestone 6 — Equipment, storage, proposals, and expenses

Goal: port operational modules that depend on identity but not the final subscription integration.

Tasks:

- Equipment: CRUD, photos, PPE/help text, inductions, session logs, missing-stop repair, and fee calculation.
- Storage boxes: assignment, availability, deposit/reference behaviour, and permissions.
- Proposals: creation/edit, member visibility, vote uniqueness, deadline, result calculation, and scheduled processing.
- Expenses: submission, private receipt storage, finance review, approve/decline, notifications, and balance-credit request.
- Replace spreadsheet statement import behind a tested parser adapter if it remains required.
- Add explicit transactions and after-commit events for approval/credit operations.
- Add command tests proving repeated scheduled execution does not duplicate results.

Acceptance:

- Each module's route/policy ledger is complete.
- Equipment fees, votes, expense credits, and deposits reconcile against agreed fixtures.
- Uploaded documents/images are private by default and access-controlled.
- Scheduled calculations are idempotent and observable.

### Milestone 7 — Finance ledger and internal money rules

Goal: establish trustworthy internal financial behaviour before connecting live providers.

Tasks:

- Document the meaning and units of every legacy money column. Resolve whether each integer stores pounds or pence and how `DOUBLE` rows were rounded.
- Use `Brick\Money\Money` at application boundaries, constructing GBP values with `Money::ofMinor($pence, 'GBP')`.
- Persist monetary values only as signed integer pence in explicitly named columns such as `amount_pence`; never cast through `float` or SQL floating-point types.
- Parse user/provider decimal strings with an explicit rounding policy at the adapter boundary, then pass `Money` or integer pence internally.
- Implement payment, subscription charge, member balance, withdrawal, refund-to-balance, cash payment, and expense credit workflows.
- Implement database transactions, row locking where concurrency requires it, and typed events dispatched after commit.
- Add unique/idempotency constraints for internal operations after duplicate reports are resolved.
- Implement finance filters, totals, member statements, and reconciliation reports.
- Implement scheduled charge creation, billing selection, membership expiry/warning/suspension/leaving checks, and balance recalculation.
- Run a shadow calculation against a snapshot: the new code may calculate but must not submit provider payments or persist to production.
- Have the finance owner review member-level and aggregate differences.

Acceptance:

- Golden scenarios cover full, partial, duplicate, failed, retried, refunded, cash, balance, and unassigned payments.
- Every multi-record operation is atomic and retry-safe.
- Repeated commands create no duplicate charge/payment/entitlement.
- Snapshot reconciliation meets the signed-off tolerance, ideally exact for counts and minor-unit totals.

### Milestone 8 — Payment providers

Goal: connect supported payment flows without inheriting unsupported SDK behaviour.

Treat each provider as a separate release unit.

Common tasks:

- Confirm whether the provider/method remains in scope and select its supported API flow.
- Implement a provider adapter, sandbox credentials, explicit API version, timeouts, retries, idempotency, and redacted logs.
- Add a durable webhook inbox keyed by provider event ID.
- Verify signatures before parsing/processing; record reject reason without leaking payload secrets.
- Process events transactionally and mark inbox state as received/processed/failed/ignored.
- Handle duplicate, replayed, delayed, out-of-order, unknown, malformed, and unsupported events.
- Add reconciliation commands/reports comparing provider records with local payments/charges.
- Add provider kill switches that stop outbound billing while continuing safe webhook capture.

Provider-specific direction:

- GoCardless: move all active mandate/payment/subscription behaviour to the current supported SDK/API and webhook model. Map legacy mandate/subscription IDs without changing them blindly.
- Stripe: replace legacy Checkout.js token/Charges flow with server-created Checkout Sessions or PaymentIntents and signed webhook completion. Do not mark a payment paid solely because the browser returned.
- PayPal: if still required, replace Merchant SDK/IPN with the supported Checkout/webhook flow. If only historical, keep records readable and retire new PayPal payments.

Acceptance:

- Sandbox end-to-end happy/failure/refund flows pass for each retained provider.
- Provider webhook conformance and replay suites pass.
- Browser redirects are not the source of truth for completed payment.
- Finance signs off provider-to-local reconciliation.

### Milestone 9 — Frontend completion and operational integrations

Goal: finish the supported browser experience and required non-payment integrations.

Tasks:

- Complete the Inertia 3/Vue 3/TypeScript application using reusable Vue and shadcn-vue components; keep Blade to the single Inertia root view unless a server-rendered exception is approved.
- Replace legacy React/Backbone widgets for notifications, expenses, payment filtering/forms, feedback, date selection, and snackbars.
- Upgrade/replace image processing, S3 access, broadcasting/realtime, mail, error reporting, and Markdown rendering.
- Retire unused Slack, CCTV GIF, Pusher realtime, Swagger UI, analytics, and debug tooling only where the feature ledger approves retirement.
- If API documentation remains required, publish an OpenAPI 3 contract generated/validated in CI.
- Add CSP and other browser security headers after external origins are final.
- Add browser smoke tests and accessibility checks for all P0 journeys.

Acceptance:

- No Gulp, Elixir, Browserify, React 0.13, Backbone, jQuery 2, Bootstrap 3, or committed third-party bundle is needed.
- A clean `npm ci && npm run build` produces deployable assets.
- Required mail, storage, image, realtime, and monitoring integrations pass staging tests.
- P0 screens meet agreed functional and accessibility acceptance.

### Milestone 10 — Full-system staging and migration rehearsal

Goal: prove the complete release on production-shaped data and infrastructure.

Tasks:

- Restore a recent sanitised snapshot and the corresponding object-store test copy.
- Run all forward migrations and record duration, locks, and row changes.
- Execute reconciliation before and after: members by status, roles, active fobs, equipment sessions, charges by status/month, payments by source/status/reason, balances, expenses, storage, proposals, and provider identifiers.
- Run every scheduled command twice and inspect side effects.
- Run device simulators and representative hardware.
- Run provider sandboxes and webhook bursts.
- Run load tests for login, member directory, finance lists, webhook bursts, access checks, and billing selection.
- Perform security review focused on authorisation, request forgery, uploads, secrets, provider/device authentication, logging, and personal-data exposure.
- Perform backup restore and application rollback drills.
- Complete operations runbooks and train operators.

Acceptance:

- All P0/P1 parity items are accepted or have a signed deliberate difference.
- Migration duration and downtime fit the agreed window.
- Reconciliation is signed off by technical, finance, membership, and access owners.
- Rollback succeeds within the agreed recovery objective.
- No unresolved critical/high security defect remains.

### Milestone 11 — Production cutover

Goal: make the new application the only writer and event processor.

Preconditions:

- The exact release artefact has passed Milestone 10.
- Database and object-store backups have been restored successfully in rehearsal.
- Provider webhook changes, DNS/release switch, workers, scheduler, and rollback commands are pre-approved.
- A manual physical-access fallback is available.
- No unrelated release is scheduled during the cutover window.

Runbook:

1. Announce the maintenance window and open the cutover record.
2. Put the legacy application into maintenance/read-only mode.
3. Stop legacy scheduler and workers; verify no billing/access jobs are running.
4. Pause outbound provider actions where supported while allowing safe event capture according to the rehearsed design.
5. Take final database/object-store backups and record checksums/release positions.
6. Run final pre-migration reconciliation and record totals.
7. Deploy the new artefact and run the rehearsed forward migrations.
8. Configure production secrets; never copy a legacy `.env` wholesale.
9. Start new workers and scheduler with outbound billing still disabled.
10. Switch HTTP traffic and provider webhook destinations.
11. Run automated smoke tests for login, member access, admin/finance read, device access, storage/image read, queue, and webhook receipt.
12. Run post-migration reconciliation and compare to the pre-migration record.
13. Enable outbound payments/scheduled operations only after explicit finance approval.
14. Monitor the heightened dashboard and make the formal go/rollback decision within the agreed window.

Success conditions:

- The new application is the only business-data writer.
- No legacy worker, schedule, or webhook is active.
- Reconciliation and P0 smoke checks pass.
- Access-control and payment-provider health are normal.

### Milestone 12 — Hypercare and retirement

Goal: stabilise the new service and remove the unsupported runtime safely.

Tasks:

- Run heightened monitoring and daily reconciliation for the agreed hypercare period.
- Review every failed provider event, queue job, access anomaly, scheduler miss, and balance difference.
- Keep the frozen legacy release available for rollback but unable to process traffic, jobs, or events concurrently.
- Complete at least two billing cycles before declaring financial migration complete.
- Revoke legacy provider/device/deployment credentials and remove old webhook URLs.
- Remove the unsupported runtime/images/cron/workers after the rollback period.
- Archive approved logs and configuration evidence under retention policy.
- Close the parity ledger and move deferred redesign/product work to a new roadmap.

Acceptance:

- Two billing cycles reconcile.
- No production dependency targets the old release.
- Legacy secrets and infrastructure are revoked/retired.
- Client signs off the modernisation programme.

## 9. Feature priority and release slices

The product owner must classify features before implementation. Proposed defaults:

| Priority | Definition | Candidate scope |
|---|---|---|
| P0 | Required for safe cutover | Login/reset/logout; roles/policies; account/profile/privacy; member status; payment/charges/balance; retained provider webhooks; access/fobs; scheduler/workers; reconciliation |
| P1 | Required shortly after or included if schedule permits | Signup; inductions; equipment/session fees; expenses; storage boxes; member directory; notifications; finance UI |
| P2 | Can follow first release | Proposals, statement import, realtime activity, expanded stats, API docs |
| Retire/replace | No parity required | Unused Slack paths, old Stripe Checkout, unsupported PayPal IPN, obsolete analytics/Google+, old log viewer/debug UI, CCTV GIF if unused |

Do not infer that a feature is unused because calls are commented out. Retirement requires client and operational confirmation.

## 10. Initial ticket backlog

Ticket size guide: S is usually 1–2 focused engineering days, M is 3–5, L is 6–10 and should be split if acceptance can be preserved. Sizes are planning aids, not commitments.

### Foundation and discovery

| ID | Ticket | Size | Done when |
|---|---|---:|---|
| NEW-001 | Confirm/tag deployed legacy baseline and create modernisation branch/worktree instructions | S | Commit and deployment source are documented; tag exists |
| NEW-002 | Complete required/changed/retired feature ledger | M | Client owner signs off every current module |
| NEW-003 | Complete route, command, integration, and status ledgers | L | No P0/P1 entry lacks owner or disposition |
| NEW-004 | Generate clean Laravel 13 root skeleton | S | Unmodified skeleton commit boots on PHP 8.4 |
| NEW-005 | Add reproducible local environment | M | New developer reaches green build from clean checkout |
| NEW-006 | Add CI quality pipeline | M | Tests, Pint, Larastan, audits, Vite run on every PR |
| NEW-007 | Automate empty staging deploy and rollback | M | Deployment and rollback evidence recorded |

### Data foundation

| ID | Ticket | Size | Done when |
|---|---|---:|---|
| DATA-001 | Acquire and protect structure/sanitised snapshot | M | Privacy owner approves handling process |
| DATA-002 | Produce schema drift report | M | Production, migrations, and dump differences documented |
| DATA-003 | Create structure-only schema baseline | M | Fresh CI database matches required legacy structure |
| DATA-004 | Port core models and casts | L | Core tables read correctly from snapshot |
| DATA-005 | Build reconciliation command/report framework | M | Before/after reports are deterministic and exportable |
| DATA-006 | Build integrity anomaly reports | M | Orphans, duplicates, invalid states, dates, money issues visible |

### Identity and membership

| ID | Ticket | Size | Done when |
|---|---|---:|---|
| ID-001 | Authenticate legacy password hashes | M | Representative snapshot accounts pass tests |
| ID-002 | Implement secure session/logout/reset/throttling | M | Security feature/browser suite passes |
| ID-003 | Implement role matrix with policies | L | Approved permission matrix passes |
| MEM-001 | Implement member/account read models | M | Self/admin/member privacy scenarios pass |
| MEM-002 | Implement profile/address/privacy editing | L | Validation, audit, upload, auth tests pass |
| MEM-003 | Implement member state machine | L | All legal/illegal transitions are covered |
| MEM-004 | Implement signup and induction | L | P1 acceptance journey passes |

### Access and equipment

| ID | Ticket | Size | Done when |
|---|---|---:|---|
| ACCESS-001 | Approve access decision table | M | Access owner signs every rule/example |
| ACCESS-002 | Implement pure access decision service | M | Decision matrix unit suite passes |
| ACCESS-003 | Implement fob administration and audit | M | Unique/lost/revoke/permission tests pass |
| ACCESS-004 | Implement device authentication/rotation/rate limits | L | Contract and security tests pass |
| ACCESS-005 | Implement legacy-compatible access/ACS endpoints | L | Simulator and hardware staging pass |
| EQUIP-001 | Implement equipment/induction CRUD | L | Route/policy/browser tests pass |
| EQUIP-002 | Implement session logging and charges | L | Duplicate/missing-stop/fee tests pass |

### Finance and payments

| ID | Ticket | Size | Done when |
|---|---|---:|---|
| FIN-001 | Document legacy money units/rounding | M | Finance signs column-by-column rules |
| FIN-002 | Implement Money and finance state enums | M | Golden calculation tests pass |
| FIN-003 | Implement payment and charge workflows | L | Transaction/idempotency suite passes |
| FIN-004 | Implement member balance and adjustments | L | Reconciliation fixtures match |
| FIN-005 | Implement subscription/member-status scheduler | L | Repeat-run and date-boundary tests pass |
| FIN-006 | Implement finance views/reports/reconciliation | L | Finance acceptance passes |
| PAY-001 | Implement durable provider webhook inbox | M | Duplicate/replay/failure lifecycle passes |
| PAY-002 | Implement current GoCardless adapter | L | Sandbox and reconciliation pass |
| PAY-003 | Implement current Stripe flow | L | Checkout/PaymentIntent webhook E2E passes |
| PAY-004 | Implement or formally retire PayPal | L/S | Approved flow passes or route is absent with history readable |

### Release readiness

| ID | Ticket | Size | Done when |
|---|---|---:|---|
| REL-001 | Port required remaining modules | L each | Module parity acceptance passes |
| REL-002 | Complete Vite/UI/accessibility/browser suite | L | P0 browser journeys and build pass |
| REL-003 | Add production observability and runbooks | L | Alerts are tested and owned |
| REL-004 | Complete security review/remediation | L | No unresolved high/critical issue |
| REL-005 | Automate full migration rehearsal | L | Repeatable report with timing/reconciliation exists |
| REL-006 | Execute cutover rehearsal and rollback | L | All sign-off owners approve evidence |
| REL-007 | Execute production cutover | L | Milestone 11 success conditions pass |
| REL-008 | Hypercare and legacy retirement | L | Milestone 12 acceptance passes |

## 11. Ticket and pull-request contract

Every implementation ticket must state:

- user/operator outcome;
- legacy evidence and source;
- in-scope and out-of-scope behaviour;
- routes/commands/tables/integrations affected;
- permission and data-privacy rules;
- transaction and idempotency requirements;
- acceptance examples including failure cases;
- telemetry and audit events;
- migration, deployment, and rollback notes;
- test layers required;
- parity ledger entries closed or deliberately changed.

Every pull request must:

- be deployable or safely dormant behind configuration;
- avoid combining skeleton/dependency churn with business changes;
- include tests before or with business behaviour;
- pass all quality gates;
- include screenshots/contracts/reconciliation evidence where applicable;
- update docs/ADR/parity/runbook artefacts affected;
- state whether it can write data and how rollback behaves.

## 12. Test strategy for rapid new development

Rapid development is safe only when tests replace the old application's implicit knowledge.

### 12.1 Test layers

| Layer | Purpose | Examples |
|---|---|---|
| Unit | Domain decisions/calculations without Laravel/DB | access decision, membership transition, fee, vote, Money |
| Feature | HTTP/request/policy/response behaviour | account isolation, validation, CSRF, role routes |
| Integration | Real production-engine persistence and transactions | payment allocation, row locking, idempotency, schema casts |
| Contract | Device/provider request and response compatibility | ACS fixtures, GoCardless/Stripe/PayPal webhooks |
| Browser | P0 cross-layer journeys | login, profile, induction, finance, provider redirect |
| Reconciliation | Old-data outcomes at aggregate/member/event level | balances, charges, statuses, active fobs |

### 12.2 Critical invariants

Tests must continuously assert:

- a provider event creates at most one local effect;
- a scheduled command may be retried without double billing;
- payment and charge states cannot disagree after a committed operation;
- member entitlement changes are attributable and atomic;
- access decisions use the approved state at decision time;
- a user never gains data/action access from a broader role by route omission;
- stored monetary calculations are integer and reconcile to provider minor units;
- a failed side effect cannot roll back a committed payment silently or run before commit;
- legacy rows with null/unusual values remain readable;
- private images/receipts are not public merely because their object key is known.

### 12.3 CI stages

Fast PR stage:

1. Composer/npm locked install.
2. Pint and frontend formatting/lint.
3. Larastan.
4. Unit and feature tests.
5. Vite build.
6. Dependency and secret audits.

Full stage:

1. Integration/contract tests on the production database engine.
2. Fresh schema and snapshot migration tests.
3. Browser smoke tests.
4. Reconciliation fixtures.
5. Architecture checks.

Nightly/pre-release stage:

1. Full sanitised snapshot reconciliation.
2. Provider sandbox tests where stable and permitted.
3. Device simulator and staging hardware checks.
4. Load, migration-duration, restore, and security scans.

## 13. Database migration and rollback design

### 13.1 Baseline approach

- The production database is already at a legacy schema state; do not replay 2014–2018 migrations during cutover.
- Capture a reviewed structure-only baseline for fresh local/CI/staging builds.
- New Laravel 13 migrations start after the baseline and are additive wherever possible.
- Existing table/column names remain unchanged for release one.
- Production backup/restore is the rollback mechanism for destructive data changes; application rollback alone is sufficient only while schema changes are backward-compatible.

### 13.2 Safe migration pattern

For meaningful changes:

1. Add nullable/default-compatible structure.
2. Deploy code that understands old and new forms.
3. Backfill in restartable, observable chunks.
4. Reconcile counts/totals/checksums.
5. Switch reads/writes.
6. Keep old structure through the rollback period.
7. Remove it in a later release.

### 13.3 Rollback levels

- Before data migration: route traffic back to legacy release.
- After backward-compatible migrations: stop new workers, route back, leave additive schema in place.
- After new writes but before provider/billing enablement: restore verified backup if legacy cannot read the writes.
- After provider events or billing: do not blindly restore and lose real-world events. Stop processing, preserve webhook inbox/event evidence, reconcile, then follow the incident runbook.

The point of no simple rollback must be named during rehearsal. The cutover owner must explicitly approve crossing it.

## 14. Deployment topology

Preferred topology before cutover:

- Legacy production: unchanged, current live database, current provider endpoints.
- New staging: isolated infrastructure, sanitised snapshot, sandbox providers, device simulator/test hardware, separate object storage and mail.
- New production candidate: built artefact and secrets prepared, but no live traffic, scheduler, workers, or provider endpoints until cutover.

Do not run both applications as ordinary writers against the live database. If a final read-only shadow comparison is required, enforce read-only database credentials and disable sessions, queues, schedules, broadcasts, mail, storage writes, and provider calls.

## 15. Governance, reporting, and critical path

### 15.1 Required sign-off roles

- Client/product owner: feature scope and deliberate behaviour differences.
- Membership owner: signup, status, induction, privacy, member communications.
- Finance owner: money semantics, providers, reconciliation, billing enablement.
- Access owner: fobs, doors/equipment, device migration, manual fallback.
- Technical owner: architecture, security, delivery, migration, rollback.
- Data/privacy owner: snapshot handling, retention, logging, deletion.

One person may hold multiple roles, but no category may be unowned.

### 15.2 Weekly evidence report

Report outcomes, not percentage-complete guesses:

- parity entries accepted/remaining/blocked;
- tickets completed and next critical tickets;
- current green build and deployment link;
- reconciliation differences;
- provider/device contract status;
- risks/decisions needing owner action;
- milestone exit criteria passed/remaining.

### 15.3 Critical path

```text
Production facts and scope
  -> clean foundation and staging
  -> schema baseline and snapshot compatibility
  -> identity/roles
  -> member lifecycle
  -> access/equipment and internal finance (can proceed in parallel)
  -> provider integrations and scheduler
  -> full reconciliation/security/operational rehearsal
  -> cutover
  -> two billing cycles and retirement
```

The longest-lead unknowns are likely provider account/API decisions, hardware/device compatibility, production schema/data anomalies, and finance reconciliation. Investigate them in Milestone 0 even though their implementation occurs later.

## 16. Programme definition of done

- The clean Laravel 13 application is the only production application, worker, scheduler, and webhook processor.
- PHP 8.4, Node 24, Composer/npm lock files, CI, staging, and production deployment are reproducible.
- Every P0/P1 parity item is accepted or has a client-approved difference.
- Existing passwords, member data, provider identifiers, object keys, and required machine contracts remain usable.
- Financial, membership, access, device, provider, and scheduler invariants pass automated tests.
- Fresh databases and production-like snapshot upgrades both pass.
- Pre/post-cutover reconciliation is signed off by finance, membership, and access owners.
- CSRF, policies, session security, provider/device authentication, rate limits, replay controls, uploads, secrets, and personal-data logging pass security review.
- All retained providers use supported integrations and reconcile.
- Deployment, rollback, restore, payment incident, queue/scheduler, and access-outage runbooks are tested.
- At least two complete billing cycles pass during hypercare.
- Legacy traffic, jobs, webhooks, credentials, and runtime infrastructure are retired after the agreed rollback period.

## 17. Decisions to close before Milestone 1 ends

1. Exact production commit, database engine/version, PHP/runtime, hosting, and deployment source.
2. Required/retired status for PayPal, both GoCardless generations, Stripe, Pusher, Slack, CCTV/GIF, Spark, Swagger, analytics, and log/debug viewers.
3. First-release feature priority: P0/P1/P2.
4. Existing database compatibility versus a planned transformed schema. This plan recommends compatibility for release one.
5. ~~Frontend direction.~~ Resolved 17 August 2026: official Laravel Inertia 3 + Vue 3 + TypeScript starter kit, Tailwind 4, and shadcn-vue components.
6. CI, staging, production hosting, queue, cache/session, monitoring, and secret-management platforms.
7. Device upgrade constraints and supported parallel protocol window.
8. Cutover downtime, recovery-time objective, recovery-point objective, and rollback period.
9. Data retention/privacy requirements and who may handle sanitised snapshots.
10. Named membership, finance, access, technical, and client sign-off owners.

No unresolved decision may be filled with a convenient technical assumption if it changes member entitlement, money, physical access, data privacy, or cutover safety.
