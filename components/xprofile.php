<?php
/**
 * Manage XProfile data.
 *
 * @since 1.2.0
 */
class BPCLI_XProfile extends BPCLI_Component {

	/**
	 * XProfile object fields.
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
	 * Create an XProfile group.
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
	 * : Whether the group can be deleted.
	 * ---
	 * Default: true.
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp xprofile create_group --name="Group Name" --description="Xprofile Group Description"
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
	 * Fetch specific XProfile field group.
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
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile get_group 500
	 *     $ wp bp xprofile get_group 56 --format=json
	 *
	 * @since 1.5.0
	 */
	public function get_group( $args, $assoc_args ) {
		$field_group_id = $args[0];

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
	 * Delete a specific XProfile field group.
	 *
	 * ## OPTIONS
	 *
	 * <field-group-id>
	 * : Identifier for the field group.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp xprofile delete_group 500
	 */
	public function delete_group( $args, $assoc_args ) {
		$field_group_id = $args[0];

		if ( ! is_numeric( $field_group_id ) ) {
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
	 * Get a list of XProfile fields.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See bp_xprofile_get_groups()
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp xprofile list_fields
	 *
	 * @subcommand list
	 */
	public function _list_fields( $_, $assoc_args ) {
		$r = array_merge( $assoc_args, array(
			'fields'       => 'id,name',
			'fetch_fields' => true,
		) );

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
	 * Create an XProfile field.
	 *
	 * ## OPTIONS
	 *
	 * --type=<type>
	 * : Field type.
	 * ---
	 * default: textbox
	 * ---
	 *
	 * --field_group_id=<field_group_id>
	 * : ID of the field group where the new field will be created.
	 *
	 * --name=<name>
	 * : Name of the new field.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp xprofile create_field --type=checkbox --field-group-id=508
	 *
	 * @since 1.2.0
	 */
	public function create_field( $args, $assoc_args ) {
		// Check this is a non-empty, valid field type.
		if ( ! in_array( $assoc_args['type'], (array) buddypress()->profile->field_types, true ) ) {
			WP_CLI::error( 'Not a valid field type.' );
		}

		$field_id = xprofile_insert_field( $assoc_args );

		if ( $field_id ) {
			$field = new BP_XProfile_Field( $field_id );
			$success = sprintf(
				'Created XProfile field "%s" (ID %d)',
				$field->name,
				$field->id
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not create XProfile field.' );
		}
	}

	/**
	 * Delete an XProfile field.
	 *
	 * ## OPTIONS
	 *
	 * <field-id>...
	 * : ID or IDs for the field. Accepts either the name of the field or a numeric ID.
	 *
	 * [--delete-data]
	 * : Delete user data for the field as well.
	 * ---
	 * default: false
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile delete_field 500
	 *     $ wp bp xprofile delete_field 458 --delete-data
	 *
	 * @since 1.4.0
	 */
	public function delete_field( $args, $assoc_args ) {
		$field_id = $this->get_field_id( $args[0] );

		parent::_delete( array( $field_id ), $assoc_args, function( $field_id ) use ( $r ) {
			$field   = new BP_XProfile_Field( $field_id );
			$name    = $field->name;
			$id      = $field->id;
			$deleted = $field->delete( $r['delete_data'] );

			if ( $deleted ) {
				return array( 'success', sprintf( 'Deleted XProfile field "%s" (ID %d)', $name, $id ) );
			} else {
				return array( 'error', sprintf( 'Failed deleting XProfile field %d.', $field_id ) );
			}
		} );
	}

	/**
	 * Get an XProfile field.
	 *
	 * ## OPTIONS
	 *
	 * <field-id>
	 * : Identifier for the field.
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
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile get_field 500
	 *     $ wp bp xprofile get_field 56 --format=json
	 *
	 * @since 1.5.0
	 */
	public function get_field( $args, $assoc_args ) {
		$field_id = $args[0];

		if ( ! is_numeric( $field_id ) ) {
			WP_CLI::error( 'Please provide a numeric field ID.' );
		}

		$object = xprofile_get_field( $field_id );

		if ( is_object( $object ) && ! empty( $object->id ) ) {
			$object_arr = get_object_vars( $object );
			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = array_keys( $object_arr );
			}
			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $object_arr );
		} else {
			WP_CLI::error( 'No XProfile field found.' );
		}
	}

	/**
	 * Set profile data for a user.
	 *
	 * ## OPTIONS
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * --field-id=<field>
	 * : Identifier for the field. Accepts either the name of the field or a numeric ID.
	 *
	 * --value=<value>
	 * : Value to set.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile set_data --user-id=45 --field-id=120 --value=teste
	 *     $ wp bp xprofile set_data --user-id=user_test --field-id=445 --value=another_test
	 *
	 * @since 1.2.0
	 */
	public function set_data( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		$field_id = $this->get_field_id( $assoc_args['field-id'] );

		$field = new BP_XProfile_Field( $field_id );

		if ( empty( $field->name ) ) {
			WP_CLI::error( 'XProfile field not found.' );
		}

		$value = ( 'checkbox' === $field->type )
			? explode( ',', $assoc_args['value'] )
			: $assoc_args['value'];

		$updated = xprofile_set_field_data( $field->id, $user_id, $value );

		if ( $updated ) {
			$success = sprintf(
				'Updated XProfile field "%s" (ID %d) with value "%s" for user %s (ID %d).',
				$field->name,
				$field->id,
				$assoc_args['value'],
				$user->user_nicename,
				$user->ID
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not set profile data.' );
		}
	}

	/**
	 * Get profile data for a user.
	 *
	 * ## OPTIONS
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--field-id=<field>]
	 * : Identifier for the field. Accepts either the name of the field or a numeric ID.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 * ---
	 *
	 * [--multi-format=<multi-format>]
	 * : The format for array data.
	 *  ---
	 * default: array
	 * options:
	 *   - array
	 *   - comma
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile get_data --user-id=45 --field-id=120
	 *     $ wp bp xprofile get_data --user-id=user_test --field-id=Hometown --multi-format=comma
	 *
	 * @since 1.5.0
	 */
	public function get_data( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		if ( isset( $assoc_args['field-id'] ) ) {
			$data = xprofile_get_field_data( $assoc_args['field-id'], $user->ID, $assoc_args['multi-format'] );
			WP_CLI::print_value( $data, $assoc_args );
		} else {
			$data = BP_XProfile_ProfileData::get_all_for_user( $user->ID );

			$formatted_data = array();
			foreach ( $data as $field_name => $field_data ) {
				// Omit WP core fields.
				if ( ! is_array( $field_data ) ) {
					continue;
				}

				$_field_data = maybe_unserialize( $field_data['field_data'] );
				$_field_data = wp_json_encode( $_field_data );

				$formatted_data[] = array(
					'field_id'   => $field_data['field_id'],
					'field_name' => $field_name,
					'value'      => $_field_data,
				);
			}

			$format_args = $assoc_args;
			$format_args['fields'] = array(
				'field_id',
				'field_name',
				'value',
			);
			$formatter = $this->get_formatter( $format_args );
			$formatter->display_items( $formatted_data );
		}

	}

	/**
	 * Delete profile data for a user.
	 *
	 * ## OPTIONS
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--field-id=<field>]
	 * : Identifier for the field. Accepts either the name of the field or a numeric ID.
	 *
	 * [--delete-all]
	 * : Delete all data for the user.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile delete_data --user-id=45 --field-id=120
	 *     $ wp bp xprofile delete_data --user-id=user_test --delete-all
	 *
	 * @since 1.5.0
	 */
	public function delete_data( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		if ( ! isset( $assoc_args['field-id'] ) && ! isset( $assoc_args['delete-all'] ) ) {
			WP_CLI::error( 'Either --field-id or --delete-all must be provided.' );
		}

		if ( isset( $assoc_args['delete-all'] ) ) {
			WP_CLI::confirm( sprintf( 'Are you sure you want to delete all profile data for the user %s (#%d)?', $user->user_login, $user->ID ) );
			xprofile_remove_data( $user->ID );
			WP_CLI::success( 'Profile data removed.' );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete that?' );
			$deleted = xprofile_delete_field_data( $assoc_args['field-id'], $user->ID );
			if ( $deleted ) {
				WP_CLI::success( 'Profile data removed.' );
			} else {
				WP_CLI::error( 'Could not delete profile data.' );
			}
		}
	}

	/**
	 * Get field ID.
	 *
	 * @param  int $field_id Field ID.
	 * @return int
	 */
	protected function get_field_id( $field_id ) {
		return ( ! is_numeric( $field_id ) )
			? xprofile_get_field_id_from_name( $field_id )
			: absint( $field_id );
	}
}

WP_CLI::add_command( 'bp xprofile', 'BPCLI_XProfile', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );
