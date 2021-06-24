<?php
/**
 * Plugin Name: Pattern Directory
 * Description: Creates a directory to manage block patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */

namespace WordPressdotorg\Pattern_Directory;

require_once __DIR__ . '/includes/class-rest-flags-controller.php';
require_once __DIR__ . '/includes/class-rest-favorite-controller.php';
require_once __DIR__ . '/includes/pattern-post-type.php';
require_once __DIR__ . '/includes/pattern-flag-post-type.php';
require_once __DIR__ . '/includes/pattern-validation.php';
require_once __DIR__ . '/includes/search.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/favorite.php';
