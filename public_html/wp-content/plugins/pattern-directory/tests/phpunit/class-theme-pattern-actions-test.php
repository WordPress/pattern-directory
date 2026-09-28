<?php
/**
 * Test the theme's front-end pattern actions.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ POST_TYPE as FLAG_POST_TYPE, TAX_TYPE as FLAG_REASON };

/**
 * `do_pattern_actions()` drafts a pattern through `wp_update_post()` rather than the REST API, so
 * `validate_status()` never sees it. Without its own check an author could draft their way out of a
 * moderator-set status here and then publish the draft over REST.
 *
 * @group pattern-status-validation
 */
class Theme_Pattern_Actions_Test extends WP_UnitTestCase {
	/**
	 * A moderator: holds `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * The pattern's author: a directory member with no moderator capability.
	 *
	 * @var int
	 */
	protected static $member;

	/**
	 * The registered hooks as they were before the theme was loaded, or null if it was already loaded.
	 *
	 * @var array|null
	 */
	protected static $hooks_before_theme = null;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );
		self::$member    = $factory->user->create( array( 'role' => 'subscriber' ) );

		/*
		 * The suite loads plugins, not the theme, so pull in the one file under test. Loading it registers the
		 * theme's hooks -- `pre_get_posts` forces `curation=core` on every main query -- which would silently
		 * filter results for any later test that runs a pattern query. Snapshot the hooks so they can be put
		 * back exactly as they were once this class is done.
		 */
		if ( ! function_exists( '\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions' ) ) {
			// `$wp_filter` holds `WP_Hook` objects, so the array has to be cloned, not just copied.
			self::$hooks_before_theme = array_map(
				function ( $hook ) {
					return clone $hook;
				},
				$GLOBALS['wp_filter']
			);

			require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/themes/wporg-pattern-directory-2024/functions.php';
		}
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		// Put the hooks back, so loading the theme here doesn't change what any later test sees.
		if ( null !== self::$hooks_before_theme ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the snapshot taken above.
			$GLOBALS['wp_filter']     = self::$hooks_before_theme;
			self::$hooks_before_theme = null;
		}

		wp_delete_user( self::$moderator );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Stop `wp_safe_redirect()` reaching `header()`, which PHPUnit's buffered output makes fatal.
	 *
	 * @var callable
	 */
	protected $suppress_redirect;

	/**
	 * The location the theme last tried to redirect to.
	 *
	 * @var string
	 */
	protected $redirected_to = '';

	/**
	 * Suppress redirects for the duration of each test.
	 */
	public function set_up(): void {
		parent::set_up();

		// An empty location makes `wp_redirect()` bail before it sends any header.
		$this->redirected_to     = '';
		$this->suppress_redirect = function ( $location ) {
			$this->redirected_to = (string) $location;
			return '';
		};
		add_filter( 'wp_redirect', $this->suppress_redirect );
	}

	/**
	 * Reset the current user and request state between tests.
	 */
	public function tear_down(): void {
		remove_filter( 'wp_redirect', $this->suppress_redirect );
		unset( $_POST['action'], $_POST['_wpnonce'], $_SERVER['REQUEST_METHOD'] );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Run the theme's draft action against a pattern, as the current user.
	 *
	 * @param int    $pattern_id The pattern to act on.
	 * @param string $method     The request method to send it with.
	 */
	protected function do_draft_action( int $pattern_id, string $method = 'POST' ): void {
		$this->go_to( get_permalink( $pattern_id ) );

		$_SERVER['REQUEST_METHOD'] = $method;
		$_POST['action']           = 'draft';
		$_POST['_wpnonce']         = wp_create_nonce( 'draft-' . $pattern_id );

		\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions();
	}

	/**
	 * Create a pattern owned by the member.
	 *
	 * @param string $status The pattern's status.
	 * @return int The new pattern ID.
	 */
	protected function create_pattern( string $status ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$member,
				'post_status' => $status,
			)
		);
	}

	/**
	 * An author cannot draft their way out of a moderator's removal.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_member_cannot_draft_an_unlisted_pattern(): void {
		$pattern_id = $this->create_pattern( UNLISTED_STATUS );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( UNLISTED_STATUS, get_post_status( $pattern_id ) );
		$this->assertStringContainsString(
			'status=draft-not-allowed',
			$this->redirected_to,
			'The author must be told why, not left on a page that silently does nothing.'
		);
	}

	/**
	 * Nor out of the spam quarantine.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_member_cannot_draft_a_spam_pattern(): void {
		$pattern_id = $this->create_pattern( SPAM_STATUS );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
	}

	/**
	 * The ordinary case still works: an author can unpublish their own published pattern.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_member_can_draft_own_published_pattern(): void {
		$pattern_id = $this->create_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( 'draft', get_post_status( $pattern_id ) );
	}

	/**
	 * A moderator can still draft an unlisted pattern.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_moderator_can_draft_an_unlisted_pattern(): void {
		$pattern_id = $this->create_pattern( UNLISTED_STATUS );
		wp_set_current_user( self::$moderator );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( 'draft', get_post_status( $pattern_id ) );
	}

	/**
	 * The draft action is only taken from a form submission: a valid nonce on a GET changes nothing.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_draft_action_is_not_taken_on_get(): void {
		$pattern_id = $this->create_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id, 'GET' );

		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
		$this->assertSame( '', $this->redirected_to, 'A GET must render the page as it is, not redirect.' );
	}

	/**
	 * The draft control is a form that posts back to the pattern with its nonce, never a link.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\Draft_Button_Block\render
	 */
	public function test_draft_button_renders_a_post_form(): void {
		// The suite loads the theme after `init`, so the block's registration hook never fired; register it here.
		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'wporg/draft-button' ) ) {
			\WordPressdotorg\Theme\Pattern_Directory_2024\Draft_Button_Block\init();
		}

		$pattern_id = $this->create_pattern( 'publish' );
		wp_set_current_user( self::$member );

		// Render with the context the singular template supplies, rather than through a Query Loop.
		$parsed = parse_blocks( '<!-- wp:wporg/draft-button /-->' );
		$html   = ( new \WP_Block( $parsed[0], array( 'postId' => $pattern_id ) ) )->render();

		$this->assertStringContainsString( 'method="post"', $html );
		$this->assertStringContainsString( 'name="action" value="draft"', $html );
		$this->assertMatchesRegularExpression( '/name="_wpnonce" value="[a-f0-9]{10}"/', $html );
		$this->assertStringNotContainsString( 'href=', $html, 'The status change must not be reachable as a link.' );
	}

	/**
	 * The Delete button must match the permissions enforced by its REST endpoint.
	 *
	 * @dataProvider data_delete_button_permissions
	 *
	 * @param string $status       The pattern status.
	 * @param bool   $is_moderator Whether to act as a moderator.
	 * @param bool   $can_delete   Whether deletion should be offered and allowed.
	 */
	public function test_delete_button_permissions( string $status, bool $is_moderator, bool $can_delete ): void {
		$pattern_id = $this->create_pattern( $status );
		wp_set_current_user( $is_moderator ? self::$moderator : self::$member );
		$block = (object) array( 'context' => array( 'postId' => $pattern_id ) );

		ob_start();
		try {
			include dirname( __DIR__, 4 ) . '/themes/wporg-pattern-directory-2024/src/blocks/delete-button/render.php';
			$html = ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$has_button = false !== strpos( $html, 'actions.triggerDelete' );
		$this->assertSame( $can_delete, $has_button );

		$response = rest_do_request( new \WP_REST_Request( 'DELETE', '/wp/v2/wporg-pattern/' . $pattern_id ) );
		$this->assertSame( $can_delete ? 200 : 403, $response->get_status() );
	}

	/**
	 * Author and moderator permissions for ordinary and moderated patterns.
	 *
	 * @return array[]
	 */
	public function data_delete_button_permissions(): array {
		return array(
			'author draft'       => array( 'draft', false, true ),
			'author published'   => array( 'publish', false, true ),
			'author unlisted'    => array( UNLISTED_STATUS, false, false ),
			'author spam'        => array( SPAM_STATUS, false, false ),
			'moderator unlisted' => array( UNLISTED_STATUS, true, true ),
			'moderator spam'     => array( SPAM_STATUS, true, true ),
		);
	}

	/**
	 * A report submitted through the public form must carry its reason.
	 *
	 * `wp_insert_post()` drops `tax_input` for a reporter who cannot `assign_terms`, which left every
	 * flag raised from the front end with no reason for moderators to act on.
	 */
	public function test_report_records_its_reason_on_the_flag(): void {
		$pattern_id = $this->create_pattern( 'publish' );
		$reason     = self::factory()->term->create(
			array(
				'taxonomy' => FLAG_REASON,
				'name'     => 'Against the guidelines',
			)
		);

		wp_set_current_user( self::$member );
		$this->go_to( get_permalink( $pattern_id ) );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['action']           = 'report';
		$_POST['_wpnonce']         = wp_create_nonce( 'report-' . $pattern_id );
		$_POST['report-reason']    = $reason;
		$details                   = "Why this pattern was reported.\nSee C:\\patterns\\example & <b>bold</b>, where a < b &amp; c.";
		$_POST['report-details']   = wp_slash( $details );

		try {
			\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions();
		} finally {
			unset( $_POST['report-reason'], $_POST['report-details'] );
		}

		$flags = get_posts(
			array(
				'post_type'   => FLAG_POST_TYPE,
				'post_parent' => $pattern_id,
				'post_status' => 'any',
			)
		);

		$this->assertCount( 1, $flags );
		$this->assertSame(
			"Why this pattern was reported.\nSee C:\\patterns\\example &amp; &lt;b&gt;bold&lt;/b&gt;, where a &lt; b &amp;amp; c.",
			$flags[0]->post_excerpt
		);
		$this->assertSame( array( $reason ), wp_get_object_terms( $flags[0]->ID, FLAG_REASON, array( 'fields' => 'ids' ) ) );
	}

	/**
	 * The report that crosses the threshold must contribute its reason to the notification.
	 */
	public function test_threshold_report_reason_is_in_notification(): void {
		$pattern_id = $this->create_pattern( 'publish' );
		$reason     = self::factory()->term->create(
			array(
				'taxonomy'    => FLAG_REASON,
				'name'        => 'Report reason',
				'description' => 'The submitted report reason.',
			)
		);
		update_option( 'wporg-pattern-flag_threshold', 1 );
		wp_set_current_user( self::$member );
		$this->go_to( get_permalink( $pattern_id ) );

		$messages = array();
		/**
		 * Capture notifications without delivering email.
		 *
		 * @param bool|null $result Short-circuit result.
		 * @param array     $atts   Mail arguments.
		 * @return bool
		 */
		$capture_mail = static function ( ?bool $result, array $atts ) use ( &$messages ): bool {
			$messages[] = $atts['message'];
			return true;
		};
		add_filter( 'pre_wp_mail', $capture_mail, 10, 2 );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['action']           = 'report';
		$_POST['_wpnonce']         = wp_create_nonce( 'report-' . $pattern_id );
		$_POST['report-reason']    = $reason;
		$_POST['report-details']   = 'The pattern does not follow the guidelines.';

		try {
			\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions();
		} finally {
			remove_filter( 'pre_wp_mail', $capture_mail, 10 );
			unset( $_POST['report-reason'], $_POST['report-details'] );
		}

		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
		$this->assertCount( 1, $messages );
		$this->assertStringContainsString( 'The submitted report reason.', $messages[0] );
	}

	/**
	 * Invalid report fields must not create flags or trigger moderation.
	 *
	 * @dataProvider data_invalid_reports
	 *
	 * @param array $fields Submitted form fields.
	 */
	public function test_invalid_reports_do_not_create_flags( array $fields ): void {
		$pattern_id = $this->create_pattern( 'publish' );
		$reason     = self::factory()->term->create( array( 'taxonomy' => FLAG_REASON ) );
		if ( isset( $fields['report-reason'] ) && 'valid' === $fields['report-reason'] ) {
			$fields['report-reason'] = (string) $reason;
		}
		update_option( 'wporg-pattern-flag_threshold', 1 );
		wp_set_current_user( self::$member );
		$this->go_to( get_permalink( $pattern_id ) );
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$original_post             = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserve the test request for restoration.
		$_POST                     = array_merge(
			array(
				'action'   => 'report',
				'_wpnonce' => wp_create_nonce( 'report-' . $pattern_id ),
			),
			$fields
		);

		try {
			\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions();
		} finally {
			$_POST = $original_post;
		}

		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'      => FLAG_POST_TYPE,
					'post_parent'    => $pattern_id,
					'post_status'    => get_post_stati(),
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			)
		);
		$this->assertStringContainsString( 'status=report-invalid', $this->redirected_to );
	}

	/**
	 * Missing, malformed, and empty report fields.
	 *
	 * @return array[]
	 */
	public function data_invalid_reports(): array {
		return array(
			'missing fields'   => array( array() ),
			'missing reason'   => array( array( 'report-details' => 'Details.' ) ),
			'unknown reason'   => array(
				array(
					'report-reason'  => '999999999',
					'report-details' => 'Details.',
				),
			),
			'malformed reason' => array(
				array(
					'report-reason'  => '1suffix',
					'report-details' => 'Details.',
				),
			),
			'array reason'     => array(
				array(
					'report-reason'  => array( 1 ),
					'report-details' => 'Details.',
				),
			),
			'missing details'  => array( array( 'report-reason' => 'valid' ) ),
			'empty details'    => array(
				array(
					'report-reason'  => 'valid',
					'report-details' => " \n\t ",
				),
			),
			'array details'    => array(
				array(
					'report-reason'  => 'valid',
					'report-details' => array( 'Details.' ),
				),
			),
		);
	}
}
