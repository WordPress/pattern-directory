<?php
/**
 * Tests for Pattern Directory settings markup.
 *
 * @package WordPressdotorg\Pattern_Directory\Tests
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use function WordPressdotorg\Pattern_Directory\Admin\Settings\render_status_field;

/**
 * Verify the default status selector renders valid option markup.
 */
class Admin_Settings_Test extends WP_UnitTestCase {

	/**
	 * The selected attribute must appear only inside the matching option.
	 */
	public function test_selected_status_does_not_emit_stray_markup(): void {
		foreach ( array( 'publish', 'pending' ) as $status ) {
			update_option( 'wporg-pattern-default_status', $status );

			ob_start();
			try {
				render_status_field();
				$html = ob_get_contents();
			} finally {
				ob_end_clean();
			}

			$this->assertSame( 1, substr_count( $html, "selected='selected'" ) );
			$this->assertStringContainsString( '<option value="' . $status . '"  selected=\'selected\'>', $html );
		}
	}
}
