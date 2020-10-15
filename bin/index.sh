#!/bin/bash

# Exit if any command fails.
set -e

# Setup the environment
npm run wp-env start

# Update wp configs
npm run wp-env run cli wp config set JETPACK_DEV_DEBUG true
npm run wp-env run cli wp config set WPORG_SANDBOXED true

# Activate plugins
npm run wp-env run cli wp plugin activate pattern-directory/bootstrap.php

# Activate theme
npm run wp-env run cli wp theme activate pattern-directory

# Install dependencies
yarn

# Change permalinks
npm run wp-env run cli wp rewrite structure '/%postname%/'
