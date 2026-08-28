---
paths:
  - 'app/Policies/UserPolicy.php|app/Http/Controllers/AccountController.php|routes/web.php|resources/js/pages/account/**|tests/Feature/AccountReadTest.php'
---

# Feature

## Protect member account reads through UserPolicy
Preserve GET /account/{account} as account.show. Verified members may view their own account; cross-account reads require the Spatie-backed members.manage ability. Inertia account props must be explicitly allow-listed and must not serialize credentials, 2FA secrets, tokens, or role relations.
