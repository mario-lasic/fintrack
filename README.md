# FinTrack

FinTrack is a personal and household finance application in development. Planned capabilities include private and shared accounts, income and expenses, transfers, budgets, savings goals, and cash-flow planning, with EUR amounts and Croatian presentation formats.

## Current State

The application currently contains the official Laravel Vue starter foundation and its generated authentication pages. Financial workflows are not implemented yet.

The local setup uses PostgreSQL with separate databases and roles for development and testing.

## Stack

- Laravel 13 and PHP
- Vue 3, TypeScript, Inertia 3, and Tailwind CSS 4
- PHPUnit 12
- Composer and npm, with both dependency lock files committed

## Local Setup

The development setup uses PHP 8.5 with `pdo_pgsql`, Composer 2, PostgreSQL 18, and Node.js 26 with npm. Start PostgreSQL and use a local `fintrack_dev` database with a `fintrack_dev` login that can create tables in the `public` schema.

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

For a new test environment, copy `.env.example` to `.env.testing`. Set `APP_ENV=testing`, `DB_DATABASE=fintrack_test`, `DB_USERNAME=fintrack_test`, and `DB_PASSWORD` to the test role's password. Generate a test application key once with `php artisan key:generate --env=testing`.

```bash
php artisan config:clear
php artisan test
```

## Working Conventions

- `main` contains integrated work. Use focused task branches and review changes before committing.
- Keep local environment files, installed dependencies, build output, database files, logs, and caches out of Git. Keep `.env.example` free of credentials and application keys.
- The developer writes application code, runs checks, and performs Git operations. The mentor explains tasks, reviews changes, and maintains essential documentation.
- Task planning and the detailed learning record are maintained outside the application repository.
