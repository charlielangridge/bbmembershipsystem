# Legacy Command and Schedule Parity Ledger

Status: candidate-source inventory complete; production runtime and owners unconfirmed
Evidence date: 18 August 2026
Evidence revision: legacy `master` at `d686bf6`

## Purpose

This ledger records every command registered and scheduled by the candidate legacy revision. Every row is required under the feature-parity decision. `NEW-001` must identify the deployed revision, and production discovery must confirm that its scheduler is active before this becomes production evidence.

## Schedule assumptions

- The legacy application configuration declares `UTC`; every time below is therefore interpreted as UTC until production configuration is confirmed.
- The legacy schedule declares no `withoutOverlapping()`, `onOneServer()`, failure callback, retry policy, or explicit maintenance-mode behaviour.
- Each legacy job sends a success heartbeat to a hard-coded Envoyer URL only when `APP_ENV` defaults or resolves to `production`. The replacement must use configured monitoring without copying those URLs.
- “Idempotency evidence” describes only visible source safeguards. Every command still needs repeat-run and partial-failure tests against production-shaped fixtures.

## Command inventory

| ID | Command | Legacy schedule | Inputs | Business outputs and side effects | Idempotency/recovery evidence | Proposed priority | Owner |
| --- | --- | --- | --- | --- | --- | --- | --- |
| CMD-01 | `bb:create-todays-sub-charges {dayOffset=7}` | Daily at 01:00 UTC | Optional day offset; current clock; subscriptions | Creates subscription charge records for the target date and each of the preceding seven dates | Deliberately revisits an eight-day window, but uniqueness/duplicate prevention lives below the command and must be proven | P0 | Finance + operations |
| CMD-02 | `bb:bill-members` | Daily at 01:30 UTC | Due/pending subscription charges | Moves pending charges to due, then bills members | Two-phase mutation with no visible transaction, lock, or checkpoint at the command boundary; repeat and mid-run recovery behaviour must be characterised | P0 | Finance + operations |
| CMD-03 | `bb:calculate-equipment-fees` | Daily at 02:00 UTC | Pending equipment usage logs | Creates fees for equipment usage | Delegates to `EquipmentCharge::calculatePendingFees()`; processed markers and duplicate-charge protection must be characterised | P1 | Finance + physical access |
| CMD-04 | `bb:update-balances` | Daily at 03:00 UTC | Every user and their financial records | Recalculates and persists each member balance | Recalculation suggests convergence, but the loop has no transaction/checkpoint and may stop after a partial population | P0 | Finance + operations |
| CMD-05 | `bb:check-memberships` | Daily at 06:00 UTC | Members in payment-warning, suspended, leaving, and subscription-check populations | Advances membership states and triggers associated lifecycle communications | Runs four ordered processes, with subscription checks deliberately last; transition and notification deduplication must be proven for retries | P0 | Membership + finance + operations |
| CMD-06 | `bb:calculate-proposal-votes` | Hourly | Finished proposals not marked processed; proposal votes | Persists vote totals, result, votes cast, abstentions, and a hard-coded quorum result | Selects “unprocessed finished” proposals, implying a processed guard; atomic result persistence and concurrent-run behaviour must be proven | P2 | Client/product + operations |
| CMD-07 | `bb:fix-equipment-log` | Hourly | Active and unbilled equipment logs; current clock | Closes stale sessions, combines adjacent logs, and marks sessions of 60 seconds or less as removed | Reprocesses unbilled records; combination, stale-close, and removed markers need repeat/concurrent-run tests | P1 | Physical access + finance + operations |
| CMD-08 | `device:check-online` | Every ten minutes | ACS nodes and their last heartbeat | Creates in-app warnings for ACS-role users when a device heartbeat is stale | Notification hash combines device ID with last-heartbeat timestamp, providing visible deduplication for an unchanged outage | P1 | Physical access + operations |

## Replacement requirements

- Preserve the command outcomes and relative billing order unless business owners approve a documented change.
- Define explicit overlap, single-server, transaction, retry, timeout, and failure-alert behaviour for every replacement schedule.
- Use timezone-aware tests around midnight, daylight-saving changes in operator-facing dates, and the charge look-ahead window even though the scheduler itself is currently UTC.
- Reconcile created charges, billed payments, balances, membership transitions, equipment fees, proposal results, repaired sessions, and device notifications before and after migration.
- Keep each command manually invokable with safe diagnostics and a non-zero exit status on failure.

## Evidence consulted

- `app/Console/Kernel.php` at legacy revision `d686bf6`.
- All eight classes under `app/Console/Commands/` at that revision.
- `config/app.php` at that revision for the declared `UTC` timezone.

## Completion checklist for the command portion of NEW-003

- [x] Inventory every command registered by the candidate legacy source revision.
- [x] Record source-visible schedules, inputs, outputs, and idempotency evidence.
- [ ] Confirm the deployed revision and production scheduler configuration.
- [ ] Confirm the production timezone and whether more than one scheduler instance runs.
- [ ] Name accountable business and operations owners.
- [ ] Characterise repeat, overlap, partial-failure, and recovery behaviour with fixtures.
- [ ] Approve replacement schedules and monitoring.
