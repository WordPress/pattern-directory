<?php
/**
 * Template part for displaying pattern details for logged in user.
 *
 * @package WordPressdotorg\Theme;
 */

namespace WordPressdotorg\Pattern_Directory\Patterns;

$user_id = get_current_user_id();
$user_patterns = Patterns::instance()->get_patterns_by_author( $user_id );

$patterns_pending_review = array_filter( $user_patterns, function ( $pattern ) {
	// TO DO: Update this to the correct pending review status;
	return $pattern->post_status === 'draft';
});

?>

<h2><?php esc_html_e( 'My patterns', 'wporg-patterns' ); ?></h2>
<p>
	<?php echo sprintf(
			/* translators: number of patterns created. */
		__( 'You\'ve created <b>%d patterns</b>.', 'wporg-patterns' ),
		count( $user_patterns )
	); ?>
</p>

<?php if ( count( $patterns_pending_review ) > 0 ) : ?> 
	<p class="notice notice-warning notice-alt notice-large">
		<?php echo sprintf(
			/* translators: number of patterns pending review. */
			__( '%d patterns pending review.', 'wporg-patterns' ),
			count( $patterns_pending_review )
		); ?> 
	</p>
<?php endif; ?>

<p><a href="<?php echo esc_url( home_url( '/create' ) ); ?>"><?php esc_html_e( 'Create new pattern', 'wporg-patterns' ); ?></a></p>

