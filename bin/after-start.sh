#!/bin/bash
#
# Runs after `wp-env start`. Handles the idempotent parts of environment setup
# that wp-env doesn't cover itself: theme activation, permalinks, and site
# options. Plugins are activated by wp-env (everything listed in `plugins`);
# one-time content seeding lives in bin/index.sh.
#

set -e

WP="npx wp-env run cli --"

# Activate the front-end theme — wp-env installs themes but doesn't activate them.
$WP wp theme activate wporg-pattern-directory-2024

# Pretty permalinks are required for the REST API and pattern routes. --hard
# writes .htaccess: wp-env's generated wp-cli.yml declares mod_rewrite, so the
# CLI container can regenerate the file.
$WP wp rewrite structure '/%postname%/' --hard

# Site identity.
$WP wp option update blogname "Pattern Directory"
$WP wp option update blogdescription "Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste."
