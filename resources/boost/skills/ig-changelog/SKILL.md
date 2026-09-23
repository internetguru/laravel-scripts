---
name: ig-changelog
description: "Write CHANGELOG.md entries the Internet Guru way. Activate whenever a change is user-visible or affects a package consumer, when the user asks to update the changelog, or when committing a feature or fix in an application or an internetguru/* package."
metadata:
  author: internetguru
---
# Changelog

Every Internet Guru application and package keeps a `CHANGELOG.md` in the [Keep a Changelog](https://keepachangelog.com/en/2.0.0/) format and follows [Semantic Versioning](https://semver.org/). The [Flow](https://github.com/internetguru/flow) script maintains the file's structure; you only write the entries.

## Where entries go

- The section depends on the branch (activate `ig-flow` for details):
  - `dev`: under `## [Unreleased]`
  - `staging`: under the topmost heading, the current release candidate. If that heading is a released version, Flow has to create the candidate first (see `ig-flow`)
  - feature and hotfix branches: don't edit the file. Flow asks for the entries at release; pipe them in as `Keyword: message` lines (see `ig-flow`)
- Make the edit in the same commit as the change it describes.
- Group entries under `### Added`, `### Changed`, `### Deprecated`, `### Removed`, `### Fixed` or `### Security`, in that order. Create a heading only when it has an entry.
- Never add a version heading, a date, a `_Stable release based on …_` note or a compare link, and never touch `VERSION`. Flow writes those when it releases.
- Change nothing in released sections: they are history.
- A release never goes out with an empty section. If nothing a user sees changed, describe the tooling, CI or dependency changes it carries.

## How to write an entry

- One entry per change, one line, one or two sentences. Brevity wins: say what changed, not how.
- Write for the reader of the release notes, not for the reviewer of the diff.
  - Application: describe what a user sees or can do. Name screens, buttons and labels as the user reads them, not classes or routes.
  - Package: describe what a consumer of the package must know. Public API names such as components, config keys and traits are fine here.
- Describe the new behaviour in the present tense, starting with its subject, not with "Fix" or "Add". End with a period:
  - `- The gift card list can be narrowed by the credit left on a card.`
  - `- Payment types are named in one word each: Register, Gateway, Manual and Bank.`
  - `- A label's icon keeps its colour once Font Awesome replaces the icon tag with an SVG.`
- A change that later work in the same `[Unreleased]` section changes again is edited in place, not appended as a second entry.
- Internal refactors, tests and dependency bumps with no visible effect get no entry.
- For a breaking change in a package, say so and what to do, e.g. `- **Breaking:** \`HasLabel::label()\` returns a string; wrap existing \`HtmlString\` returns.` Point out to the user that it needs a major release.
