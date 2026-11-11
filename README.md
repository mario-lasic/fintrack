# FinTrack

FinTrack is a personal and household finance application in development. Planned capabilities include private and shared accounts, income and expenses, transfers, budgets, savings goals, and cash-flow planning, with EUR amounts and Croatian presentation formats.

## Current State

The application currently contains the official Laravel Vue starter foundation and its generated authentication pages. Financial workflows are not implemented yet.

The local setup below uses PostgreSQL. Development and testing use separate databases and roles. The PHPUnit configuration for PostgreSQL is pending.

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

## Working Conventions

- `main` contains integrated work. Use focused task branches and review changes before committing.
- Keep local environment files, installed dependencies, build output, database files, logs, and caches out of Git. Keep `.env.example` free of credentials and application keys.
- The developer writes application code, runs checks, and performs Git operations. The mentor explains tasks, reviews changes, and maintains essential documentation.
- Task planning and the detailed learning record are maintained outside the application repository.
