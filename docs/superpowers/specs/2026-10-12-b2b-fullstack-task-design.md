# B2B Full-Stack Practical Task: Design

Date: 2026-10-12
Source: `Full-Stack_Engineer_Practical_Task_B2B_Team___.pdf` (6 pages)

## Goal

A submission repo for the B2B team's practical task: a Laravel endpoint with tests (Task 1), a Nuxt applicant review card with tests (Task 2), and a `TASK_REPORT.md` covering AI workflow (Task 3) and a production incident write-up (Task 4), plus a `README.md` with setup steps. The PDF estimates 3 to 4 hours of work and allows 3 days.

Done means: `php artisan test` passes in `backend/`, `npm test` and `npm run test:e2e` pass in `frontend/`, both apps run locally and the card works against the real API, and the report answers every point in Tasks 3 and 4.

## Stack and versions

- PHP 8.4 (`"php": "^8.4"` in composer.json; the PDF asks for 8.2+). Doctrine ORM 3 uses PHP 8.4 native lazy objects, so no proxy classes are generated. Composer 2.10.
- Laravel 13 from `composer create-project laravel/laravel backend`.
- Doctrine through `laravel-doctrine/orm` 3.3 (ORM 3, DBAL 4) and `laravel-doctrine/migrations` 3.4. No Eloquent models in the app code.
- `spatie/laravel-data` 4.23 for every DTO: the typed input the controllers pass on, and the response documents. Validation lives in FormRequest classes (change request #1).
- `spatie/laravel-typescript-transformer` 3.3 with its laravel-data extension, generating the frontend's API types from those DTOs and the PHP enums.
- SQLite for local dev and tests. MySQL works by changing `DB_CONNECTION` in `.env`; nothing in the code is SQLite specific.
- Node 22 (`nvm`, pinned in `frontend/.nvmrc`). The machine default is Node 18.14, which Nuxt 4 does not support.
- Nuxt 4.6, Vue 3 `<script setup lang="ts">`, Tailwind CSS 4. No PrimeVue, no Pinia.
- Frontend tests: Vitest through `@nuxt/test-utils` 4.x, Playwright 1.64 for one smoke test.

## Repository layout

```
b2b-fullstack-task-submission/
├── backend/                 Laravel 13 + Doctrine
├── frontend/                Nuxt 4
├── docs/
│   └── superpowers/         this spec and the implementation plan
├── TASK_REPORT.md
└── README.md
```

## Backend

### Data model (Doctrine entities, PHP attribute mapping)

`app/Entities/Partner.php`, table `partners`:
- `id` guid, primary key, generated with `Str::uuid()` in the constructor
- `name` string
- `apiTokenHash` string(64), unique. SHA-256 of the bearer token.

`app/Entities/Campaign.php`, table `campaigns`:
- `id` guid
- `partner` ManyToOne Partner, not null
- `name` string
- `eligibilityThreshold` smallint, default 70

`app/Entities/Applicant.php`, table `applicants`:
- `id` guid
- `campaign` ManyToOne Campaign, not null
- `fullName` string
- `email` string
- `aiScore` smallint, nullable, 0 to 100. Null means the AI review has not run yet.
- `highlights` json (list of strings)
- `status` string(20) mapped to `App\Enums\ApplicantStatus` (`pending`, `shortlisted`, `rejected`), default `pending`
- `appliedAt` datetime_immutable
- Index on `(campaign_id, ai_score)`.

`app/Enums/ScoreTier.php`: backed enum `top`, `eligible`, `below`, `unscored`, with `ScoreTier::for(?int $score, int $eligibilityThreshold)`. Null -> `unscored`, 90 or more -> `top`, at or above the threshold -> `eligible`, otherwise `below`. Constants on the same enum: top tier score 90, top tier highlight limit 3.

Schema is managed by one Doctrine migration in `database/migrations`, built with the DBAL 4.5 schema editors and created through the platform's SQL (change request #3), so it uses no deprecated DBAL API and runs on SQLite and MySQL alike. Index and foreign key names are the ones Doctrine generates for the mapping. After migrating, `doctrine:migrations:diff` must report no changes, and `MigrationTest` pins both properties. The skeleton's Eloquent `User` model, its factory and the default migrations are deleted. The API is stateless: `SESSION_DRIVER=array`, `CACHE_STORE=file`.

`database/seeders/DatabaseSeeder.php` (uses the EntityManager, Faker for names):
- Demo partner with token `demo-partner-token` (local only, documented in the README).
- Demo campaign with fixed id `0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81`, threshold 70, 24 applicants with a spread of scores including 2 unscored.
- Empty campaign with fixed id `7d1e9a40-2b3c-4d5e-8f60-718293a4b5c6`, no applicants.

### Endpoints

All routes live in `routes/api.php` under `/api/v1`, behind the `AuthenticatePartner` middleware.

`GET /api/v1/campaigns/{campaign}/applicant-summary`

Query parameters:
- `min_score`: optional integer 0 to 100. Filters the list to `aiScore >= min_score`, which also leaves out unscored applicants. Does not affect `meta.summary`.
- `page[size]`: optional integer 1 to 50, default 10.
- `page[number]`: optional integer, at least 1, default 1. A page past the last returns 200 with empty `data`.

List order: `aiScore` descending (unscored last), then `appliedAt` ascending, then `id`.

200 response (`Content-Type: application/vnd.api+json`):

```json
{
  "data": [
    {
      "type": "applicants",
      "id": "5f0c...",
      "attributes": {
        "name": "Ada Lovelace",
        "email": "ada@example.com",
        "aiScore": 94,
        "tier": "top",
        "status": "pending",
        "appliedAt": "2026-10-01T09:30:00+00:00",
        "highlights": ["Led a robotics club to nationals", "Strong maths portfolio"]
      }
    }
  ],
  "meta": {
    "summary": {
      "totalApplicants": 24,
      "eligibleApplicants": 15,
      "averageAiScore": 74.6,
      "topTier": [{ "id": "5f0c...", "name": "Ada Lovelace", "aiScore": 94 }]
    },
    "page": { "currentPage": 1, "perPage": 10, "total": 24, "lastPage": 3 }
  },
  "links": {
    "self": "http://localhost:8000/api/v1/campaigns/0b8f.../applicant-summary?page[number]=1&page[size]=10",
    "first": "...page[number]=1...",
    "prev": null,
    "next": "...page[number]=2...",
    "last": "...page[number]=3..."
  }
}
```

Summary rules (campaign wide, ignores `min_score`):
- `totalApplicants`: all applicants in the campaign, scored or not.
- `eligibleApplicants`: `aiScore >= campaign.eligibilityThreshold`.
- `averageAiScore`: average over scored applicants only, rounded to 1 decimal. 0 when no applicant is scored, as the PDF asks for zeroed aggregates. PHP encodes a whole float without the fraction, so JSON shows `0`, not `0.0`.
- `topTier`: up to 3 applicants with `aiScore >= 90`, highest first, then earliest `appliedAt`. Empty list when none.
- `meta.page.total` counts the filtered list. `lastPage` is at least 1.
- Links keep `min_score` when it was given.

`PATCH /api/v1/applicants/{applicant}`

Request body:

```json
{ "data": { "type": "applicants", "id": "5f0c...", "attributes": { "status": "shortlisted" } } }
```

Rules: `data.type` must be `applicants`, `data.id` must equal the route id, `status` must be one of the three enum values. Response 200 with `{ "data": <applicant resource> }`.

### Errors

Every error from `/api/*` is rendered in one place, `bootstrap/app.php` `withExceptions`, as a JSON:API error document with `Content-Type: application/vnd.api+json`:

```json
{ "errors": [ { "status": "422", "title": "Invalid parameter", "detail": "The page[size] field must not be greater than 50.", "source": { "parameter": "page[size]" } } ] }
```

- 401: missing or unknown bearer token. Adds `WWW-Authenticate: Bearer`.
- 404: campaign or applicant does not exist, or belongs to another partner. Same response either way, so ids cannot be probed.
- 404 for a malformed id as well (change request #2): `{campaign}` and `{applicant}` are resolved by route bindings in `AppServiceProvider` within the authenticated partner, so a malformed, unknown or foreign id gives the same 404. The partner middleware runs before `SubstituteBindings`.
- 422 for query parameters: one error object per failed rule, `source.parameter` uses the bracket form (`page[size]`, not `page.size`).
- 422 for the PATCH body: `source.pointer`, for example `/data/attributes/status`.
- Any other `HttpException` keeps its status. Anything else is a 500 with `title: "Server Error"` and no detail; the exception is logged.

### Units

- `app/Http/Middleware/AuthenticatePartner.php`: reads `Authorization: Bearer <token>`, looks up the Partner by `hash('sha256', $token)`, puts it on the request (`$request->attributes->set('partner', $partner)`), throws `AuthenticationException` otherwise.
- `app/Http/Middleware/JsonApiContentType.php`: sets `Content-Type: application/vnd.api+json` on every `/api` response.
- FormRequest classes in `app/Http/Requests/` validate the query or body before the controller runs, and each has a `toData()` that builds the typed input DTO. The bound entity, not an id, reaches the controller:
  - `ApplicantSummaryRequest`: `min_score` (nullable integer 0 to 100), `page.size` (integer 1 to 50, default 10), `page.number` (integer at least 1, default 1).
  - `UpdateApplicantStatusRequest`: `data.type` (must be `applicants`), `data.id` (a string equal to the bound applicant's id, case-insensitive), `data.attributes.status` (`ApplicantStatus` enum).
  - No `exists` rules, since those go through Laravel's query builder rather than Doctrine. Existence and ownership are checked in the service.
- Input DTOs in `app/Http/Input/` (outside `app/Data`, so they stay out of the generated TypeScript): `ApplicantSummaryQueryData` (`minScore`, `pageSize`, `pageNumber`) and `UpdateApplicantStatusData` (`status`), plain laravel-data objects with no validation attributes.
- Output DTOs in `app/Data/`, each marked for TypeScript generation:
  - `ApplicantAttributesData` (name, email, aiScore, tier, status, appliedAt, highlights) and `ApplicantResourceData` (`type`, `id`, `attributes`), built from an Applicant entity plus the campaign's eligibility threshold, so the tier is computed without touching the lazy `campaign` association per row.
  - `TopTierApplicantData`, `ApplicantSummaryData`, `PageMetaData`, `PaginationLinksData`.
  - `ApplicantSummaryDocumentData` (`data`, `meta.summary`, `meta.page`, `links`) and `ApplicantDocumentData` (`data`) for the PATCH response.
  - `JsonApiErrorData`, `JsonApiErrorSourceData` and `JsonApiErrorDocumentData`, used by the exception renderer.
  - `ApplicantAggregatesData` (`total`, `eligible`, `average`), returned by `ApplicantRepository::aggregates()` and built from the DQL row by `fromRow()`. It is internal, marked `#[Hidden]` so it stays out of the generated TypeScript (change request #4). Members the spec leaves out (`code`, `source`, `detail`) are laravel-data `Optional`, so they are absent from the JSON and optional in TypeScript.
- `app/Repositories/ApplicantRepository.php` (Doctrine `EntityRepository`, set as the Applicant `repositoryClass`). Four queries per summary request, whatever the campaign size: one DQL aggregate (`COUNT`, `SUM(CASE WHEN aiScore >= :threshold ...)`, `AVG(aiScore)`), one top tier query, one page query with `setFirstResult`/`setMaxResults`, one filtered count. Nothing is loaded into PHP to be counted.
- `app/Services/ApplicantSummaryService.php`: takes the bound `Campaign` and `ApplicantSummaryQueryData`, calls the repository, applies the summary rules (null average -> 0, rounding) and returns `ApplicantSummaryDocumentData`.
- `app/Http/Controllers/Api/V1/ApplicantSummaryController.php` and `UpdateApplicantStatusController.php`: invokable, a few lines each, return the document DTO.
- Validation errors come from the FormRequests keyed by input name (`min_score`, `page.size`, `data.attributes.status`). The renderer turns query keys into bracket form for `source.parameter` and body keys into JSON pointers for `source.pointer`.

### TypeScript generation

`app/Providers/TypeScriptTransformerServiceProvider.php` (created by `php artisan typescript:install`) registers the laravel-data extension, the enum transformer as string unions, `app_path()` as the transform directory, and the `FlatModuleWriter` with output in `../frontend/types/`. `composer types` runs `php artisan typescript:transform`. The generated file is committed and never edited by hand; after any DTO or enum change, running `composer types` and then `git diff --exit-code frontend/types` must show no change.

### Backend tests

Test DB: SQLite in memory. A `RefreshesDoctrineSchema` trait rebuilds the schema with Doctrine's `SchemaTool` before each test. A small `CreatesEntities` trait gives `makePartner`, `makeCampaign`, `makeApplicant`.

`tests/Feature/ApplicantSummaryTest.php`:
- 200 with exact `totalApplicants`, `eligibleApplicants`, `averageAiScore`, `topTier`, list order and JSON:API content type.
- `min_score=80` filters `data` and `meta.page.total` but leaves `meta.summary` unchanged.
- Pagination: `page[size]=2&page[number]=2` returns the right slice, `meta.page`, and `prev`/`next`/`last` links.
- 404 for a non-UUID campaign id, the same body as an unknown one.
- 422 for `page[size]=0`, `page[size]=51`, `page[number]=0`, `min_score=101`, `min_score=abc`, each with the bracket-form `source.parameter`.
- 404 for an unknown UUID, and for another partner's campaign.
- 401 without a token and with a wrong token.
- Zero applicants: 200, empty `data`, summary `0, 0, 0, []`, `lastPage` 1.
- Unscored applicants count in `totalApplicants` but not in the average or eligible count.

`tests/Feature/UpdateApplicantStatusTest.php`:
- 200 and the status is persisted (re-read through a fresh EntityManager).
- 422 for an unknown status, with `source.pointer`.
- 422 when `data.id` does not match the route.
- 404 for another partner's applicant.

`tests/Unit/ScoreTierTest.php`: boundaries at 89/90, at and below the threshold, null.
`tests/Unit/ApplicantSummaryDataTest.php`: the summary math lives in `ApplicantSummaryData::fromAggregates()` and is tested from fixed input (null average -> 0, rounding to 1 decimal).

## Frontend

### Layout

`nuxt.config.ts` sets `srcDir: '.'` so the folders match the PDF tree: `components/`, `composables/`, `pages/`, `server/`, `types/`, `tests/`, `assets/css/main.css` (`@import "tailwindcss";`).

Runtime config:
- `apiBase` (`NUXT_API_BASE`) and `apiToken` (`NUXT_API_TOKEN`), server only, read from `frontend/.env`, with `frontend/.env.example` committed holding the local demo values (change request #5). There are no defaults in the code; when either is missing the proxy answers 500 "API proxy is not configured" and logs which setting to add.
- `public.demoCampaignId` (`NUXT_PUBLIC_DEMO_CAMPAIGN_ID`, default the seeded demo id).

Styling is Tailwind utilities in templates. No `<style>` blocks in `.vue` files; if a component ever needs CSS that utilities cannot express, it goes in its own partial imported from `assets/css/main.css`.

### Units

- `types/`: the file generated by the backend's `composer types` (see TypeScript generation). Components and the composable import `ApplicantResourceData`, `ApplicantSummaryDocumentData`, `ApplicantStatus`, `ScoreTier` and the rest from it. No hand-written copies of API types.
- `server/api/v1/[...path].ts`: Nitro handler, `proxyRequest(event, apiBase + event.path, { headers: { Authorization: 'Bearer ' + apiToken } })`. The token never reaches the browser and there is no CORS setup.
- `composables/useApplicantReview.ts` (campaign id in, state and actions out):
  - Fetches on the client only (called from `onMounted`). Portal data sits behind auth and this keeps the skeleton visible on first paint.
  - State: `applicants`, `pageMeta`, `loading`, `loadError`, `minScore` (0 to 100), `page`, `pendingIds`, `actionErrors` (per applicant id).
  - `minScore` changes are debounced 250 ms with a plain `setTimeout`, reset `page` to 1 and refetch. `min_score` is omitted when the slider is at 0, so unscored applicants show.
  - Responses from superseded requests are ignored (request counter), so a fast slider cannot leave stale results on screen.
  - `setStatus(id, status)`: sets the new status immediately, marks the id pending, sends the PATCH, and on failure restores the previous status and sets `actionErrors[id]`. A second click while pending is ignored.
- `components/ApplicantReviewCard.vue` (presentational). Props: `applicant`, `pending`, `error`. Emits `update-status`.
  - Name, score badge coloured by `tier` (top emerald, eligible blue, below amber, unscored gray reading "Not scored"), with screen reader text "AI match score 94 out of 100, top tier".
  - Applied date via `Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeZone: 'UTC' })`. Fixed locale and zone keep tests deterministic.
  - First 3 highlights as a bullet list.
  - Status pill: "Pending review", "Shortlisted", "Rejected", inside an `aria-live="polite"` region that also carries the action error.
  - Shortlist and Reject buttons. The one matching the current status has `aria-pressed="true"` and is disabled; both are disabled while pending.
  - View Details toggle (`aria-expanded`) that reveals the email and every highlight.
- `components/ApplicantReviewBoard.vue` (uses the composable). Props: `campaignId`.
  - Labelled range input "Minimum AI score", 0 to 100, step 5, with the current value shown in an `<output>`.
  - Loading: 3 skeleton cards (`animate-pulse`), `aria-busy="true"` on the list.
  - Empty: "No applicants yet." at 0, otherwise "No applicants scored {n} or higher."
  - Load error: "Couldn't load applicants." with a Retry button.
  - Prev and Next buttons with "Page X of Y", hidden when there is one page.
- `pages/campaigns/[id].vue` renders the board. `pages/index.vue` redirects to `/campaigns/{demoCampaignId}`.

### Frontend tests

`vitest.config.ts` defines one `defineVitestProject` project with `environment: 'nuxt'` that includes `tests/nuxt/**/*.spec.ts`; Playwright owns `tests/e2e`. API calls are mocked with `registerEndpoint` from `@nuxt/test-utils/runtime`.

`tests/nuxt/ApplicantReviewCard.spec.ts`:
- Renders name, score, formatted date and the first 3 highlights.
- Badge classes for each tier, "Not scored" for null.
- Clicking Shortlist emits `update-status` with `shortlisted`; buttons are disabled while `pending`.
- View Details toggles `aria-expanded` and shows the email.

`tests/nuxt/ApplicantReviewBoard.spec.ts`:
- Shows skeletons, then the applicants from the mocked endpoint.
- Moving the slider to 80 sends `min_score=80` and the DOM shows only the filtered applicants.
- Shortlist shows "Shortlisted" before the PATCH resolves.
- A failing PATCH (500) restores "Pending review" and shows the error.
- Empty response shows the empty state.

`tests/e2e/smoke.spec.ts` (Playwright, `webServer` runs `nuxt dev` on port 3100, API mocked with `page.route('**/api/v1/**')`): page loads, cards render, Shortlist updates the card and the mocked route received a PATCH with `status: shortlisted`.

## Task 3: capturing real AI output

Three pieces get a raw AI draft: (1) `ApplicantRepository` plus `ApplicantSummaryService`, (2) `ApplicantReviewCard.vue`, (3) `useApplicantReview.ts`.

For each piece, in order:
1. Write and commit the tests for it first.
2. Dispatch a fresh subagent (model: Sonnet) with a plain one-shot prompt, the kind of request a developer types into an editor assistant: the requirement in a few sentences plus the entity, DTO or type definitions it needs. The subagent is told not to read or write files and to answer with the file content only.
3. Save the reply verbatim to the real path and commit it as `chore(ai-raw): ...`.
4. Run the tests and record the failures.
5. Fix it into the final version and commit (`refactor(...)` or `fix(...)`).

The report quotes the three drafting prompts verbatim, puts raw and final code side by side, lists each defect with the test or review that caught it, and estimates time saved from commit timestamps against a manual estimate per piece, with the brainstorming -> spec -> plan -> subagent workflow described as it actually ran.

## Task 4: report section outline

Root cause:
- The LRU cache is capped at 50 keys. A viral campaign brings many partner and campaign variants, and query strings such as UTM tags or cache busters can end up in the key, so the hit rate collapses and nearly every request recompiles the embed script. Compilation is CPU work on Node's single event loop, so every other request waits behind it: 120 ms -> 4.5 s.
- The cap counts keys, not bytes, so it says nothing about memory. When the 30 minute TTL expires on a hot key, every concurrent miss compiles the same script at once (cache stampede), each holding its own buffers.
- `/api/trk` streams events straight to the warehouse from the same Nitro process. When the warehouse slows down, requests hold sockets and buffered bodies in memory. Memory hits the limit, the process is killed or stops answering health checks, the load balancer returns 502, and every event still in flight is lost. The SDK does not retry and events carry no id to deduplicate on, so they cannot simply be resent.
- Billing counts come from the backend database while conversion numbers come from the warehouse, so lost events show up as a mismatch between the two.

Mitigation within an hour:
- CDN in front of the embed scripts with `Cache-Control: public, s-maxage=300, stale-while-revalidate=86400`, cache key without query strings.
- Normalise the in-process cache key, cap the LRU by bytes, and coalesce in-flight compiles so concurrent misses wait for one compile.
- Scale replicas out, set `--max-old-space-size` below the container limit.
- Log every tracking event as a structured line before forwarding, so a log sink gives a replay source.
- Route `/api/trk` to its own deployment so tracking load cannot take embeds down.
- Flag invoices for the incident window until reconciliation runs.

Long term, for 50x volume:
- Embed assets: content-hashed scripts and styles on object storage behind the CDN, `Cache-Control: immutable, max-age=31536000`. A small loader script with a short TTL plus stale-while-revalidate points at the current hash. Campaign config as small JSON cached at the edge, purged by surrogate key on publish. Iframe HTML cached per campaign version. Nitro `routeRules` SWR with a shared Redis storage driver instead of per-process memory.
- Ingestion: the SDK batches events, gives each one a client-generated UUIDv7 and sends with `sendBeacon`, keeping unsent events in local storage for retry. A thin collector validates, returns 202 right after publishing to Pub/Sub (Redis Streams if the team stays in its current stack). Consumers write batches to BigQuery, deduplicating on event id; repeated failures go to a dead-letter topic.
- Billing and webhooks: verify the provider signature, insert the provider event id into `webhook_events` (unique index) in the same transaction as the subscription change, return 200 on duplicates, ignore events older than the stored version. Usage is upserted per (partner, period, metric). A daily reconciliation job compares warehouse conversions with billed usage and flags drift above a threshold; invoices are finalised only after the late-event window closes.
- Portal analytics read from pre-aggregated tables cached in Redis, not from raw events.
- One Mermaid sequence diagram covering partner page -> CDN -> loader/iframe, SDK -> collector -> Pub/Sub -> consumer -> BigQuery, and webhook -> backend -> reconciliation.

## README

Prerequisites, then backend steps (`composer install`, copy `.env.example`, `php artisan key:generate`, `php artisan doctrine:migrations:migrate`, `php artisan db:seed`, `php artisan serve`, `composer types` after DTO changes), frontend steps (`nvm use`, `npm ci`, `npm run dev`, open the demo campaign), the demo token, and the test commands for both apps.

## Working rules

- Conventional Commits, no Asana ids (personal assessment repo).
- Work happens on branches in git worktrees and lands on `main` with merge commits. No rebase, no force push.
- Nothing is pushed to `origin` until the final state is approved.
- No screenshots, recordings or scratch scripts are committed.
- Prose in the report, README and commit messages is plain: no em dashes, no emojis.

## Out of scope

Real OAuth2, Redis, a Gemini integration, PrimeVue, Pinia, and any code for Task 4 (it is a written analysis). A Docker setup was added later at the user's request (change request #6): `docker-compose.yml` runs MySQL 8, the API (migrate and seed on start, a fresh `APP_KEY` per start) and the built Nuxt server, which proxies to the API. Change request #8 added `docker-compose.override.yml`, which compose reads by default: it mounts both apps from the host, runs the frontend with `nuxt dev` (Vite, hot reload), and keeps the installed dependencies in anonymous volumes. `docker compose -f docker-compose.yml up` still runs the production frontend build.
