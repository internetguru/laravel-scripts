---
name: ig-frontend
description: "Build the frontend of an Internet Guru application: Blade views and components, the x-ig:: package components, Sass on Bootstrap 5, Alpine.js, Font Awesome icons and Vite assets. Activate when creating or editing anything under resources/views, resources/sass or resources/js, when adding a form, card, modal, button, icon or image, or when a style, icon or Alpine component does not show up."
metadata:
  author: internetguru
---
# Frontend

The stack is Blade and Livewire 4 on Bootstrap 5, compiled by Vite, with Alpine.js from Livewire and Font Awesome SVG icons. `internetguru/laravel-common` and its sibling packages supply the components, Sass and Alpine components that applications share.

## Blade

- **Reuse package components before writing markup:**
  - `x-ig::` (laravel-common): `input`, `form`, `submit`, `card`, `card-row`, `modal`, `label`, `message`, `breadcrumb`, `print-button`, `editable`, `copy-url`, `share-page`, `association-history`, `footer`
  - `x-ig-user::` (laravel-user) and `x-model-browser::` (laravel-model-browser)
  - Read a component's class in the package (`src/View/Components`) for its attributes before using it.
- **Write forms with `x-ig::form`, `x-ig::input` and `x-ig::submit`,** not raw `<form>`, `<input>` and `<button>` elements. They handle labels, validation errors, old input and translations.
- **Check `resources/views/vendor/<namespace>` before changing a package component.** It may hold the application's own fork of that view.
- **Text:**
  - Plain text uses `@lang('file.key')`.
  - `__('file.key')` is only for places `@lang` cannot go: attributes, component props and PHP expressions.
  - Activate `ig-translations`.
- **No inline `style="…"`.** Add a class and style it in Sass. The only exception is a value that exists only at runtime, passed as a CSS custom property, e.g. `style="--progress: {{ $percent }}%"`.
- **No `<script>` in Blade** unless there is no other way, e.g. JSON-LD or a third-party snippet that must sit in the page. Behaviour belongs in a global Alpine component.

## Alpine.js

- Alpine comes from Livewire's ESM bundle (`import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm'` in `resources/js/app.js`). Never install or import `alpinejs` separately. Plugins such as `@alpinejs/mask` are registered with `Alpine.plugin()` before `Livewire.start()`.
- **Prefer global components.** Put each one in its own module in `resources/js/<name>.js` exporting a factory, and register it with `Alpine.data('<name>', factory)` in `app.js`. The packages do the same: `editable`, `print`, `clearable`, `cardRow`, `tagCloud` and `mergeSearch` are already registered, so reuse them.
- Inline `x-data="{ open: false }"` is fine for a one-line toggle. Anything with methods, or used twice, becomes a global component.
- Server state and anything validated or saved belong in Livewire. Alpine handles only UI state such as toggles, copying and sharing.

## Sass

- **`resources/sass/app.scss` only imports partials,** in this order:
  1. package variables (`ig::common/variables`, …), then the app's `abstract/variables`
  2. Bootstrap
  3. package components (`ig::common/btn`, `card`, `form`, …)
  4. the app's components, layout and page styles
  5. package page styles (`ig::user/…`, `ig::model-browser/main`)
- **Partials go in:**
  - `abstract/`: variables, mixins
  - `components/`: reusable pieces
  - `layout/`: page frame
  - `specific/`: one screen or feature
  - A new partial must be imported in `app.scss`.
- **Variables:** use Bootstrap and package variables (`$spacer`, `$card-max-width`, theme colours) instead of hardcoded values. Add a new value to `abstract/_variables.scss`.
- **Grid:** `ig::common` disables Bootstrap's grid classes (`$enable-grid-classes: false`). `row`/`col-md-6` markup does nothing unless the app defines it; layouts use the app's own `.row` or `.col` rules and the `flex`/`flexNowrap` mixins. Bootstrap's other utility classes (spacing, display, text) are available and preferred over new CSS.
- **Breakpoints:** mobile-first, with the `ig::common` mixins `@include media-1` … `media-4` (min-width 30, 40, 60 and 80em). Avoid raw `@media` and Bootstrap's `media-breakpoint-*` mixins.
- **Imports:** keep `@import`, because the `ig::` package partials depend on it. Do not convert files to `@use`.
- **Custom properties:** never write `url(...)` in SCSS for an image passed through a custom property, not even as a `var()` fallback. Vite rewrites the whole declaration and drops the `var()`. The view must pass an absolute URL (`url('…')` in Blade).

## Icons and images

- **Icons:** Font Awesome uses the SVG core with an explicit library. Every icon must be imported and added to `library.add(…)` in `resources/js/fontawesome-init.js`. Otherwise the `<i class="fa-solid fa-…">` tag silently renders nothing, even when the class name is right.
- **Images:** site images live in `resources/images` and are referenced with `Vite::asset('resources/images/…')`, so they are content-hashed. Only fixed-path files such as favicons, the web manifest and `public/icons` stay in `public/`.

## Building

Never run `npm run build` or `npm run dev`. If a change does not appear in the browser, tell the user it needs a Vite rebuild (`composer run dev`).
