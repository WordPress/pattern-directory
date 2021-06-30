<?php
namespace WordPressdotorg\Pattern_Translations;

class PatternsTranslator {
	public $patterns;
	public $locale;
	public $domain;

	public function __construct( array $patterns, string $locale, string $domain = 'default' ) {
		$this->patterns = $patterns;
		$this->locale   = $locale;
		$this->domain   = $domain;
	}

	public function translate() : array {
		return temporary_switch_to_locale(
			$this->locale,
			function () {
				$this->extend_translations();

				$translated_patterns = [];

				foreach ( $this->patterns as $pattern ) {
					$parser = new PatternParser( $pattern );

					$replacements = [];

					foreach ( $parser->to_strings() as $string ) {
						// We're using translate() here to avoid important warnings about passing variables
						// because we think/hope we really know what we're doing and we're exposing the strings to i18n
						// tooling elsewhere.
						// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
						$replacements[ $string ] = translate( $string, $this->domain );
					}

					$translated_patterns[] = $parser->replace_strings_with_kses( $replacements );
				}
				return $translated_patterns;
			}
		);
	}

	/*
	 * Load translations from the https://translate.wordpress.com/projects/wpcom/block-patterns/ project
	 * into the $domain domain.
	 */
	private function extend_translations() {
		$current_translations = get_translations_for_domain( $this->domain );

		if ( $current_translations && $current_translations->get_header( 'block-pattern-translations' ) ) {
			return $current_translations;
		};

		$mofile = WP_LANG_DIR . '/block-patterns/' . $this->locale . '.mo';
		$loaded = load_textdomain( $this->domain, $mofile );
		if ( ! $loaded ) {
			return false;
		}

		$extended_translations = get_translations_for_domain( $this->domain );
		$extended_translations->set_header( 'block-pattern-translations', 'loaded' );
		return $extended_translations;
	}
}
