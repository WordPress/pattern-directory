<?php
/**
 * Block translation parser helpers.
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations\Parsers;

/**
 * Translate Paragraph block content.
 */
class Paragraph implements BlockParser {
	use GetSetAttribute;

	/**
	 * Extract translatable block strings.
	 *
	 * @param array $block Parsed block.
	 * @return array Extracted strings.
	 */
	public function to_strings( array $block ): array {
		$strings = $this->get_attribute( 'placeholder', $block );

		$matches = array();

		if ( preg_match( '/<p[^>]*>(.+)<\/p>/is', $block['innerHTML'], $matches ) ) {
			if ( ! empty( $matches[1] ) ) {
				$strings[] = $matches[1];
			}
		}

		return $strings;
	}

	/**
	 * Replace translated strings in a block.
	 *
	 * @param array $block        Parsed block.
	 * @param array $replacements Translations keyed by original string.
	 * @return array Updated block.
	 */
	public function replace_strings( array $block, array $replacements ): array {
		$this->set_attribute( 'placeholder', $block, $replacements );

		$html = $block['innerHTML'];

		foreach ( $this->to_strings( $block ) as $original ) {
			if ( ! empty( $original ) && isset( $replacements[ $original ] ) ) {
				$regex = '#(<p[^>]*>)(' . preg_quote( $original, '/' ) . ')(<\/p>)#is';
				$html  = preg_replace( $regex, '${1}' . addcslashes( $replacements[ $original ], '\\$' ) . '${3}', $html );
			}
		}

		$block['innerHTML']    = $html;
		$block['innerContent'] = array( $html );

		return $block;
	}
}
