<?php
// This file handles special loading of mu-plugins.

/*
 * The GlotPress/WordPress locales (`GP_Locale`/`GP_Locales`) live in the meta
 * repository's `pub` mu-plugin, which has no wp-env source. Map it in via
 * `.wp-env.override.json` to enable the locale switcher and locale-aware REST
 * endpoints; without it the environment still boots and those features no-op.
 */
if ( file_exists( WPMU_PLUGIN_DIR . '/pub/locales.php' ) ) {
	require_once WPMU_PLUGIN_DIR . '/pub/locales.php';
}

require_once WPMU_PLUGIN_DIR . '/wporg-mu-plugins/mu-plugins/loader.php';
