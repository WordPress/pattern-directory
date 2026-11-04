<?php
/**
 * Test the conversion of tax queries into Elasticsearch filters.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use function WordPressdotorg\Pattern_Directory\Search\get_tax_filter;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Elasticsearch rejects a malformed filter with a 400, and Jetpack then falls back to a MySQL search, so a
 * bad conversion shows up as slow, unranked results rather than an error.
 *
 * @group search
 */
class Search_Tax_Filter_Test extends WP_UnitTestCase {
	/**
	 * The `core` keyword's term ID.
	 *
	 * @var int
	 */
	protected static $core_keyword;

	/**
	 * A pattern category's term ID.
	 *
	 * @var int
	 */
	protected static $category;

	/**
	 * Create the terms.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$core_keyword = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-keyword',
				'slug'     => 'core',
			)
		);
		self::$category     = $factory->term->create( array( 'taxonomy' => 'wporg-pattern-category' ) );
	}

	/**
	 * `curation=core` passes the keyword as a slug string, which must become an array of term IDs.
	 */
	public function test_curation_core_resolves_slug_to_term_ids() {
		$filter = get_tax_filter(
			array(
				'core_keyword' => array(
					'taxonomy' => 'wporg-pattern-keyword',
					'field'    => 'slug',
					'terms'    => 'core',
					'operator' => 'IN',
				),
			)
		);

		$this->assertSame(
			array( 'bool' => array( 'must' => array( array( 'terms' => array( 'taxonomy.wporg-pattern-keyword.term_id' => array( self::$core_keyword ) ) ) ) ) ),
			$filter
		);
	}

	/**
	 * `curation=community` excludes the keyword; it must not be applied as an include.
	 */
	public function test_not_in_is_excluded() {
		$filter = get_tax_filter(
			array(
				'core_keyword' => array(
					'taxonomy' => 'wporg-pattern-keyword',
					'field'    => 'slug',
					'terms'    => 'core',
					'operator' => 'NOT IN',
				),
			)
		);

		$this->assertSame(
			array( array( 'terms' => array( 'taxonomy.wporg-pattern-keyword.term_id' => array( self::$core_keyword ) ) ) ),
			$filter['bool']['must'][0]['bool']['must_not']
		);
	}

	/**
	 * An unknown slug matches nothing, rather than dropping the filter.
	 */
	public function test_unknown_slug_matches_nothing() {
		$filter = get_tax_filter(
			array(
				array(
					'taxonomy' => 'wporg-pattern-keyword',
					'field'    => 'slug',
					'terms'    => 'no-such-keyword',
				),
			)
		);

		$this->assertSame( array( 'terms' => array( 'taxonomy.wporg-pattern-keyword.term_id' => array() ) ), $filter['bool']['must'][0] );
	}

	/**
	 * `operator: AND` requires every term.
	 */
	public function test_and_requires_each_term() {
		$filter = get_tax_filter(
			array(
				array(
					'taxonomy' => 'wporg-pattern-category',
					'field'    => 'term_id',
					'terms'    => array( '5', 7 ),
					'operator' => 'AND',
				),
			)
		);

		$this->assertSame(
			array(
				array( 'term' => array( 'taxonomy.wporg-pattern-category.term_id' => 5 ) ),
				array( 'term' => array( 'taxonomy.wporg-pattern-category.term_id' => 7 ) ),
			),
			$filter['bool']['must'][0]['bool']['must']
		);
	}

	/**
	 * `tax_relation=OR` needs only one clause to match.
	 */
	public function test_or_relation() {
		$filter = get_tax_filter(
			array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'wporg-pattern-category',
					'terms'    => array( self::$category ),
				),
				array(
					'taxonomy' => 'wporg-pattern-keyword',
					'terms'    => array( self::$core_keyword ),
				),
			)
		);

		$this->assertCount( 2, $filter['bool']['should'] );
		$this->assertSame( 1, $filter['bool']['minimum_should_match'] );
		$this->assertArrayNotHasKey( 'must', $filter['bool'] );
	}

	/**
	 * Private taxonomies, a lone `relation`, and an empty query produce no filter.
	 */
	public function test_nothing_to_filter() {
		$this->assertNull( get_tax_filter( '' ) );
		$this->assertNull( get_tax_filter( array( 'relation' => 'AND' ) ) );
		$this->assertNull(
			get_tax_filter(
				array(
					array(
						'taxonomy' => 'wporg-pattern-flag-reason',
						'terms'    => array( 1 ),
					),
				)
			)
		);
	}

	/**
	 * The tax query the REST API actually builds converts cleanly.
	 */
	public function test_rest_tax_query() {
		$tax_query = null;
		$capture   = function ( $args ) use ( &$tax_query ) {
			$tax_query = $args['tax_query'];
			return $args;
		};
		add_filter( 'rest_' . POST_TYPE . '_query', $capture, PHP_INT_MAX );

		$request = new WP_REST_Request( 'GET', '/wp/v2/' . POST_TYPE );
		$request->set_param( 'search', 'block' );
		$request->set_param( 'curation', 'core' );
		$request->set_param( 'pattern-categories_exclude', array( self::$category ) );
		$request->set_param( 'tax_relation', 'AND' );
		rest_do_request( $request );

		remove_filter( 'rest_' . POST_TYPE . '_query', $capture, PHP_INT_MAX );

		$this->assertSame(
			array(
				array( 'terms' => array( 'taxonomy.wporg-pattern-keyword.term_id' => array( self::$core_keyword ) ) ),
				array( 'bool' => array( 'must_not' => array( array( 'terms' => array( 'taxonomy.wporg-pattern-category.term_id' => array( self::$category ) ) ) ) ) ),
			),
			$this->sort_clauses( get_tax_filter( $tax_query )['bool']['must'] )
		);
	}

	/**
	 * Order clauses with the includes first, since the REST API and `curation` add them in different orders.
	 *
	 * @param array $clauses Filter clauses.
	 *
	 * @return array
	 */
	private function sort_clauses( array $clauses ): array {
		usort(
			$clauses,
			function ( $a, $b ) {
				return isset( $a['bool'] ) <=> isset( $b['bool'] );
			}
		);

		return $clauses;
	}
}
