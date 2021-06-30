<?php
/**
 * Plugin Name: Pattern Translations
 * Description: Imports Pattern translations into GlotPress and provides translated patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */
namespace WordPressdotorg\Pattern_Translations;

const GLOTPRESS_PROJECT = 'disabled/patterns';

require __DIR__ . '/pattern-stores.php';
require __DIR__ . '/parser.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/makepot.php';

if ( defined( 'WP_CLI' ) ) {
	require __DIR__ . '/cli-commands.php';
}