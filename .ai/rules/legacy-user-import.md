---
paths:
  - 'app/Actions/Legacy/**|app/Console/Commands/Legacy*|tests/Feature/Legacy*'
---

# Legacy user import

## Preserve compatible legacy password hashes

Legacy account migration must retain compatible bcrypt strings verbatim; never pass an existing hash to Hash::make(). Assess hashes against the configured bcrypt driver first. Laravel/Fortify may rehash a lower-cost compatible hash only after a successful plaintext login.

## Map legacy identities deterministically
Preserve each compatible legacy user ID, bcrypt hash, created_at, and updated_at. Normalize the combined name and lowercase/trim email. Clear legacy remember_token values so cutover requires a fresh authenticated session.

## Use the explicit verification cutover time
The write import requires an ISO 8601 --verified-at value. Convert it to UTC and assign it only to legacy email_verified=1 accounts; map email_verified=0 to null and block unsupported values.

## Prepare the production identity cutover
Before the production cutover run, freeze legacy writes and rerun the dry-run against that frozen snapshot. The production runbook must reconcile user counts and verify or reset the canonical users primary-key sequence after the target database engine is selected.
