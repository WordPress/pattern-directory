<?php
/**
 * Test the handling of data an ordinary account supplies about its own pattern.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ POST_TYPE as FLAG_POST_TYPE, TAX_TYPE as FLAG_REASON, PENDING_STATUS };

/**
 * Test author-supplied data handling.
 *
 * @group content-validation
 */
class Pattern_Author_Data_Integrity_Test extends WP_UnitTestCase {
	/**
	 * Three valid blocks, enough to satisfy the block-count rule.
	 */
	private const THREE_BLOCKS = "<!-- wp:heading -->\n<h2>Heading</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:separator -->\n<hr class=\"wp-block-separator\"/>\n<!-- /wp:separator -->";

	/**
	 * An ordinary account with no role.
	 *
	 * @var int
	 */
	protected static $author;

	/**
	 * Set up the submitting account.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$author = $factory->user->create( array( 'role' => '' ) );
	}

	/**
	 * A reporter with no role must end up with the reason recorded on their flag.
	 *
	 * `wp_insert_post()` drops `tax_input` for a user who cannot `assign_terms`, which left moderators a
	 * queue of reasonless reports.
	 */
	public function test_reporter_ends_up_with_a_reason_on_their_flag() {
		$reason     = self::factory()->term->create( array(
			'taxonomy' => FLAG_REASON, 'name' => 'Spam',
		) );
		$pattern_id = self::factory()->post->create( array(
			'post_type' => POST_TYPE, 'post_status' => 'publish',
		) );

		wp_set_current_user( self::$author );

		$flag_id = wp_insert_post( array(
			'post_type'    => FLAG_POST_TYPE,
			'post_parent'  => $pattern_id,
			'post_excerpt' => 'Reason goes here.',
			'post_status'  => PENDING_STATUS,
		) );
		wp_set_object_terms( $flag_id, array( $reason ), FLAG_REASON );

		$this->assertCount( 1, wp_get_object_terms( $flag_id, FLAG_REASON ) );
	}

	/**
	 * The same taxonomy records why a moderator unlisted a pattern, so an author must not be able to
	 * clear it from their own pattern over REST.
	 */
	public function test_author_cannot_strip_the_reason_from_their_own_unlisted_pattern() {
		$reason     = self::factory()->term->create( array(
			'taxonomy' => FLAG_REASON, 'name' => 'Guidelines',
		) );
		$pattern_id = self::factory()->post->create( array(
			'post_type'   => POST_TYPE,
			'post_author' => self::$author,
			'post_status' => UNLISTED_STATUS,
		) );
		wp_set_object_terms( $pattern_id, array( $reason ), FLAG_REASON );

		wp_set_current_user( self::$author );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . $pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( FLAG_REASON => array() ) ) );
		rest_do_request( $request );

		$this->assertCount( 1, wp_get_object_terms( $pattern_id, FLAG_REASON ) );
	}

	/**
	 * The derived block-type list must describe the content, not whatever the author sent.
	 *
	 * It drives the `allowed_blocks` filter, so a forged value places a pattern in inserter results for
	 * block sets it does not contain.
	 */
	public function test_contains_block_types_is_recomputed_over_an_author_supplied_value() {
		wp_set_current_user( self::$author );

		$create = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern' );
		$create->set_header( 'content-type', 'application/json' );
		$create->set_body( wp_json_encode( array(
			'title'   => 'Recompute hero',
			'content' => self::THREE_BLOCKS,
			'status'  => 'publish',
			'meta'    => array( 'wpop_description' => 'A description.' ),
		) ) );
		$response = rest_do_request( $create );
		$this->assertFalse( $response->is_error() );

		$pattern_id = $response->get_data()['id'];

		$update = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . $pattern_id );
		$update->set_header( 'content-type', 'application/json' );
		$update->set_body( wp_json_encode( array(
			'content' => self::THREE_BLOCKS,
			'meta'    => array( 'wpop_contains_block_types' => 'core/paragraph' ),
		) ) );
		rest_do_request( $update );

		$this->assertSame( 'core/heading,core/paragraph,core/separator', get_post_meta( $pattern_id, 'wpop_contains_block_types', true ) );
	}
}
