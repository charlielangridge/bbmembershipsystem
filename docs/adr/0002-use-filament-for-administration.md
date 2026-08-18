---
status: accepted
---

# Use Filament for administration

The Laravel 13 application will use the Filament panel builder for privileged administration workflows, while the Inertia Vue application remains the member-facing experience. Filament was selected to deliver auditable administrative tables, forms, actions, and dashboards quickly without rebuilding generic back-office UI in Vue.

## Consequences

- Target the current Laravel 13-compatible Filament major (`^5.0` when this decision was recorded) and recheck compatibility when installation begins.
- Filament and the member application share the same `User` model and `web` guard; Fortify remains the member authentication backend, and there is no second administrator account system.
- Filament's Livewire runtime remains inside the administration boundary and does not replace or mix into the Inertia Vue member application.
- Panel entry requires an explicit Spatie permission from the approved matrix through `canAccessPanel()`.
- Laravel policies authorize Filament resources and actions. Hiding an action in Filament is presentation, not authorization.
- Filament is limited to privileged administration. Member self-service, public pages, and ordinary member journeys remain Inertia Vue.
