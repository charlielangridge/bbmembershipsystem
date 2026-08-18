# Handoff: Laravel 13 modernisation

## Resume point

Continue the Laravel 13 implementation plan from the parity-discovery checkpoint. The repository is on branch `modernisation/laravel-13`; the worktree was clean at handoff. The latest commits are:

- `e25b867` — correct parity source evidence
- `7d713c0` — complete parity contract fields

## Immediate next decision

Integration discovery added two required capabilities that were absent from the initial feature set:

- `OPS-03` — production error reporting and alerting, currently `Proposed P1`.
- `ANALYTICS-01` — usage analytics and reporting telemetry, currently `Proposed P2`.

Their required status is agreed, but their exact priorities still need product-owner approval. Ask whether to approve `OPS-03` as P1 and `ANALYTICS-01` as P2. If approved, update the feature ledger, completion checklist, plan status, and `.ai/rules/parity.md` consistently, then commit the decision.

## Current decisions and constraints

- Complete feature parity is required before production cutover, including P3.
- The final tranche is implemented last: historical direct-debit migration, PayPal/IPN donations, CCTV capture, Discord notifications, API documentation, and secure log viewing.
- Member/public journeys use Inertia; privileged administration uses Filament; authentication uses Fortify.
- Spatie Laravel Permission is the intended authorization model.
- The candidate legacy source revision is `d686bf6`; it is not confirmed as the deployed production revision.
- No credentials, tokens, webhook values, or other secret values are recorded in the repository documentation.

## Evidence completed

The candidate-source parity ledgers are complete for their source-visible inventory:

- [Feature ledger](parity/features.md): 49 required capabilities, including the two proposed-priority discoveries.
- [Route ledger](parity/routes.md): 145 concrete active method/path rows, with exhaustive authentication and response-family coverage.
- [Command ledger](parity/commands.md): eight registered/scheduled commands and post-run callbacks.
- [Status ledger](parity/statuses.md): source-observed state families and transitions with per-fact references.
- [Integration ledger](parity/integrations.md): 23 integrations, including package/protocol versions and application/external endpoint fields.

Production reconciliation remains open: deployed revision, live routes/configuration, provider/device dashboards, production-shaped data, named domain owners, and redacted contract fixtures.

## Review state

The Standards and Spec re-reviews are clean through `e25b867`. The last corrections aligned the Gravatar citation with `UserImage::gravatar()` and documented the GoCardless webhook response accurately (`Success`/HTTP 200 accepted; empty HTTP 403 invalid signature).

## Reference documents

- [Laravel 13 implementation plan](../LARAVEL_13_IMPLEMENTATION_PLAN.md)
- [Modernisation master plan](../MODERNISATION_MASTERPLAN.md)
- [Parity ADR: require complete parity before cutover](adr/0003-require-full-parity-before-cutover.md)
- [Project context/glossary](../CONTEXT.md)
- [Parity rule](../.ai/rules/parity.md)

## Suggested skills for the next session

- `grill-with-docs` — resolve the two proposed priorities and record the decision.
- `implement` — continue the implementation plan after the decision is recorded.
- `code-review` — run Standards and Spec review after each documentation or implementation slice.
- `laravel-best-practices` — use for Laravel PHP changes.
- `fortify-development` — use for authentication work.
- `inertia-vue-development` and `wayfinder-development` — use for member-facing Inertia route/UI work.
- `domain-modeling` — use when changing the glossary or ADRs.

