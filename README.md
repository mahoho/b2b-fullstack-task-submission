# B2B full-stack practical task

A Laravel 13 API (Doctrine ORM, spatie/laravel-data) in `backend/` and a Nuxt 4 applicant review page in `frontend/`. `TASK_REPORT.md` covers all four tasks.

## Prerequisites

PHP 8.4 with `pdo_sqlite`, Composer 2, and Node 22 (`frontend/.nvmrc` pins 22.23.1).

## Run with Docker

Docker is the only thing you need installed.

    docker compose up --build

The first build takes a few minutes. When it settles:

- App: http://localhost:3000 (redirects to the demo campaign)
- API: http://localhost:8000/api/v1/... (bearer token `demo-partner-token`)

The backend container waits for MySQL, runs `php artisan doctrine:migrations:migrate`
and `php artisan db:seed`, then starts the API. Both are safe to repeat, so restarting
is fine. The frontend proxies `/api/v1/**` to the backend with `NUXT_API_BASE` and
`NUXT_API_TOKEN`, which compose sets.

### Live reload

`docker compose up` also reads `docker-compose.override.yml`, which runs both apps from
the source on your machine. The frontend runs `nuxt dev`, Vite's dev server, so a saved
change shows up in the open page without a reload. PHP changes, new routes included,
apply to the next request. A new migration runs on `docker compose restart backend`.

The installed dependencies stay inside the containers. After changing `package-lock.json`
or `composer.lock`, run `docker compose up --build -V`. `-V` swaps in the dependencies
from the rebuilt images.

To run the production frontend build instead, leave the override out:

    docker compose -f docker-compose.yml up --build

To reset the database, run `docker compose down -v`. That deletes the MySQL volume.
To stop without losing data, run `docker compose down`.

## Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan doctrine:migrations:migrate --no-interaction
php artisan db:seed
php artisan serve
```

The seeder creates a demo partner with the bearer token `demo-partner-token`, a campaign with 24 applicants (`0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81`) and an empty campaign (`7d1e9a40-2b3c-4d5e-8f60-718293a4b5c6`).

The list also takes `status` (`pending`, `shortlisted` or `rejected`) and `search` (part of a name or email, any case). Like `min_score`, they narrow the list but not the summary. `-g` stops curl from reading the brackets in `page[size]` as a URL pattern.

```bash
curl -g -H 'Authorization: Bearer demo-partner-token' \
  'http://127.0.0.1:8000/api/v1/campaigns/0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81/applicant-summary?min_score=80&page[size]=5'
```

Run the tests with `php artisan test`. `composer analyse` runs PHPStan at level 8 (with Larastan and phpstan-doctrine), `composer lint` checks the code style with Pint, and `composer format` fixes it.

The frontend's API types in `frontend/types/api.ts` are generated from the DTOs in `app/Data` and the enums in `app/Enums`. After changing either, run `composer types`.

## Frontend

With the backend running:

```bash
cd frontend
nvm use
npm ci
cp .env.example .env
npm run dev
```

Open http://localhost:3000, which redirects to the demo campaign. The Nuxt server proxies the two API calls the page makes to `NUXT_API_BASE` and adds the `NUXT_API_TOKEN` bearer token, so the token never reaches the browser. Both come from `frontend/.env` (git-ignored), and `.env.example` holds the local demo values. There are no defaults in the code, so if either is missing the proxy answers 500 and logs which setting to add. `nuxt dev` and `nuxt preview` read `.env`; a production server started with `node .output/server/index.mjs` does not, so set the two variables in its environment.

```bash
npm test                          # Vitest component tests
npm run typecheck
npm run lint                      # ESLint, style rules included
npm run lint:fix                  # fixes what it can
npx playwright install chromium   # once
npm run test:e2e                  # Playwright tests with the API mocked
```
