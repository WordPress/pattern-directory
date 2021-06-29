<?php
//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DomDocument/DOMXPath returns classes that use camelCasing

namespace WordPressdotorg\Pattern_Translations;

require_once ABSPATH . '/wp-includes/pomo/po.php';

require_once __DIR__ . '/parsers/BasicTextParser.php';
require_once __DIR__ . '/parsers/BlockParser.php';
require_once __DIR__ . '/parsers/ButtonParser.php';
require_once __DIR__ . '/parsers/HeadingParser.php';
require_once __DIR__ . '/parsers/JetpackContactInfoAddressParser.php';
require_once __DIR__ . '/parsers/JetpackContactInfoEmailParser.php';
require_once __DIR__ . '/parsers/JetpackContactInfoPhoneParser.php';
require_once __DIR__ . '/parsers/NoopParser.php';
require_once __DIR__ . '/parsers/ParagraphParser.php';
require_once __DIR__ . '/parsers/ShortcodeBlockParser.php';
require_once __DIR__ . '/parsers/TextNodeParser.php';

class PatternParser {
	public $pattern;
	public $parsers = [];
	public $fallback;

	public $excluded_tags = [
		'block_pattern_published',
		'block_pattern_show_in_inserter',
		'premium_block_pattern',
	];

	public function __construct( Pattern $pattern ) {
		$this->pattern = $pattern;

		$this->parsers = [
			'core/paragraph' => new ParagraphParser(),
			'core/heading' => new HeadingParser(),
			'core/button' => new ButtonParser(),
			'core/buttons' => new BasicTextParser(),
			'core/list' => new BasicTextParser(),
			'core/column' => new BasicTextParser(),
			'core/columns' => new BasicTextParser(),
			'core/cover' => new BasicTextParser(),
			'core/spacer' => new NoopParser(),
			'core/group' => new BasicTextParser(),
			'core/image' => new BasicTextParser(),
			'core/media-text' => new BasicTextParser(),
			'core/separator' => new BasicTextParser(),
			'core/social-link' => new BasicTextParser(),
			'coblocks/dynamic-separator' => new BasicTextParser(),
			'coblocks/gallery-masonry' => new BasicTextParser(),
			'jetpack/subscriptions' => new ShortcodeBlockParser( [ 'subscribePlaceholder', 'submitButtonText' ] ),
			'jetpack/submit-button' => new ShortcodeBlockParser( [ 'subscribePlaceholder', 'submitButtonText' ] ),
			'jetpack/rating-star' => new BasicTextParser(),
			'jetpack/layout-grid' => new BasicTextParser(),
			'jetpack/layout-grid-column' => new BasicTextParser(),
			'jetpack/business-hours' => new BasicTextParser(),
			'jetpack/contact-info' => new BasicTextParser(),
			'jetpack/email' => new JetpackContactInfoEmailParser(),
			'jetpack/phone' => new JetpackContactInfoPhoneParser(),
			'jetpack/address' => new JetpackContactInfoAddressParser(),
		];

		$this->fallback = new BasicTextParser();
	}

	public function block_parser_to_strings( array $block ) : array {
		$parser = $this->parsers[ $block['blockName'] ] ?? $this->fallback;

		$strings = $parser->to_strings( $block );

		foreach ( $block['innerBlocks'] as $inner_block ) {
			$strings = array_merge( $strings, $this->block_parser_to_strings( $inner_block ) );
		}

		return $strings;
	}

	public function block_parser_replace_strings( array &$block, array $replacements ) : array {
		$parser = $this->parsers[ $block['blockName'] ] ?? $this->fallback;
		$block = $parser->replace_strings( $block, $replacements );

		foreach ( $block['innerBlocks'] as &$inner_block ) {
			$inner_block = $this->block_parser_replace_strings( $inner_block, $replacements );
		}

		return $block;
	}

	public function to_strings() : array {
		$blocks = parse_blocks( $this->pattern->html );

		$strings = [];

		if ( ! empty( $this->pattern->title ) ) {
			$strings = [ $this->pattern->title ];
		}

		if ( ! empty( $this->pattern->description ) ) {
			$strings[] = $this->pattern->description;
		}

		foreach ( $blocks as $block ) {
			$strings = array_merge( $strings, $this->block_parser_to_strings( $block ) );
		}

		foreach ( $this->pattern->categories as $category ) {
			if ( is_string( $category ) ) {
				$strings[] = $category;
			} else if ( is_array( $category ) ) {
				$strings = array_merge( $strings, array_filter( array_values( $category ) ) );
			}
		}

		foreach ( $this->pattern->tags as $slug => $tag ) {
			if ( is_string( $tag ) ) {
				$strings[] = $tag;
			} else if ( is_array( $tag ) ) {
				if ( ! in_array( $slug, $this->excluded_tags ) ) {
					$strings = array_merge( $strings, array_filter( array_values( $tag ) ) );
				}
			}
		}

		return array_unique( $strings );
	}

	public function replace_strings_with_kses( array $replacements ) : Pattern {
		// Sanitize replacement strings before injecting them into blocks and block attributes.
		$sanitized_replacements = $replacements;
		foreach ( $sanitized_replacements as &$replacement ) {
			$replacement = wp_kses_post( $replacement );
		}
		return $this->replace_strings( $sanitized_replacements );
	}

	public function replace_strings( array $replacements ) : Pattern {
		$translated = clone $this->pattern;
		$translated->title = $replacements[ $translated->title ] ?? $translated->title;
		$translated->description = $replacements[ $translated->description ] ?? $translated->description;

		$blocks = parse_blocks( $translated->html );

		foreach ( $blocks as &$block ) {
			$block = $this->block_parser_replace_strings( $block, $replacements );
		}

		foreach ( $this->pattern->categories as $slug => $category ) {
			if ( is_string( $category ) ) {
				if ( isset( $replacements[ $category ] ) ) {
					$translated->categories[ $slug ] = $replacements[ $category ];
				}
			} else if ( is_array( $category ) ) {
				$translated->categories[ $slug ] = array_merge(
					$category,
					[
						'slug' => $replacements[ $category['slug'] ] ?? $category['slug'],
						'title' => $replacements[ $category['title'] ] ?? $category['title'],
						'description' => $replacements[ $category['description'] ] ?? $category['description'],
					]
				);
			}
		}

		foreach ( $this->pattern->tags as $slug => $tag ) {
			if ( ! in_array( $slug, $this->excluded_tags ) ) {
				if ( is_string( $tag ) ) {
					if ( isset( $replacements[ $tag ] ) ) {
						$translated->tags[ $slug ] = $replacements[ $tag ];
					}
				} else if ( is_array( $tag ) ) {
					$translated->tags[ $slug ] = array_merge(
						$tag,
						[
							'slug' => $replacements[ $tag['slug'] ] ?? $tag['slug'],
							'title' => $replacements[ $tag['title'] ] ?? $tag['title'],
							'description' => $replacements[ $tag['description'] ] ?? $tag['description'],
						]
					);
				}
			}
		}

		// If we pass `serialize_blocks` a block that includes unicode characters in the
		// attributes, these attributes will be encoded with a unicode escape character, e.g.
		// "subscribePlaceholder":"😀" becomes "subscribePlaceholder":"\ud83d\ude00".
		// After we get the serialized blocks back from `serialize_blocks` we need to convert these
		// characters back to their unicode form so that we don't break blocks in the editor.
		$translated->html = $this->decode_unicode_characters( serialize_blocks( $blocks ) );

		return $translated;
	}

	/**
	 * Decode a string containing unicode escape sequences.
	 * Excludes decoding characters not allowed within block attributes.
	 *
	 * @param string $string A string containing serialized blocks.
	 * @return string A string containing decoded unicode characters.
	 */
	public function decode_unicode_characters( string $string ): string {

		// In WordPress core, `serialize_block_attributes` intentionally leaves some characters
		// in the block attributes encoded in their unicode form. These are characters that would
		// interfere with characters in block comments e.g. consider potential values entered
		// in the placeholder attribute: <!-- wp:paragraph {"placeholder":"dangerous characters go here"} -->
		// Reference: https://github.com/WordPress/WordPress/blob/HEAD/wp-includes/blocks.php#L367

		$excluded_characters = [
			'\\u002d\\u002d', // '--'
			'\\u003c',        // '<'
			'\\u003e',        // '>'
			'\\u0026',        // '&'
			'\\u0022',        // '"'
		];

		// Match any uninterrupted sequence of \u escaped unicode characters.
		$decoded_string = preg_replace_callback(
			'#(\\\\u[a-zA-Z0-9]{4})+#',
			function ( $matches ) use ( $excluded_characters ) {
				// If we encounter any excluded characters, don't decode this match.
				foreach ( $excluded_characters as $excluded_character ) {
					if ( false !== mb_stripos( $matches[0], $excluded_character ) ) {
						return $matches[0];
					}
				}
				// If we didn't encounter excluded characters, use json_decode to do the heavy lifting.
				return json_decode( '"' . $matches[0] . '"' );
			},
			$string
		);

		return $decoded_string;
	}
}
