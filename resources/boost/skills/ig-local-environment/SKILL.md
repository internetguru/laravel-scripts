---
name: ig-local-environment
description: "Run, test and debug an Internet Guru Laravel application locally: the docker-ansible Docker stack, the laravel-scripts composer commands, and running tests or Artisan outside Docker. Activate when tests, Artisan, migrations or the dev server fail to start, when the database path or port 80 is in the way, or when the user asks how to run the application."
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

`internetguru/laravel-scripts` adds these to every application. They run inside the `laravel` container:

| Command | What it does |
| --- | --- |
| `composer artisan <command>` | `php artisan <command>` in the container |
| `composer test:php` | The full suite: recreate and migrate `database/testing.sqlite`, then `php artisan test`, in parallel (one process per CPU) when Paratest is installed. Extra arguments go after `--`, e.g. `composer test:php -- --compact` |
| `composer migrate:fresh` | Recreate `database/database.sqlite`, then `migrate:fresh --seed` |
| `composer bash` | Shell in the container |
| `composer dev` | `npm install` and the Vite dev server (the developer runs this, not the agent) |
| `composer test:e2e`, `test:e2e:ui`, `test:e2e:report` | Playwright, on the host |

## Running tests without Docker

`phpunit.xml` sets `DB_DATABASE=/app/database/testing.sqlite`, which exists only in the container. PHPUnit's `<env>` does not override a variable that is already set, so point it at the host path instead:

```bash
touch database/testing.sqlite
DB_DATABASE=$PWD/database/testing.sqlite php artisan test --compact
```

If the project has `database/schema/sqlite-schema.dump`, Laravel loads it through the `sqlite3` command-line tool. When that binary is missing (the PHP extension alone is not enough), the suite fails early. Ask the user to install `sqlite3`, or put a small script named `sqlite3` on `PATH` that pipes stdin into the database through PDO.

A test that also fails on a clean checkout is pre-existing. Name it as such instead of attributing it to your change.
