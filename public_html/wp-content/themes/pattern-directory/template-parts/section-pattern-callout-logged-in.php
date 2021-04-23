<?php
/**
 * Template part for displaying pattern details for logged in user.
 *
 * @package WordPressdotorg\Theme;
 */

namespace WordPressdotorg\Pattern_Directory\Patterns;

$user_patterns = Patterns::instance()->get_patterns_by_author( get_current_user_id() );

$patterns_pending_review = array_filter( $user_patterns, function ( $pattern ) {
	return $pattern->post_status === 'pending';
});

$user_pattern_count = count( $user_patterns );
$patterns_pending_review_count = count( $patterns_pending_review );

?>

<h2><?php esc_html_e( 'My patterns', 'wporg-patterns' ); ?></h2>
<p>
	<?php echo sprintf(
			/* translators: number of patterns created. */
		_n( 'You\'ve created <b>%d pattern</b>.', 'You\'ve created <b>%d patterns</b>.', $user_pattern_count, 'wporg-patterns' ),
		$user_pattern_count
	); ?>
</p>

<?php if ( $patterns_pending_review_count > 0 ) : ?> 
	<p class="notice notice-warning notice-alt notice-large">
		<?php echo sprintf(
			/* translators: number of patterns pending review. */
			__( '%d patterns pending review.', 'wporg-patterns' ),
			$patterns_pending_review_count
		); ?> 
	</p>
<?php endif; ?>

<p><a href="<?php echo esc_url( home_url( '/create' ) ); ?>"><?php esc_html_e( 'Create new pattern', 'wporg-patterns' ); ?></a></p>

