<?php
/**
 * Template part for displaying pattern details for logged in user.
 *
 * @package WordPressdotorg\Theme;
 */

namespace WordPressdotorg\Pattern_Directory\Patterns;

$user_patterns = Patterns::instance()->get_patterns_by_author( get_current_user_id() );
$patterns_pending_review = Patterns::instance()->filter_pending_patterns( $user_patterns );
$user_pattern_count = count( $user_patterns );
$patterns_pending_review_count = count( $patterns_pending_review );

?>

<h2><?php esc_html_e( 'My patterns', 'wporg-patterns' ); ?></h2>
<p>
	<?php
		echo sprintf(
			esc_html__( 'You\'ve created %s.', 'wporg-patterns' ),
			sprintf(
				/* translators: %d: number of patterns created. */
				'<b>%d %s</b>',
				intval( $user_pattern_count ),
				esc_attr( _n( 'pattern', 'patterns', $user_pattern_count, 'wporg-patterns' ) ),
			)
		); ?>
</p>

<?php if ( $patterns_pending_review_count > 0 ) : ?> 
	<p class="notice notice-warning notice-alt notice-large">
		<?php echo sprintf(
			/* translators: %d number of patterns pending review. */
			esc_attr( _n( '%d pattern pending review.', '%d patterns pending review.', $patterns_pending_review_count, 'wporg-patterns' ) ),
			intval( $patterns_pending_review_count )
		); ?> 
	</p>
<?php endif; ?>

<p><a href="<?php echo esc_url( home_url( '/create' ) ); ?>"><?php esc_html_e( 'Create new pattern', 'wporg-patterns' ); ?></a></p>

