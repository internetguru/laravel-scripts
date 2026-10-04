---
name: ig-local-environment
description: "Run, test and debug an Internet Guru Laravel application locally: the docker-ansible Docker stack, the laravel-scripts composer commands, and running tests or Artisan outside Docker. Activate when tests, browser tests, Artisan, migrations or the dev server fail to start, when the database path or port 80 is in the way, or when the user asks how to run the application."
metadata:
  author: internetguru
---
# Local environment

## The Docker stack

The application runs in Docker. docker-ansible deploys it locally to the `<group>-localhost` host and generates `Dockerfile`, `docker-compose.yml`, `docker-compose.dev.yml`, `docker-compose.mail.yml` and the server-managed `.env` values. These files are not committed. Change them in docker-ansible (`templates/`, `group_vars/<group>/apps.yml`) rather than in the application.

- Service `laravel` runs Octane on FrankenPHP with the code mounted at `/app`. The dev compose file publishes port 80 (`WWW_PORT`), Vite on 5173 (`VITE_PORT`) and Mailpit on 8025 (`MAILPIT_PORT`).
- Octane runs with `--max-requests=1` locally, so PHP changes are picked up on the next request.
- To run unreleased `internetguru/*` package code, the stack is deployed with an extra compose file that mounts package checkouts over `/app/vendor/internetguru/*`: `./run.sh deploy <group> <domain> -f <path>/docker-compose.vendor.yml`. See `ig-package-development`.
- Only one application can hold port 80. If containers fail to start, another project's stack is probably running. Ask the user before stopping it.

## Composer scripts

`internetguru/laravel-scripts` adds these to every application. They run inside the `laravel` container. Call them with `composer run <script>`, because the bare `composer test:php` form is not available for plugin scripts, and pass arguments after `--`, e.g. `composer run artisan -- migrate:status`. They need the stack's `docker-compose.yml`; if docker compose reports "no configuration file provided", the file is missing and the stack needs redeploying with docker-ansible.

| Command | What it does |
| --- | --- |
| `composer run artisan <command>` | `php artisan <command>` in the container |
| `composer run test:php` | The full suite: recreate and migrate `database/testing.sqlite`, then `php artisan test`, in parallel (one process per CPU) when Paratest is installed. Extra arguments go after `--`, e.g. `composer run test:php -- --compact` |
| `composer run migrate:fresh` | Recreate `database/database.sqlite`, then `migrate:fresh --seed` |
| `composer run bash` | Shell in the container |
| `composer run dev` | `npm install` and the Vite dev server (the developer runs this, not the agent) |
| `composer run test:browser` | Only the Pest browser tests (`tests/Browser`), on a freshly migrated test database. Extra arguments go after `--`, e.g. `composer run test:browser -- --compact tests/Browser/OrderCreateTest.php` |

## Browser tests

Pest browser tests (`pestphp/pest-plugin-browser`) run inside the `laravel` container: the test starts the application in its own process and drives a headless Chromium through Playwright. The test shares the database transaction, factories, fakes and `actingAs()` of any feature test.

- **The image.** docker-ansible builds the localhost image with the `sockets` extension, Alpine's Chromium and `PLAYWRIGHT_BROWSERS_PATH`. Playwright ships no Alpine browsers, so `playwright-chromium-link` points the paths of the project's Playwright version at that Chromium. The container runs it on start and `test:php` / `test:browser` run it before the tests. If tests fail with a missing `sockets` extension or "Executable doesn't exist", the stack predates this: ask the user to redeploy it. Run `playwright-chromium-link` in the container after `npm` changes the Playwright version.
- **Assets.** Pages load the JavaScript and CSS from the developer's Vite dev server (`public/hot`), so frontend edits are tested without a build. If a test times out on the first `visit()`, or the page has no styles and Livewire does not react, Vite is not running or the container cannot reach it. Check with `docker compose exec laravel wget -qO- -T 3 "$(cat public/hot)/@vite/client"`. A host firewall (UFW) blocks containers by default; the user allows them once with `sudo ufw allow from 172.16.0.0/12 to any port <vite port> proto tcp`. Never build assets to work around it.
- **Setting up a project** (with the user's approval, it adds dependencies):
  - `composer require --dev pestphp/pest-plugin-browser`; on the host add `--ignore-platform-req=ext-sockets`, the extension lives in the container.
  - `npm install -D playwright@<version>`, the exact version the plugin requires (its error names it). Remove `@playwright/test`, `playwright.config.ts` and `tests/e2e` once their specs are ported to Pest.
  - A `Browser` testsuite for `./tests/Browser` in `phpunit.xml`, and `uses(TestCase::class)->in('Browser')` in `tests/Pest.php`, adding `->beforeEach(fn () => $this->withVite())` when the `TestCase` calls `withoutVite()`. Keep the `TestCase`'s `DatabaseTransactions`: the in-process server shares its connection.
  - `/tests/Browser/Screenshots` in `.gitignore`.
  - In CI: the `sockets` PHP extension, `npm run build`, then `npx playwright install --with-deps chromium` before the tests.
- **Debugging.** A failure saves a screenshot to `tests/Browser/Screenshots` (ignored by git); read it. `->debug()` and `--headed` need a display, which the container does not have.

## Running tests without Docker

`phpunit.xml` sets `DB_DATABASE=/app/database/testing.sqlite`, which exists only in the container. PHPUnit's `<env>` does not override a variable that is already set, so point it at the host path instead:

```bash
touch database/testing.sqlite
DB_DATABASE=$PWD/database/testing.sqlite php artisan test --compact
```

If the project has `database/schema/sqlite-schema.dump`, Laravel loads it through the `sqlite3` command-line tool. When that binary is missing (the PHP extension alone is not enough), the suite fails early. Ask the user to install `sqlite3`, or put a small script named `sqlite3` on `PATH` that pipes stdin into the database through PDO.

A test that also fails on a clean checkout is pre-existing. Name it as such instead of attributing it to your change.
