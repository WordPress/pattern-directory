#!/bin/bash
#
# One-time environment bootstrap (`npm run create`). Installs dependencies,
# builds the projects, and starts wp-env — which sources every dependency and
# runs bin/after-start.sh for the idempotent setup — then seeds the local
# content. Re-run `npm run wp-env start` for day-to-day work.
#

# Exit if any command fails.
set -e

# Install dependencies.
npm install

# Build the project.
npm run build --workspaces

# Start the environment. wp-env fetches core, plugins, themes, and mu-plugins,
# then runs bin/after-start.sh (plugin/theme activation, permalinks, options).
npm run wp-env start --update

# Create the table for locales.
npm run wp-env run cli wp db import wp-content/uploads/data/wporg_locales.sql

# Create the default pages.
npm run wp-env run cli -- wp post create --post_type=page --post_status='publish' --post_name='front-page' --post_title='Pattern Directory'
npm run wp-env run cli -- wp post create --post_type=page --post_status='publish' --post_name='archives' --post_title='Archives'

npm run wp-env run cli wp option update show_on_front 'page'
npm run wp-env run cli wp option update page_on_front 4
npm run wp-env run cli wp option update page_for_posts 5
npm run wp-env run cli wp option update posts_per_page 18

# Import content.
npm run wp-env run cli -- wp import --authors=create --skip=image_resize wp-content/uploads/data/exports/pattern-dir.000.xml
npm run wp-env run cli -- wp import --authors=create --skip=image_resize wp-content/uploads/data/exports/pattern-dir.001.xml
