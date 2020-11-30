# WordPress.org Pattern Directory

## Prerequisites
- Docker
- Node/NPM
- Yarn
- Composer

## Setup
1. `yarn`
2. `yarn run create`
3. Visit site at `localhost:8888`

### Stopping & Starting Environment

If you need to work on another project, your environment can be safely stopped with:

	yarn run wp-env stop

When you want to come back to work, bring the project back up with:

	yarn run wp-env start

Make sure you're in the project root (same as `.wp-env.json`), otherwise `wp-env` will create a new site instance in one of the sub-projects (and you'll spend a while wondering why nothing's synced 🤨).

### WP-CLI Commands

You can run wp-cli commands on your site using the cli container. Send any command to it like this:

	yarn wp-env run cli "theme list"

### Removing Environment

To remove your environment entirely, you can [destroy it.](https://github.com/WordPress/gutenberg/tree/master/packages/env#6-nuke-everything-and-start-again-) This will wipe everything associated with your site!

	yarn run wp-env destroy

## Development

While working on the theme & plugin, you might need to rebuild the CSS or JavaScript.

To build both projects, you can run:

	yarn workspaces run build

To build one at a time, run

	yarn workspace wporg-pattern-directory build

If you want to watch for changes, run `start`. This can only be run in one project at a time:

	yarn workspace wporg-pattern-directory start

### Workspaces

The available workspaces are:

	"wporg-pattern-creator": "public_html/wp-content/plugins/pattern-creator"
	"wporg-pattern-directory": "public_html/wp-content/plugins/pattern-directory"
	"wporg-pattern-directory-theme": "public_html/wp-content/themes/pattern-directory"

### Linting

This project has eslint, stylelint, and phpcs set up for linting the code. This ensures all developers are working from the same style. To check your code before pushing it to the repo, run

	yarn workspaces run lint:css
	yarn workspaces run lint:js
	composer run lint

These checks will also be run automatically on each PR.
