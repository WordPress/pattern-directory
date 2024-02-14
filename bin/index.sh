#!/bin/bash

# Exit if any command fails.
set -e

# Install dependencies
composer update
yarn

# Build the project
yarn workspaces run build

# Setup the environment
yarn wp-env start --update

# Update wp configs
yarn wp-env run cli wp config set JETPACK_DEV_DEBUG true
yarn wp-env run cli wp config set WPORG_SANDBOXED true

# Create the table for locales
yarn wp-env run cli wp db import wp-content/uploads/data/wporg_locales.sql

# Activate plugins
yarn wp-env run cli wp plugin activate wordpress-importer
yarn wp-env run cli wp plugin activate gutenberg
yarn wp-env run cli wp plugin activate pattern-directory/bootstrap.php
yarn wp-env run cli wp plugin activate pattern-creator

# Activate theme
yarn wp-env run cli wp theme activate wporg-pattern-directory-2022

# Change permalinks
yarn wp-env run cli wp rewrite structure '/%postname%/'

# Set up site title
yarn wp-env run cli wp option update blogname "Pattern Directory"
yarn wp-env run cli wp option update blogdescription "Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste."

# Create the default pages
yarn wp-env run cli wp post create --post_type=page --post_status='publish' --post_name='front-page' --post_title='Pattern Directory'
yarn wp-env run cli wp post create --post_type=page --post_status='publish' --post_name='archives' --post_title='Archives'

yarn wp-env run cli wp option update show_on_front 'page'
yarn wp-env run cli wp option update page_on_front 4
yarn wp-env run cli wp option update page_for_posts 5
yarn wp-env run cli wp option update posts_per_page 18

# Import content
yarn wp-env run cli wp import --authors=create --skip=image_resize wp-content/uploads/data/exports/pattern-dir.000.xml
yarn wp-env run cli wp import --authors=create --skip=image_resize wp-content/uploads/data/exports/pattern-dir.001.xml
