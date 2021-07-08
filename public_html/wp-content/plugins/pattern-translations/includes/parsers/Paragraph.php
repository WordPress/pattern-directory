<?php
namespace WordPressdotorg\Pattern_Translations\Parsers;

class Paragraph implements BlockParser {
	use GetSetAttribute;

	public function to_strings( array $block ) : array {
		$strings = $this->get_attribute( 'placeholder', $block );

		$matches = [];

		if ( preg_match( '/<p[^>]*>(.+)<\/p>/is', $block['innerHTML'], $matches ) ) {
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
				$regex = '#(<p[^>]*>)(' . preg_quote( $original, '/' ) . ')(<\/p>)#is';
				$html  = preg_replace( $regex, '${1}' . addcslashes( $replacements[ $original ], '\\$' ) . '${3}', $html );
			}
		}

		$block['innerHTML']    = $html;
		$block['innerContent'] = [ $html ];

		return $block;
	}
}
