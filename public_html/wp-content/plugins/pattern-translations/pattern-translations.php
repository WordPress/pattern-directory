<?php
/**
 * Plugin Name: Pattern Translations
 * Description: Imports Pattern translations into GlotPress and provides translated patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */
namespace WordPressdotorg\Pattern_Translations;

const GLOTPRESS_PROJECT = 'disabled/patterns';
const TRANSLATED_BY_GLOTPRESS_KEY = '_glotpress_translated';

require __DIR__ . '/includes/pattern.php';
require __DIR__ . '/includes/parser.php';
require __DIR__ . '/includes/i18n.php';
require __DIR__ . '/includes/makepot.php';

if ( defined( 'WP_CLI' ) ) {
	require __DIR__ . '/includes/cli-commands.php';
}