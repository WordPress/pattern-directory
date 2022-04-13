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
yarn wp-env run cli "wp config set JETPACK_DEV_DEBUG true"
yarn wp-env run cli "wp config set WPORG_SANDBOXED true"

# Create the table for locales
yarn wp-env run cli "wp db import wp-content/uploads/data/wporg_locales.sql"

# Activate plugins
yarn wp-env run cli "wp plugin activate wordpress-importer"
yarn wp-env run cli "wp plugin activate gutenberg"
yarn wp-env run cli "wp plugin activate pattern-directory/bootstrap.php"
yarn wp-env run cli "wp plugin activate pattern-creator"

# Activate theme
yarn wp-env run cli "wp theme activate pattern-directory"

# Change permalinks
yarn wp-env run cli "wp rewrite structure '/%postname%/'"

# Create editor user
yarn wp-env run cli "wp user create editor editor@wp.local --role=editor --user_pass=password"

# Create the default pages
yarn wp-env run cli "wp post create --post_type=page --post_status='publish' --post_name='new-pattern' --post_title='New Pattern'"
yarn wp-env run cli "wp post create --post_type=page --post_status='publish' --post_name='favorites' --post_title='My Favorites'"
yarn wp-env run cli "wp post create --post_type=page --post_status='publish' --post_name='my-patterns' --post_title='My Patterns'"

# Create some block categories for the submission window
yarn wp-env run cli "wp term create wporg-pattern-category Header --description='A header pattern'"
yarn wp-env run cli "wp term create wporg-pattern-category Footer --description='A footer pattern'"
yarn wp-env run cli "wp term create wporg-pattern-category Button --description='A button pattern'"
yarn wp-env run cli "wp term create wporg-pattern-category Columns --description='A column pattern'"
yarn wp-env run cli "wp term create wporg-pattern-category Gallery --description='A gallery pattern'"
yarn wp-env run cli "wp term create wporg-pattern-category Image --description='An image pattern'"

# Create flag reasons for block patttern moderation
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Rude, Crude, or Inappropriate' --slug=1-inappropriate --description='This pattern contains content deemed inappropriate for a general audience.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Copyrighted or Trademark Issue' --slug=2-copyright --description='This pattern contains copyrighted material or uses a trademark without permission.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Broken or Unusable' --slug=3-broken --description='This pattern is broken or does not display correctly.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Spam' --slug=4-spam --description='This pattern was determined to be spam.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Not English' --slug=5-not-english --description='This pattern is not in English. Patterns should be submitted in English, and will be translated through translate.wordpress.org.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Not a Pattern' --slug=6-not-a-pattern --description='This content is not a valid pattern. It may have too much or too little content. Patterns should keep placeholder text to a minimum, while still showcasing what each block can do.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Invalid Name' --slug=7-invalid-name --description='The title for this pattern should be changed to something more descriptive.'"
yarn wp-env run cli "wp term create wporg-pattern-flag-reason 'Other' --slug=9-other --description='Additional review has been requested for this pattern.'"
