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
