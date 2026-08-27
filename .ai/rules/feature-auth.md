---
paths:
  - 'app/Http/**|app/Models/User.php|config/auth.php|config/fortify.php|config/sanctum.php|routes/**|resources/js/pages/auth/**|tests/Feature/Auth/**'
---

# Feature Auth

## Use Fortify sessions and Sanctum API authentication
Fortify owns all human authentication flows. Authenticate the same-origin Inertia and Filament applications with the web guard and secure session cookies; use Sanctum only for approved first-party API/mobile clients and scoped API tokens. Do not port legacy auth controllers or convert access-control device credentials into member tokens without an approved device-authentication contract.
