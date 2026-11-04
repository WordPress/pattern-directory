<?php
/**
 * Test that searches keep the collection's other restrictions.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use function WordPressdotorg\Pattern_Directory\Search\get_query_filters;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Elasticsearch searches ignored `author_name`, `allowed_blocks`, `include` and `orderby` (#547).
 *
 * @group search
 */
class Search_Query_Filters_Test extends WP_UnitTestCase {
	/**
	 * A pattern author.
	 *
	 * @var \WP_User
	 */
	protected static $author;

	/**
	 * Create the author.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$author = $factory->user->create_and_get( array( 'user_login' => 'pattern-author' ) );
	}

	/**
	 * Run a collection request and return the WP_Query args the REST API built for it.
	 *
	 * @param array $params Query parameters.
	 * @return array The query args.
	 */
	private function get_query_args( array $params ): array {
		$args    = array();
		$capture = function ( $query_args ) use ( &$args ) {
			$args = $query_args;
			return $query_args;
		};
		add_filter( 'rest_' . POST_TYPE . '_query', $capture, PHP_INT_MAX );

		$request = new WP_REST_Request( 'GET', '/wp/v2/' . POST_TYPE );
		$request->set_query_params( $params );
		rest_do_request( $request );

		remove_filter( 'rest_' . POST_TYPE . '_query', $capture, PHP_INT_MAX );

		return $args;
	}

	/**
	 * Catches a search returning other authors' patterns, patterns with blocks outside the list, or other IDs.
	 */
	public function test_search_keeps_author_blocks_and_ids(): void {
		$args = $this->get_query_args(
			array(
				'search'         => 'image',
				'author_name'    => 'pattern-author',
				'allowed_blocks' => array( 'core/heading', 'core/gallery' ),
				'include'        => array( 12, 34 ),
			)
		);

		$this->assertSame(
			array(
				array( 'terms' => array( 'post_id' => array( 12, 34 ) ) ),
				array( 'terms' => array( 'author_login' => array( 'pattern-author' ) ) ),
				array( 'regexp' => array( 'meta.wpop_contains_block_types.value.raw' => '((core/heading|core/gallery),?)+' ) ),
			),
			get_query_filters( $args )
		);
	}

	/**
	 * Catches a search dropping the order a request asks for, or no longer ranking by relevance by default.
	 *
	 * @dataProvider data_search_orders
	 *
	 * @param array  $params   Query parameters.
	 * @param string $expected The query's `orderby`.
	 */
	public function test_search_order( array $params, string $expected ): void {
		$this->assertSame( $expected, $this->get_query_args( $params + array( 'search' => 'image' ) )['orderby'] );
	}

	/**
	 * Requests and the order they should get.
	 *
	 * @return array[]
	 */
	public function data_search_orders(): array {
		return array(
			'no order picked' => array( array(), 'relevance' ),
			'by date'         => array( array( 'orderby' => 'date' ), 'date' ),
		);
	}
}
