#!/bin/bash
#
# Runs after `wp-env start`. Handles the idempotent parts of environment setup:
# activating plugins and the theme, permalinks, the .htaccess workaround, and
# site options. One-time content seeding lives in bin/index.sh.
#

set -e

WP="npx wp-env run cli --"

# Activate supporting plugins and the two directory plugins.
echo "Activating plugins..."
$WP wp plugin activate wordpress-importer gutenberg pattern-directory/bootstrap.php pattern-creator

# Activate the front-end theme.
$WP wp theme activate wporg-pattern-directory-2024

# Pretty permalinks are required for the REST API and pattern routes.
$WP wp rewrite structure '/%postname%/'

# Write .htaccess — `wp rewrite flush --hard` doesn't work from the CLI container
# because WordPress doesn't detect Apache in that context. Route the file through
# the already-mapped data directory so the content stays in one place (.wp-env/.htaccess).
cp .wp-env/.htaccess .wp-env/data/.htaccess.tmp
$WP bash -c "cp /var/www/html/wp-content/uploads/data/.htaccess.tmp /var/www/html/.htaccess"
rm .wp-env/data/.htaccess.tmp

# Site identity.
$WP wp option update blogname "Pattern Directory"
$WP wp option update blogdescription "Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste."
