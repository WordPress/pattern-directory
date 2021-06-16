<?php
/**
 * Template for search form.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WPBBP
 */
global $wp;

$action = home_url( $wp->request );

?>
<form role="search" method="get" class="pattern-search" action="<?php echo $action; ?>">
	<label for="serach" class="screen-reader-text"><?php echo esc_html( _x( 'Search for:', 'label', 'wporg-patterns]' ) ); ?></label>
	<input
		type="search"
		id="search"
		value="<?php echo get_query_var( 'search' ); ?>"
		name="search"
		placeholder="<?php esc_html_e( 'Search patterns', 'wporg-learn' ); ?>"
	/>
	<button type="submit" class="pattern-search__button">
		<span class="screen-reader-text">
			<?php esc_html_e( 'Search', 'wporg-learn' ); ?>
		</span>
		<i class="dashicons dashicons-search"></i>
	
	</button>
</form>
