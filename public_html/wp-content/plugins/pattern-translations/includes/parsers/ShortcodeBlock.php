<?php
namespace WordPressdotorg\Pattern_Translations\Parsers;

/*
 * Handle blocks with attributes present in the attributes that are also used
 * in a shortcode within the block content.
 *
 * Example: new Parsers\ShortcodeBlock( [ 'subscribePlaceholder', 'submitButtonText' ] );
 */
class ShortcodeBlock implements BlockParser {
	use GetSetAttribute;

	public $attribute_names = [];

	public function __construct( array $attribute_names ) {
		$this->attribute_names = $attribute_names;
	}

	public function to_strings( array $block ) : array {
		$strings = [];
		foreach ( $this->attribute_names as $attribute_name ) {
			$strings = array_merge( $strings, $this->get_attribute( $attribute_name, $block ) );
		}

		return $strings;
	}

	public function replace_strings( array $block, array $replacements ) : array {
		foreach ( $this->attribute_names as $attribute_name ) {
			$this->set_attribute( $attribute_name, $block, $replacements );

			foreach ( $block['innerContent'] as $i => &$inner_content ) {
				if ( is_string( $inner_content ) ) {
					$shortcode_param_regex = '/(\b' . $this->snake_case( $attribute_name ) . ')="(.*?)("\n?)/';

					$block['innerContent'][ $i ] = preg_replace_callback(
						$shortcode_param_regex,
						function( $matches ) use ( $replacements ) {
							return $this->preg_replace_gutenberg_attributes_handler( $matches, $replacements );
						},
						$inner_content
					);
				}
			}
		}

		$regex              = '/\b(\w*?)="(.*?)(")/';
		$block['innerHTML'] = preg_replace_callback(
			$regex,
			function( $matches ) use ( $replacements ) {
				return $this->preg_replace_gutenberg_attributes_handler( $matches, $replacements );
			},
			$block['innerHTML']
		);
		return $block;
	}

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	protected function snake_case( $camelCaseString ) {
		return ltrim(
			preg_replace_callback(
				'/([A-Z]+)/',
				function( $matches ) {
					return '_' . strtolower( $matches[1] ); },
				$camelCaseString
			),
			'_'
		);
	}
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

	public function preg_replace_gutenberg_attributes_handler( array $matches, array $replacements ) {
		$current_value = $matches[2];

		if ( ! isset( $replacements[ $current_value ] ) ) {
			return $matches[0];
		}

		$new_value = $replacements[ $current_value ];
		$property  = $matches[1];
		return "$property=\"$new_value" . $matches[3];
	}
}
