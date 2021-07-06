<?php
/**
 * Template Name: Search
 *
 * The main search file.
 *
 * @package WordPressdotorg\Pattern_Directory\Theme
 */

namespace WordPressdotorg\Pattern_Directory\Theme;

get_header();

global $wp_query;
?> 

<div id="patterns-search__container" data-result-count="<?php echo intval( $wp_query->found_posts ); ?>"></div>

<?php
get_footer();
