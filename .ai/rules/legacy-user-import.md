---
paths:
  - 'app/Actions/Legacy/**|app/Console/Commands/Legacy*|tests/Feature/Legacy*'
---

# Legacy user import

## Preserve compatible legacy password hashes

Legacy account migration must retain compatible bcrypt strings verbatim; never pass an existing hash to Hash::make(). Assess hashes against the configured bcrypt driver first. Laravel/Fortify may rehash a lower-cost compatible hash only after a successful plaintext login.
