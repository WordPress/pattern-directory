<?php
/**
 * Test that tests test.
 *
 * This file will be replaced with a real test shortly.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Test.
 */
class Very_Basic_Test extends WP_UnitTestCase {
	/**
	 * Verify the pattern directory plugin is loaded.
	 */
	public function test_tests() {
		$this->assertEquals( POST_TYPE, 'wporg-pattern' );
	}
}
