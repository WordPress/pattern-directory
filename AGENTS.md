# AGENTS.md

## What this is

The codebase for **wordpress.org/patterns** — the WordPress.org Block Pattern Directory site. It is a meta-environment monorepo: WordPress core, third-party plugins, and shared WordPress.org themes/mu-plugins are sourced directly by wp-env (`.wp-env.json`), while the project's own code lives in three npm workspaces under `public_html/wp-content/`.

## Setup & environment

Requires Docker and Node/npm. Composer is only needed for linting and PHPUnit (`composer install`).

- `npm run create` — full first-time bootstrap (`bin/index.sh`): runs `npm install`, builds all workspaces, and starts wp-env — which fetches every dependency and runs `bin/after-start.sh` (theme activation, permalinks, options) — then imports seed pattern content. Site comes up at `localhost:8888`.
- `npm run wp-env start` / `npm run wp-env stop` — bring the environment up/down. **Always run from the repo root** (where `.wp-env.json` lives), or wp-env spins up a stray instance in a sub-project.
- `npm run wp-env run cli "<wp-cli command>"` — run WP-CLI against the site, e.g. `npm run wp-env run cli "plugin list"`.
- The local environment runs **WordPress trunk on PHP 8.4** (`.wp-env.json`); Composer's `platform.php` is pinned to 7.4 for dependency resolution, so PHP code must stay 7.4-compatible.

## Build, lint, test

These are npm-workspace commands — most run per-workspace via `--workspaces` or `--workspace=<name>`.

- **Build:** `npm run build --workspaces` (all), or the convenience scripts `npm run build:creator` / `build:directory` / `build:theme`.
- **Watch:** `npm start --workspace=<name>` — one workspace at a time. Convenience: `npm run start:creator` etc.
- **Lint JS/CSS:** `npm run lint:js --workspaces` and `npm run lint:css --workspaces`.
- **Lint/format PHP:** `npm run lint:php` (= `composer run lint` = `phpcs`), `npm run format:php` (= `phpcbf`). Config in `phpcs.xml.dist` (WordPress coding standards).
- **PHP tests:** `npm run test:php` — runs PHPUnit as **multisite** (`WP_TESTS_MULTISITE=1`) in a dedicated wp-env instance defined by `.wp-env.test.json`. Requires `composer install` (provides PHPUnit) and the test instance running: it shares ports with the dev environment, so `npm run wp-env stop`, then `npm run wp-env -- --config .wp-env.test.json start`. Suite config: `public_html/wp-content/tests/phpunit/phpunit.xml`; tests live in `public_html/wp-content/plugins/pattern-directory/tests/phpunit/` (files suffixed `-test.php`).
- **JS tests:** `npm run test:unit --workspace=wporg-pattern-creator` (Jest via wp-scripts). The directory plugin and theme have no JS tests.
- Run a single PHP test: `npm run test:php -- --filter <TestNameOrMethod>`.

CI (`.github/workflows/`) runs linters on every PR and PHP+JS unit tests on changes under `public_html/` or to the environment/tooling manifests (`.wp-env.test.json`, `composer.*`, `package*.json`). The default branch is **`trunk`**.

## Workspaces & architecture

Three workspaces, each a standard `@wordpress/scripts` project extending the root `eslint.config.js` / `.stylelintrc` / `.prettierrc.js`:

| Workspace | Path | Role |
|---|---|---|
| `wporg-pattern-directory` | `plugins/pattern-directory` | Core data layer (PHP-heavy) |
| `wporg-pattern-creator` | `plugins/pattern-creator` | Front-end pattern editor (React/JS-heavy) |
| `wporg-pattern-directory-2024-theme` | `themes/wporg-pattern-directory-2024` | Block theme for the site |

(`plugins/pattern-translations` is a fourth plugin, not a JS workspace.)

**pattern-directory** is the backbone. Entry point `bootstrap.php` wires up everything via `includes/`: the `wporg-pattern` custom post type and `wporg-pattern-flag` post type, pattern validation, search, favorites, stats, badges, notifications, admin screens, and two REST controllers (`class-rest-flags-controller.php`, `class-rest-favorite-controller.php`). The single JS bundle (`src/pattern-post-type.js`) augments the block-editor admin experience for patterns.

**pattern-creator** is a front-end SPA-style block editor (`pattern-creator.php` enqueues the build of `src/index.js`) letting logged-in users create/edit patterns on the site front end. It uses a `@wordpress/data` store (`src/store`), an `api-middleware` layer, React components, and hooks — a substantial subset of Gutenberg editor packages as dependencies.

**pattern-translations** imports pattern strings into GlotPress and serves translated patterns. It depends on the directory plugin's `POST_TYPE` constant and runs scheduled cron + WP-CLI commands. The `i18n.yml` workflow regenerates translation strings twice daily and commits them to `trunk`.

### How the pieces fit

The directory plugin owns the pattern data model and APIs; the creator plugin is a front-end client that writes patterns through those APIs; the theme renders the public directory; the translations plugin localizes pattern content. All four share the `WordPressdotorg\Pattern_Directory\*` PHP namespaces and the `wporg-patterns` text domain.

wp-env sources shared WordPress.org infrastructure (`wporg-mu-plugins`, the `wporg-parent-2021` parent theme, `wporg-internal-notes`) plus Gutenberg, Stream, and the WordPress Importer directly via `.wp-env.json` — Composer only provides dev tooling (phpcs, PHPUnit). The meta repository's `pub` mu-plugin (locale data) is optional; see the readme for the `.wp-env.override.json` mapping.

## Conventions

- WordPress PHP coding standards (`phpcs.xml.dist`); keep PHP 7.4-compatible.
- Per-project `.editorconfig` and the shared root JS/CSS lint configs govern style — run the linters before pushing; CI enforces them.
