# Build Brighton Membership System

This branch is the clean Laravel rebuild of Build Brighton's legacy membership system. The hosted legacy application remains unchanged and is used only as a behavioural and data reference while features are ported as tested vertical slices.

Canonical repository: [charlielangridge/bbmembershipsystem](https://github.com/charlielangridge/bbmembershipsystem)

## Foundation stack

- Laravel 13 on PHP 8.4
- Inertia 3, Vue 3, and TypeScript
- Tailwind CSS 4 and shadcn-vue components
- Laravel Fortify authentication with registration, password reset, email verification, password confirmation, 2FA, and passkeys
- Pest 5
- Brick/Money; monetary values are persisted as integer pence, never floats
- Laravel Boost project guidance, skills, and MCP configuration

## Local setup

Requirements: PHP 8.4, Composer 2, Node.js 24 LTS, and npm 11.

```shell
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run build
```

On PowerShell, create the SQLite file with `New-Item database/database.sqlite -ItemType File` if it does not already exist.

Run the development environment with:

```shell
composer run dev
```

## Quality checks

```shell
php artisan test --compact
vendor/bin/pint --dirty --format agent
composer run types:check
npm run types:check
npm run lint:check
npm run format:check
npm run build
```

The migration approach and acceptance gates are documented in [LARAVEL_13_IMPLEMENTATION_PLAN.md](LARAVEL_13_IMPLEMENTATION_PLAN.md). The original audit is retained in [MODERNISATION_MASTERPLAN.md](MODERNISATION_MASTERPLAN.md).
