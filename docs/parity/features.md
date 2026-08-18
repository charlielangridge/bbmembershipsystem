# Legacy Feature Parity Ledger

Status: feature dispositions, owner authority, and pre-cutover order confirmed; two discovery-added priorities, deployed baseline, and named domain owners pending
Evidence date: 18 August 2026
Evidence revision: legacy `master` at `d686bf6`

## Purpose

This ledger inventories behaviour visible in the legacy repository. Product direction on 18 August 2026 requires feature parity for every inventoried capability before production cutover. Five grouped areas—historical direct-debit migration, PayPal IPN/donations, CCTV capture, Discord notifications, and Swagger/log viewing—form the final parity tranche after every other capability is accepted but still before cutover.

The evidence revision is the current Git `master` tip, not a confirmed production baseline. `NEW-001` must confirm the deployed commit before this inventory can be treated as complete production evidence. Runtime configuration, provider dashboards, device versions, production data, and operator interviews may reveal additional behaviour.

## Decision vocabulary

- `required` — preserve the business outcome or external contract before production cutover.
- `changed` — deliver an explicitly approved replacement or deliberate difference.
- `retired` — omit only with named owner approval and evidence that no required workflow or client depends on it.
- `pending` — no owner decision has been recorded.

The P0/P1/P2 values below are the approved implementation order. P3 is the confirmed final tranche, implemented after every other required capability but before production cutover. Required business outcomes and contracts may use supported replacement implementations; feature parity does not require obsolete packages or unsafe protocols.

## Sign-off owners

| Area | Owner | Status |
| --- | --- | --- |
| Client/product | Charlie Langridge | Approved 18 August 2026 |
| Membership | Unassigned | Pending |
| Finance | Unassigned | Pending |
| Physical access | Unassigned | Pending |
| Operations | Unassigned | Pending |
| Technical | Unassigned | Pending |
| Data/privacy | Unassigned | Pending |

## Feature inventory

| ID | Feature area | Legacy evidence | Approved boundary | Decision | Priority | Sign-off owner | Notes/questions |
| --- | --- | --- | --- | --- | --- | --- | --- |
| APP-01 | Home page and authenticated dashboard | `/`, `HomeController::index`, `home.blade.php`, homepage tests | Member-facing Inertia journey | required | P1 | Client/product | Confirm the public and authenticated content/actions required at the root route. |
| AUTH-01 | Login, logout, password reset, and session handling | `SessionController`, `ReminderController`, `routes.php`, login tests | Fortify replacement already selected | required | P0 | Client/product + technical | Confirm legacy password compatibility separately under `ID-001`; retain Fortify rather than legacy controllers. |
| AUTH-02 | Realtime/Pusher session authorisation | `session/pusher`, `SessionController::pusherAuth`, Pusher dependency | Supported realtime replacement | required | P2 | Client/product + technical | Preserve session-authorised realtime behaviour using a supported broadcasting implementation. |
| MEM-01 | Public registration and account setup | `register`, `AccountController::create/store`, signup tests | Member-facing Inertia journey | required | P1 | Membership | Confirm current signup, approval, and initial payment expectations. |
| MEM-02 | Member account administration and lifecycle actions | `account` resource, admin update, rejoin, cancel/destroy, trusted missing photos | Filament administration plus member self-service | required | P0 | Membership | Capture every legal status transition in `statuses.md`. |
| MEM-03 | Profile, address, photo, and privacy editing | `ProfileController`, account profile views, profile/photo tests | Member-facing Inertia journey | required | P0 | Membership + data/privacy | Confirm image storage, visibility, and retention rules. |
| MEM-04 | Email confirmation and member communications state | `account/confirm-email`, account/email views and notifications | Fortify email verification replacement | required | P0 | Membership | Map legacy verification values to Fortify email verification. |
| MEM-05 | Member directory and member detail | `MembersController`, `members/*`, member tests | Member-facing Inertia journey | required | P1 | Membership + data/privacy | Confirm which fields are public, member-only, or staff-only. |
| MEM-06 | Groups and group membership views | `GroupsController`, `groups/*`, group tests | Member-facing Inertia journey | required | P1 | Membership | Confirm groups remain distinct from authorisation roles where their business purpose differs. |
| MEM-07 | Member induction submission and approval | `MemberInductionController`, `member_inductions`, induction tests | Inertia submission plus Filament approval | required | P1 | Membership | Record approver roles, evidence, and state transitions. |
| AUTHZ-01 | Roles, role membership, and permission management | `RolesController`, `RoleUsersController`, role middleware and tests | Spatie Laravel Permission + policies; Filament administration | required | P0 | Client/product + technical | Produce an approved permission matrix and reconciled legacy-role import before implementation. |
| AUDIT-01 | User and profile-data change audit trail | `UserAuditObserver`, `AuditLog`, observer registration for `User` and `ProfileData` | Laravel audit workflow | required | P1 | Membership + data/privacy | Confirm audited fields, actor attribution, retention, visibility, and historical-entry migration. |
| FIN-01 | Member balance and BB credit | `BalanceController`, `BalancePaymentController`, `account/balance`, finance tests | Cutover-critical finance | required | P0 | Finance | Confirm stored units, negative-balance rules, and reconciliation totals. |
| FIN-02 | Withdrawals and balance-funded payments | `BalanceController::withdrawal`, `BalancePaymentController::store` | Cutover-critical finance | required | P0 | Finance | Confirm approval, notification, and audit requirements. |
| FIN-03 | Payment administration and payment overview | `PaymentController`, `PaymentOverviewController`, `payments/*`, finance tests | Filament finance administration | required | P0 | Finance | Map payment states and correction/deletion rules. |
| FIN-04 | Cash/manual payments | `CashPaymentController`, manual-payment tests | Filament finance administration | required | P0 | Finance | Preserve cash/manual payment acceptance and define who may reverse entries. |
| FIN-05 | Subscription amounts, methods, and recurring charges | `SubscriptionController`, sub-charge views, subscription tests | Cutover-critical finance | required | P0 | Finance + membership | Confirm minimum contribution and secondary-payment behaviour. |
| FIN-06 | Bank statement import | `StatementImportController`, statement import views, spreadsheet reader dependency | Supported import replacement | required | P2 | Finance | Confirm the production bank format with representative fixtures. |
| PAY-01 | GoCardless payment initiation and webhook processing | `GoCardlessPaymentController`, `GoCardlessWebhookController`, webhook tests | Current supported GoCardless API/SDK | required | P0 | Finance + technical | Identify active API generation, mandates, webhook version, and sandbox account. |
| PAY-02 | GoCardless direct-debit migration workflow | `migrate-direct-debit`, `PaymentController::migrateDD`, migration views/stats | Final parity tranche | required | P3 | Finance | Implement after every P0–P2 item is accepted; preserve the historical migration outcome with supported APIs. |
| PAY-03 | Stripe card payment flow | `StripePaymentController`, Stripe PHP 1.x dependency | Current supported Stripe flow | required | P0 | Finance + technical | Replace legacy Checkout/API use while preserving the member payment outcome. |
| PAY-04 | PayPal IPN and donation handling | `paypal-ipn`, `PaypalIPNController`, PayPal SDK, donation email | Final parity tranche; supported PayPal replacement | required | P3 | Finance | Implement after every P0–P2 item is accepted; preserve payment/donation outcomes and readable history without the unsupported IPN SDK. |
| ACCESS-01 | Key-fob administration | `KeyFobController`, `keyfob` routes, key-fob tables | Cutover-critical physical access | required | P0 | Physical access | Confirm uniqueness, lost/revoked states, and audit history. |
| ACCESS-02 | Main-door and generic device access decisions | `AccessControlController`, `DeviceAccessControlController`, API tests | Preserved device contracts | required | P0 | Physical access + technical | Inventory every deployed client and preserve required response contracts. |
| ACCESS-03 | ACS tag status and node lifecycle endpoints | `ACS/StatusController`, `ACS/NodeController`, ACS tests | Preserved device contracts | required | P0 | Physical access + technical | Confirm active node firmware versions, credentials, boot, heartbeat, and status payloads. |
| ACCESS-04 | ACS activity/session logging | `ACS/ActivityController`, `/acs/activity` routes, ACS tests | Cutover-critical equipment finance | required | P0 | Physical access + finance | Determine where equipment use becomes a charge and how interrupted sessions close. |
| ACCESS-05 | Legacy ACS and Spark endpoints | `ACSController`, `ACSSparkController`, `/acs`, `/acs/spark` | Compatibility adapter/window | required | P1 | Physical access + technical | Identify hardware still calling each endpoint and define compatibility windows. |
| ACCESS-06 | Device inventory, detected devices, and online checks | `DeviceController`, `DetectedDevicesController`, `CheckDeviceOnlineStatuses` | Filament operations administration | required | P1 | Physical access + operations | Confirm which inventory is authoritative and what offline alerting is required. |
| ACCESS-07 | CCTV event and image/GIF capture | `CCTVController`, `/camera/*`, GIF/image dependencies | Final parity tranche; supported media replacement | required | P3 | Physical access + data/privacy | Implement after every P0–P2 item is accepted; preserve capture outcomes subject to lawful-basis, retention, and storage controls. |
| EQUIP-01 | Equipment catalogue, photos, and equipment inductions | `EquipmentController`, `InductionController`, equipment views/tests | Member Inertia plus Filament administration | required | P1 | Membership + physical access | Confirm member edit permissions and required training evidence. |
| EQUIP-02 | Equipment session correction and fee calculation | `EquipmentLogController`, equipment commands and tests | Domain workflow plus Filament correction UI | required | P1 | Finance + physical access | Document fee units, missing-stop repair, duplicate handling, and rounding. |
| OPS-01 | Scheduled membership, billing, balance, proposal, equipment, and device jobs | eight commands in `Console\Kernel`; hourly/daily schedules | Laravel scheduler and queues | required | P0 | Operations + relevant business owner | Record timezone, overlap behaviour, retry/idempotency, and current scheduler ownership in `commands.md`. |
| OPS-02 | Scheduler heartbeat monitoring | Envoyer heartbeat calls in `Console\Kernel` | Supported monitoring replacement | required | P1 | Operations + technical | Replace embedded heartbeat URLs with approved monitoring and secret handling. |
| OPS-03 | Production error reporting and alerting | Rollbar service provider/configuration, server package, browser shim | Supported error-reporting replacement | required | Proposed P1 | Operations + technical + data/privacy | Confirm alerting needs, PII scrubbing, retention, environments, release tracking, and incident ownership. |
| COMM-01 | In-app notifications | `NotificationController`, notification views/package | Laravel notifications plus member Inertia UI | required | P1 | Membership | Confirm read/unread semantics and retained notification types. |
| COMM-02 | Broadcast email to members/groups | `NotificationEmailController`, email views, Slack-related group fields | Laravel mail/notifications | required | P1 | Membership + data/privacy | Confirm audience selection, consent, audit, and delivery provider. |
| COMM-03 | Feedback submission | `FeedbackController`, feedback widget/email | Member-facing Inertia journey | required | P2 | Client/product | Confirm destination and retention. |
| COMM-04 | Member lifecycle emails triggered by status changes | `UserObserver`, `UserMailer`, welcome/payment-warning/suspended/left email views | Laravel events/notifications | required | P1 | Membership | Confirm trigger transitions, recipients, templates, retry behaviour, and audit requirements. |
| COMM-05 | Discord status-change webhook notifications | `UserObserver::sendSlackNotification`, `DISCORD_WEBHOOK`, Discord webhook request | Final parity tranche; supported webhook integration | required | P3 | Membership + operations | Implement after every P0–P2 item is accepted; preserve required status-event notifications and correct the historical naming. |
| GOV-01 | Proposals, voting, and vote calculation | `ProposalController`, proposal command/views/tests | Member-facing Inertia journey | required | P2 | Client/product | Confirm voting rules, electorate, deadlines, visibility, and historical access. |
| FIN-07 | Expense submission and approval | `ExpensesController`, expense views/emails/tests | Inertia submission plus Filament approval | required | P1 | Finance | Confirm approval roles, receipt storage, payment states, and retention. |
| SPACE-01 | Member storage-box allocation and charging | `StorageBoxController`, storage views/tests | Member Inertia plus Filament administration | required | P1 | Membership + finance | Confirm allocation rules, pricing, and historical balances. |
| REPORT-01 | Member and direct-debit statistics | `StatsController`, stats views/tests | Filament reports/widgets | required | P2 | Client/product + finance | List exact decisions/reports these statistics support. |
| REPORT-02 | Activity and realtime activity views | `ActivityController`, activity views/tests, Pusher integration | Supported realtime replacement | required | P2 | Client/product + data/privacy | Confirm visibility and retention while preserving realtime behaviour. |
| CONTENT-01 | Member resources and policy documents | `ResourcesController`, resource/policy views | Member Inertia plus Filament administration | required | P1 | Client/product | Identify the current document source and acceptance/versioning workflow. |
| ADMIN-01 | Application settings update | `SettingsController`, settings table/migration | Filament administration | required | P0 | Technical + relevant business owner | Inventory every setting key from production-safe evidence. |
| ADMIN-02 | Swagger/API documentation endpoints | `/api-docs`, Swagger configuration/dependency | Final parity tranche; OpenAPI 3 replacement | required | P3 | Technical | Implement after every P0–P2 item is accepted using a supported generated/validated API contract and UI. |
| ADMIN-03 | Web log viewer and Clockwork/debug tooling | `/logs`, log-viewer and Clockwork dependencies | Final parity tranche; secure observability replacement | required | P3 | Operations + technical | Implement after every P0–P2 item is accepted without exposing unrestricted raw production logs. |
| ANALYTICS-01 | Usage analytics and reporting telemetry | Universal Analytics scripts in production layouts; authenticated internal user ID sent | Privacy-approved analytics replacement | required | Proposed P2 | Client/product + data/privacy | Define the decisions analytics must support, consent/lawful basis, identifier policy, retention, and approved replacement. |

## Evidence consulted

- `app/Http/routes.php` at legacy revision `d686bf6`.
- Controllers under `app/Http/Controllers/` and observers under `app/Observer/` at that revision.
- Scheduled commands and `app/Console/Kernel.php` at that revision.
- Legacy views, migrations, Composer dependencies, PHPUnit tests, and Codeception suites at that revision.
- Priority candidates and non-goals in `LARAVEL_13_IMPLEMENTATION_PLAN.md`.

## Completion checklist for NEW-002

- [x] Inventory current modules visible in the candidate legacy source revision.
- [ ] Confirm the deployed legacy revision through `NEW-001` and reconcile this inventory against it.
- [ ] Name every sign-off owner.
- [x] Record `required`, `changed`, or `retired` for every row.
- [x] Approve the P0/P1/P2 assignments for core parity rows.
- [ ] Approve proposed P1/P2 priorities for discovery-added `OPS-03` and `ANALYTICS-01`.
- [x] Assign the six rows representing the five deferred areas to the confirmed final P3 tranche.
- [ ] Record evidence for any retirement decision.
- [x] Obtain client/product-owner direction on feature disposition and ordering.
