---
name: ig-flow
description: "Branches, versions and releases in Internet Guru repositories, which are managed by the Flow script (github.com/internetguru/flow). Activate before starting a task (to pull coworkers' changes with `flow --pull` and offer a feature or hotfix branch), after committing (to offer a push or release), before committing, before touching VERSION or a CHANGELOG.md version heading, when deciding which branch a change belongs on, when the user mentions a release, release candidate, hotfix, feature branch, tag or `flow`, or when git history shows commits like 'Update changelog' or 'Update control files'."
metadata:
  author: internetguru
---
# Flow

Every Internet Guru application and package uses [Flow](https://github.com/internetguru/flow), a git-flow script, for branching, versioning and releases. Flow owns `VERSION`, the version headings and compare links in `CHANGELOG.md`, the tags, and every merge between key branches. Developers run it by hand; `flow -h` and `man flow` describe it.

## Branches

| Branch | Holds | `VERSION` |
| --- | --- | --- |
| `dev` | Ongoing development | Next minor, e.g. `2.12.0` |
| `staging` | The release candidate under test | Candidate, e.g. `2.11.0`, with changelog heading `[2.11.0-rc.5]` |
| `main` or `master` | Production; every release is tagged `vX.Y.Z` | Released, e.g. `2.10.2` |
| `main-N` / `master-N` | Production line of major version N, for hotfixing an older major | Released |
| `feature-<user>` or any other name | One feature, branched from `dev` | As `dev` |
| `hotfix-<user>` | One fix, branched from production | As production |

Some repositories use `main`, others `master`; follow what exists.

How a change moves:

- `flow <name>` on `dev` creates a feature branch, and `flow` on that branch merges it back into `dev`.
- `flow` on `dev` creates or increments the release candidate on `staging` and bumps `dev` to the next minor.
- `flow` on `staging` releases to production, tags it, and advances `main-N`.
- `flow hotfix` on production creates `hotfix-<user>`, and `flow` on it releases a patch version to production and merges it back down.

## Only through Flow

Never do any of these by hand. Flow does them, and doing them yourself breaks its validation:

- Change `VERSION`.
- Add, rename or date a version heading (`## [2.11.0] - 2026-09-15`, `## [2.11.0-rc.6]`), add the `_Stable release based on …_` note, or edit the compare links at the bottom of `CHANGELOG.md`.
- Create tags, or merge, rebase or cherry-pick between `dev`, `staging`, production, `main-N`, feature and hotfix branches.
- Write or amend commits named `Update changelog`, `Update changelog and increment version`, `Update changelog and restore version` or `Update control files`. These are Flow's.

## Before starting a task

First run `flow --pull` in the repository. Coworkers push to the same branches, and it brings every local branch up to date with origin (fast-forward only) before anything changes. It needs a clean working tree. If there are uncommitted changes (exit 5), ask the user how to handle them instead of stashing or discarding. A failed fast-forward means the branches have diverged: report it and don't merge on your own. When working across an application and its packages, pull each repository you are about to change.

`flow --pull` checks out every remote branch in turn. If an old branch still tracks a file that docker-ansible now generates (`docker-compose.yml`, `Dockerfile`), Git replaces the ignored local copy with that branch's version and then deletes it when switching back. After pulling an application, check that `Dockerfile` and `docker-compose*.yml` still exist. If one is gone, tell the user the local stack needs redeploying with docker-ansible (`./run.sh deploy <group> <domain>`, plus `-f …/docker-compose.vendor.yml` if they test packages); don't recreate it by hand.

Then check the current branch (`git branch --show-current`), and ask the user where the work should happen before changing any file. Offer the option that fits, and recommend one:

- **A new feature on `dev`:** work directly on `dev`, or create a feature branch with `flow --yes <name>` (a short kebab-case name; `flow --yes feature` gives `feature-<user>`).
- **A fix for production:** create a hotfix branch with `flow --yes hotfix` from `main`/`master` (or from `main-N` for an older major), or fix it on `staging` if the fix belongs in the current release candidate.
- **Already on a feature or hotfix branch:** continue there, unless the task is unrelated.

Skip the question when the user has already said where to work, or when the task changes no files.

## After committing

Once the commit is made, ask the user what comes next, and say exactly what each option runs:

- **Nothing yet:** leave it local.
- **Push:** `flow --push` pushes all branches and tags. Or push only the current branch with `git push origin <branch>`.
- **Release**, per branch:
  - feature → `dev`: `flow --yes`
  - `dev` → a release candidate on `staging`: `flow --yes`
  - `staging` → production, tagged `vX.Y.Z`: `flow --yes --conform` on `staging` (see below)
  - hotfix → a production patch: `flow --yes` on the hotfix branch
  - Follow a release with `flow --push`, if the user wants it pushed.

Run a Flow command only after the user says yes to that specific step. Approval for one step does not cover the next: releasing a candidate to `staging` is not approval to release to production. Always pass `--yes`, because Flow's confirmations need a terminal you don't have. A non-zero exit (3 nonconforming, 5 uncommitted changes, 6 nothing to do, 7 merge conflicts) stops the sequence: report it and don't retry with `--force`, or with `--conform` outside `staging`, unless the user asks.

### Work committed on `staging`

Commits made directly on `staging` are not in `dev` yet, and until they are, every Flow command stops with exit 3 ("Branch 'staging' is not merged into 'dev'", or "Missing or invalid version file on staging" when `staging` holds no release candidate yet). On `staging`, `--conform` is the expected way through:

1. Once the user approves pushing or releasing the commit, first run `flow --yes --conform --pull`. It merges `staging` into `dev` and stops without releasing. If `staging` held no candidate, it turns it into one: `VERSION` becomes the next minor and an empty `## [X.Y.0-rc.1]` heading appears in `CHANGELOG.md`.
2. Write the entries for everything on the candidate under that heading and commit them. A candidate is never released with an empty section.
3. Release `staging` to production with `flow --yes --conform`.

## Changelog entries per branch

Where an entry goes depends on the branch you are on (`git branch --show-current`):

- **`dev`**: under `## [Unreleased]`, in the same commit as the change.
- **`staging`**: under the topmost heading, the current release candidate (e.g. `## [2.11.0-rc.5]`). Do not create an `[Unreleased]` section there. If the topmost heading is a released version, `staging` holds no candidate yet: never write under it, run `flow --yes --conform --pull` first (see "Work committed on `staging`") and write under the new `-rc.1` heading.
- **Feature branch**: do not edit `CHANGELOG.md`. Flow asks for the entries when it merges the feature into `dev`, and adds a generic "New feature '…'" line if none is given. When the user approves the release, pipe the entries in, one `Keyword: message` per line and an empty line to finish:

  ```bash
  printf 'Added: The gift card list can be narrowed by expiration.\nFixed: A label keeps its colour.\n\n' | flow --yes
  ```

- **Hotfix branch**: the same. Flow creates the new patch heading on release and asks for the entries, `Fixed` by default; pipe them in the same way.
- **Production or `main-N`**: do not commit there. Offer a hotfix branch instead (see "Before starting a task").

See `ig-changelog` for how to word an entry.

## Commits

- Commit when the user asks, on the current branch, then follow "After committing".
- The subject is one short imperative sentence saying what changed, with no prefix or trailing period, e.g. `Say how long a redemption took to confirm`.
- Flow refuses to run on uncommitted changes, so leave the working tree either committed or clearly reported.
