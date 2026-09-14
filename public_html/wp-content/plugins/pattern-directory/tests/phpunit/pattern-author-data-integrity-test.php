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
	 * Re-sending the field unchanged is not a write, so an ordinary edit must not be refused.
	 *
	 * The taxonomy is `show_in_rest`, so it comes back on every pattern response and a client that
	 * round-trips a record will send it straight back.
	 */
	public function test_round_trip_without_a_reason_is_allowed() {
		$pattern_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$author,
				'post_status' => 'publish',
			)
		);

		wp_set_current_user( self::$author );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . $pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'excerpt'   => 'An edit that changes no terms.',
					FLAG_REASON => array(),
				)
			)
		);
		$response = rest_do_request( $request );

		$this->assertFalse( $response->is_error(), 'An unchanged flag-reason field was treated as a write.' );
	}

	/**
	 * Core refuses an unchanged *non-empty* reason before this plugin is consulted.
	 *
	 * `check_assign_terms_permission()` runs in the permission check and walks the submitted term ids,
	 * so a round trip of a pattern that already carries a reason still fails for a non-moderator. The
	 * no-op allowance above only covers the empty case; this records the boundary.
	 */
	public function test_round_trip_with_an_existing_reason_is_still_refused_by_core() {
		$reason     = self::factory()->term->create(
			array(
				'taxonomy' => FLAG_REASON,
				'name'     => 'Guidelines',
			)
		);
		$pattern_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$author,
				'post_status' => UNLISTED_STATUS,
			)
		);
		wp_set_object_terms( $pattern_id, array( $reason ), FLAG_REASON );

		wp_set_current_user( self::$author );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . $pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( FLAG_REASON => array( $reason ) ) ) );
		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_cannot_assign_term', $response->get_data()['code'] );
		$this->assertCount( 1, wp_get_object_terms( $pattern_id, FLAG_REASON ) );
	}

	/**
	 * The same taxonomy records why a moderator unlisted a pattern, so an author must not be able to
	 * clear it from their own pattern over REST.
	 */
	public function test_author_cannot_strip_the_reason_from_their_own_unlisted_pattern() {
		$reason     = self::factory()->term->create(
			array(
				'taxonomy' => FLAG_REASON,
				'name'     => 'Guidelines',
			)
		);
		$pattern_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$author,
				'post_status' => UNLISTED_STATUS,
			)
		);
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
		$create->set_body(
			wp_json_encode(
				array(
					'title'   => 'Recompute hero',
					'content' => self::THREE_BLOCKS,
					'status'  => 'publish',
					'meta'    => array( 'wpop_description' => 'A description.' ),
				)
			)
		);
		$response = rest_do_request( $create );
		$this->assertFalse( $response->is_error() );

		$pattern_id = $response->get_data()['id'];

		$update = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . $pattern_id );
		$update->set_header( 'content-type', 'application/json' );
		$update->set_body(
			wp_json_encode(
				array(
					'content' => self::THREE_BLOCKS,
					'meta'    => array( 'wpop_contains_block_types' => 'core/paragraph' ),
				)
			)
		);
		rest_do_request( $update );

		$this->assertSame( 'core/heading,core/paragraph,core/separator', get_post_meta( $pattern_id, 'wpop_contains_block_types', true ) );
	}
}
