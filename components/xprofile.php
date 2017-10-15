<?php
/**
 * Manage Xprofile data.
 *
 * @since 1.2.0
 */
class BPCLI_XProfile extends BPCLI_Component {

	/**
	 * Xprofile object fields
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'name',
		'description',
		'type',
		'group_id',
		'is_required',
	);

	/**
	 * Create an xprofile group.
	 *
	 * ## OPTIONS
	 *
	 * [--name=<name>]
	 * : The name for this field group.
	 *
	 * [--description=<description>]
	 * : The description for this field group.
	 *
	 * [--can-delete=<can-delete>]
	 * : Whether the group can be deleted. Default: true.
	 *
	 * ## EXAMPLE
	 *
	 *    wp bp xprofile create_group --name="Group Name" --description="Xprofile Group Description"
	 *
	 * @synopsis [--name=<name>] [--description=<description>] [--can-delete=<can-delete>]
	 *
	 * @since 1.2.0
	 */
	public function create_group( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'name'        => '',
			'description' => '',
			'can_delete'  => true,
		) );

		if ( empty( $r['name'] ) ) {
			WP_CLI::error( 'Please specify a group name.' );
		}

		$group = xprofile_insert_field_group( $r );

		if ( $group ) {
			$group = new BP_XProfile_Group( $group );
			$success = sprintf(
				'Created XProfile field group "%s" (ID %d)',
				$group->name,
				$group->id
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not create field group.' );
		}
	}

	/**
	 * Fetch specific profile field group.
	 *
	 * ## OPTIONS
	 *
	 * <field-group-id>
	 * : Identifier for the field group.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *    wp bp xprofile get_group 500
	 *    wp bp xprofile get_group 56 --format=json
	 *
	 * @synopsis <field-group-id> [--fields=<fields>] [--format=<format>]
	 *
	 * @since 1.5.0
	 */
	public function get_group( $args, $assoc_args ) {
		$field_group_id = isset( $args[0] ) ? $args[0] : '';

		if ( empty( $field_group_id ) ) {
			WP_CLI::error( 'Please specify a field group ID.' );
		}

		if ( ! is_numeric( $field_group_id ) ) {
			WP_CLI::error( 'This is not a valid field group ID.' );
		}

		$object = xprofile_get_field_group( $field_group_id );

		$object_arr = get_object_vars( $object );
		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $object_arr );
		}
		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $object_arr );
	}

	/**
	 * Delete a specific profile field group.
	 *
	 * ## OPTIONS
	 *
	 * <field-group-id>
	 * : Identifier for the field group.
	 *
	 * ## EXAMPLE
	 *
	 *    wp bp xprofile delete_group 500
	 *
	 * @synopsis <field-group-id>
	 *
	 * @since 1.5.0
	 */
	public function delete_group( $args, $assoc_args ) {
		$field_group_id = isset( $args[0] ) ? $args[0] : '';

		if ( empty( $field_group_id ) ) {
			WP_CLI::error( 'Please specify a field group ID.' );
		}

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'This is not a valid field group ID.' );
		}

		// Delete field group. True if deleted.
		if ( xprofile_delete_field_group( $field_group_id ) ) {
			WP_CLI::success( 'Field group deleted.' );
		} else {
			WP_CLI::error( 'Could not delete the field group.' );
		}
	}

	/**
	 * Get a list of xprofile fields.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See bp_xprofile_get_groups()
	 *
	 * ## EXAMPLES
	 *
	 *    wp bp xprofile list_fields
	 *
	 * @synopsis [--field=<value>]
	 *
	 * @since 1.4.0
	 */
	public function list_fields_( $_, $assoc_args ) {
		$r = array_merge( $assoc_args, array(
			'fields'       => 'id,name',
			'fetch_fields' => true,
		);

		$formatter = $this->get_formatter( $assoc_args );
		$groups = bp_xprofile_get_groups( $r );

		// Reformat so that field_group_id is a property of fields.
		$fields = array();
		foreach ( $groups as $group ) {
			foreach ( $group->fields as $field ) {
				$fields[ $field->id ] = $field;
			}
		}

		ksort( $fields );

		$formatter->display_items( $fields );
	}

	/**
	 * Create an xprofile field.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See xprofile_insert_field()
	 *
	 * ## EXAMPLE
	 *
	 *    wp bp xprofile create_field -
	 *
	 * @synopsis [--field=<value>]
	 *
	 * @since 1.2.0
	 */
	public function create_field( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'type'           => '',
			'field_group_id' => '',
		) );

		if ( empty( $r['type'] ) ) {
			WP_CLI::error( 'Please specify a field type.' );
		}

		if ( empty( $r['field_group_id'] ) ) {
			WP_CLI::error( 'Please specify a field group id.' );
		}

		$field_id = xprofile_insert_field( $r );

		if ( $field_id ) {
			$field = new BP_XProfile_Field( $field_id );
			$success = sprintf(
				'Created XProfile field "%s" (ID %d)',
				$field->name,
				$field->id
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not create field.' );
		}
	}

	/**
	 * Delete an xprofile field.
	 *
	 * ## OPTIONS
	 *
	 * <field-id>
	 * : Field ID. Accepts either the name of the field or a numeric ID.
	 *
	 * [--delete-data=<delete-data>]
	 * : Whether to delete user data for the field as well. Default: false
	 *
	 * ## EXAMPLE
	 *
	 *    wp bp xprofile delete_field 500
	 *
	 * @synopsis <field-id> [--delete-data=<delete-data>]
	 *
	 * @since 1.4.0
	 */
	public function delete_field( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'delete_data' => false,
		) );

		$field_id = isset( $args[0] ) ? $args[0] : '';

		if ( empty( $field_id ) ) {
			WP_CLI::error( 'Please specify a field ID.' );
		}

		$field_id = ( ! is_numeric( $field_id ) ) 
			? xprofile_get_field_id_from_name( $field_id )
			: absint( $field_id );

		parent::_delete( array( $field_id ), $assoc_args, function( $field_id ) use ( $r ) {
			$field   = new BP_XProfile_Field( $field_id );
			$name    = $field->name;
			$id      = $field->id;
			$deleted = $field->delete( $r['delete_data'] );

			if ( $deleted ) {
				return array( 'success', sprintf( 'Deleted XProfile field "%s" (ID %d)', $name, $id ) );
			} else {
				return array( 'error', sprintf( 'Failed deleting field %d.', $field_id ) );
			}
		} );
	}

	/**
	 * Get a profile field.
	 *
	 * ## OPTIONS
	 *
	 * <field-id>
	 * : Identifier for the field group.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *    wp bp xprofile get_field 500
	 *    wp bp xprofile get_field 56 --format=json
	 *
	 * @synopsis <field-id> [--fields=<fields>] [--format=<format>]
	 *
	 * @since 1.5.0
	 */
	public function get_field( $args, $assoc_args ) {
		$field_id = isset( $args[0] ) ? $args[0] : '';

		if ( empty( $field_id ) ) {
			WP_CLI::error( 'Please specify a field ID.' );
		}

		if ( ! is_numeric( $field_id ) ) {
			WP_CLI::error( 'This is not a valid field ID.' );
		}

		$object = xprofile_get_field( $field_id );

		if ( is_object( $object ) ) {
			$object_arr = get_object_vars( $object );
			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = array_keys( $object_arr );
			}
			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $object_arr );
		} else {
			WP_CLI::error( 'No field found.' );
		}
	}

	/**
	 * Set profile data for a user.
	 *
	 * ## OPTIONS
	 *
	 * [--user-id=<user>]
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--field-id=<field>]
	 * : Field ID. Accepts either the name of the field or a numeric ID.
	 *
	 * [--value=<value>]
	 * : Value to set.
	 *
	 * [--is-required=<is-required>]
	 * : Whether a non-empty value is required. Default: false
	 *
	 * ## EXAMPLE
	 *
	 *    wp bp xprofile set_data --user-id=45 --field-id=120 --value=teste
	 *
	 * @synopsis [--user-id=<user>] [--field-id=<field>] [--value=<value>] [--is-required=<is-required>]
	 *
	 * @since 1.2.0
	 */
	public function set_data( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'user_id'     => '',
			'field_id'    => '',
			'value'       => '',
			'is_required' => false,
		) );

		$user = $this->get_user_id_from_identifier( $r['user_id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( empty( $r['field_id'] ) ) {
			WP_CLI::error( 'Please specify a field ID.' );
		}

		if ( empty( $r['value'] ) ) {
			WP_CLI::error( 'Please specify a value information to set.' );
		}

		$field_id = $r['field_id'];
		$field_id = ( ! is_numeric( $field_id ) ) 
			? xprofile_get_field_id_from_name( $field_id )
			: absint( $field_id );


		$field = new BP_XProfile_Field( $field_id );

		if ( empty( $field->name ) ) {
			WP_CLI::error( 'No field found by that name' );
		}

		if ( 'checkbox' === $field->type ) {
			$r['value'] = explode( ',', $r['value'] );
		}

		$updated = xprofile_set_field_data( $field->id, $user_id, $r['value'], $r['is_required'] );

		if ( $updated ) {
			$success = sprintf(
				'Updated field "%s" (ID %d) with value "%s" for user %s (ID %d)',
				$field->name,
				$field->id,
				$r['value'],
				$user->user_nicename,
				$user->ID
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not set profile data.' );	
		}
	}
}

WP_CLI::add_command( 'bp xprofile', 'BPCLI_XProfile', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );
