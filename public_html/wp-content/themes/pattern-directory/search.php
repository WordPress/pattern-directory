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
?> 

<div id="patterns__search" data-search-term="<?php echo get_search_query(); ?>"></div>

<?php
get_footer();
