<?php

namespace WordPressdotorg\Pattern_Directory;

use WP_Error, WP_Post, WP_Post_Type;
use WP_REST_Posts_Controller, WP_REST_Request, WP_REST_Server;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_TAX;

defined( 'WPINC' ) || die();

/**
 * Class REST_Flags_Controller
 *
 * @package WordPressdotorg\Pattern_Directory
 */
class REST_Flags_Controller extends WP_REST_Posts_Controller {
	/**
	 * Parent post type.
	 *
	 * @var string
	 */
	protected $parent_post_type;

	/**
	 * Constructor.
	 *
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {
		parent::__construct( $post_type );

		$this->parent_post_type = PATTERN;
	}

	/**
	 * Retrieves an array of endpoint arguments from the item schema for the controller.
	 *
	 * @param string $method Optional. HTTP method of the request. The arguments for `CREATABLE` requests are
	 *                       checked for required values and may fall-back to a given default, this is not done
	 *                       on `EDITABLE` requests. Default WP_REST_Server::CREATABLE.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$endpoint_args = $this->get_item_schema();

		if ( WP_REST_Server::CREATABLE === $method ) {
			$endpoint_args['properties'] = array_intersect_key(
				$endpoint_args['properties'],
				array(
					'parent'  => true,
					'excerpt' => true,
					FLAG_TAX  => true,
				)
			);
		} elseif ( WP_REST_Server::EDITABLE === $method ) {
			$endpoint_args['properties'] = array_intersect_key(
				$endpoint_args['properties'],
				array(
					'status' => true,
					FLAG_TAX => true,
				)
			);
		}

		return rest_get_endpoint_args_for_schema( $endpoint_args, $method );
	}

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$parent_post_type = get_post_type_object( PATTERN );

		if ( ! current_user_can( $parent_post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit patterns.', 'wporg-patterns' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return parent::get_items_permissions_check( $request );
	}

	/**
	 * Checks if a given request has access to read a post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$parent = $this->get_parent( $post->post_parent );
		if ( is_wp_error( $parent ) ) {
			return $parent;
		}

		if ( ! current_user_can( 'edit_post', $parent->ID ) ) {
			return new WP_Error(
				'rest_cannot_read',
				__( 'Sorry, you are not allowed to view flags for this pattern.', 'wporg-patterns' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return parent::get_item_permissions_check( $request );
	}

	/**
	 * Checks if a given request has access to create a post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'rest_post_exists',
				__( 'Cannot create existing post.', 'wporg-patterns' ),
				array( 'status' => 400 )
			);
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_post_exists',
				__( 'You must be logged in to submit a flag.', 'wporg-patterns' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Prepares a single post for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \stdClass|WP_Error Post object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$schema = $this->get_item_schema();

		$prepared_post = parent::prepare_item_for_database( $request );

		$prepared_post->post_author = get_current_user_id();

		if ( ! isset( $request['status'] ) ) {
			$prepared_post->post_status = $schema['properties']['status']['default'];
		}

		return $prepared_post;
	}

	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = parent::get_item_schema();

		$schema['properties']['status']['default'] = 'pending';
		$schema['properties']['status']['enum']    = array( 'pending', 'private' );

		$schema['properties']['parent'] = array(
			'description' => __( 'The ID for the parent of the object.', 'wporg-patterns' ),
			'type'        => 'integer',
			'context'     => array( 'view', 'edit' ),
		);

		return $schema;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['status']['default']       = 'pending';
		$query_params['status']['items']['enum'] = array( 'pending', 'private', 'any' );

		$query_params['parent']         = array(
			'description' => __( 'Limit result set to items with particular parent IDs.', 'wporg-patterns' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);
		$query_params['parent_exclude'] = array(
			'description' => __( 'Limit result set to all items except those of a particular parent ID.', 'wporg-patterns' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		return $query_params;
	}

	/**
	 * Get the parent post, if the ID is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param int $parent Supplied ID.
	 *
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_parent( $parent ) {
		$error = new WP_Error(
			'rest_post_invalid_parent',
			__( 'Invalid post parent ID.', 'wporg-patterns' ),
			array( 'status' => 404 )
		);
		if ( (int) $parent <= 0 ) {
			return $error;
		}

		$parent = get_post( (int) $parent );
		if ( empty( $parent ) || empty( $parent->ID ) || $this->parent_post_type !== $parent->post_type ) {
			return $error;
		}

		return $parent;
	}
}
