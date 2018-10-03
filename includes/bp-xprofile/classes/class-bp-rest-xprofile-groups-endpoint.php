<?php
/**
 * BP REST: BP_REST_XProfile_Groups_Endpoint class
 *
 * @package BuddyPress
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile field groups.
 *
 * Use /xprofile/groups to find info about all field groups
 * Use /xprofile/groups/{id} to return info about a single field group
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Groups_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = 'buddypress/v1';
		$this->rest_base = buddypress()->profile->id . '/groups';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => $this->get_item_params(),

			),
			'schema' => array( $this, 'get_item_schema' ),
		) );
	}

	/**
	 * Retrieve XProfile field groups.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response List of XProfile field groups.
	 */
	public function get_items( $request ) {
		$args = array(
			'user_id'                => $request['user_id'],
			'member_type'            => $request['member_type'],
			'hide_empty_groups'      => $request['hide_empty_groups'],
			'hide_empty_fields'      => $request['hide_empty_fields'],
			'fetch_fields'           => $request['fetch_fields'],
			'fetch_field_data'       => $request['fetch_field_data'],
			'fetch_visibility_level' => $request['fetch_visibility_level'],
			'exclude_groups'         => $request['exclude_groups'],
			'exclude_fields'         => $request['exclude_fields'],
			'update_meta_cache'      => $request['update_meta_cache'],
		);

		$field_groups = bp_xprofile_get_groups( $args );

		$retval = array();
		foreach ( $field_groups as $item ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $item, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of field groups are fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param array            $field_groups Fetched field groups.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'rest_xprofile_field_group_get_items', $field_groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to xprofile group items.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_authorization_required',
				__( 'Sorry, you are not allowed to see the field groups.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->can_see() ) {
			return new WP_Error( 'rest_user_cannot_view_field_groups',
				__( 'Sorry, you cannot view the field groups.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		return true;
	}

	/**
	 * Retrieve single XProfile field group.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$field_group = $this->get_xprofile_field_group_object( $request );

		if ( empty( $field_group->id ) ) {
			return new WP_Error( 'rest_invalid_field_group_id',
				__( 'Invalid field group id.', 'buddypress' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $field_group, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a field group is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param BP_XProfile_Group $field_group Fetched field group.
		 * @param WP_REST_Response  $response    The response data.
		 * @param WP_REST_Request   $request     The request sent to the API.
		 */
		do_action( 'rest_xprofile_field_group_get_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific field group.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_authorization_required',
				__( 'Sorry, you are not allowed to see this field group.', 'buddypress' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->can_see() ) {
			return new WP_Error( 'rest_user_cannot_view_field_group',
				__( 'Sorry, you cannot view this field group.', 'buddypress' ),
				array(
					'status' => 500,
				)
			);
		}

		return true;
	}

	/**
	 * Prepares single XProfile field group data for return as an object.
	 *
	 * @since 0.1.0
	 *
	 * @param BP_XProfile_Group $group   XProfile field group data.
	 * @param WP_REST_Request   $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $group, $request ) {
		$data = array(
			'id'          => (int) $group->id,
			'name'        => $group->name,
			'description' => $group->description,
			'group_order' => (int) $group->group_order,
			'can_delete'  => (bool) $group->can_delete,
		);

		// If the fields have been requested, we populate them.
		if ( $request['fetch_fields'] ) {
			$data['fields']    = array();
			$fields_controller = new BP_REST_XProfile_Fields_Endpoint();

			foreach ( $group->fields as $field ) {
				$data['fields'][] = $fields_controller->assemble_response_data( $field, $request );
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $group ) );

		/**
		 * Filter the XProfile field group returned from the API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param BP_XProfile_Group $group    XProfile field group.
		 */
		return apply_filters( 'rest_xprofile_field_group_prepare_value', $response, $request, $group );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 0.1.0
	 *
	 * @param array $group XProfile field group.
	 *
	 * @return array Links for the given plugin.
	 */
	protected function prepare_links( $group ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $group->id;

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
	}

	/**
	 * Can this user see the XProfile field groups?
	 *
	 * @since 0.1.0
	 *
	 * @return boolean
	 */
	protected function can_see() {
		$user_id = bp_loggedin_user_id();
		$retval  = false;

		// Moderators.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the retval.
		 *
		 * @since 0.1.0
		 *
		 * @param bool retval   Return value.
		 * @param int  $user_id User ID.
		 */
		return apply_filters( 'rest_xprofile_field_group_can_see', $retval, $user_id );
	}

	/**
	 * Get XProfile field group object.
	 *
	 * @since 0.1.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_XProfile_Group|string XProfile field group object.
	 */
	public function get_xprofile_field_group_object( $request ) {
		$args = array(
			'profile_group_id'       => (int) $request['id'],
			'user_id'                => $request['user_id'],
			'member_type'            => $request['member_type'],
			'hide_empty_fields'      => $request['hide_empty_fields'],
			'fetch_fields'           => $request['fetch_fields'],
			'fetch_field_data'       => $request['fetch_field_data'],
			'fetch_visibility_level' => $request['fetch_visibility_level'],
			'exclude_fields'         => $request['exclude_fields'],
			'update_meta_cache'      => $request['update_meta_cache'],
		);

		$field_group = current( bp_xprofile_get_groups( $args ) );

		if ( empty( $field_group->id ) ) {
			return '';
		}

		return $field_group;
	}

	/**
	 * Clean up member_type input.
	 *
	 * @since 0.1.0
	 *
	 * @param string $value Comma-separated list of group types.
	 * @return array|null
	 */
	public function sanitize_member_types( $value ) {
		if ( ! empty( $value ) ) {
			$types              = explode( ',', $value );
			$registered_types   = bp_get_member_types();
			$registered_types[] = 'any';
			$valid_types        = array_intersect( $types, $registered_types );

			return ( ! empty( $valid_types ) ) ? $valid_types : null;
		}
		return $value;
	}

	/**
	 * Validate member_type input.
	 *
	 * @since 0.1.0
	 *
	 * @param  mixed           $value mixed value.
	 * @param  WP_REST_Request $request Full details about the request.
	 * @param  string          $param string.
	 *
	 * @return WP_Error|boolean
	 */
	public function validate_member_types( $value, $request, $param ) {
		if ( ! empty( $value ) ) {
			$types            = explode( ',', $value );
			$registered_types = bp_get_member_types();

			// Add the special value.
			$registered_types[] = 'any';
			foreach ( $types as $type ) {
				if ( ! in_array( $type, $registered_types, true ) ) {
					/* translators: %1$s and %2$s is replaced with the registered types */
					return new WP_Error( 'rest_invalid_group_type', sprintf( __( 'The member type you provided, %$1s, is not one of %$2s.' ), $type, implode( ', ', $registered_types ) ) );
				}
			}
		}
		return true;
	}

	/**
	 * Get the XProfile field group schema, conforming to JSON Schema.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'xprofile_field_group',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'A unique alphanumeric ID for the object.', 'buddypress' ),
					'readonly'    => true,
					'type'        => 'integer',
				),

				'name'        => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The name of the XProfile field group.', 'buddypress' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),

				'description' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The description of the XProfile field group.', 'buddypress' ),
					'type'        => 'string',
				),

				'group_order' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The order of the group.', 'buddypress' ),
					'type'        => 'integer',
				),

				'can_delete'  => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Whether the XProfile field group can be deleted or not.', 'buddypress' ),
					'type'        => 'boolean',
				),

				'fields'      => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The fields associated with this field group.', 'buddypress' ),
					'type'        => 'array',
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the query params for XProfile field groups.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['hide_empty_groups'] = array(
			'description'       => __( 'True to hide groups that do not have any fields.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Required if you want to load a specific user\'s data.', 'buddypress' ),
			'type'              => 'integer',
			'default'           => bp_loggedin_user_id(),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['member_type'] = array(
			'description'       => __( 'Limit fields by those restricted to a given member type, or array of member types. If `$user_id` is provided, the value of `$member_type` will be overridden by the member types of the provided user. The special value of \'any\' will return only those fields that are unrestricted by member type - i.e., those applicable to any type.', 'buddypress' ),
			'type'              => 'array',
			'default'           => null,
			'sanitize_callback' => array( $this, 'sanitize_member_types' ),
			'validate_callback' => array( $this, 'validate_member_types' ),
		);

		$params['hide_empty_groups'] = array(
			'description'       => __( 'True to hide field groups where the user has not provided data.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['hide_empty_fields'] = array(
			'description'       => __( 'True to hide fields where the user has not provided data.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_fields'] = array(
			'description'       => __( 'Whether to fetch the fields for each group.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_field_data'] = array(
			'description'       => __( 'Whether to fetch data for each field. Requires a $user_id.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_visibility_level'] = array(
			'description'       => __( 'Whether to fetch the visibility level for each field.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_groups'] = array(
			'description'       => __( 'Ensure result set excludes specific profile field groups.', 'buddypress' ),
			'type'              => 'array',
			'default'           => false,
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['exclude_fields'] = array(
			'description'       => __( 'Ensure result set excludes specific profile fields.', 'buddypress' ),
			'type'              => 'array',
			'default'           => false,
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['update_meta_cache'] = array(
			'description'       => __( 'Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.', 'buddypress' ),
			'default'           => true,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the query params for a XProfile field group.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['user_id'] = array(
			'description'       => __( 'Required if you want to load a specific user\'s data.', 'buddypress' ),
			'type'              => 'integer',
			'default'           => bp_loggedin_user_id(),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['member_type'] = array(
			'description'       => __( 'Limit fields by those restricted to a given member type, or array of member types. If `$user_id` is provided, the value of `$member_type` will be overridden by the member types of the provided user. The special value of \'any\' will return only those fields that are unrestricted by member type - i.e., those applicable to any type.', 'buddypress' ),
			'type'              => 'array',
			'default'           => null,
			'sanitize_callback' => array( $this, 'sanitize_member_types' ),
			'validate_callback' => array( $this, 'validate_member_types' ),
		);

		$params['hide_empty_fields'] = array(
			'description'       => __( 'True to hide fields where the user has not provided data.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_fields'] = array(
			'description'       => __( 'Whether to fetch the fields for each group.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_field_data'] = array(
			'description'       => __( 'Whether to fetch data for each field. Requires a $user_id.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_visibility_level'] = array(
			'description'       => __( 'Whether to fetch the visibility level for each field. Requires a $user_id.', 'buddypress' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_fields'] = array(
			'description'       => __( 'Ensure result set excludes specific profile fields.', 'buddypress' ),
			'type'              => 'array',
			'default'           => false,
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$params['update_meta_cache'] = array(
			'description'       => __( 'Whether to pre-fetch XProfile meta for all retrieved groups, fields, and data.', 'buddypress' ),
			'default'           => true,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
