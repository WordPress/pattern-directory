<?php
/**
 * Plugin Name: Pattern Directory
 * Description: Creates a directory to manage block patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */

namespace WordPressdotorg\Pattern_Directory;

require_once __DIR__ . '/admin/admin-bootstrap.php';
//require_once __DIR__ . '/creator/creator-bootstrap.php';

// Bundle the theme with the plugin, because the loose coupling in past directory projects has been frustrating.
register_theme_directory( __DIR__ . '/themes' );
