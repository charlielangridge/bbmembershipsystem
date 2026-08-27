# Legacy Route Parity Ledger

Status: candidate-source route inventory complete; production use and response contracts unconfirmed
Evidence date: 18 August 2026
Evidence revision: legacy `master` at `d686bf6`

## Purpose and evidence

This ledger expands every active `Route::resource` declaration into its concrete Laravel 5.1 methods, paths, names, and actions. Every table row is sourced from `app/Http/routes.php` at the evidence revision; controller-specific audience constraints are sourced from the named controller constructor/action. Global legacy middleware evidence comes from `app/Http/Kernel.php`.

Feature parity preserves each required outcome. Member/public browser routes move to Inertia, privileged administration moves to Filament, authentication moves to Fortify, and provider/device routes keep compatibility contracts until an approved versioned replacement is deployed. Exact legacy browser paths/names remain compatibility candidates until production traffic/bookmarks are assessed. Each route row's authentication and source-visible response fields are supplied by the exhaustive register below; more-specific entries override broader section entries.

Audience abbreviations: `Public`, `Member`, `Admin`, `Finance`, `Comms`, `Equipment`, human `ACS role`, and machine `API key`. The legacy global stack enforces SSL/session behavior, but its CSRF middleware is commented out; the replacement must restore CSRF for browser mutations and use explicit provider/device authentication for non-browser contracts.

## Authentication and response register

This register is part of every matching route row. “Internal permission” means a controller/model check visible outside route middleware; it must become an explicit Laravel policy. Response families summarize source-visible success behavior; exact validation/error payloads, headers, and redirects remain fixture work.

| Matching rows | Authentication / authorization | Source-visible response family | Source |
| --- | --- | --- | --- |
| `/`; public member-directory and public policy GETs | Optional session or none; no role middleware | HTML view, normally 200 | `routes.php`; named controllers |
| `/login`, `/session/create`, password GETs, `/register`, `/account/create` | None; registration guest-only middleware is not active | HTML form view, normally 200 | `routes.php`; auth/account controllers |
| `POST /session`, password POSTs, public registration POST | None; legacy browser CSRF absent | Redirect with session notification/errors | auth/account controllers; global middleware |
| logout routes | Session may exist; no explicit route role; legacy GET mutates session | Redirect after session destruction | `SessionController::destroy()` |
| `/session/pusher` | Session + `role:member`; private channel constrained to authenticated user ID | Pusher authorization JSON/string or provider error; replace with Reverb/Echo standard private-channel authorisation | `SessionController::pusherAuth()` |
| Account/profile/balance/member-induction member rows | Session + controller/route `role:member`; self-or-role checks where named in Audience | HTML view for GET; redirect or JSON for mutations | named controllers; `User::findWithPermission()` |
| Account/admin, member-induction approval, cash, key-fob, role rows | Session + `role:admin`/`role:comms` as shown | HTML/Filament view for GET; redirect/JSON after mutation | route groups; named controllers |
| Finance payment/statement rows | Session + `role:finance`; payment destroy also inherits member controller middleware | HTML view/file form for GET; redirect/JSON after mutation | finance route group; payment/statement controllers |
| GoCardless member initiation/cancellation | Session + member controller middleware, except legacy completion GET which declares none | Provider redirect or redirect/JSON after local/provider mutation | subscription/GoCardless controllers |
| GoCardless webhook | HMAC-SHA256 `Webhook-Signature`; no browser session/CSRF | Body `Success` with HTTP 200 on accepted events; empty HTTP 403 for an invalid signature; logging/error paths for unknown data | `GoCardlessWebhookController` |
| Stripe member payment | Session + member controller middleware | JSON/redirect payment result; local paid record on provider success | `StripePaymentController` |
| PayPal IPN | PayPal SDK IPN validation; no browser session/CSRF | Empty HTTP acknowledgement path with logging/events | `PaypalIPNController` |
| Equipment catalogue member GETs | Session + `role:member` | HTML view, normally 200 | equipment route group/controller |
| Equipment administration/resource mutations | Session + `role:member` and controller `role:equipment` for resource actions | HTML form view or redirect after mutation | `EquipmentController::__construct()` |
| Equipment custom photo/log rows | Session + `role:member`; additional internal checks are incomplete/ambiguous | Redirect/JSON after upload/removal/correction | named controllers |
| Notifications/activity/storage/stats/proposals/groups/resources/expenses member rows | Session + `role:member`, plus internal ownership/admin checks where shown | HTML view for GET; redirect or JSON for mutations | route declarations; named controllers |
| Broadcast-email rows | Session + `role:member` plus internal group/admin checks | HTML form then redirect/session notification | `NotificationEmailController` |
| `/feedback` | Session + `role:member` | JSON object containing success flag | `FeedbackController::store()` |
| `/access-control/*`, `/acs`, `/acs/spark` | No verified route authentication; legacy session driver changed to array | Legacy JSON/text contract, often HTTP 200 even for domain denial; exact bytes require fixtures | global middleware; named controllers |
| Camera rows | No route authentication | Empty/HTTP response after synchronous object/media mutation; exact error/status contract untested | `CCTVController` |
| `/detected_devices*` | Session + `role:admin` | Index HTML; missing/stub actions have no successful response contract | route group; `DetectedDevicesController` |
| `/devices*` | Session + human `role:acs` | HTML views or redirects after node mutation | route group; `DeviceController` |
| Newer `/acs/*` rows | `ApiKey` header matched to ACS node | JSON/empty responses with source-visible 200/201/204/400/404 families | ACS middleware/controllers/annotations |
| `/settings` | None declared (authorization defect) | Redirect/response after arbitrary setting mutation | `routes.php`; `SettingsController` |
| `/logs` | Session + `role:admin` | Package-rendered HTML log view | log route/package controller |
| `/api-docs.json` | None | JSON file response 200 or 404; route accepts any method | docs closure in `routes.php` |
| `/api-docs` | None | Generated Swagger HTML view, normally 200 | docs closure in `routes.php` |

## Home and authentication

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/` | `home` | `HomeController@index` | Public/session-aware | Inertia; APP-01 |
| GET | `/login` | `login` | `SessionController@create` | Public | Fortify; AUTH-01 |
| GET | `/logout` | `logout` | `SessionController@destroy` | Public | Changed to protected POST logout; AUTH-01 |
| GET | `/session/create` | `session.create` | `SessionController@create` | Public | Fortify compatibility/redirect; AUTH-01 |
| POST | `/session` | `session.store` | `SessionController@store` | Public | Fortify compatibility; AUTH-01 |
| DELETE | `/session/{session}` | `session.destroy` | `SessionController@destroy` | Public | Fortify compatibility; AUTH-01 |
| POST | `/session/pusher` | `session.pusher` | `SessionController@pusherAuth` | Member | Replace with Reverb/Echo standard private-channel authorisation; AUTH-02 |
| GET | `/password/forgotten` | `password-reminder.create` | `ReminderController@create` | Public | Fortify compatibility/redirect; AUTH-01 |
| POST | `/password/forgotten` | `password-reminder.store` | `ReminderController@store` | Public | Fortify compatibility; AUTH-01 |
| GET | `/password/reset/{id}` | unnamed | `ReminderController@getReset` | Public | Fortify reset compatibility; AUTH-01 |
| POST | `/password/reset` | `password.reset.complete` | `ReminderController@postReset` | Public | Fortify reset compatibility; AUTH-01 |

## Accounts, members, profiles, balance, and induction

`AccountController` adds member middleware except to create/store and adds admin middleware to index.

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/account/trusted_missing_photos` | `account.trusted_missing_photos` | `AccountController@trustedMissingPhotos` | Admin | Filament; MEM-02/MEM-03 |
| GET | `/account` | `account.index` | `AccountController@index` | Member + Admin | Filament; MEM-02 |
| GET | `/account/create` | `account.create` | `AccountController@create` | Public | Inertia registration; MEM-01 |
| POST | `/account` | `account.store` | `AccountController@store` | Public | Inertia registration; MEM-01 |
| GET | `/account/{account}` | `account.show` | `AccountController@show` | Member/self-or-role | Inertia + Filament; MEM-02 |
| GET | `/account/{account}/edit` | `account.edit` | `AccountController@edit` | Member/self-or-role | Inertia + Filament; MEM-02 |
| PUT/PATCH | `/account/{account}` | `account.update` | `AccountController@update` | Member/self-or-role | Inertia + Filament; MEM-02 |
| DELETE | `/account/{account}` | `account.destroy` | `AccountController@destroy` | Member/self-or-role | Inertia + Filament; MEM-02 |
| GET | `/register` | `register` | `AccountController@create` | Public | Inertia canonical registration; MEM-01 |
| GET | `/account/{account}/profile/edit` | `account.profile.edit` | `ProfileController@edit` | Member | Inertia; MEM-03 |
| PUT | `/account/{account}/profile` | `account.profile.update` | `ProfileController@update` | Member | Inertia; MEM-03 |
| PUT | `/account/{account}/alter-subscription` | `account.alter-subscription` | `AccountController@alterSubscription` | Admin | Filament; FIN-05 |
| PUT | `/account/{account}/admin-update` | `account.admin-update` | `AccountController@adminUpdate` | Admin | Filament; MEM-02 |
| PUT | `/account/{account}/rejoin` | `account.rejoin` | `AccountController@rejoin` | Member/self-or-role | Inertia + Filament; MEM-02 |
| GET | `/account/confirm-email/{id}/{hash}` | `account.confirm-email` | `AccountController@confirmEmail` | Member | Fortify signed verification compatibility; MEM-04 |
| GET | `/account/{account}/balance` | `account.balance.index` | `BalanceController@index` | Member/self-or-role | Inertia; FIN-01 |
| POST | `/account/{account}/balance/withdrawal` | `account.balance.withdrawal` | `BalanceController@withdrawal` | Member/self-or-role | Inertia; FIN-02 |
| GET | `/account/{account}/induction` | `account.induction.show` | `MemberInductionController@show` | Member | Inertia; MEM-07 |
| PUT | `/account/{account}/induction` | `account.induction.update` | `MemberInductionController@update` | Member | Inertia; MEM-07 |
| GET | `/member_inductions` | `account.induction.index` | `MemberInductionController@index` | Comms | Filament; MEM-07 |
| PUT | `/member_inductions/{account}` | `account.induction.approve` | `MemberInductionController@approve` | Comms | Filament; MEM-07 |
| GET | `/members` | `members.index` | `MembersController@index` | Public | Inertia; MEM-05 |
| GET | `/members/{member}` | `members.show` | `MembersController@show` | Public | Inertia; MEM-05 |

## Subscriptions, payments, and statements

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/account/{account}/subscription/store` | `account.subscription.store` | `SubscriptionController@store` | Public/provider redirect | Changed to safe callback/redirect; PAY-01/FIN-05 |
| GET | `/account/{account}/subscription/create` | `account.subscription.create` | `SubscriptionController@create` | Member | Inertia + provider; PAY-01/FIN-05 |
| DELETE | `/account/{account}/subscription/{subscription}` | `account.subscription.destroy` | `SubscriptionController@destroy` | Member | Inertia + provider; PAY-01/FIN-05 |
| POST | `/gocardless/webhook` | unnamed | `GoCardlessWebhookController@receive` | Provider HMAC | Supported webhook with replay controls; PAY-01 |
| POST | `/account/{account}/payment` | `account.payment.store` | `PaymentController@store` | Admin | Filament; FIN-03 |
| GET | `/payments` | `payments.index` | `PaymentController@index` | Finance | Filament; FIN-03 |
| PUT/PATCH | `/payments/{payment}` | `payments.update` | `PaymentController@update` | Finance | Filament; FIN-03 |
| DELETE | `/payments/{payment}` | `payments.destroy` | `PaymentController@destroy` | Finance + Member constructor rules | Filament; FIN-03 |
| GET | `/payments/overview` | `payments.overview` | `PaymentOverviewController@index` | Finance | Filament; FIN-03 |
| GET | `/payments/sub-charges` | `payments.sub-charges` | `SubscriptionController@listCharges` | Finance | Filament; FIN-05 |
| POST | `/account/{account}/payment/create` | `account.payment.create` | `PaymentController@create` | Member | Inertia; FIN-01/FIN-03 |
| POST | `/account/{account}/update-sub-payment` | `account.update-sub-payment` | `AccountController@updateSubscriptionAmount` | Member | Inertia; FIN-05 |
| POST | `/account/{account}/update-sub-method` | `account.update-sub-method` | `SubscriptionController@updatePaymentMethod` | Public in route source | Required outcome with corrected authorization; FIN-05 |
| POST | `/account/{account}/payment/stripe` | `account.payment.stripe.store` | `StripePaymentController@store` | Member | Supported provider flow; PAY-03 |
| POST | `/account/{account}/payment/gocardless` | `account.payment.gocardless.create` | `GoCardlessPaymentController@create` | Member | Supported provider flow; PAY-01 |
| POST | `/account/{account}/payment/balance` | `account.payment.balance.create` | `BalancePaymentController@store` | Member | Inertia; FIN-01/FIN-02 |
| POST | `/account/{account}/payment/cash/create` | `account.payment.cash.create` | `CashPaymentController@store` | Admin | Filament; FIN-04 |
| DELETE | `/account/{account}/payment/cash` | `account.payment.cash.destroy` | `CashPaymentController@destroy` | Admin | Filament; FIN-04 |
| GET | `/statement-import/create` | `statement-import.create` | `StatementImportController@create` | Finance | Filament; FIN-06 |
| POST | `/statement-import` | `statement-import.store` | `StatementImportController@store` | Finance | Filament; FIN-06 |
| POST | `/account/payment/migrate-direct-debit` | `account.payment.gocardless-migrate` | `PaymentController@migrateDD` | Member | Supported provider flow; PAY-02/P3 |
| POST | `/paypal-ipn` | unnamed | `PaypalIPNController@receiveNotification` | Provider validation | Supported PayPal webhook; PAY-04/P3 |

## Equipment, training, notifications, and key fobs

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| POST | `/equipment_training/update` | `equipment_training.update` | `InductionController@update` | Admin | Investigate broken parameter contract, then Filament; EQUIP-01 |
| PUT/PATCH | `/account/{account}/induction/{induction}` | `account.induction.update` | `InductionController@update` | Admin | Filament; EQUIP-01 |
| DELETE | `/account/{account}/induction/{induction}` | `account.induction.destroy` | `InductionController@destroy` | Admin | Filament; EQUIP-01 |
| GET | `/equipment` | `equipment.index` | `EquipmentController@index` | Member | Inertia; EQUIP-01 |
| GET | `/equipment/create` | `equipment.create` | `EquipmentController@create` | Member + Equipment | Filament; EQUIP-01 |
| POST | `/equipment` | `equipment.store` | `EquipmentController@store` | Member + Equipment | Filament; EQUIP-01 |
| GET | `/equipment/{equipment}` | `equipment.show` | `EquipmentController@show` | Member | Inertia; EQUIP-01 |
| GET | `/equipment/{equipment}/edit` | `equipment.edit` | `EquipmentController@edit` | Member + Equipment | Filament; EQUIP-01 |
| PUT/PATCH | `/equipment/{equipment}` | `equipment.update` | `EquipmentController@update` | Member + Equipment | Filament; EQUIP-01 |
| DELETE | `/equipment/{equipment}` | `equipment.destroy` | `EquipmentController@destroy` | Member + Equipment | Filament; EQUIP-01 |
| POST | `/equipment/{id}/photo` | `equipment.photo.store` | `EquipmentController@addPhoto` | Member route; controller coverage ambiguous | Authorized Inertia/Filament upload; EQUIP-01 |
| DELETE | `/equipment/{id}/photo/{key}` | `equipment.photo.destroy` | `EquipmentController@destroyPhoto` | Member route; controller coverage ambiguous | Authorized Inertia/Filament removal; EQUIP-01 |
| POST | `/equipment/log/{logId}` | `equipment_log.update` | `EquipmentLogController@update` | Member/internal checks | Inertia + Filament; EQUIP-02 |
| GET | `/notifications` | `notifications.index` | `NotificationController@index` | Declared Member resource option | Inertia; COMM-01 |
| PUT/PATCH | `/notifications/{notification}` | `notifications.update` | `NotificationController@update` | Declared Member resource option | Inertia; COMM-01 |
| GET | `/keyfob` | `keyfob.index` | `KeyFobController@index` | Admin | Filament; ACCESS-01 |
| POST | `/keyfob` | `keyfob.store` | `KeyFobController@store` | Admin | Filament; ACCESS-01 |
| PUT/PATCH | `/keyfob/{keyfob}` | `keyfob.update` | `KeyFobController@update` | Admin | Filament; ACCESS-01 |
| DELETE | `/keyfob/{keyfob}` | `keyfob.destroy` | `KeyFobController@destroy` | Admin | Filament with history safeguards; ACCESS-01 |

## Device/access compatibility and administration

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| POST | `/access-control/main-door` | unnamed | `AccessControlController@mainDoor` | Public/device | Preserve captured contract behind compatibility auth; ACCESS-02 |
| POST | `/access-control/status` | unnamed | `AccessControlController@status` | Public/device | Confirm active use, then preserve/version; ACCESS-02 |
| GET | `/access-control/status` | unnamed | `AccessControlController@status` | Public/device | Confirm active use, then preserve/version; ACCESS-02 |
| POST | `/access-control/device` | unnamed | `DeviceAccessControlController@device` | Public/device | Preserve captured contract behind compatibility auth; ACCESS-02/ACCESS-04 |
| POST | `/acs` | unnamed | `ACSController@store` | Public/device | Compatibility adapter with verified authentication/replay; ACCESS-05 |
| POST | `/acs/spark` | unnamed | `ACSSparkController@handle` | Public/device | Compatibility adapter; ACCESS-05 |
| POST | `/camera/event/store` | unnamed | `CCTVController@store` | Public/device | Authenticated supported media ingestion; ACCESS-07/P3 |
| POST | `/camera/store` | unnamed | `CCTVController@storeSingle` | Public/device | Authenticated supported media ingestion; ACCESS-07/P3 |
| GET | `/detected_devices` | `detected_devices.index` | `DetectedDevicesController@index` | Admin | Filament; ACCESS-06 |
| GET | `/detected_devices/create` | `detected_devices.create` | missing controller method | Admin | Investigate advertised-but-missing behavior; ACCESS-06 |
| POST | `/detected_devices` | `detected_devices.store` | missing controller method | Admin | Investigate advertised-but-missing behavior; ACCESS-06 |
| GET | `/detected_devices/{detected_device}` | `detected_devices.show` | `DetectedDevicesController@show` stub | Admin | Investigate stub behavior; ACCESS-06 |
| GET | `/detected_devices/{detected_device}/edit` | `detected_devices.edit` | `DetectedDevicesController@edit` stub | Admin | Investigate stub behavior; ACCESS-06 |
| PUT/PATCH | `/detected_devices/{detected_device}` | `detected_devices.update` | `DetectedDevicesController@update` stub | Admin | Investigate stub behavior; ACCESS-06 |
| DELETE | `/detected_devices/{detected_device}` | `detected_devices.destroy` | `DetectedDevicesController@destroy` stub | Admin | Investigate stub behavior; ACCESS-06 |
| GET | `/devices` | `devices.index` | `DeviceController@index` | ACS role | Filament; ACCESS-06 |
| GET | `/devices/create` | `devices.create` | `DeviceController@create` | ACS role | Filament; ACCESS-06 |
| POST | `/devices` | `devices.store` | `DeviceController@store` | ACS role | Filament; ACCESS-06 |
| GET | `/devices/{device}` | `devices.show` | `DeviceController@show` | ACS role | Filament; ACCESS-06 |
| GET | `/devices/{device}/edit` | `devices.edit` | `DeviceController@edit` | ACS role | Filament; ACCESS-06 |
| PUT/PATCH | `/devices/{device}` | `devices.update` | `DeviceController@update` | ACS role | Filament; ACCESS-06 |
| DELETE | `/devices/{device}` | `devices.destroy` | `DeviceController@destroy` | ACS role | Filament with history/credential safeguards; ACCESS-06 |

## Newer ACS node API

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/acs/test` | unnamed | `ACS\TestController@index` | API key | Confirm diagnostic contract and restrict/remove by approved replacement; ACCESS-03 |
| GET | `/acs/status/{tagId}` | unnamed | `ACS\StatusController@show` | API key | Preserve/version device contract; ACCESS-03 |
| POST | `/acs/node/boot` | unnamed | `ACS\NodeController@boot` | API key | Preserve/version device contract; ACCESS-03 |
| POST | `/acs/node/heartbeat` | unnamed | `ACS\NodeController@heartbeat` | API key | Preserve/version device contract; ACCESS-03 |
| POST | `/acs/activity` | unnamed | `ACS\ActivityController@store` | API key | Preserve/version with node/device authorization; ACCESS-04 |
| PUT | `/acs/activity/{sessionId}` | unnamed | `ACS\ActivityController@update` | API key | Preserve/version with session ownership; ACCESS-04 |
| DELETE | `/acs/activity/{sessionId}` | unnamed | `ACS\ActivityController@destroy` | API key | Preserve/version with session ownership; ACCESS-04 |

## Activity, storage, statistics, and communication

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/activity` | `activity.index` | `ActivityController@index` | Member | Inertia; REPORT-02 |
| GET | `/activity/realtime` | `activity.realtime` | `ActivityController@realtime` | Member | Inertia + Reverb/Echo after activity audience/payload approval; REPORT-02/AUTH-02 |
| POST | `/activity` | `activity.create` | `ActivityController@create` | Member | Authorized Inertia mutation; REPORT-02 |
| GET | `/storage_boxes` | `storage_boxes.index` | `StorageBoxController@index` | Member | Inertia; SPACE-01 |
| PUT | `/storage_boxes/{id}` | `storage_boxes.update` | `StorageBoxController@update` | Member/internal rules | Inertia + Filament with explicit policy; SPACE-01 |
| GET | `/stats` | `stats.index` | `StatsController@index` | Member | Approved-audience Inertia/Filament report; REPORT-01 |
| GET | `/stats/gocardless` | `stats.gocardless` | `StatsController@ddSwitch` | Member | Approved-audience report; REPORT-01/PAY-02 |
| GET | `/notification_email/create` | `notificationemail.create` | `NotificationEmailController@create` | Member/internal role checks | Filament; COMM-02 |
| POST | `/notification_email` | `notificationemail.store` | `NotificationEmailController@store` | Member/internal role checks | Filament with explicit audience policy; COMM-02 |
| POST | `/feedback` | `feedback.store` | `FeedbackController@store` | Member | Inertia; COMM-03 |

## Proposals, roles, groups, and resources

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/proposals` | `proposals.index` | `ProposalController@index` | Member | Inertia; GOV-01 |
| GET | `/proposals/create` | `proposals.create` | `ProposalController@create` | Admin | Filament or authorized Inertia; GOV-01 |
| POST | `/proposals` | `proposals.store` | `ProposalController@store` | Admin | Filament or authorized Inertia; GOV-01 |
| GET | `/proposals/{id}` | `proposals.show` | `ProposalController@show` | Member | Inertia; GOV-01 |
| POST | `/proposals/{id}` | `proposals.vote` | `ProposalController@vote` | Member | Inertia with approved voting boundary; GOV-01 |
| GET | `/proposals/{id}/edit` | `proposals.edit` | `ProposalController@edit` | Admin | Filament or authorized Inertia; GOV-01 |
| POST | `/proposals/{id}/update` | `proposals.update` | `ProposalController@update` | Admin | Changed to conventional protected mutation; GOV-01 |
| GET | `/roles` | `roles.index` | `RolesController@index` | Admin | Spatie + Filament; AUTHZ-01 |
| GET | `/roles/create` | `roles.create` | `RolesController@create` | Admin | Spatie + Filament; AUTHZ-01 |
| POST | `/roles` | `roles.store` | `RolesController@store` | Admin | Spatie + Filament; AUTHZ-01 |
| GET | `/roles/{role}` | `roles.show` | `RolesController@show` | Admin | Spatie + Filament; AUTHZ-01 |
| GET | `/roles/{role}/edit` | `roles.edit` | `RolesController@edit` | Admin | Spatie + Filament; AUTHZ-01 |
| PUT/PATCH | `/roles/{role}` | `roles.update` | `RolesController@update` | Admin | Spatie + Filament; AUTHZ-01 |
| DELETE | `/roles/{role}` | `roles.destroy` | `RolesController@destroy` | Admin | Spatie + Filament with assignment safeguards; AUTHZ-01 |
| POST | `/roles/{role}/users` | `roles.users.store` | `RoleUsersController@store` | Admin | Spatie + Filament; AUTHZ-01 |
| DELETE | `/roles/{role}/users/{user}` | `roles.users.destroy` | `RoleUsersController@destroy` | Admin | Spatie + Filament; AUTHZ-01 |
| GET | `/groups` | `groups.index` | `GroupsController@index` | Member | Inertia business groups; MEM-06 |
| GET | `/groups/{group}` | `groups.show` | `GroupsController@show` | Member | Inertia business groups; MEM-06 |
| GET | `/resources` | `resources.index` | `ResourcesController@index` | Member | Inertia; CONTENT-01 |
| GET | `/resources/policy/{title}` | `resources.policy.view` | `ResourcesController@viewPolicy` | Public | Inertia/public content; CONTENT-01 |

## Expenses

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| GET | `/expenses` | `expenses.index` | `ExpensesController@index` | Member | Inertia + Filament; FIN-07 |
| GET | `/expenses/create` | `expenses.create` | `ExpensesController@create` | Member | Inertia; FIN-07 |
| POST | `/expenses` | `expenses.store` | `ExpensesController@store` | Member | Inertia; FIN-07 |
| GET | `/expenses/{expense}` | `expenses.show` | `ExpensesController@show` | Member | Inertia with ownership/privacy policy; FIN-07 |
| GET | `/expenses/{expense}/edit` | `expenses.edit` | `ExpensesController@edit` stub | Member | Investigate stub behavior; FIN-07 |
| PUT/PATCH | `/expenses/{expense}` | `expenses.update` | `ExpensesController@update` | Member + internal Admin | Filament approval; FIN-07 |
| DELETE | `/expenses/{expense}` | `expenses.destroy` | `ExpensesController@destroy` stub | Member | Investigate stub behavior; FIN-07 |

## Settings, logs, and API documentation

| Method | Path | Name | Action | Audience | Target / feature |
| --- | --- | --- | --- | --- | --- |
| POST | `/settings` | `settings.update` | `SettingsController@update` | Public in route source | Filament with explicit permission; ADMIN-01 |
| GET | `/logs` | `logs` | legacy package controller | Admin | Secure observability replacement; ADMIN-03/P3 |
| ANY | `/api-docs.json` | unnamed | closure serves generated JSON | Public | Validated OpenAPI endpoint; ADMIN-02/P3 |
| GET | `/api-docs` | unnamed | closure scans/generates Swagger UI | Public | Supported authenticated/approved-audience OpenAPI UI; ADMIN-02/P3 |

## Route conflicts and security-critical evidence

- `account.induction.update` is registered twice; the nested equipment-induction resource is registered later and wins named-route lookup (`routes.php`).
- `/equipment_training/update` cannot supply both arguments expected by `InductionController::update()`, while nested destroy supplies two route parameters to a one-argument action (`routes.php`; `InductionController`).
- Full `detected_devices` and `expenses` resources advertise missing or stub actions (`routes.php`; named controllers). These routes need production-use evidence before defining their successful replacement behavior.
- `/logout` and the GoCardless subscription completion route are state-changing GETs (`routes.php`).
- `/settings` and `/account/{account}/update-sub-method` have no declared route authorization; equipment photo custom actions may bypass the controller's resource-action role filter (`routes.php`; controller constructors).
- Device/provider compatibility routes are intentionally reachable outside browser member middleware, but the replacement requires explicit signature/API-key, replay, rate-limit, network, and authorization contracts (`routes.php`; `docs/parity/integrations.md`).
- Commented routes are not active parity routes: balance transfer `POST /account/{account}/balance/transfer` and legacy `GET /acs` (`routes.php`).

## Completion checklist for the route portion of NEW-003

- [x] Expand every active candidate-source resource declaration into concrete method/path/name/action rows.
- [x] Record source-visible audiences and map every active route to a required feature and target boundary.
- [x] Record source-visible authentication/authorization and response families for every row through the matching-route register.
- [x] Record route-name collisions, advertised stub/missing actions, unsafe verbs, and authorization gaps.
- [ ] Confirm the deployed revision and reconcile production route output against this ledger.
- [ ] Capture detailed validation, redirect location, session, error, header, byte-framing, and side-effect fixtures for every active route.
- [ ] Confirm which browser URLs require direct preservation versus permanent redirects.
- [ ] Name feature/domain owners and approve each deliberate route difference.
- [ ] Add a contract/feature/browser test reference for every retained or changed route before cutover.
