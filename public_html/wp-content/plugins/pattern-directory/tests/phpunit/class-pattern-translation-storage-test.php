<?php
/**
 * Test that a translated pattern is stored with the bytes the job assembled.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use WordPressdotorg\Pattern_Translations\Pattern as Translations_Pattern;
use WordPressdotorg\Pattern_Translations\PatternParser;
use function WordPressdotorg\Pattern_Translations\create_or_update_translated_pattern;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * `wp_insert_post()` expects slashed input, and the serialiser writes attribute backslashes as `\u005c`;
 * the row has to hold what the job checked.
 *
 * @group pattern-translation-storage
 */
class Pattern_Translation_Storage_Test extends WP_UnitTestCase {
	const LOCALE = 'fr_FR';

	/**
	 * The published English original.
	 *
	 * @var int
	 */
	protected static $parent_id;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$parent_id = $factory->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_title'   => 'Title',
				'post_status'  => 'publish',
				'post_content' => "<!-- wp:heading {\"placeholder\":\"Subtitle\"} -->\n<h2>Subtitle</h2>\n<!-- /wp:heading -->",
				'meta_input'   => array(
					'wpop_locale'      => 'en_US',
					'wpop_description' => 'Description',
					'wpop_keywords'    => 'one, two',
				),
			)
		);
	}

	/**
	 * `wpop_locale` sanitizes against the `wporg_locales` table, which the test environment lacks.
	 */
	public function set_up(): void {
		parent::set_up();

		wp_cache_add_global_groups( array( 'locale-associations' ) );
		wp_cache_set( 'locale-list', array( self::LOCALE ), 'locale-associations' );
	}

	/**
	 * Every translator-supplied field, including a backslash inside an attribute, is stored as assembled.
	 */
	public function test_stored_fields_match_the_assembled_pattern(): void {
		$pattern = $this->translate(
			array(
				'Title'       => 'Titre \ barre',
				'Description' => 'Chemin C:\Temp',
				'one'         => 'un\deux',
				'Subtitle'    => 'C:\Temp',
			)
		);
		$post_id = create_or_update_translated_pattern( $pattern );
		$stored  = get_post( $post_id );

		$this->assertSame( $pattern->html, $stored->post_content );
		$this->assertSame( 'C:\Temp', parse_blocks( $stored->post_content )[0]['attrs']['placeholder'] );
		$this->assertSame( 'Titre \ barre', $stored->post_title );
		$this->assertSame( 'Chemin C:\Temp', $stored->wpop_description );
		$this->assertSame( 'un\deux, two', $stored->wpop_keywords );
	}

	/**
	 * A translator typing the escape for a byte KSES deletes on save does not get a block out of it.
	 */
	public function test_typed_control_character_escape_stays_text(): void {
		$pattern = $this->translate( array( 'Subtitle' => '<!--\u0001 wp:shortcode /-->' ) );
		$post_id = create_or_update_translated_pattern( $pattern );

		$this->assertIsInt( $post_id );
		$this->assertDoesNotMatchRegularExpression( '#<!--\s+wp:shortcode#', get_post( $post_id )->post_content );
	}

	/**
	 * Run the parent through the parser with the given translator strings, as the job does.
	 *
	 * @param array $replacements Translations keyed by original string.
	 * @return Translations_Pattern The assembled translation.
	 */
	protected function translate( array $replacements ): Translations_Pattern {
		$parent = Translations_Pattern::from_post( get_post( self::$parent_id ) );

		$pattern         = ( new PatternParser( $parent ) )->replace_strings_with_kses( $replacements );
		$pattern->ID     = 0;
		$pattern->locale = self::LOCALE;
		$pattern->parent = $parent;

		return $pattern;
	}
}
