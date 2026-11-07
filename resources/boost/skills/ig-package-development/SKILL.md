---
name: ig-package-development
description: "Decide whether a feature belongs in an internetguru/* Composer package (laravel-common, laravel-user, laravel-model-browser, laravel-feedback, ...) and change that package. Activate before building a new feature or reusable piece in the application, when a fix or feature belongs in a package rather than the application, when the user mentions a package by name or its view namespace (ig-common, ig-user, ig-feedback, model-browser), or when tempted to edit anything under vendor/internetguru."
metadata:
  author: internetguru
---
# Developing internetguru packages

The application installs `internetguru/*` packages from Packagist. Their source lives in separate GitHub repositories (`github.com/internetguru/<package>`), which developers check out next to their applications.

## Where a feature belongs

Before building a new feature, or a reusable piece of one, decide where it lives. Prefer a package whenever it makes sense, so every application gets it and it is maintained once.

1. **An existing package.** List the installed ones with `composer show 'internetguru/*'`, keep in mind the other `laravel-*` packages at `github.com/internetguru`, and read their READMEs. If the feature extends what a package already does, or is generic enough that other Internet Guru applications would use it (a Blade or Livewire component, trait, validation rule, middleware, helper, Artisan command, Sass partial, Alpine component), it goes there. `laravel-common` takes generic pieces that fit no more specific package.
2. **A new package**, as a last resort: only for a self-contained area that no existing package covers and that more applications will need. Propose its name and scope; don't create it yourself.
3. **The application**, for what only this application needs: its own domain models, pages, business rules and texts.

A feature can split: keep the generic part in a package and the application-specific part, such as configuration, texts or a view override, in the application.

Before writing code, tell the user where you would put the feature and why, and let them decide. When it goes to a package, check its compatibility first (see below).

When a package change is worth considering but not made now, for example because the application uses a stopgap until the package is released, record it under the application's Considerations (see the Internet Guru guidelines). Name the package, and the stopgap to remove once the release is in the application.

## Rules

- Never edit `vendor/`, never copy files into it, and never add a path repository or symlink. To run unreleased package code, use the vendor mount described below.
- Make the change in the package's own checkout. If you don't know where it is, ask the user for the path instead of guessing.
- Work on the package's `dev` branch unless the user says otherwise. Packages use Flow like applications: `VERSION`, tags and merges go only through Flow, after the user agrees. Activate `ig-flow`.
- Add a `CHANGELOG.md` entry in the same commit; activate `ig-changelog`.
- Follow the package's own conventions, not the application's. Read sibling files first, and run the package's Pint config if it has one.

## Running unreleased package code in the application

Developers test a package change in the application by mounting the package checkout over its `vendor/` directory inside the containers. This happens in a Docker override file, never in the host's `vendor/`:

- Each developer keeps a `docker-compose.vendor.yml` next to their package checkouts (on the maintainer's machine, `composer/internetguru/docker-compose.vendor.yml`). It is personal, holds absolute paths, and is not committed.
- For each package under test it mounts `<checkout>:/app/vendor/internetguru/<package>` on the `laravel`, `queue` and `scheduler` services. Only the packages being tested are left uncommented; the rest are commented out.
- The local stack is redeployed with it: `./run.sh deploy <group> <domain> -f <path>/docker-compose.vendor.yml` from docker-ansible, run against the `<group>-localhost` host.

Check which packages are mounted in the running container:

```bash
docker inspect -f '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{println}}{{end}}' $(docker compose ps -q laravel)
```

- **Mounted:** the change is live in the container. `composer run test:all`, `composer run artisan` and the browser run it. Tests run natively on the host still use the released code in the host's `vendor/`.
- **Not mounted:** the application runs the released version, so the change is **not** visible in it. Say so rather than implying the application already has the fix. Tell the user which line to uncomment in their `docker-compose.vendor.yml` and that the stack needs redeploying with `-f`. Do not edit that file or redeploy yourself.

Either way, the change reaches other environments only after a maintainer releases the package with Flow (`dev` → `staging` → `main`) and the application's dependency is updated. If the application needs a stopgap before then, propose one explicitly, such as a view override under `resources/views/vendor/<namespace>`, and let the user decide.

## Compatibility

Several applications depend on each package with caret constraints (e.g. `^7`). Before changing a public API (a component's attributes, config keys, a trait's or contract's methods, translation keys, published views), check whether the change is backwards compatible. If it is not, stop and tell the user: it needs a major release and changes in every application.

## Testing

- Packages that have tests ship a `./test.sh`, which builds the package's Dockerfile and runs PHPUnit inside it. Run it from the package root.
- Some packages have no tests or no working harness. Say so rather than claiming the change is verified.
- Against the application: run the application's tests inside the container with the package mounted (see above).
- Fallback when Docker is unavailable: load the edited class files ahead of Composer's autoloader for a single native test run, by setting `auto_prepend_file` in an ini file on `PHP_INI_SCAN_DIR`. The Pest child process inherits that setting, which it does not with `php -d`. Delete the temporary files afterwards.

## Package guidelines

If the change adds or alters something applications should know about, update the package's `resources/boost/guidelines/core.md` if the package has one. Boost merges it into every application that installs the package.
