---
name: ig-frontend
description: "Build the frontend of an Internet Guru application: Blade views and components, the x-ig:: package components, Sass on Bootstrap 5, Alpine.js, Font Awesome icons and Vite assets. Activate when creating or editing anything under resources/views, resources/sass or resources/js, when adding a form, card, modal, info box, button, icon or image, when setting a font size, colour or spacing, or when a style, icon or Alpine component does not show up."
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
- **Units:** never `px`, in Sass or in Blade. Use relative units so the layout follows the user's font size and zoom:
  - `rem` for spacing, sizes and font sizes; `em` for a size that should follow the element's own font, such as an icon or a button's padding
  - `%`, `vw`, `vh`, `dvh`, `svh` for fractions of the parent or the viewport, `ch` for text widths
  - borders: the keywords `thin`, `medium`, `thick`, or `$border-width`
  - media queries in `em` (the `media-N` mixins already are)
  - `0` without a unit
  - `px` stays only where no relative unit can express the value, e.g. a third-party widget that requires it.
- **Grid:** `ig::common` disables Bootstrap's grid classes (`$enable-grid-classes: false`). `row`/`col-md-6` markup does nothing unless the app defines it; layouts use the app's own `.row` or `.col` rules and the `flex`/`flexNowrap` mixins. Bootstrap's other utility classes (spacing, display, text) are available and preferred over new CSS.
- **Breakpoints:** mobile-first, with the `ig::common` mixins `@include media-1` … `media-4` (min-width 30, 40, 60 and 80em). Avoid raw `@media` and Bootstrap's `media-breakpoint-*` mixins.
- **Imports:** keep `@import`, because the `ig::` package partials depend on it. Do not convert files to `@use`.
- **Custom properties:** never write `url(...)` in SCSS for an image passed through a custom property, not even as a `var()` fallback. Vite rewrites the whole declaration and drops the `var()`. The view must pass an absolute URL (`url('…')` in Blade).

## Readability

Never make text harder to read to make it look less important. Show that something is secondary through its position, spacing, a heading, an icon, a border or a background, not by making the text smaller, lighter or thinner. A small grey info box with faded text is the typical mistake. It is exactly the text people skip or can't read, and it often holds the one note they need.

- **Font size:** no text below `1rem` (`$font-size-base`). That includes hints, help and form text, info and note boxes, footnotes, captions, table cells, dates, prices and legal text. Bootstrap's `<small>`, `.small`, `.form-text` and `.btn-sm` render smaller than the base size (`.875`). Don't use them, or set `$small-font-size`, `$form-text-font-size` and `$btn-font-size-sm` to `1em`. Never lower `$font-size-base`. Where space is short, shorten the text or let it wrap.
- **Contrast:** at least WCAG AA:
  - 4.5:1 for normal text, 3:1 for large text (from `1.5rem`, or `1.2rem` bold)
  - 3:1 for icons, borders and focus rings that carry meaning
  - Check the text colour against the background it actually sits on. Grey text on a light grey or tinted box (`$gray-600` on `$gray-100`, `.text-muted` inside `.bg-light` or an `.alert`) usually fails even though each looks fine on white.
- **No fading:** no `opacity` on text, no light greys (`$gray-400`–`$gray-600`) for content, and no `.text-muted` or `.text-body-secondary` for anything people need to read. Use `$body-color`, or a theme colour that passes contrast.
- **Weight and style:** body text stays at weight 400 or more. Don't use light or thin weights, long italic passages, or `text-uppercase` on more than a few words.
- **Line length and spacing:** paragraphs at most about `70ch` wide, `line-height` at least 1.5, no negative `letter-spacing`.
- **Text over images and colour:** put text on a solid background or an overlay that keeps the contrast. Never let colour alone carry the meaning: add a word or an icon.
- **Disabled and placeholder text:** a placeholder is not a label and does not replace one. Don't put information only in placeholder or disabled text, which browsers render faded.
- **Zoom:** never block zooming (`user-scalable=no`, `maximum-scale`). The page must stay usable at 200 % zoom and reflow without horizontal scrolling at a width of `20rem`. The relative units above make this work.

## Icons and images

- **Icons:** Font Awesome uses the SVG core with an explicit library. Every icon must be imported and added to `library.add(…)` in `resources/js/fontawesome-init.js`. Otherwise the `<i class="fa-solid fa-…">` tag silently renders nothing, even when the class name is right.
- **Images:** site images live in `resources/images` and are referenced with `Vite::asset('resources/images/…')`, so they are content-hashed. Only fixed-path files such as favicons, the web manifest and `public/icons` stay in `public/`.

## Building

Never run `npm run build` or `npm run dev`. If a change does not appear in the browser, tell the user it needs a Vite rebuild (`composer run dev`).
