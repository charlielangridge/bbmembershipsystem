# Legacy Status and Transition Parity Ledger

Status: candidate-source inventory complete; production values and business rules unconfirmed
Evidence date: 18 August 2026
Evidence revision: legacy `master` at `d686bf6`

## Purpose

This ledger separates the state machines visible in the candidate legacy source. It records observed values and transitions; it does not approve contradictory legacy states or defects as the Laravel 13 design. `NEW-001` and a production-safe distinct-value/count report must reconcile these values before enum constraints or data migrations are implemented.

## State families

The legacy application uses several independent concepts that must remain distinct:

- membership lifecycle (`users.status`) and account enablement (`users.active`);
- payment settlement (`payments.status`);
- subscription obligation (`subscription_charge.status`);
- equipment-session progress/disposition (`active`, `finished`, `removed`, `billed`, `processed`);
- proposal voting window and result processing;
- access/device availability derived from fob, equipment, node, and heartbeat fields.

## Membership lifecycle

### Values

| Value | Source-visible meaning | Important coupled data |
| --- | --- | --- |
| `setting-up` | Default for a newly registered account; an unactivated account can be hard-deleted | Defaults to `active=false`; payment setup can activate it |
| `active` | Ordinary billable membership | Normally `active=true`; subscription expiry and payment method drive scheduled checks |
| `payment-warning` | Intended warning state before membership loss | No source-visible writer was found; observer sends warning email on entry |
| `suspended` | Membership disabled for non-payment | `setSuspended()` also sets `active=false`; observer sends email and Discord notification |
| `leaving` | Cancellation requested while existing membership runs to expiry | Legacy code leaves `active` unchanged; scheduled check later completes departure |
| `on-hold` | Displayed legacy state with no explicit transition implementation | Relationship to `active`, billing, access, and expiry is unproven |
| `left` | Inactive historical member | `memberLeft()` sets `active=false`, clears payment day, and cancels pending/due charges; rejoin remains possible |
| `honorary` | Special membership excluded from ordinary billing | Can see protected member photos; activation/access rules remain unproven |

`active`, `banned`, `trusted`, `key_holder`, `email_verified`, and `induction_completed` are separate flags, not lifecycle statuses. Production data may contain contradictory combinations.

### Observed transitions

| From | Trigger | To | Side effects / evidence |
| --- | --- | --- | --- |
| none | Register account | `setting-up` | Creates user/profile/address; defaults `active=false` |
| `setting-up` or another state | Establish supported payment method / extend covered membership | `active` | Sets `active=true`, sets expiry, and may create an initial due charge |
| `active` | Expired beyond payment-method grace with no later coverage | `suspended` | Sets `active=false`; sends suspended email/Discord notification |
| `suspended` | Later payment coverage found | `active` | Extends expiry and sets `active=true` |
| `suspended` | Expired with no later coverage | `left` | Cancels pending/due charges and sends left email/Discord notification |
| `payment-warning` | Expired with no adequate payment | `left` | Same departure side effects; entry into `payment-warning` is not implemented in visible source |
| non-setup member | Cancel membership/account | `leaving` | Some paths clear payment identifiers; member remains enabled until scheduled expiry handling |
| `leaving` | Subscription expiry passes | `left` | Disables account and cancels pending/due charges |
| `left` | Rejoin action | `setting-up` | Only status is changed; account remains inactive until payment activation |
| `setting-up` | Delete account | deleted | Hard-deletes the never-activated account rather than retaining `left` history |

Any transition away from `setting-up` triggers the legacy welcome email/Discord “joined” notification, even when the destination is not `active`. Entry into `payment-warning`, `suspended`, and `left` triggers lifecycle emails; suspended/left also trigger Discord messages.

### Membership decisions required

- Define one authoritative relationship between lifecycle status and account/access enablement; reject impossible combinations at write boundaries.
- Decide whether `payment-warning` is a real stage, its duration, and its entry/exit transitions.
- Define `on-hold` and `honorary` billing, access, expiry, communication, and rejoin rules.
- Confirm whether `leaving` members retain door/equipment/member access until expiry.
- Limit the welcome/joined side effect to an approved transition rather than every exit from `setting-up`.
- Decide whether an unactivated account may be hard-deleted and what audit/retention rules apply.

## Payment settlement

### Observed values

| Value | Evidence / behaviour |
| --- | --- |
| `pending` | Model default and normalized replacement for provider `pending_submission` |
| `pending_submission` | Displayed by the presenter and received from providers; some creation paths normalize it, but stored occurrence must be checked |
| `paid` | Sets `paid_at`; emits settlement side effects and can settle a linked subscription charge |
| `failed` | Default failure value; provider-specific failure values can also be stored |
| `cancelled` | Documented cancellation/failure value |
| `refunded` | Recognized in provider handling, but refund mutation is left as a TODO |
| `withdrawn` | Treated as paid by presentation and included in balance/payment queries; exact business meaning is unproven |

The database column is an unconstrained string and legacy provider statuses can be persisted without validation. Production distinct values are therefore required before defining a replacement enum.

### Observed transitions

- Creation can start at `pending`, `paid`, or an arbitrary caller/provider value.
- Submitted webhook: existing payment → `pending`.
- Paid webhook/manual settlement: existing payment → `paid`, records `paid_at`, and emits settlement events.
- Failure/cancellation webhook: existing payment → provider-supplied failure value and returns a linked processing charge to due.
- Resubmission can move the linked subscription charge back to processing; payment-state mutation depends on later provider events.
- Refund notification is detected but not implemented.

### Payment decisions required

- Map every production/provider value into an approved internal lifecycle while retaining the raw provider event separately.
- Define legal transitions, terminality, `paid_at` invariants, refund/withdrawal semantics, and correction/reversal rules.
- Define out-of-order, duplicate, replay, partial-payment, and manual-adjustment behaviour atomically with linked charges and balances.

## Subscription-charge lifecycle

The schema enum defines `pending`, `due`, `processing`, `paid`, and `cancelled`.

| From | Trigger | To | Side effects / caveats |
| --- | --- | --- | --- |
| none | Generate future charge | `pending` | Charge uniqueness is checked by member/date in application code |
| `pending` | Charge date becomes billable | `due` | Daily billing workflow selects due charges |
| `due` | Payment initiated/recorded pending | `processing` | Legacy event handling can activate/extend membership before funds settle |
| `pending`, `due`, or `processing` | Successful payment | `paid` | Stores amount/payment date and extends membership |
| `processing` | Payment failure | `due` | Clears payment date and resets amount to zero unless already cancelled |
| `pending` or `due` | Membership becomes left | `cancelled` | Bulk cancellation does not include processing charges |

The repository does not generally enforce legal transitions; a cancelled charge could be marked paid through public methods. `paid` and `cancelled` appear terminal by intent, not enforcement.

Required decisions: whether processing funds grant membership, terminal-state rules, retry prevention, cancellation of processing charges, amount-reset semantics, and transaction boundaries between payments, charges, membership expiry, and balance recalculation.

## Equipment-session lifecycle

Equipment sessions use coupled fields rather than a single status:

| Derived state | Field conditions | Transition evidence |
| --- | --- | --- |
| Open | `active=true`, `started` set, `finished=null` | Start closes an existing session for the device, then opens a new one |
| Heartbeating | Open with advancing `last_update` | Activity update rejects an already-finished session |
| Finished | `active=false`, `finished` set | Explicit end or hourly stale-session repair |
| Removed | `removed=true` | Hourly repair ignores sessions of 60 seconds or less; this is exclusion, not deletion |
| Billable | Finished, not removed, not billed | Equipment-fee workflow applies a 15-minute minimum unless reason rules exempt it |
| Billed | `billed=true` | A fee/balance payment was generated or the reason was treated as non-chargeable |
| Processed (legacy) | `processed=true` | Present in schema/sample data, but current repair code no longer writes it |

Required decisions: one-session-per-device concurrency, inaccurate/synthetic start/end handling, stale threshold, 60-second removal boundary, 15-minute minimum, reason exemptions, correction permissions, rerun idempotency, and whether `processed` is migrated or derived.

## Proposal and vote lifecycle

Proposal state is derived from dates plus `processed`:

| Derived state | Condition | Allowed/observed behaviour |
| --- | --- | --- |
| Scheduled | Today precedes `start_date` | Admin may edit; voting closed |
| Open | Started and not finished | Eligible member may create or replace one vote |
| Finished, unprocessed | End boundary passed and `processed=false` | Hourly command calculates result |
| Processed | `processed=true` | Tallies/result stored; intended terminal result state |

Vote choices are `+1`, `0`, `-1`, or abstention. The result is votes-for minus votes-against; neutral votes count as cast, abstentions do not; quorum is hard-coded `true`.

The legacy finish calculation and tally query use different date boundaries, so a proposal may be tallied while the UI/controller still permits voting. There is also no database unique constraint for one vote per member/proposal.

Required decisions: exact inclusive/exclusive voting window and timezone, post-processing lock, vote uniqueness/concurrency, eligibility, neutral-versus-abstain semantics, quorum calculation, tie/result rules, recount/correction, and audit visibility.

## Access and device state

- Key fob: usable lookup requires `active=true`; marking lost sets `lost=true` and `active=false`. Reactivation/replacement rules are not explicit.
- ACS node: online/offline is derived rather than stored; a monitored node warns after more than one hour without a heartbeat, and notifications deduplicate by device plus last-heartbeat timestamp.
- Equipment availability: `working`, `archive`, `requires_induction`, credit, member status/active flag, trust/key-holder/photo state, and roles are separate access inputs.
- Access attempt: source-visible result codes include `200` success and `402` denial; `delayed` marks historically/offline-submitted events.
- Detected device: registry data has no lifecycle state.

Required decisions: authoritative access-decision inputs, denial reason contract, HTTP/domain-code separation, lost-fob recovery, archive versus not-working semantics, node offline/recovery rules, null heartbeat, notification recovery/deduplication, and delayed-attempt handling.

## Evidence consulted

- Legacy entities, repositories, processes, handlers, listeners, observers, presenters, controllers, services, commands, migrations, dump schema/sample rows, and tests at revision `d686bf6`.
- `docs/parity/features.md` and `docs/parity/commands.md` for approved scope and schedule ordering.

## Completion checklist for the status portion of NEW-003

- [x] Inventory candidate-source member, payment, charge, equipment-session, proposal/vote, fob, node, and access states.
- [x] Record observed transitions, triggers, side effects, terminal intent, and contradictions.
- [ ] Reconcile every production distinct value and invalid combination against the deployed baseline.
- [ ] Name membership, finance, physical-access, operations, and client/product sign-off owners.
- [ ] Approve legal transition matrices and invariants for the Laravel 13 domain model.
- [ ] Add production-shaped transition, boundary, concurrency, and reconciliation fixtures.
