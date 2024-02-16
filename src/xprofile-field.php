<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage XProfile Fields.
 *
 * @since 1.5.0
 */
class XProfile_Field extends BuddyPressCommand {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'name',
		'description',
		'type',
		'group_id',
		'is_required',
	];

	/**
	 * Get a list of XProfile fields.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See bp_xprofile_get_groups()
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 *  * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each field:
	 *
	 * * id
	 * * name
	 * * description
	 * * type
	 * * group_id
	 * * is_required
	 *
	 * ## EXAMPLE
	 *
	 *     # List XProfile fields.
	 *     $ wp bp xprofile field list
	 *     +----+------+-------------+---------+----------+-------------+
	 *     | id | name | description | type    | group_id | is_required |
	 *     +----+------+-------------+---------+----------+-------------+
	 *     | 1  | Name |             | textbox | 1        | 1           |
	 *     +----+------+-------------+---------+----------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$args = array_merge(
			$assoc_args,
			[
				'fields'       => 'id,name',
				'fetch_fields' => true,
			]
		);

		$fields = [];
		$groups = bp_xprofile_get_groups( $args );

		// Reformat so that field_group_id is a property of fields.
		foreach ( $groups as $group ) {
			foreach ( $group->fields as $field ) {
				$fields[ $field->id ] = $field;
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $fields, 'id' ) : $fields );
	}

	/**
	 * Create a XProfile field.
	 *
	 * ## OPTIONS
	 *
	 * --field-group-id=<field-group-id>
	 * : ID of the field group where the new field will be created.
	 *
	 * --name=<name>
	 * : Name of the new field.
	 *
	 * [--type=<type>]
	 * : Field type.
	 * ---
	 * default: textbox
	 * ---
	 *
	 * [--silent]
	 * : Whether to silent the XProfile field creation.
	 *
	 * [--porcelain]
	 * : Output just the new field id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a XProfile field.
	 *     $ wp bp xprofile field create --type=checkbox --field-group-id=508 --name="Field Name"
	 *     Success: Created XProfile field "Field Name" (ID 24564).
	 *
	 *     # Create a XProfile field.
	 *     $ wp bp xprofile field add --field-group-id=165 --name="Another Field"
	 *     Success: Created XProfile field "Another Field" (ID 5465).
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		// Check this is a non-empty, valid field type.
		if ( ! in_array( $assoc_args['type'], (array) buddypress()->profile->field_types, true ) ) {
			WP_CLI::error( 'Not a valid field type.' );
		}

		$xprofile_field_id = xprofile_insert_field(
			[
				'type'           => $assoc_args['type'],
				'name'           => $assoc_args['name'],
				'field_group_id' => $assoc_args['field-group-id'],
			]
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $xprofile_field_id ) {
			WP_CLI::error( 'Could not create XProfile field.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $xprofile_field_id );
		} else {
			$field = new \BP_XProfile_Field( $xprofile_field_id );

			WP_CLI::success(
				sprintf(
					'Created XProfile field "%s" (ID %d).',
					$field->name,
					$field->id
				)
			);
		}
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
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get a xprofile field.
	 *     $ wp bp xprofile field get 500
	 *
	 *     # Get a xprofile field in JSON format.
	 *     $ wp bp xprofile field see 56 --format=json
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$field_id = $this->get_field_id( $args[0] );
		$object   = xprofile_get_field( $field_id );

		if ( empty( $object->id ) && ! is_object( $object ) ) {
			WP_CLI::error( 'No XProfile field found.' );
		}

		$object_arr = get_object_vars( $object );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $object_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $object_arr );
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
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a field.
	 *     $ wp bp xprofile field delete 500 --yes
	 *     Success: Deleted XProfile field "Field Name" (ID 500).
	 *
	 *     # Delete a field and its data.
	 *     $ wp bp xprofile field remove 458 --delete-data --yes
	 *     Success: Deleted XProfile field "Another Field Name" (ID 458).
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$delete_data = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'delete-data' );
		$field_ids   = wp_parse_id_list( $args );

		if ( count( $field_ids ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these fields?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this field?', $assoc_args );
		}

		parent::_delete(
			$field_ids,
			$assoc_args,
			function ( $field_id ) use ( $delete_data ) {
				$field = new \BP_XProfile_Field( $field_id );
				$name  = $field->name;
				$id    = $field->id;

				if ( $field->delete( $delete_data ) ) {
					return [ 'success', sprintf( 'Deleted XProfile field "%s" (ID %d).', $name, $id ) ];
				}

				return [ 'error', sprintf( 'Failed deleting XProfile field "%s" (ID %d).', $name, $id ) ];
			}
		);
	}
}
