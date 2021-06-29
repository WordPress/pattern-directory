<?php

namespace A8C\Lib\Patterns;

class PatternTranslator {
	public $pattern;
	public $locale;
	public $domain;

	public function __construct( Pattern $pattern, string $locale, string $domain = 'default' ) {
		$this->pattern = $pattern;
		$this->locale = $locale;
		$this->domain = $domain;
	}

	public function translate() : Pattern {
		return temporary_switch_to_locale(
			$this->locale,
			function () {
				$this->extend_translations();

				$parser = new PatternParser( $this->pattern );

				$replacements = [];

				foreach ( $parser->to_strings() as $string ) {
					// We're using translate() here to avoid important warnings about passing variables
					// because we think/hope we really know what we're doing and we're exposing the strings to i18n
					// tooling elsewhere.
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
					$replacements[ $string ] = translate( $string, $this->domain );
				}

				return $parser->replace_strings_with_kses( $replacements );
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

	// Used by https://mc.a8c.com/patterns/
	public function get_translated_strings_without_fallback() : array {
		return temporary_switch_to_locale(
			$this->locale,
			function () : array {
				$this->extend_translations();

				$parser = new PatternParser( $this->pattern );

				$original_strings = $parser->to_strings();
				$replacements = [];

				foreach ( $original_strings as $string ) {
					// We're using translate() here to avoid important warnings about passing variables
					// because we think/hope we really know what we're doing and we're exposing the strings to i18n
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
					$translation = translate( $string, $this->domain );

					// Only add strings if they were translated.
					if ( was_it_translated() ) {
						$replacements[ $string ] = $translation;
					} else {
						$replacements[ $string ] = null;
					}
				}
				return $replacements;
			}
		);
	}

	// Used by https://mc.a8c.com/patterns/
	public function get_translation_status() : float {
		return temporary_switch_to_locale(
			$this->locale,
			function () : float {
				$this->extend_translations();

				$parser = new PatternParser( $this->pattern );

				$original_strings = $parser->to_strings();
				$replacements = [];

				foreach ( $original_strings as $string ) {
					// We're using translate() here to avoid important warnings about passing variables
					// because we think/hope we really know what we're doing and we're exposing the strings to i18n
					// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralDomain
					$translation = translate( $string, $this->domain );

					// Only add strings if they were translated.
					if ( was_it_translated() ) {
						$replacements[ $string ] = $translation;
					}
				}
				$num_original_strings = count( $original_strings );
				if ( 0 === $num_original_strings ) {
					return 0.0;
				}
				return floatval( count( $replacements ) / count( $original_strings ) );
			}
		);
	}
}

/**
 * This class is effectively the same as PatternTranslator, but accepts and returns
 * an array of Patterns instead of a single pattern.
 *
 * The goal is to eventually deprecate PatternTranslator and move all usage to this
 * class.
 */
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
