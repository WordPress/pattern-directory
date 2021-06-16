<?php
/**
 * Template for search form.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WPBBP
 */

?>
<form role="search" method="get" class="pattern-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="searchform" class="screen-reader-text"><?php echo esc_html( _x( 'Search for:', 'label', 'wporg-patterns]' ) ); ?></label>
	<input
		type="search"
		id="searchform"
		value="<?php the_search_query(); ?>"
		name="searchform"
		placeholder="<?php esc_html_e( 'Search patterns', 'wporg-learn' ); ?>"
	/>
	<button type="submit" class="pattern-search__button">
		<span class="screen-reader-text">
			<?php esc_html_e( 'Search', 'wporg-learn' ); ?>
		</span>
		<i class="dashicons dashicons-search"></i>
	
	</button>
</form>
