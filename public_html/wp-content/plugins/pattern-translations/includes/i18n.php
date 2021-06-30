<?php
namespace WordPressdotorg\Pattern_Translations;
use GlotPress_Translate_Bridge;

class PatternsTranslator {
	public $patterns;
	public $locale;
	public $domain;

	public function __construct( array $patterns, string $locale ) {
		$this->patterns = $patterns;
		$this->locale   = $locale;
	}

	public function translate() : array {
		$translated_patterns = [];

		switch_to_locale( $this->locale );

		foreach ( $this->patterns as $pattern ) {
			$parser = new PatternParser( $pattern );

			$replacements = [];

			foreach ( $parser->to_strings() as $string ) {
				$replacements[ $string ] = GlotPress_Translate_Bridge::translate( $string, GLOTPRESS_PROJECT );
			}

			$translated_patterns[] = $parser->replace_strings_with_kses( $replacements );
		}

		restore_current_locale();

		return $translated_patterns;
	}

}
