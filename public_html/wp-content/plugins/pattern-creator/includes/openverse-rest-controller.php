<?php
/**
 * Class Openverse_REST_Controller
 *
 * This serves as a proxy layer to authenticate and cache the Openverse API requests.
 *
 * @package WordPressdotorg\Pattern_Creator
 */
class Openverse_REST_Controller extends WP_REST_Controller {
	/**
	 * @var string The namespace of this controller's route.
	 */
	protected $namespace = 'wporg/v1';

	/**
	 * @var string The base of this controller's route.
	 */
	protected $rest_base = 'openverse';

	/**
	 * Register the search route for Openverse.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}

	/**
	 * Return the list of search results, possibly from a local cache.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$ov_client = new Openverse_Client( $request->get_params() );
		$results = $ov_client->search();

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		$data = array();
		foreach ( $results->results as $item ) {
			$itemdata = $this->prepare_item_for_response( $item, $request );
			$data[] = $this->prepare_response_for_collection( $itemdata );
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', (int) $results->result_count );
		$response->header( 'X-WP-TotalPages', (int) $results->page_count );

		return $response;
	}

	/**
	 * All logged-in users can make openverse requests.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'read' );
	}

	/**
	 * Prepare the item for the REST response, strip out unused fields.
	 *
	 * @param mixed           $item Object as returned from the Openverse API.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	public function prepare_item_for_response( $item, $request ) {
		return array(
			'id' => sanitize_text_field( $item->id ),
			'title' => sanitize_text_field( $item->title ),
			'url' => esc_url( $item->url ),
			'thumbnail' => esc_url( $item->thumbnail ),
		);
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 30,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => 'Limit results to those matching a string.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
