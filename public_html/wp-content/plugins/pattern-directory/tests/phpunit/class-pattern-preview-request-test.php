<?php
/**
 * Test the pattern preview request predicate.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\is_preview_request;

/**
 * The theme switch and the template selection both rely on this one predicate, so it has to accept only
 * the preview forms the site itself produces.
 *
 * @group pattern-preview
 */
class Pattern_Preview_Request_Test extends WP_UnitTestCase {
	/**
	 * The request URI before the test changed it.
	 *
	 * @var string|null
	 */
	protected $request_uri;

	/**
	 * Snapshot the request URI.
	 */
	public function set_up(): void {
		parent::set_up();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- a snapshot of the test request, put back as it was.
		$this->request_uri = $_SERVER['REQUEST_URI'] ?? null;
	}

	/**
	 * Restore the request URI.
	 */
	public function tear_down(): void {
		if ( null === $this->request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->request_uri;
		}
		parent::tear_down();
	}

	/**
	 * Only the forms the site itself produces are previews.
	 *
	 * @dataProvider data_request_uris
	 *
	 * @param string $request_uri The request URI.
	 * @param bool   $is_preview  Whether it is a preview.
	 */
	public function test_is_preview_request( string $request_uri, bool $is_preview ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;

		$this->assertSame( $is_preview, is_preview_request() );
	}

	/**
	 * Request URIs and whether they are previews.
	 *
	 * @return array
	 */
	public function data_request_uris(): array {
		return array(
			'pretty endpoint'                           => array( '/patterns/pattern/example/view/', true ),
			'the form the site emits'                   => array( '/patterns/pattern/example/?view=1', true ),
			'plain permalink'                           => array( '/?p=123&view=1', true ),
			'boolean word'                              => array( '/patterns/pattern/example/?view=true', true ),
			'other value'                               => array( '/patterns/pattern/example/?view=2', false ),
			'a letter the old character class accepted' => array( '/patterns/pattern/example/?view=t', false ),
			'empty'                                     => array( '/patterns/pattern/example/?view=', false ),
			'endpoint with a value'                     => array( '/patterns/pattern/example/view/foo/', false ),
			'no view at all'                            => array( '/patterns/pattern/example/', false ),
		);
	}
}
