---
name: ig-package-development
description: "Change an internetguru/* Composer package (laravel-common, laravel-user, laravel-model-browser, laravel-feedback, ...) that the application uses. Activate when a fix or feature belongs in a package rather than the application, when the user mentions a package by name or its view namespace (ig-common, ig-user, ig-feedback, model-browser), or when tempted to edit anything under vendor/internetguru."
metadata:
  author: internetguru
---
# Developing internetguru packages

The application installs `internetguru/*` packages from Packagist. Their source lives in separate GitHub repositories (`github.com/internetguru/<package>`), which developers check out next to their applications.

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

- **Mounted:** the change is live in the container. `composer run test:php`, `composer run artisan` and the browser run it. Tests run natively on the host still use the released code in the host's `vendor/`.
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
