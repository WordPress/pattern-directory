<?php
/**
 * Test the Patterns list table admin helpers.
 */

use function WordPressdotorg\Pattern_Directory\Admin\Patterns\display_post_states;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };

/*
 * These tests set the request superglobal directly to drive the list table filter, which is the
 * point of them. The sniff guarding production input handling does not apply.
 */
// phpcs:disable WordPress.Security.NonceVerification.Recommended

/**
 * Test the extra post states shown on the Patterns list table.
 */
class Pattern_Admin_Post_States_Test extends WP_UnitTestCase {
	/**
	 * An unlisted pattern.
	 *
	 * @var int
	 */
	protected static $unlisted_pattern_id;

	/**
	 * A pattern flagged as spam.
	 *
	 * @var int
	 */
	protected static $spam_pattern_id;

	/**
	 * A published pattern, which never gets an extra state.
	 *
	 * @var int
	 */
	protected static $published_pattern_id;

	/**
	 * The $_REQUEST superglobal as it was before the current test ran.
	 *
	 * @var array
	 */
	protected $original_request;

	/**
	 * Set up shared fixtures.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$unlisted_pattern_id  = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => UNLISTED_STATUS,
			)
		);
		self::$spam_pattern_id      = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => SPAM_STATUS,
			)
		);
		self::$published_pattern_id = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Isolate each test from the real request.
	 */
	public function set_up() {
		parent::set_up();

		$this->original_request = $_REQUEST;
		$_REQUEST               = array();
	}

	/**
	 * Restore the real request.
	 */
	public function tear_down() {
		$_REQUEST = $this->original_request;

		parent::tear_down();
	}

	/**
	 * Without a status filter, unlisted and spam patterns are labelled so they stand out in the
	 * "All" view.
	 *
	 * @dataProvider data_flagged_statuses
	 *
	 * @param string $property Name of the fixture property holding the pattern ID.
	 * @param string $status   The post status that should be labelled.
	 */
	public function test_flagged_status_is_labelled_in_unfiltered_view( $property, $status ) {
		$post = get_post( self::${$property} );

		$states = display_post_states( array(), $post );

		$this->assertArrayHasKey( $status, $states );
		$this->assertSame( get_post_status_object( $status )->label, $states[ $status ] );
	}

	/**
	 * Statuses that earn an extra label.
	 *
	 * @return array[]
	 */
	public function data_flagged_statuses() {
		return array(
			'unlisted' => array( 'unlisted_pattern_id', UNLISTED_STATUS ),
			'spam'     => array( 'spam_pattern_id', SPAM_STATUS ),
		);
	}

	/**
	 * When the list table is already filtered to that status, the label is redundant and omitted.
	 */
	public function test_label_is_omitted_when_already_filtering_by_that_status() {
		$_REQUEST['post_status'] = UNLISTED_STATUS;
		$post                    = get_post( self::$unlisted_pattern_id );

		$this->assertSame( array(), display_post_states( array(), $post ) );
	}

	/**
	 * An ordinary published pattern never gets an extra state.
	 */
	public function test_published_pattern_gets_no_extra_state() {
		$post = get_post( self::$published_pattern_id );

		$this->assertSame( array(), display_post_states( array(), $post ) );
	}

	/**
	 * Existing states are preserved rather than replaced.
	 */
	public function test_existing_states_are_preserved() {
		$post = get_post( self::$unlisted_pattern_id );

		$states = display_post_states( array( 'sticky' => 'Sticky' ), $post );

		$this->assertArrayHasKey( 'sticky', $states );
		$this->assertArrayHasKey( UNLISTED_STATUS, $states );
	}

	/**
	 * The `post_status` request variable runs through `sanitize_key()`, which lowercases it. A
	 * differently-cased filter therefore matches the post status and suppresses the label, where
	 * an unsanitized comparison would not.
	 */
	public function test_post_status_request_variable_is_sanitized() {
		$_REQUEST['post_status'] = strtoupper( UNLISTED_STATUS );
		$post                    = get_post( self::$unlisted_pattern_id );

		$this->assertSame( array(), display_post_states( array(), $post ) );
	}

	/**
	 * Sanitizing strips characters a real status can never contain, so a value carrying markup
	 * cannot match and the label stays.
	 */
	public function test_post_status_carrying_markup_does_not_match() {
		$_REQUEST['post_status'] = '<script>alert(1)</script>' . UNLISTED_STATUS;
		$post                    = get_post( self::$unlisted_pattern_id );

		$states = display_post_states( array(), $post );

		$this->assertArrayHasKey( UNLISTED_STATUS, $states );
		$this->assertStringNotContainsString( '<script>', wp_json_encode( $states ) );
	}

	/**
	 * A non-string `post_status` must not raise a type error.
	 */
	public function test_array_post_status_is_handled() {
		$_REQUEST['post_status'] = array( UNLISTED_STATUS );
		$post                    = get_post( self::$unlisted_pattern_id );

		$states = display_post_states( array(), $post );

		$this->assertArrayHasKey( UNLISTED_STATUS, $states );
	}
}
