# Legacy Feature Parity Ledger

Status: discovery draft awaiting owner decisions
Evidence date: 18 August 2026
Evidence revision: legacy `master` at `d686bf6`

## Purpose

This ledger inventories behaviour visible in the legacy repository so the product owner can classify each area as `required`, `changed`, or `retired` for the Laravel 13 release.

The evidence revision is the current Git `master` tip, not a confirmed production baseline. `NEW-001` must confirm the deployed commit before this inventory can be treated as complete production evidence. Runtime configuration, provider dashboards, device versions, production data, and operator interviews may reveal additional behaviour.

## Decision vocabulary

- `required` — preserve the business outcome or external contract for the first release.
- `changed` — deliver an explicitly approved replacement or deliberate difference.
- `retired` — omit only with named owner approval and evidence that no required workflow or client depends on it.
- `pending` — no owner decision has been recorded.

Priorities remain pending until the product owner assigns `P0`, `P1`, or `P2`. A proposed release boundary is included only where the implementation plan already names one.

## Sign-off owners

| Area | Owner | Status |
| --- | --- | --- |
| Client/product | Unassigned | Pending |
| Membership | Unassigned | Pending |
| Finance | Unassigned | Pending |
| Physical access | Unassigned | Pending |
| Operations | Unassigned | Pending |
| Technical | Unassigned | Pending |
| Data/privacy | Unassigned | Pending |

## Feature inventory

| ID | Feature area | Legacy evidence | Proposed boundary from plan | Decision | Priority | Sign-off owner | Notes/questions |
| --- | --- | --- | --- | --- | --- | --- | --- |
| APP-01 | Home page and authenticated dashboard | `/`, `HomeController::index`, `home.blade.php`, homepage tests | Candidate P0/P1 | pending | Pending | Client/product | Confirm the public and authenticated content/actions required at the root route. |
| AUTH-01 | Login, logout, password reset, and session handling | `SessionController`, `ReminderController`, `routes.php`, login tests | Fortify replacement already selected | pending | Pending | Client/product + technical | Confirm legacy password compatibility separately under `ID-001`; retain Fortify rather than legacy controllers. |
| AUTH-02 | Realtime/Pusher session authorisation | `session/pusher`, `SessionController::pusherAuth`, Pusher dependency | Candidate retire/replace | pending | Pending | Client/product + technical | Confirm whether any current interface still uses realtime activity. |
| MEM-01 | Public registration and account setup | `register`, `AccountController::create/store`, signup tests | Candidate P1 | pending | Pending | Membership | Confirm current signup, approval, and initial payment expectations. |
| MEM-02 | Member account administration and lifecycle actions | `account` resource, admin update, rejoin, cancel/destroy, trusted missing photos | Candidate P0 | pending | Pending | Membership | Capture every legal status transition in `statuses.md`. |
| MEM-03 | Profile, address, photo, and privacy editing | `ProfileController`, account profile views, profile/photo tests | Candidate P0/P1 | pending | Pending | Membership + data/privacy | Confirm image storage, visibility, and retention rules. |
| MEM-04 | Email confirmation and member communications state | `account/confirm-email`, account/email views and notifications | Candidate P0 | pending | Pending | Membership | Map legacy verification values to Fortify email verification. |
| MEM-05 | Member directory and member detail | `MembersController`, `members/*`, member tests | Candidate P1 | pending | Pending | Membership + data/privacy | Confirm which fields are public, member-only, or staff-only. |
| MEM-06 | Groups and group membership views | `GroupsController`, `groups/*`, group tests | Candidate P1/P2 | pending | Pending | Membership | Confirm whether groups remain distinct from authorisation roles. |
| MEM-07 | Member induction submission and approval | `MemberInductionController`, `member_inductions`, induction tests | Candidate P1 | pending | Pending | Membership | Record approver roles, evidence, and state transitions. |
| AUTHZ-01 | Roles, role membership, and permission management | `RolesController`, `RoleUsersController`, role middleware and tests | Spatie Laravel Permission + policies; Filament administration | pending | Pending | Client/product + technical | Produce an approved permission matrix and reconciled legacy-role import before implementation. |
| AUDIT-01 | User and profile-data change audit trail | `UserAuditObserver`, `AuditLog`, observer registration for `User` and `ProfileData` | Candidate P0/P1 | pending | Pending | Membership + data/privacy | Confirm audited fields, actor attribution, retention, visibility, and whether historical entries must migrate. |
| FIN-01 | Member balance and BB credit | `BalanceController`, `BalancePaymentController`, `account/balance`, finance tests | Candidate P0 | pending | Pending | Finance | Confirm stored units, negative-balance rules, and reconciliation totals. |
| FIN-02 | Withdrawals and balance-funded payments | `BalanceController::withdrawal`, `BalancePaymentController::store` | Candidate P0 | pending | Pending | Finance | Confirm approval, notification, and audit requirements. |
| FIN-03 | Payment administration and payment overview | `PaymentController`, `PaymentOverviewController`, `payments/*`, finance tests | Candidate P0 | pending | Pending | Finance | Map payment states and correction/deletion rules. |
| FIN-04 | Cash/manual payments | `CashPaymentController`, manual-payment tests | Candidate P0/P1 | pending | Pending | Finance | Confirm whether cash remains accepted and who may reverse entries. |
| FIN-05 | Subscription amounts, methods, and recurring charges | `SubscriptionController`, sub-charge views, subscription tests | Candidate P0 | pending | Pending | Finance + membership | Confirm minimum contribution and secondary-payment behaviour. |
| FIN-06 | Bank statement import | `StatementImportController`, statement import views, spreadsheet reader dependency | Candidate P2 | pending | Pending | Finance | Confirm current bank format and whether this is still operational. |
| PAY-01 | GoCardless payment initiation and webhook processing | `GoCardlessPaymentController`, `GoCardlessWebhookController`, webhook tests | Candidate P0 if retained | pending | Pending | Finance + technical | Identify active API generation, mandates, webhook version, and sandbox account. |
| PAY-02 | GoCardless direct-debit migration workflow | `migrate-direct-debit`, `PaymentController::migrateDD`, migration views/stats | Candidate change/retire | pending | Pending | Finance | Determine whether any members still require this historical migration. |
| PAY-03 | Stripe card payment flow | `StripePaymentController`, Stripe PHP 1.x dependency | Candidate P0 if retained | pending | Pending | Finance + technical | Confirm current provider flow and replace legacy Checkout/API use if retained. |
| PAY-04 | PayPal IPN and donation handling | `paypal-ipn`, `PaypalIPNController`, PayPal SDK, donation email | Candidate retire/replace | pending | Pending | Finance | Retirement requires provider and transaction-history confirmation. |
| ACCESS-01 | Key-fob administration | `KeyFobController`, `keyfob` routes, key-fob tables | Candidate P0 | pending | Pending | Physical access | Confirm uniqueness, lost/revoked states, and audit history. |
| ACCESS-02 | Main-door and generic device access decisions | `AccessControlController`, `DeviceAccessControlController`, API tests | Candidate P0 | pending | Pending | Physical access + technical | Inventory every deployed client and preserve required response contracts. |
| ACCESS-03 | ACS tag status and node lifecycle endpoints | `ACS/StatusController`, `ACS/NodeController`, ACS tests | Candidate P0 | pending | Pending | Physical access + technical | Confirm active node firmware versions, credentials, boot, heartbeat, and status payloads. |
| ACCESS-04 | ACS activity/session logging | `ACS/ActivityController`, `/acs/activity` routes, ACS tests | Candidate P0/P1 | pending | Pending | Physical access + finance | Determine where equipment use becomes a charge and how interrupted sessions close. |
| ACCESS-05 | Legacy ACS and Spark endpoints | `ACSController`, `ACSSparkController`, `/acs`, `/acs/spark` | Candidate change/retire | pending | Pending | Physical access + technical | Identify hardware still calling each endpoint before deciding compatibility windows. |
| ACCESS-06 | Device inventory, detected devices, and online checks | `DeviceController`, `DetectedDevicesController`, `CheckDeviceOnlineStatuses` | Candidate P0/P1 | pending | Pending | Physical access + operations | Confirm which inventory is authoritative and what offline alerting is required. |
| ACCESS-07 | CCTV event and image/GIF capture | `CCTVController`, `/camera/*`, GIF/image dependencies | Candidate retire/replace | pending | Pending | Physical access + data/privacy | Confirm live use, lawful basis, retention, and storage before retaining. |
| EQUIP-01 | Equipment catalogue, photos, and equipment inductions | `EquipmentController`, `InductionController`, equipment views/tests | Candidate P1 | pending | Pending | Membership + physical access | Confirm member edit permissions and required training evidence. |
| EQUIP-02 | Equipment session correction and fee calculation | `EquipmentLogController`, equipment commands and tests | Candidate P0/P1 | pending | Pending | Finance + physical access | Document fee units, missing-stop repair, duplicate handling, and rounding. |
| OPS-01 | Scheduled membership, billing, balance, proposal, equipment, and device jobs | eight commands in `Console\Kernel`; hourly/daily schedules | Candidate P0 where retained | pending | Pending | Operations + relevant business owner | Record timezone, overlap behaviour, retry/idempotency, and current scheduler ownership in `commands.md`. |
| OPS-02 | Scheduler heartbeat monitoring | Envoyer heartbeat calls in `Console\Kernel` | Candidate change | pending | Pending | Operations + technical | Replace embedded heartbeat URLs with approved monitoring and secret handling. |
| COMM-01 | In-app notifications | `NotificationController`, notification views/package | Candidate P1 | pending | Pending | Membership | Confirm read/unread semantics and retained notification types. |
| COMM-02 | Broadcast email to members/groups | `NotificationEmailController`, email views, Slack-related group fields | Candidate P1 | pending | Pending | Membership + data/privacy | Confirm audience selection, consent, audit, and delivery provider. |
| COMM-03 | Feedback submission | `FeedbackController`, feedback widget/email | Candidate P2 | pending | Pending | Client/product | Confirm destination, retention, and whether another support channel replaces it. |
| COMM-04 | Member lifecycle emails triggered by status changes | `UserObserver`, `UserMailer`, welcome/payment-warning/suspended/left email views | Candidate P0/P1 | pending | Pending | Membership | Confirm trigger transitions, recipients, templates, retry behaviour, and audit requirements. |
| COMM-05 | Discord status-change webhook notifications | `UserObserver::sendSlackNotification`, `DISCORD_WEBHOOK`, Discord webhook request | Candidate retire/replace | pending | Pending | Membership + operations | Despite the historical method name, current candidate code posts to Discord; confirm whether this is live and which status events must remain. |
| GOV-01 | Proposals, voting, and vote calculation | `ProposalController`, proposal command/views/tests | Candidate P2 | pending | Pending | Client/product | Confirm voting rules, electorate, deadlines, visibility, and historical access. |
| FIN-07 | Expense submission and approval | `ExpensesController`, expense views/emails/tests | Candidate P1 | pending | Pending | Finance | Confirm approval roles, receipt storage, payment states, and retention. |
| SPACE-01 | Member storage-box allocation and charging | `StorageBoxController`, storage views/tests | Candidate P1 | pending | Pending | Membership + finance | Confirm allocation rules, pricing, and historical balances. |
| REPORT-01 | Member and direct-debit statistics | `StatsController`, stats views/tests | Candidate P2 | pending | Pending | Client/product + finance | List exact decisions/reports these statistics support. |
| REPORT-02 | Activity and realtime activity views | `ActivityController`, activity views/tests, Pusher integration | Candidate P2/change | pending | Pending | Client/product + data/privacy | Confirm visibility, retention, and whether realtime remains required. |
| CONTENT-01 | Member resources and policy documents | `ResourcesController`, resource/policy views | Candidate P1 | pending | Pending | Client/product | Identify the current document source and acceptance/versioning workflow. |
| ADMIN-01 | Application settings update | `SettingsController`, settings table/migration | Candidate P0/P1; Filament administration | pending | Pending | Technical + relevant business owner | Inventory every setting key from production-safe evidence. |
| ADMIN-02 | Swagger/API documentation endpoints | `/api-docs`, Swagger configuration/dependency | Candidate retire/replace | pending | Pending | Technical | Determine whether operators or devices consume generated documentation. |
| ADMIN-03 | Web log viewer and Clockwork/debug tooling | `/logs`, log-viewer and Clockwork dependencies | Candidate retire/replace | pending | Pending | Operations + technical | Replace with approved observability; do not expose production logs through the app. |

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
- [ ] Record `required`, `changed`, or `retired` for every row.
- [ ] Assign P0/P1/P2 to every retained or changed row.
- [ ] Record evidence for any retirement decision.
- [ ] Obtain client/product-owner sign-off.
