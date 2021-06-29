<?php
/**
 * Plugin Name: Pattern Translations
 * Description: Imports Pattern translations into GlotPress and provides translated patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */
namespace A8C\Lib\Patterns;

require __DIR__ . '/init.php';
require __DIR__ . '/pattern-meta.php';
require __DIR__ . '/pattern-media.php';
require __DIR__ . '/pattern-stores.php';
require __DIR__ . '/pattern-cache.php';
require __DIR__ . '/parser.php';
require __DIR__ . '/i18n.php';
require __DIR__ . '/makepot.php';

if ( defined( 'WP_CLI' ) ) {
	require __DIR__ . '/cli-commands.php';
}