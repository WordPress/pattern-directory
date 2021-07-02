<?php

namespace WordPressdotorg\Pattern_Translations;

class PatternMakepot {
	public $patterns;

	public function __construct( array $patterns ) {
		$this->patterns = $patterns;
	}

	public function makepot( $revision_time = null ) : string {
		return $this->makepo( $revision_time, $comment )->export();
	}

	public function makepo( $revision_time = null ) : \PO {
		require_once ABSPATH . '/wp-includes/pomo/po.php';

		$po = new \PO();

		$po->set_header( 'PO-Revision-Date', gmdate( 'Y-m-d H:i:s', $revision_time ?? time() ) . '+0000' );
		$po->set_header( 'MIME-Version', '1.0' );
		$po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
		$po->set_header( 'Content-Transfer-Encoding', '8bit' );
		$po->set_header( 'X-Generator', 'wp_cli_patterns_makepot' );

		foreach ( $this->entries() as $entry ) {
			$po->add_entry( $entry );
		}

		return $po;
	}

	public function entries() : array {
		$entries = [];

		foreach ( $this->patterns as $pattern ) {

			$parser = new PatternParser( $pattern );

			foreach ( $parser->to_strings() as $string ) {
				if ( ! isset( $entries[ $string ] ) ) {
					$entries[ $string ] = new \Translation_Entry(
						[
							'singular' => $string,
							'extracted_comments' => "Found in the '{$pattern->title}' pattern.",
							'references' => [],
						]
					);
				}

				if ( ! empty( $pattern->source_url ) && ! in_array( $pattern->source_url, $entries[ $string ]->references ) ) {
					$entries[ $string ]->references[] = $pattern->source_url;
				}
			}
		}

		return array_values( $entries );
	}

	public function import( $save = false ) {
		// Avoid attempting to import strings when no patterns are found.
		// This is a precautionary check to ensure we don't accidentally remove all translations.
		if ( empty( $this->patterns ) ) {
			return 'No patterns found: skipping import.';
		}

		// Load GlotPress for the API.
		switch_to_blog( WPORG_TRANSLATE_BLOGID );
		$this->load_glotpress();

		$project = \GP::$project->by_path( GLOTPRESS_PROJECT );
		if ( ! $project ) {
			return 'Project not found!';
		}

		$po = $this->makepo();

		if ( true === $save ) {
			add_filter( 'gp_import_project_originals', [ $this, 'extend_imported_originals' ], 10, 3 );
			list( $added, $existing, $fuzzied, $obsoleted, $error ) = \GP::$original->import_for_project( $project, $po );
			remove_filter( 'gp_import_project_originals', [ $this, 'extend_imported_originals' ], 10, 3 );

			$notice = sprintf(
				'%1$s new strings added, %2$s updated, %3$s fuzzied, and %4$s obsoleted.',
				$added,
				$existing,
				$fuzzied,
				$obsoleted
			);

			if ( $error ) {
				$notice .= ' ' . sprintf(
					'%s new string(s) were not imported due to an error.',
					$error
				);
			}

			restore_current_blog();

			return $notice;
		} else {
			restore_current_blog();

			return sprintf( 'dry-run: %s translations would be imported using the --save flag', count( $po->entries ) );
		}
	}

	/**
	 * In the patterns case we import patterns in separate runs and can't rely on the full set of strings
	 * being imported at the same time.
	 *
	 * We want to leave most originals active and only obsolete things when they're really no longer in use.
	 */
	public static function extend_imported_originals( $po, $project, $originals_by_key ) {
		// Only prevent dropping originals for the patterns project
		if ( GLOTPRESS_PROJECT !== $project->path ) {
			return $po;
		}

		// Let's get all the references that we are importing.
		$import_references = array();
		if ( ! empty( $po->entries ) ) {
			$import_references = array_merge( ... array_column( $po->entries, 'references' ) );
		}

		foreach ( $originals_by_key as $entry_key => $original ) {
			// We can skip doing anything with currently obsolete originals, these will be added back by our PO as we expect
			if ( '-obsolete' === $original->status ) {
				continue;
			}
			// else we need to merge the current original into our PO so that it doesn't get obsoleted unless its expected

			// Remove import_references from the current original references,
			// the corrected import references will be added back below, or the original is supposed to be going obsolete
			$original_references = array_diff( explode( ' ', $original->references ), $import_references );
			if ( empty( $original_references ) ) {
				continue;
			}

			// If we're importing this original again, merge any references we're not currently pulling in
			if ( isset( $po->entries[ $entry_key ] ) ) {
				$po->entries[ $entry_key ]->references = array_merge( $original_references, $po->entries[ $entry_key ]->references );
			} else {
				// otherwise, merge the original into the po as a new entry
				$po->add_entry(
					new \Translation_Entry(
						[
							'context' => $original->context,
							'singular' => $original->singular,
							'plural' => $original->plural,
							'extracted_comments' => $original->comment,
							'references' => $original_references, // Only include references not covered by our import
						]
					)
				);
			}

			// Changes to reference ordering triggers updates we can avoid
			sort( $po->entries[ $entry_key ]->references );
		}

		return $po;
	}

	/**
	 * Load GlotPress so that we can interact with the GlotPress APIs.
	 */
	public function load_glotpress() {
		// TODO: Figure out how to properly do the following stuff.
		// Maybe this needs to be run in the context of translate.w.org
		// and switch_to_site( PATTERN_DIRECTORY ) instead? But post type
		// would not be registered still.
		// Maybe this should be a two-part operation, Export all strings to
		// .po file, then import into GlotPress as an additional call.

		$GLOBALS['gp_table_prefix'] = GLOTPRESS_TABLE_PREFIX;

		// Load any GlotPress plugins as needed.
		$plugins = get_option( 'active_plugins', [] );
		array_walk( $plugins, function( $plugin ) {
			include_once trailingslashit( WP_PLUGIN_DIR ) . $plugin;
		} );

		// Run the GlotPress init routines.
		if ( ! did_action( 'gp_init' ) ) {
			gp_init();
		}
	}
}
