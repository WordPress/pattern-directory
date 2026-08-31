# WordPress.org Pattern Directory

## Prerequisites
- Docker
- Node/NPM
- Composer (only for linting and PHPUnit — the environment itself no longer needs it)

## Setup
1. `npm install`
2. `npm run create`
3. Visit site at `localhost:8888`

`wp-env` fetches every dependency directly: WordPress core, the directory plugins and theme, Gutenberg, and the `wporg-mu-plugins` build (which provides the global header and footer). No Composer step is required to build the site.

### Optional: locale data

The GlotPress/WordPress locales live in the meta repository's `pub` mu-plugin, which has no `wp-env` source. The environment boots without it; the locale switcher and locale-aware REST endpoints simply no-op. To enable them, check out [`WordPress/wordpress.org`](https://github.com/WordPress/wordpress.org) and map `pub` in a (git-ignored) `.wp-env.override.json`:

```json
{
	"mappings": {
		"wp-content/mu-plugins/pub": "../wordpress.org/public_html/wp-content/mu-plugins/pub"
	}
}
```

### Stopping & Starting Environment

If you need to work on another project, your environment can be safely stopped with:

	npm run wp-env stop

When you want to come back to work, bring the project back up with:

	npm run wp-env start

Make sure you're in the project root (same as `.wp-env.json`), otherwise `wp-env` will create a new site instance in one of the sub-projects (and you'll spend a while wondering why nothing's synced 🤨).

### WP-CLI Commands

You can run wp-cli commands on your site using the cli container. Send any command to it like this:

	npm run wp-env run cli "theme list"

### Removing Environment

To remove your environment entirely, you can [destroy it.](https://github.com/WordPress/gutenberg/tree/master/packages/env#6-nuke-everything-and-start-again-) This will wipe everything associated with your site!

	npm run wp-env destroy

## Development

While working on the theme & plugin, you might need to rebuild the CSS or JavaScript.

To build both projects, you can run:

	npm run build --workspaces

To build one at a time, run

	npm run --workspace=wporg-pattern-directory

If you want to watch for changes, run `start`. This can only be run in one project at a time:

	npm start --workspace=wporg-pattern-directory

### Workspaces

The available workspaces are:

	"wporg-pattern-creator": "public_html/wp-content/plugins/pattern-creator"
	"wporg-pattern-directory": "public_html/wp-content/plugins/pattern-directory"

### Linting

This project has eslint, stylelint, and phpcs set up for linting the code. This ensures all developers are working from the same style. phpcs comes from Composer, so run `composer install` once first. To check your code before pushing it to the repo, run

	npm run lint:css --workspaces
	npm run lint:js --workspaces
	composer run lint

These checks will also be run automatically on each PR.

### PHP unit tests

PHPUnit is installed with `composer install`. The test suite runs in its own `wp-env` instance, defined by `.wp-env.test.json` and served at `localhost:8889`, so it can run alongside the development environment. Start the test instance once, then run the tests:

	npm run test:env start
	npm run test:php

`npm run test:env` accepts all the usual `wp-env` commands (`stop`, `destroy`, `run cli ...`) and targets the test instance.

To run a single test or test class, pass PHPUnit's `--filter`:

	npm run test:php -- --filter <TestNameOrMethod>
