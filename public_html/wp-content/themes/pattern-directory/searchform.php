<?php
/**
 * Template for search form.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPressdotorg\Pattern_Directory\Theme
 */

?>
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="pattern-search">
	<label for="s" class="screen-reader-text"><?php echo esc_html( _x( 'Search for:', 'label', 'wporg-patterns' ) ); ?></label>
	<input
		type="search"
		id="s"
		value="<?php echo get_search_query(); ?>"
		name="s"
		placeholder="<?php esc_html_e( 'Search patterns', 'wporg-patterns' ); ?>"
		required
	/>
	<button type="submit" class="pattern-search__button">
		<span class="screen-reader-text">
			<?php esc_html_e( 'Search', 'wporg-patterns' ); ?>
		</span>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-hidden="true" focusable="false"><path d="M13.5 6C10.5 6 8 8.5 8 11.5c0 1.1.3 2.1.9 3l-3.4 3 1 1.1 3.4-2.9c1 .9 2.2 1.4 3.6 1.4 3 0 5.5-2.5 5.5-5.5C19 8.5 16.5 6 13.5 6zm0 9.5c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z"></path></svg>	
	</button>
</form>
