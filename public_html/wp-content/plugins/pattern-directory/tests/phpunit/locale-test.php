<?php
/**
 * Test locale negotiation in the Pattern Translations plugin.
 */

use function WordPressdotorg\Pattern_Translations\locale;

/*
 * These tests drive the request superglobals directly and assert on what the filter leaves behind,
 * which is the point of them. The sniffs guarding production input handling do not apply.
 */
// phpcs:disable WordPress.Security.NonceVerification.Recommended
// phpcs:disable WordPress.Security.ValidatedSanitizedInput

/*
 * `locale()` only honours the `locale` query variable on api.wordpress.org requests, which are
 * identified by this constant. It is the only place in the codebase that reads it, so defining it
 * for the whole run does not affect any other test.
 */
if ( ! defined( 'WPORG_IS_API' ) ) {
	define( 'WPORG_IS_API', true );
}

/**
 * Test the `locale` filter callback.
 */
class Pattern_Translations_Locale_Test extends WP_UnitTestCase {
	/**
	 * The $_GET superglobal as it was before the current test ran.
	 *
	 * @var array
	 */
	protected $original_get;

	/**
	 * The $_SERVER superglobal as it was before the current test ran.
	 *
	 * @var array
	 */
	protected $original_server;

	/**
	 * Isolate each test from the real request.
	 */
	public function set_up() {
		parent::set_up();

		$this->original_get    = $_GET;
		$this->original_server = $_SERVER;
		$_GET                  = array();
	}

	/**
	 * Restore the real request.
	 */
	public function tear_down() {
		$_GET    = $this->original_get;
		$_SERVER = $this->original_server;

		parent::tear_down();
	}

	/**
	 * A well-formed locale is honoured.
	 */
	public function test_valid_locale_is_returned() {
		$_GET['locale'] = 'fr_FR';

		$this->assertSame( 'fr_FR', locale( 'en_US' ) );
	}

	/**
	 * With no locale requested, the passed-in locale survives untouched.
	 */
	public function test_missing_locale_falls_through() {
		$this->assertSame( 'en_US', locale( 'en_US' ) );
	}

	/**
	 * A locale is only honoured when sanitizing leaves it unchanged, so anything containing
	 * characters `sanitize_locale_name()` strips is rejected outright rather than coerced.
	 *
	 * @dataProvider data_malformed_locales
	 *
	 * @param string $requested The value of the `locale` query variable.
	 */
	public function test_malformed_locale_is_rejected( $requested ) {
		$_GET['locale'] = $requested;

		$this->assertSame( 'en_US', locale( 'en_US' ) );
	}

	/**
	 * Locale values that must never be honoured.
	 *
	 * @return array[]
	 */
	public function data_malformed_locales() {
		return array(
			'dot separator'  => array( 'en.US' ),
			'path traversal' => array( '../../etc/passwd' ),
			'markup'         => array( '<script>alert(1)</script>' ),
			'null byte'      => array( "en_US\0" ),
			'whitespace'     => array( 'en US' ),
			'empty string'   => array( '' ),
			'zero string'    => array( '0' ),
		);
	}

	/**
	 * A non-string `locale` cannot satisfy the string comparison, and must not raise a type error.
	 */
	public function test_array_locale_is_rejected() {
		$_GET['locale'] = array( 'fr_FR' );

		$this->assertSame( 'en_US', locale( 'en_US' ) );
	}

	/**
	 * WordPress slashes $_GET, so the value is unslashed before it is compared against its own
	 * sanitized form. A value that is only valid once unslashed is therefore honoured, and what
	 * gets returned is the sanitized form rather than the raw input.
	 */
	public function test_slashed_locale_is_unslashed_before_comparison() {
		$_GET['locale'] = 'fr\\_FR';

		$this->assertSame( 'fr_FR', locale( 'en_US' ) );
	}

	/**
	 * `?_locale=user` is rewritten on JSON requests, so localised sites don't return untranslated
	 * details to authenticated users.
	 */
	public function test_user_locale_is_rewritten_on_json_requests() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		$_GET['_locale']        = 'user';

		locale( 'en_US' );

		$this->assertSame( 'site', $_GET['_locale'] );
	}

	/**
	 * Outside a JSON request, `_locale` is left alone.
	 */
	public function test_user_locale_is_untouched_on_regular_requests() {
		unset( $_SERVER['HTTP_ACCEPT'], $_SERVER['CONTENT_TYPE'] );
		$_GET['_locale'] = 'user';

		locale( 'en_US' );

		$this->assertSame( 'user', $_GET['_locale'] );
	}

	/**
	 * A `_locale` other than `user` is left alone even on a JSON request.
	 */
	public function test_other_locale_values_are_untouched() {
		$_SERVER['HTTP_ACCEPT'] = 'application/json';
		$_GET['_locale']        = 'site';

		locale( 'en_US' );

		$this->assertSame( 'site', $_GET['_locale'] );
	}
}
