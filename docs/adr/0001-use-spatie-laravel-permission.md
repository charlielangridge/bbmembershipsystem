---
status: accepted
---

# Use Spatie Laravel Permission

The Laravel 13 application will use `spatie/laravel-permission` as the authoritative store and API for user roles and permissions because the legacy role checks are scattered across middleware, controllers, models, and queries. Legacy roles and assignments will be imported through an explicit, reconciled migration; application authorization will continue to flow through Laravel gates and policies rather than package checks embedded throughout business code.

## Consequences

- Target the current Laravel 13-compatible package major (`^8.0` when this decision was recorded) and recheck compatibility when installation begins.
- Define the approved permission matrix before importing legacy roles or exposing privileged actions.
- Treat the package's role and permission schema as authoritative in the new application; do not create a second role store.
- Assign permissions through roles by default. Any direct user permission requires an approved, tested exception.
