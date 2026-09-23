---
name: ig-translations
description: "Add or change user-facing text and translations in an Internet Guru application or package. Activate when adding a string to a Blade view, Livewire component, notification, validation message or enum label, when editing files under lang/, or when a translation key shows up on a page instead of its text."
metadata:
  author: internetguru
---
# Translations

## Layout

- Translations are PHP array files per screen or area: `lang/<locale>/<file>.php`, used as `__('file.key')`. JSON translation files are not used.
- Each file is a flat array, and dot notation lives in the key string itself. Never nest arrays:

  ```php
  // Right
  'checkout' => 'Confirm and leave',
  'checkout.alert' => 'Are you sure you want to check out the order?',

  // Wrong
  'checkout' => [
      'alert' => 'Are you sure you want to check out the order?',
  ],
  ```

  Keys are lowercase kebab-case segments joined by dots (`online.pay-button`). A key can coexist with its dotted children (`checkout` next to `checkout.alert`), which nesting cannot do. The only exception is Laravel's own `validation.php`, which keeps the framework's structure.
- Every locale the project has (`ls lang`, typically `cs` and `en`, some add `da`) gets every key. `en` is the reference.
- Package strings are namespaced: `__('ig-common::file.key')`. Override them in `lang/vendor/<namespace>/<locale>/<file>.php`, not in the package.

## Rules

- Never hardcode user-facing text, including button labels, flash messages, e-mail subjects and `title`/`aria-label` attributes.
- In Blade, write plain text as `@lang('file.key')`. Use `__('file.key')` only where a directive cannot go: attributes, component props and PHP expressions.
- Spell keys out literally. Use a `match` over enum cases rather than `__("status.{$this->value}")`. Interpolated keys are invisible to the checks below, so a missing translation reaches the page as the raw key.
- Put a key in the file of the screen that uses it. Move text shared by several screens to the existing shared file (look for `layouts.php` or `navig.php`) rather than duplicating it.
- Use `:placeholder` parameters instead of concatenating translated fragments, since word order differs between languages.
- Write proper Czech: diacritics, the formal "vy" in customer-facing text, and the plural forms needed by `trans_choice` (`{1} … |[2,4] … |[5,*] …`). When unsure of a translation, write your best one and tell the user which keys to review.

## Checks

Projects that ship them have scripts in the root:

- `./validate_trans.sh` prints the keys used in code that are missing from `lang/en`.
- `./langdiff.sh lang/en lang/cs` prints the keys present in one locale and missing in the other.

Run them after changing strings, if present. A clean run is the bar.
