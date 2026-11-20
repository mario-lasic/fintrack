# FinTrack

FinTrack is a personal and household finance application in development. Planned capabilities include private and shared accounts, income and expenses, transfers, budgets, savings goals, and cash-flow planning, with EUR amounts and Croatian presentation formats.

## Current State

FinTrack currently includes registration, sign-in, email verification, password reset, and profile settings. Self-service account deletion is temporarily disabled. Household management is the next planned epic.

The local setup uses PostgreSQL with separate databases and roles for development and testing.

## Stack

- Laravel 13 and PHP
- Vue 3, TypeScript, Inertia 3, and Tailwind CSS 4
- PHPUnit 12
- Composer and npm, with both dependency lock files committed

## Local Setup

The local setup uses PHP 8.5 with `pdo_pgsql`, Composer 2, PostgreSQL 18, and Node.js 26 with npm.

### PostgreSQL databases

Start PostgreSQL. On a fresh PostgreSQL instance, run these commands once in `psql` as a PostgreSQL administrator:

```sql
CREATE ROLE fintrack_dev LOGIN;
CREATE ROLE fintrack_test LOGIN;
\password fintrack_dev
\password fintrack_test
CREATE DATABASE fintrack_dev OWNER fintrack_dev;
CREATE DATABASE fintrack_test OWNER fintrack_test;
```

Enter a separate password for each role when prompted. Each role owns its own database.

### Application setup

From a fresh checkout, copy the environment template:

```bash
cp .env.example .env
```

In `.env`, set `DB_DATABASE` and `DB_USERNAME` to `fintrack_dev`. Set `DB_PASSWORD` to the password for that role. The template uses PostgreSQL on `127.0.0.1:5432`.

```bash
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

Open the URL printed by the development server, normally `http://localhost:8000`.

## Run Tests

Use a dedicated `fintrack_test` database and a `fintrack_test` login with permission to create tables in the `public` schema. The test suite can recreate tables in this database.

From the project root, create the test configuration once:

```bash
cp .env.example .env.testing
```

In `.env.testing`, set `APP_ENV=testing`, `DB_DATABASE=fintrack_test`, `DB_USERNAME=fintrack_test`, and `DB_PASSWORD` to the test role's password. Generate the test application key once, then run the suite:

```bash
php artisan key:generate --env=testing
php artisan config:clear
php artisan test
```

The test bootstrap checks the connected PostgreSQL database and role before the feature tests can refresh the schema.

## Quality Checks

Run these scripts from the project root:

```bash
composer run lint:check
composer run types:check
npm run build
npm run check
npm run types:check
```

Pint checks PHP formatting. PHPStan with Larastan analyzes PHP at level 7. The frontend build generates the Wayfinder route files used by the frontend checks. Vite+ checks formatting and lint rules, and `vue-tsc` checks types in TypeScript and Vue files.

## Working Conventions

- `main` contains integrated work. Use one feature branch per epic and focused commits for individual tasks. Review and verify the epic before merging into `main`.
- Keep local environment files, installed dependencies, build output, database files, logs, and caches out of Git. Keep `.env.example` free of credentials and application keys.
- The developer writes application code, runs checks, and performs Git operations. The mentor explains tasks, reviews changes, and maintains essential documentation.
- Task planning and the detailed learning record are maintained outside the application repository.

## Project Documentation

- [Authentication routes, tests, and local checks](docs/AUTHENTICATION.md)
- [Architecture and financial invariants](docs/ARCHITECTURE.md)
- [Household and finance UI specifications](docs/UI-UX.md)
