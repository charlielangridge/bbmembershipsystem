---
paths:
  - 'app/Providers/Filament/**|app/Http/Middleware/AuthenticateFilament.php|app/Filament/**|app/Models/User.php|app/Policies/UserPolicy.php|tests/Feature/Filament*'
---

# Policies Feature

## Keep Filament administration fail-closed and read-only by default
The admin panel uses the web guard with strict authorization. Panel entry requires a verified user with admin-panel.access through canAccessPanel(); guests and visible logout actions use Fortify routes. Member browsing additionally requires members.manage through UserPolicy. New Filament resources must begin with list/view only and add mutations only with an approved policy-tested workflow.
