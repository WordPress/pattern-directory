<?php
namespace WordPressdotorg\Pattern_Translations\Parsers;

require_once __DIR__ . '/BlockParser.php';

class Heading implements BlockParser {
	use GetSetAttribute;

	public function to_strings( array $block ) : array {
		$strings = $this->get_attribute( 'placeholder', $block );

		if ( preg_match( '/<h[1-6][^>]*>(.+)<\/h[1-6]>/is', $block['innerHTML'], $matches ) ) {
			if ( ! empty( $matches[1] ) ) {
				$strings[] = $matches[1];
			}
		}

		return $strings;
	}

	// todo: this needs a fix to properly rebuild innerContent - see ParagraphParserTest
	public function replace_strings( array $block, array $replacements ) : array {
		$this->set_attribute( 'placeholder', $block, $replacements );

		$html = $block['innerHTML'];

		foreach ( $this->to_strings( $block ) as $original ) {
			if ( ! empty( $original ) && isset( $replacements[ $original ] ) ) {
				$regex = '#(<h[1-6][^>]*>)(' . preg_quote( $original, '/' ) . ')(<\/h[1-6]>)#is';
				$html  = preg_replace( $regex, '${1}' . addcslashes( $replacements[ $original ], '\\$' ) . '${3}', $html );
			}
		}

		$block['innerHTML']    = $html;
		$block['innerContent'] = [ $html ];

		return $block;
	}
}
