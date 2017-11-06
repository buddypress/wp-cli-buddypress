<?php
/**
 * Manage XProfile fields.
 *
 * @since 1.5.0
 */
class BPCLI_XProfile_Field extends BPCLI_Component {

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
	 * Get a list of XProfile fields.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See bp_xprofile_get_groups()
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp xprofile field list
	 *
	 * @subcommand list
	 */
	public function _list( $_, $assoc_args ) {
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
	 * --field-group-id=<field-group-id>
	 * : ID of the field group where the new field will be created.
	 *
	 * --name=<name>
	 * : Name of the new field.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile field create --type=checkbox --field-group-id=508 --name="Field Name"
	 *     Success: Created XProfile field "Field Name" (ID 24564).
	 *
	 *     $ wp bp xprofile field add --type=checkbox --field-group-id=165 --name="Another Field"
	 *     Success: Created XProfile field "Another Field" (ID 5465).
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		// Check this is a non-empty, valid field type.
		if ( ! in_array( $assoc_args['type'], (array) buddypress()->profile->field_types, true ) ) {
			WP_CLI::error( 'Not a valid field type.' );
		}

		$field_id = xprofile_insert_field( $assoc_args );

		if ( $field_id ) {
			$field = new BP_XProfile_Field( $field_id );
			$success = sprintf(
				'Created XProfile field "%s" (ID %d).',
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
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile field delete 500 --yes
	 *     Success: Deleted XProfile field "Field Name" (ID 500).
	 *
	 *     $ wp bp xprofile field delete 458 --delete-data --yes
	 *     Success: Deleted XProfile field "Another Field Name" (ID 458).
	 */
	public function delete( $args, $assoc_args ) {
		$field_id = $this->get_field_id( $args[0] );

		WP_CLI::confirm( 'Are you sure you want to delete this field?', $assoc_args );

		parent::_delete( array( $field_id ), $assoc_args, function( $field_id ) use ( $r ) {
			$field   = new BP_XProfile_Field( $field_id );
			$name    = $field->name;
			$id      = $field->id;
			$deleted = $field->delete( $r['delete_data'] );

			if ( $deleted ) {
				return array( 'success', sprintf( 'Deleted XProfile field "%s" (ID %d).', $name, $id ) );
			} else {
				return array( 'error', sprintf( 'Failed deleting XProfile field (ID %d).', $field_id ) );
			}
		} );
	}

	/**
	 * Get an XProfile field.
	 *
	 * ## OPTIONS
	 *
	 * <field-id>
	 * : Identifier for the field. Accepts either the name of the field or a numeric ID.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 * ---
	 * Default: All fields.
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp xprofile field get 500
	 *     $ wp bp xprofile field see 56 --format=json
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$field_id = $this->get_field_id( $args[0] );
		$object   = xprofile_get_field( $field_id );

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
}

WP_CLI::add_command( 'bp xprofile field', 'BPCLI_XProfile_Field', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );