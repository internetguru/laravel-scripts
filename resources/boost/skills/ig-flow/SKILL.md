---
name: ig-flow
description: "Branches, versions and releases in Internet Guru repositories, managed by the Flow script (github.com/internetguru/flow). Activate before starting a task (to pull with `flow --pull` and pick the branch), before committing, after committing (to offer pushing or releasing), before touching VERSION or a CHANGELOG.md version heading, when the user mentions a release, release candidate, hotfix, feature branch, tag or `flow`, or when git history shows commits like 'Update changelog' or 'Update control files'."
---

# Flow

Every Internet Guru repository with a `VERSION` file and a `dev` branch is managed by [Flow](https://github.com/internetguru/flow), a git-flow script.

## Model

| Branch | Holds |
| --- | --- |
| `dev` | Ongoing development; `VERSION` is the next minor |
| `staging` | The release candidate under test |
| `main` or `master` | Production; every release is tagged `vX.Y.Z` |
| `main-N` or `master-N` | Production line of an older major N |
| feature | One feature, from `dev` |
| hotfix | One fix, from production |

A change moves feature → `dev` → `staging` → production; a hotfix goes straight to production and is merged down.

## Use the `flow` command

- Flow owns `VERSION`, the version headings and compare links in `CHANGELOG.md`, the tags, and every merge between the branches above. Never do these by hand, and never write or amend Flow's commits (`Update changelog…`, `Update control files`).
- The one exception is a major bump, when a breaking change requires it and the user decides to: a plain `VERSION` edit on `dev`, committed alone, e.g. `Start version 4.0.0`.
- Commit finished changes yourself, on the current branch. After committing, offer to push or release and say exactly what each runs.
- Run a Flow command only after the user agrees to that specific step; approving one step does not approve the next. Pass `--yes`, since there is no terminal for Flow's prompts.
- A non-zero exit stops the sequence: report it. Never retry with `--force`, and use `--conform` only on `staging`, unless the user asks.
- Changelog entries go under `[Unreleased]` on `dev`, feature and hotfix branches, and under the current release candidate heading on `staging` (see `ig-changelog`).

## Common commands

Check the major version first with `flow -V`.

The same in Flow 3 and 4:

| Task | Command |
| --- | --- |
| Before a task: update all branches | `flow --pull` (needs a clean tree; ask about uncommitted changes) |
| Advance the current branch: feature → `dev`, `dev` → candidate on `staging`, `staging` → production, hotfix → patch release | `flow --yes` |
| Push work committed on `staging` | `flow --yes --conform --pull`, then `flow --push` |
| Release `staging` with its own commits | `flow --yes --conform` on `staging` |
| Push all branches and tags | `flow --push` |

What differs:

| Task | Flow 3 | Flow 4 |
| --- | --- | --- |
| Start a feature | `flow --yes NAME` on `dev` | `flow --yes NAME` on `dev` (a name is required) |
| Start a hotfix | `flow --yes hotfix` | `flow --yes -b main` or `flow --yes -b main NAME` |
| Act on another branch | `flow --yes BRANCH` | `flow --yes -b BRANCH` |
| Hotfix an older major | by hand before 3.3; read Flow's changelog | `flow --yes -b main-N` |

In Flow 4 the argument is always a new branch name; an existing branch is given with `-b`.

For anything else, read `flow -h`, `man flow`, or the [Flow repository](https://github.com/internetguru/flow) and its changelog. Do not guess.

## Before starting a task

1. Run `flow --pull`. If it fails to fast-forward, the branches have diverged: report it and do not merge.
2. After pulling an application, check that `Dockerfile` and `docker-compose*.yml` still exist. If one is gone, tell the user to redeploy with docker-ansible; do not recreate it.
3. Check the branch with `git branch --show-current`. Work there if it fits the task. Otherwise recommend one and ask: a new feature branch on `dev`, a hotfix from production, or `staging` for a fix of the release candidate. Never commit on production.
