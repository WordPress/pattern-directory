<?php
/**
 * Test that the patterns REST collection does not report on other authors' non-public patterns.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };

/**
 * Test collection scoping.
 *
 * @group content-validation
 */
class Pattern_Collection_Scope_Test extends WP_UnitTestCase {
	/**
	 * The author whose unpublished patterns must stay private.
	 *
	 * @var int
	 */
	protected static $victim;

	/**
	 * An ordinary account that is not the author.
	 *
	 * @var int
	 */
	protected static $attacker;

	/**
	 * A directory moderator, who is allowed to see everything.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * Give the victim one pattern in each status worth hiding.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$victim    = $factory->user->create( array( 'role' => '' ) );
		self::$attacker  = $factory->user->create( array( 'role' => '' ) );
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );

		foreach ( array( 'publish', 'draft', UNLISTED_STATUS, SPAM_STATUS ) as $status ) {
			$factory->post->create(
				array(
					'post_type'   => POST_TYPE,
					'post_author' => self::$victim,
					'post_status' => $status,
					'post_title'  => "zqx{$status} secret",
				)
			);

			/*
			 * The caller owns one of each too. Without these a wrongly applied restriction reads the
			 * same as a correctly empty result, which is how the author-filter handling slipped through.
			 */
			$factory->post->create(
				array(
					'post_type'   => POST_TYPE,
					'post_author' => self::$attacker,
					'post_status' => $status,
					'post_title'  => "own{$status} pattern",
				)
			);
		}
	}

	/**
	 * Query the collection as a given user.
	 *
	 * @param int   $user   The user to act as.
	 * @param array $params Query parameters.
	 *
	 * @return array The response's reported total and returned item count.
	 */
	protected function collection_as( $user, $params ) {
		wp_set_current_user( $user );

		$request = new WP_REST_Request( 'GET', '/wp/v2/wporg-pattern' );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = rest_do_request( $request );
		$headers  = $response->get_headers();

		return array(
			'total'    => (int) ( $headers['X-WP-Total'] ?? 0 ),
			'returned' => $response->is_error() ? 0 : count( $response->get_data() ),
		);
	}

	/**
	 * The reported total must not count another author's non-public patterns.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_total_excludes_other_authors_non_public_patterns( $status ) {
		$result = $this->collection_as( self::$attacker, array( 'status' => $status ) );

		// The caller's own, never the other author's.
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 1, $result['returned'] );
	}

	/**
	 * `search` must not become a boolean oracle over another author's unpublished text.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_search_is_not_an_oracle_over_other_authors( $status ) {
		$hit  = $this->collection_as(
			self::$attacker,
			array(
				'status' => $status,
				'search' => "zqx{$status}",
			)
		);
		$miss = $this->collection_as(
			self::$attacker,
			array(
				'status' => $status,
				'search' => 'zqxnothingmatches',
			)
		);

		$this->assertSame( $miss['total'], $hit['total'], 'A matching search was distinguishable from a non-matching one.' );
	}

	/**
	 * An author still sees their own non-public patterns.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_author_still_sees_their_own( $status ) {
		$result = $this->collection_as( self::$victim, array( 'status' => $status ) );

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 1, $result['returned'] );
	}

	/**
	 * A moderator still sees everything.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_moderator_still_sees_everything( $status ) {
		$result = $this->collection_as( self::$moderator, array( 'status' => $status ) );

		$this->assertSame( 2, $result['total'] );
	}

	/**
	 * `author_name` must not reopen what the status scoping closed.
	 *
	 * It is applied after the other query filtering, so a pin set before it is silently overwritten.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_author_name_does_not_override_the_scoping( $status ) {
		$slug = get_userdata( self::$victim )->user_nicename;

		$plain = $this->collection_as(
			self::$attacker,
			array(
				'status'      => $status,
				'author_name' => $slug,
			)
		);
		$hit   = $this->collection_as(
			self::$attacker,
			array(
				'status'      => $status,
				'author_name' => $slug,
				'search'      => "zqx{$status}",
			)
		);
		$miss  = $this->collection_as(
			self::$attacker,
			array(
				'status'      => $status,
				'author_name' => $slug,
				'search'      => 'zqxnothingmatches',
			)
		);

		$this->assertSame( 0, $plain['total'] );
		$this->assertSame( $miss['total'], $hit['total'], 'A matching search was distinguishable through `author_name`.' );
	}

	/**
	 * The numeric `author` parameter must not reopen it either.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_author_param_does_not_override_the_scoping( $status ) {
		$result = $this->collection_as(
			self::$attacker,
			array(
				'status' => $status,
				'author' => array( self::$victim ),
			)
		);

		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 0, $result['returned'] );
	}

	/**
	 * Excluding the caller has an empty answer, not the caller's own patterns.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_excluding_the_caller_returns_nothing( $status ) {
		$result = $this->collection_as(
			self::$attacker,
			array(
				'status'         => $status,
				'author_exclude' => array( self::$attacker ),
			)
		);

		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 0, $result['returned'] );
	}

	/**
	 * Asking for the caller's own patterns still returns them.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_asking_for_own_patterns_still_returns_them( $status ) {
		$result = $this->collection_as(
			self::$attacker,
			array(
				'status' => $status,
				'author' => array( self::$attacker ),
			)
		);

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 1, $result['returned'] );
	}

	/**
	 * Excluding a different author leaves the caller's own patterns in place.
	 *
	 * @dataProvider data_non_public_statuses
	 *
	 * @param string $status A status the directory keeps out of public view.
	 */
	public function test_excluding_another_author_keeps_own_patterns( $status ) {
		$result = $this->collection_as(
			self::$attacker,
			array(
				'status'         => $status,
				'author_exclude' => array( self::$victim ),
			)
		);

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 1, $result['returned'] );
	}

	/**
	 * `context=edit` must not reach past the caller's own patterns either.
	 */
	public function test_edit_context_is_scoped_to_the_caller() {
		$result = $this->collection_as( self::$attacker, array( 'context' => 'edit' ) );

		// Their own published pattern, not the other author's.
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 1, $result['returned'] );
	}

	/**
	 * The public listing is unchanged for anonymous visitors.
	 */
	public function test_public_listing_is_unaffected() {
		$result = $this->collection_as( 0, array() );

		$this->assertSame( 2, $result['total'] );
		$this->assertSame( 2, $result['returned'] );
	}

	/**
	 * Statuses that must never be countable across authors.
	 *
	 * @return array[]
	 */
	public function data_non_public_statuses() {
		return array(
			'draft'    => array( 'draft' ),
			'unlisted' => array( UNLISTED_STATUS ),
			'spam'     => array( SPAM_STATUS ),
		);
	}
}
