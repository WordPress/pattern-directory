<?php
/**
 * Local stand-in for the `WordPressdotorg\Locales` helpers.
 *
 * The pattern plugins read the locale list from the meta repository's `pub`
 * mu-plugin, which has no wp-env source. This provides a representative set so
 * the environment boots and tests run without it. When `pub` is mapped in
 * through `.wp-env.override.json`, `0-sandbox.php` loads it first and these
 * definitions are skipped.
 *
 * @package pattern-directory-env
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Locales;

if ( ! function_exists( __NAMESPACE__ . '\get_locales' ) ) {
	/**
	 * The locales offered locally, keyed by WordPress locale.
	 *
	 * @return object[] Locale objects with `wp_locale`, `native_name`, and `english_name`.
	 */
	function get_locales(): array {
		$data = array(
			'en_US' => array( 'English (United States)', 'English (United States)' ),
			'ar'    => array( 'العربية', 'Arabic' ),
			'de_DE' => array( 'Deutsch', 'German' ),
			'el'    => array( 'Ελληνικά', 'Greek' ),
			'es_ES' => array( 'Español', 'Spanish (Spain)' ),
			'fa_IR' => array( 'فارسی', 'Persian' ),
			'fi'    => array( 'Suomi', 'Finnish' ),
			'fr_FR' => array( 'Français', 'French (France)' ),
			'he_IL' => array( 'עברית', 'Hebrew' ),
			'hi_IN' => array( 'हिन्दी', 'Hindi' ),
			'id_ID' => array( 'Bahasa Indonesia', 'Indonesian' ),
			'it_IT' => array( 'Italiano', 'Italian' ),
			'ja'    => array( '日本語', 'Japanese' ),
			'ko_KR' => array( '한국어', 'Korean' ),
			'nl_NL' => array( 'Nederlands', 'Dutch' ),
			'pl_PL' => array( 'Polski', 'Polish' ),
			'pt_BR' => array( 'Português do Brasil', 'Portuguese (Brazil)' ),
			'pt_PT' => array( 'Português', 'Portuguese (Portugal)' ),
			'ru_RU' => array( 'Русский', 'Russian' ),
			'sv_SE' => array( 'Svenska', 'Swedish' ),
			'tr_TR' => array( 'Türkçe', 'Turkish' ),
			'uk'    => array( 'Українська', 'Ukrainian' ),
			'vi'    => array( 'Tiếng Việt', 'Vietnamese' ),
			'zh_CN' => array( '简体中文', 'Chinese (China)' ),
			'zh_TW' => array( '繁體中文', 'Chinese (Taiwan)' ),
		);

		$locales = array();
		foreach ( $data as $wp_locale => $names ) {
			$locales[ $wp_locale ] = (object) array(
				'wp_locale'    => $wp_locale,
				'native_name'  => $names[0],
				'english_name' => $names[1],
			);
		}

		return $locales;
	}
}

if ( ! function_exists( __NAMESPACE__ . '\get_locales_with_native_names' ) ) {
	/**
	 * Locale native names, keyed by WordPress locale.
	 *
	 * @return string[]
	 */
	function get_locales_with_native_names(): array {
		return wp_list_pluck( get_locales(), 'native_name', 'wp_locale' );
	}
}

if ( ! function_exists( __NAMESPACE__ . '\get_locales_with_english_names' ) ) {
	/**
	 * Locale English names, keyed by WordPress locale.
	 *
	 * @return string[]
	 */
	function get_locales_with_english_names(): array {
		return wp_list_pluck( get_locales(), 'english_name', 'wp_locale' );
	}
}
