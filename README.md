# Laravel Scripts

Composer scripts for Internet Guru Laravel applications, plus the shared Internet Guru guidelines and skills for AI agents.

## Installation

`composer require internetguru/laravel-scripts:^1`, and allow the plugin when Composer asks.

## Scripts

The plugin adds these scripts to the application. They run in its Docker stack (service `laravel`); call them as `composer run <script>` or `composer <script>`, and see them with their descriptions in `composer run list:scripts`.

| Script | What it does |
| :-- | :-- |
| `artisan` | Run an Artisan command in the `laravel` container, e.g. `composer run artisan -- migrate:status` |
| `bash` | Open a shell in the `laravel` container |
| `dev` | Install npm packages and start the Vite dev server |
| `install:docker` | Run `composer install` in a temporary Composer 2 container, for a machine without matching PHP |
| `list:scripts` | List these scripts with what they do |
| `migrate:fresh` | Recreate the development database and seed it |
| `test:all` | Run the PHP tests, then the browser tests |
| `test:browser` | Run the browser tests one at a time |
| `test:php` | Run the PHP tests (Unit and Feature) in parallel |
| `analyse` | Run PHPStan, in the `laravel` container when the project has a `docker-compose.yml`, otherwise with the local PHP; `composer analyse -- --generate-baseline` regenerates the baseline |
| `analyse:init` | Write a `phpstan.neon` with the shared rules and an empty baseline when they are missing, and print the remaining steps |

## PHPStan

Every Internet Guru application and package runs [Larastan](https://github.com/larastan/larastan) at level 8 with the shared rules in `phpstan/ig.neon`. Each project keeps Larastan in its own `require-dev` (`composer require --dev larastan/larastan`; a package also requires this package as a dev dependency and allows its plugin), and a `phpstan.neon` with its own paths and baseline:

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/internetguru/laravel-scripts/phpstan/ig.neon
    - phpstan-baseline.neon

parameters:
    paths:
        - app/   # src/ in a package
```

CI runs it through the shared workflow in this repository. Add `.github/workflows/phpstan.yml` to the project:

```yaml
name: PHPStan

on:
  push:
    branches: [dev, staging, main, master]
  pull_request:
    branches: [dev, staging, main, master]

jobs:
  phpstan:
    uses: internetguru/laravel-scripts/.github/workflows/phpstan.yml@main
```

The workflow takes optional `php-version` (default `8.4`) and `extensions` inputs. After every `composer update`, the plugin warns when the project has no PHPStan setup, does not include the shared rules, or has no workflow calling the shared one. It never fails the update, and it stays quiet in CI and under `--no-dev`.

New errors are fixed, not added to the baseline; the shared rules report baseline entries whose error is gone, so baselines only shrink.

## AI guidelines

The package ships [Laravel Boost](https://github.com/laravel/boost) guidelines (`resources/boost/guidelines`) and skills (`resources/boost/skills`). Boost merges them into `CLAUDE.md` / `AGENTS.md` and installs the skills.

1. In `boost.json`, add `internetguru/laravel-scripts` to `packages` (or pick it when `php artisan boost:update` asks).
2. After every `composer update`, the package runs `php artisan boost:update` itself (in projects with a `boost.json`); commit the result.

Put project-specific rules in the application's `.ai/rules`, not here.

## License & Commercial Terms

### License

Copyright © 2026 **Internet Guru**

This software is licensed under the [Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)](http://creativecommons.org/licenses/by-nc-sa/4.0/) license.

> **Disclaimer:** This software is provided "as is", without warranty of any kind, express or implied. In no event shall the authors or copyright holders be liable for any claim, damages or other liability.

### Commercial Use

The standard CC BY-NC-SA license prohibits commercial use. If you wish to use this software in a commercial environment or product, we offer **flexible commercial licenses** tailored to:

* Your company size.
* The nature of your project.
* Your specific integration needs.

**Note:** In many instances (especially for startups or small-scale tools), this may result in no fees being charged at all. Please contact us to obtain written permission or a commercial agreement.

**Contact for Licensing:** [info@internetguru.io](mailto:info@internetguru.io)

### Professional Services

Are you looking to get the most out of this project? We are available for:

* **Custom Development:** Tailoring the software to your specific requirements.
* **Integration & Support:** Helping your team implement and maintain the solution.
* **Training & Workshops:** Seminars and hands-on workshops for your developers.

Reach out to us at [info@internetguru.io](mailto:info@internetguru.io) — we are more than happy to assist you!
