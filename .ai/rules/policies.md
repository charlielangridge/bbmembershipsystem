---
paths:
  - 'app/Filament/**|app/Models/User.php|app/Policies/**|tests/**'
---

# Policies

## Use Spatie permissions and Filament administration
Spatie Laravel Permission is the authoritative role/permission store; enforce abilities through Laravel policies. Filament is limited to privileged administration, shares the User model and web guard with Fortify member flows, and requires explicit panel permission via canAccessPanel(). Keep Livewire inside the administration boundary; member-facing journeys remain Inertia Vue.
