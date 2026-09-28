<?php
/**
 * Block Parser interface and traits to be used by individual block parsers.
 *
 * Each block parser needs to implement a to_strings and replace_strings method.
 * The traits are provided here as helper functions for specific tasks, such as
 * dealing with wrapping markup in html tags, getting and setting block attributes,
 * and encoding and decoding tags.
 *
 * @phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations\Parsers;

/**
 * Extract and replace strings for a specific block type.
 */
interface BlockParser {
	/**
	 * Extract translatable block strings.
	 *
	 * @param array $block Parsed block.
	 * @return array Extracted strings.
	 */
	public function to_strings( array $block ): array;

	/**
	 * Replace translated strings in a block.
	 *
	 * @param array $block        Parsed block.
	 * @param array $replacements Translations keyed by original string.
	 * @return array Updated block.
	 */
	public function replace_strings( array $block, array $replacements ): array;
}

/**
 * Preserve whitespace by wrapping fragments before DOM parsing.
 */
trait DomUtils {
	/**
	 * Wrap an HTML fragment in a document.
	 *
	 * @param string $html HTML fragment.
	 * @return string Wrapped HTML.
	 */
	private function add_html( string $html ): string {
		return "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"></head><body>$html</body></html>";
	}

	/**
	 * Remove the document wrapper.
	 *
	 * @param string $html Wrapped HTML.
	 * @return string HTML fragment.
	 */
	private function remove_html( string $html ): string {
		return preg_replace(
			array(
				'/^\s*<html><head><meta http-equiv="Content-Type" content="text\/html; charset=utf-8"><\/head><body>/sm',
				// $dom->saveHTML() can have a trailing newline after the closing </html>, match to the real end of the document.
				'/<\/body><\/html>\s*$/sm',
			),
			'',
			$html
		);
	}

	/**
	 * Parse a fragment while preserving surrounding whitespace.
	 *
	 * @param string $html HTML fragment.
	 * @return \DOMDocument Parsed document.
	 */
	private function get_dom( string $html ): \DOMDocument {
		$previous = libxml_use_internal_errors( true );
		$dom      = new \DomDocument();
		$dom->loadHTML( $this->add_html( $html ), LIBXML_HTML_NODEFDTD | LIBXML_COMPACT );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		return $dom;
	}
}

/**
 * Read and replace translatable block attributes.
 */
trait GetSetAttribute {
	/**
	 * Extract a translatable attribute.
	 *
	 * @param string $attribute_name Attribute name.
	 * @param array  $block          Parsed block.
	 * @return array Attribute strings.
	 */
	private function get_attribute( string $attribute_name, array $block ): array {
		if ( isset( $block['attrs'][ $attribute_name ] ) && is_string( $block['attrs'][ $attribute_name ] ) ) {
			return array( $block['attrs'][ $attribute_name ] );
		}
		return array();
	}

	/**
	 * Replace a translatable attribute.
	 *
	 * @param string $attribute_name Attribute name.
	 * @param array  $block          Parsed block.
	 * @param array  $replacements   Translations keyed by original string.
	 * @return void
	 */
	private function set_attribute( string $attribute_name, array &$block, array $replacements ) {
		if ( isset( $block['attrs'][ $attribute_name ] ) && is_string( $block['attrs'][ $attribute_name ] ) ) {
			if ( isset( $replacements[ $block['attrs'][ $attribute_name ] ] ) ) {
				$block['attrs'][ $attribute_name ] = $replacements[ $block['attrs'][ $attribute_name ] ];
			}
		}
	}
}

/**
 * Encode inline tags while translating text nodes.
 */
trait SwapTags {
	/**
	 * Inline tags preserved during translation.
	 *
	 * @var string[]
	 */
	private $safe_tags = array(
		'strong',
		'em',
	);

	/**
	 * Encode inline tags before DOM parsing.
	 *
	 * @param string $raw_html Original HTML.
	 * @return string Encoded HTML.
	 */
	private function encode_tags( string $raw_html ): string {
		foreach ( $this->safe_tags as $tag ) {
			$raw_html = preg_replace(
				'#(<' . $tag . '([^>]*)>)(.*)(</' . $tag . '>)#',
				'{' . $tag . '$2}$3{/' . $tag . '}',
				$raw_html
			);
		}
		return $raw_html;
	}

	/**
	 * Restore encoded inline tags.
	 *
	 * @param string $encoded_html Encoded HTML.
	 * @return string Decoded HTML.
	 */
	private function decode_tags( string $encoded_html ): string {
		foreach ( $this->safe_tags as $tag ) {
			$encoded_html = preg_replace(
				'#({' . $tag . '([^}]*)})(.*)({/' . $tag . '})#',
				'<' . $tag . '$2>$3</' . $tag . '>',
				$encoded_html
			);
		}
		return $encoded_html;
	}
}

/**
 * Locate translatable text and attributes.
 */
trait TextNodesXPath {
	/**
	 * Selectors for translatable text and attributes.
	 *
	 * @var string[]
	 */
	private $xpaths = array(
		'//text()',   // Visible Text nodes.
		'//img/@alt', // Image alt="" text.
		'//*/@title', // title="" text.
	);

	/**
	 * Build the translatable text XPath query.
	 *
	 * @return string XPath query.
	 */
	protected function text_nodes_xpath_query() {
		return implode( ' | ', $this->xpaths );
	}
}
