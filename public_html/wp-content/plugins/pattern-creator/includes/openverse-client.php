<?php
/**
 * Class Openverse_Client
 *
 * @package WordPressdotorg\Pattern_Creator
 */
class Openverse_Client {
	/**
	 * @var string The option key where the cache is saved in the database.
	 */
	const CACHE_KEY = 'ov-request';

	/**
	 * @var string The option key where the cache is saved in the database.
	 */
	const TOKEN_OPTION_KEY = 'ov-oauth-token';

	/**
	 * @var array<string, string> The set of parameters for this request.
	 */
	protected $params = array();

	/**
	 * @var string The base URL for requests.
	 */
	protected $url = 'https://api.openverse.engineering';

	/**
	 * Openverse_Client constructor.
	 */
	public function __construct( array $params = [] ) {
		$defaults = array(
			'per_page' => 30,
			'page' => 1,
		);

		$this->params = wp_parse_args( $params, $defaults );
	}

	/**
	 * Get the parameters for this request.
	 *
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}

	/**
	 * Get the parameters for this request.
	 *
	 * @param string $key     Which parameter to return.
	 * @param mixed  $default The default value, if not found in the set params.
	 * @return array
	 */
	public function get_param( $key, $default = null ) {
		if ( isset( $this->params[ $key ] ) ) {
			return $this->params[ $key ];
		}
		return $default;
	}

	/**
	 * Check if token exists and has not expired.
	 *
	 * @param array $token Token object.
	 * @return bool|int False upon failure, seconds remaining for token on success.
	 */
	public function is_valid_token( $token ) {
		if ( ! isset( $token['access_token'] ) || ! $token['access_token'] ) {
			return false;
		}
		if ( ! isset( $token['expires_at'] ) ) {
			return false;
		}
		if ( time() >= $token['expires_at'] ) {
			return false;
		}
		return time() - $token['expires_at'];
	}

	/**
	 * Get (or generate) a valid oauth token.
	 *
	 * @return string|WP_Error A valid token or WP_Error if there was an error.
	 */
	public function get_oauth_token() {
		$token = get_option( self::TOKEN_OPTION_KEY, array() );
		$token_life = $this->is_valid_token( $token );
		if ( $token_life >= 5 * MINUTE_IN_SECONDS ) {
			return $token['access_token'];
		}

		$being_refreshed = get_option( self::TOKEN_OPTION_KEY . '_refresh' );
		if ( $being_refreshed > time() - MINUTE_IN_SECONDS ) {
			// Return stale token if possible.
			if ( $token_life ) {
				return $token['access_token'];
			}

			// Token has expired, and we're still refreshing the token, which means it's failed multiple times in a row.
			return new WP_Error(
				'invalid-ov-token-refresh',
				'The token refresh process is locked, and we have no almost-expiring tokens.'
			);
		}

		// Lock token refresh
		update_option( self::TOKEN_OPTION_KEY . '_refresh', time() );

		$response = wp_remote_post( $this->url . '/v1/auth_tokens/token/', array(
			'body' => array(
				'client_id' => PATTERN_OV_OAUTH_ID,
				'client_secret' => PATTERN_OV_OAUTH_SECRET,
				'grant_type' => 'client_credentials',
			),
		) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error(
				'invalid-ov-token-request',
				sprintf( 'The token generation request failed with a %s error.', $response_code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$token = json_decode( $body, true ); // Second param returns decoded value as assoc array.
		if ( null === $token ) {
			return new WP_Error( 'invalid-ov-token-response', 'The token generation response is malformed.' );
		}

		// Invalidate the token a minute early, ensures that any requests around the "expiry" succeed as long as the request is made within a minute of the token validation being run.
		$token['expires_at'] = time() + $token['expires_in'] - MINUTE_IN_SECONDS;
		update_option( self::TOKEN_OPTION_KEY, $token );
		delete_option( self::TOKEN_OPTION_KEY . '_refresh' );

		return $token['access_token'];
	}

	/**
	 * Get the results from the Openverse API.
	 *
	 * @return WP_Error|array
	 */
	public function fetch_results() {
		$auth_token = $this->get_oauth_token();
		if ( is_wp_error( $auth_token ) ) {
			return $auth_token;
		}

		$url = add_query_arg(
			array(
				'format' => 'json',
				'license' => 'cc0',
				'q' => $this->get_param( 'search' ),
				'page' => $this->get_param( 'page' ),
				'page_size' => $this->get_param( 'per_page' ),
			),
			$this->url . '/v1/images',
		);
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $auth_token,
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return new WP_Error(
				'invalid-openverse-request',
				sprintf( 'The Openverse API request failed with a %s error.', $response_code )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$data = json_decode( $body );
		if ( null === $data ) {
			return new WP_Error( 'invalid-openverse-response', 'The Openverse API response is malformed.' );
		}

		return $data;
	}

	/**
	 * Return the list of search results, possibly from a local cache.
	 *
	 * @return WP_Error|array
	 */
	public function search() {
		if ( ! $this->get_param( 'search' ) ) {
			return new WP_Error( 'invalid-empty-search', __( 'Search term cannot be empty.', 'wporg-patterns' ) );
		}

		$cache_key = self::CACHE_KEY . md5( wp_json_encode( $this->get_params() ) );
		$ttl = HOUR_IN_SECONDS;

		$results = get_transient( $cache_key );
		if ( false === $results ) {
			$results = $this->fetch_results();
			if ( is_wp_error( $results ) ) {
				// During development, return the upstream error.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					return $results;
				}

				// Log the error
				trigger_error( $results->get_error_code(), E_USER_WARNING ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				// Set a short timeout to avoid hammering the API during outages.
				set_transient( $cache_key, [], 0.5 * MINUTE_IN_SECONDS );

				return new WP_Error(
					'search-request-failed',
					__( 'The search API provider is currently experiencing an error. Try again in a few seconds', 'wporg-patterns' ) // Publicly facing human-readable error.
				);
			}
			set_transient( $cache_key, $results, $ttl );
		}

		return $results;
	}
}
