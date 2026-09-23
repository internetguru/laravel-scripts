---
name: ig-package-updates
description: "Update internetguru/* packages, or any Composer dependency, in an Internet Guru application. Activate when the user asks to update, upgrade or bump laravel-common, laravel-user or another internetguru package, when composer.lock changes, or after a package release."
metadata:
  author: internetguru
---
# Updating internetguru packages

## Update without --with-all-dependencies

Applications set `"minimum-stability": "dev"` so they can install package branches. With `-W`, Composer may therefore resolve a transitive dependency to a dev branch. This has happened before: brick/math was lifted past ramsey/uuid's cap, which pulled `ramsey/uuid` to `4.x-dev` and dragged laravel/framework, carbon and monolog along with it.

```bash
composer update 'internetguru/*'
```

Add `-W` only for the one package that needs it. Then check the lock diff:

```bash
git diff composer.lock | grep -E '"version": ".*(dev|x-dev)'
```

Any new `dev` or `x-dev` version that was not there before blocks the update. Report it to the user instead of committing.

## After updating

1. Run `php artisan boost:update` so the packages' Boost guidelines and skills are refreshed in `CLAUDE.md` and the skills directory.
2. Compare the application's overrides in `resources/views/vendor/<namespace>` with the new package views. Re-apply the application's changes on top of the new version, and flag any override that no longer matches the package's markup or data.
3. Read the packages' `CHANGELOG.md` for the versions skipped, and handle every `Breaking` or `Removed` entry.
4. Run the tests.

Commit the lock file with a short subject naming what was updated, e.g. `Update laravel-common and user`. A dependency bump gets no application changelog entry unless users can notice it.
