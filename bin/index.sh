#!/bin/bash

# Exit if any command fails.
set -e

# Install dependencies
composer update
npm install

# Build the project
npm run build --workspaces

# Setup the environment
npm run wp-env start --update

# Update wp configs
npm run wp-env run cli wp config set JETPACK_DEV_DEBUG true
npm run wp-env run cli wp config set WPORG_SANDBOXED true

# Create the table for locales
npm run wp-env run cli wp db import wp-content/uploads/data/wporg_locales.sql

# Activate plugins
npm run wp-env run cli wp plugin activate wordpress-importer
npm run wp-env run cli wp plugin activate gutenberg
npm run wp-env run cli wp plugin activate pattern-directory/bootstrap.php
npm run wp-env run cli wp plugin activate pattern-creator

# Activate theme
npm run wp-env run cli -- wp theme activate wporg-pattern-directory-2024

# Change permalinks
npm run wp-env run cli wp rewrite structure '/%postname%/'

# Set up site title
npm run wp-env run cli wp option update blogname "Pattern Directory"
npm run wp-env run cli wp option update blogdescription "Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste."

# Create the default pages
npm run wp-env run cli -- wp post create --post_type=page --post_status='publish' --post_name='front-page' --post_title='Pattern Directory'
npm run wp-env run cli -- wp post create --post_type=page --post_status='publish' --post_name='archives' --post_title='Archives'

npm run wp-env run cli wp option update show_on_front 'page'
npm run wp-env run cli wp option update page_on_front 4
npm run wp-env run cli wp option update page_for_posts 5
npm run wp-env run cli wp option update posts_per_page 18

# Import content
npm run wp-env run cli -- wp import --authors=create --skip=image_resize wp-content/uploads/data/exports/pattern-dir.000.xml
npm run wp-env run cli -- wp import --authors=create --skip=image_resize wp-content/uploads/data/exports/pattern-dir.001.xml
