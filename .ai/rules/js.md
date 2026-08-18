---
paths:
  - 'resources/js/**'
---

# Js

## Use the Wayfinder next namespace
Wayfinder is pinned to dev-next. Import named routes from `@/wayfinder/routes/**` and controller actions from `@/wayfinder/<PHP namespace>`; the former `@/routes` and `@/actions` paths are obsolete. Generate with `php artisan wayfinder:generate` because `--with-form`, `--skip-actions`, and `--skip-routes` were removed.
