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
	 * Storing the same translation again leaves the post and its terms alone, backslashes included.
	 */
	public function test_unchanged_translation_is_not_rewritten(): void {
		$category = self::factory()->term->create( array( 'taxonomy' => 'wporg-pattern-category' ) );
		wp_set_object_terms( self::$parent_id, array( $category ), 'wporg-pattern-category' );

		$replacements = array(
			'Title'       => 'Titre \ barre',
			'Description' => 'Chemin C:\Temp',
			'Subtitle'    => 'C:\Temp',
		);
		$post_id      = create_or_update_translated_pattern( $this->translate( $replacements ) );

		$saves     = did_action( 'save_post_' . POST_TYPE );
		$term_sets = did_action( 'set_object_terms' );

		$this->assertSame( $post_id, create_or_update_translated_pattern( $this->translate( $replacements, $post_id ), $written ) );
		$this->assertFalse( $written );
		$this->assertSame( $saves, did_action( 'save_post_' . POST_TYPE ) );
		$this->assertSame( $term_sets, did_action( 'set_object_terms' ) );
	}

	/**
	 * A stale parent `wpop_contains_block_types` doesn't force rewrites.
	 */
	public function test_stale_parent_block_types_do_not_force_rewrites(): void {
		update_post_meta( self::$parent_id, 'wpop_contains_block_types', '' );

		$post_id = create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ) ) );
		// Saving changed strings runs the `post_updated` hook, which recomputes the value.
		create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Nouveau titre' ), $post_id ) );
		create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Nouveau titre' ), $post_id ), $written );

		$this->assertFalse( $written );
		$this->assertSame( 'core/heading', get_post_meta( $post_id, 'wpop_contains_block_types', true ) );
	}

	/**
	 * A changed translator string reaches the stored translation.
	 */
	public function test_changed_string_is_stored(): void {
		$post_id = create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ) ) );
		create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Nouveau titre' ), $post_id ), $written );

		$this->assertTrue( $written );
		$this->assertSame( 'Nouveau titre', get_post( $post_id )->post_title );
	}

	/**
	 * A change to only the parent's meta, or only the translated description, is stored.
	 */
	public function test_meta_only_changes_are_stored(): void {
		$post_id = create_or_update_translated_pattern( $this->translate( array( 'Description' => 'Une description' ) ) );

		update_post_meta( self::$parent_id, 'wpop_wp_version', '6.9' );
		create_or_update_translated_pattern( $this->translate( array( 'Description' => 'Une description' ), $post_id ), $written );

		$this->assertTrue( $written );
		$this->assertSame( '6.9', get_post_meta( $post_id, 'wpop_wp_version', true ) );

		create_or_update_translated_pattern( $this->translate( array( 'Description' => 'Autre description' ), $post_id ), $written );

		$this->assertTrue( $written );
		$this->assertSame( 'Autre description', get_post_meta( $post_id, 'wpop_description', true ) );
	}

	/**
	 * Fields the job doesn't translate are still reset when edited on a translation.
	 */
	public function test_edited_password_is_reset(): void {
		$post_id = create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ) ) );
		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_password' => 'secret',
			)
		);

		create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ), $post_id ) );

		$this->assertSame( '', get_post( $post_id )->post_password );
	}

	/**
	 * A change to only the parent's terms is copied without rewriting the post.
	 */
	public function test_parent_term_change_is_copied(): void {
		$post_id = create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ) ) );

		$category = self::factory()->term->create( array( 'taxonomy' => 'wporg-pattern-category' ) );
		wp_set_object_terms( self::$parent_id, array( $category ), 'wporg-pattern-category' );

		$saves = did_action( 'save_post_' . POST_TYPE );
		create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ), $post_id ), $written );

		$this->assertTrue( $written );
		$this->assertSame( $saves, did_action( 'save_post_' . POST_TYPE ) );
		$this->assertSame( array( $category ), wp_get_object_terms( $post_id, 'wporg-pattern-category', array( 'fields' => 'ids' ) ) );
	}

	/**
	 * A change to only the parent's status is copied.
	 */
	public function test_parent_status_change_is_copied(): void {
		$post_id = create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ) ) );

		wp_update_post(
			array(
				'ID'          => self::$parent_id,
				'post_status' => 'draft',
			)
		);
		create_or_update_translated_pattern( $this->translate( array( 'Title' => 'Titre' ), $post_id ), $written );

		$this->assertTrue( $written );
		$this->assertSame( 'draft', get_post_status( $post_id ) );
	}

	/**
	 * Run the parent through the parser with the given translator strings, as the job does.
	 *
	 * @param array $replacements Translations keyed by original string.
	 * @param int   $existing_id  Optional. The stored translation the job would find.
	 * @return Translations_Pattern The assembled translation.
	 */
	protected function translate( array $replacements, int $existing_id = 0 ): Translations_Pattern {
		$parent = Translations_Pattern::from_post( get_post( self::$parent_id ) );

		$pattern         = ( new PatternParser( $parent ) )->replace_strings_with_kses( $replacements );
		$pattern->ID     = $existing_id;
		$pattern->locale = self::LOCALE;
		$pattern->parent = $parent;

		if ( $existing_id ) {
			$pattern->name = get_post( $existing_id )->post_name;
		}

		return $pattern;
	}
}
