# FinTrack

FinTrack is a personal and household finance application in development. Planned capabilities include private and shared accounts, income and expenses, transfers, budgets, savings goals, and cash-flow planning, with EUR amounts and Croatian presentation formats.

## Current State

The application currently contains the official Laravel Vue starter foundation and its generated authentication pages. Financial workflows are not implemented yet.

The starter currently uses SQLite. PostgreSQL is the intended application database; its Laravel connection and separate test database configuration are pending. The PostgreSQL test baseline has not been established.

## Stack

- Laravel 13 and PHP
- Vue 3, TypeScript, Inertia 3, and Tailwind CSS 4
- PHPUnit 12
- Composer and npm, with both dependency lock files committed

## Local Setup

The verified development environment uses PHP 8.5, Composer 2, and Node.js 26 with npm. The current SQLite bootstrap requires PHP's `pdo_sqlite` extension. PostgreSQL 18 and `pdo_pgsql` will support the planned database connection.

From a fresh checkout, using the current SQLite defaults:

```bash
cp .env.example .env
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
```

Start local development:

```bash
composer run dev
```

Open the URL printed by the development server, normally `http://localhost:8000`. The initial application, login, and registration pages have been checked manually. A successful frontend build does not establish that the automated test suite passes.

## Working Conventions

- `main` contains integrated work. Use focused task branches and review changes before committing.
- Keep local environment files, installed dependencies, build output, database files, logs, and caches out of Git. Keep `.env.example` free of credentials and application keys.
- The developer writes application code, runs checks, and performs Git operations. The mentor explains tasks, reviews changes, and maintains essential documentation.
- Task planning and the detailed learning record are maintained outside the application repository.
