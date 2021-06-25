#!/bin/bash

# Exit if any command fails.
set -e

# Install dependencies
yarn

# Build the project
yarn workspaces run build

# Setup the environment
yarn wp-env start --update

# Update wp configs
yarn wp-env run cli wp config set JETPACK_DEV_DEBUG true
yarn wp-env run cli wp config set WPORG_SANDBOXED true

# Activate plugins
yarn wp-env run cli wp plugin activate gutenberg
yarn wp-env run cli wp plugin activate pattern-directory/bootstrap.php
yarn wp-env run cli wp plugin activate pattern-creator

# Activate theme
yarn wp-env run cli wp theme activate pattern-directory

# Change permalinks
yarn wp-env run cli wp rewrite structure '/%postname%/'

# Import tables
npm run wp-env run cli wp db import wp-content/uploads/data/wporg_locales.sql
