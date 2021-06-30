<?php
/**
 * Plugin Name: Pattern Translations
 * Description: Imports Pattern translations into GlotPress and provides translated patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */
namespace WordPressdotorg\Pattern_Translations;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

const GLOTPRESS_PROJECT = 'disabled/patterns';
const TRANSLATED_BY_GLOTPRESS_KEY = '_glotpress_translated';

require __DIR__ . '/includes/pattern.php';
require __DIR__ . '/includes/parser.php';
require __DIR__ . '/includes/i18n.php';
require __DIR__ . '/includes/makepot.php';
require __DIR__ . '/includes/cron.php';

if ( defined( 'WP_CLI' ) ) {
	require __DIR__ . '/includes/cli-commands.php';
}

/**
 * Creates or updates a localised pattern.
 */
function create_or_update_translated_pattern( Pattern $pattern ) {
	$args = [
		'ID'           => $pattern->ID,
		'post_type'    => POST_TYPE,
		'post_title'   => $pattern->title,
		'post_name'    => $pattern->ID ? $pattern->name : ( $pattern->name . '-' . $pattern->locale ), // TODO: Translate the slug?
		'post_content' => $pattern->html,
		'post_parent'  => $pattern->parent ? $pattern->parent->ID : 0,
		'post_author'  => $pattern->parent ? get_post( $pattern->parent->ID )->post_author : 0,
		'post_status'  => 'publish',
		'meta_input'   => [
			'wpop_description' => $pattern->description,
			'wpop_locale'      => $pattern->locale,
		]
	];

	if ( ! $args['ID'] ) {
		unset( $args['ID'] );
	}

	return wp_insert_post( $args, true );
}