<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage XProfile Groups.
 *
 * @since 1.5.0
 */
class XProfile_Group extends BuddyPressCommand {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'name',
		'description',
		'group_order',
		'can_delete',
	];

	/**
	 * Object ID key.
	 *
	 * @var string
	 */
	protected $obj_id_key = 'id';

	/**
	 * Create an XProfile group.
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : The name for this field group.
	 *
	 * [--description=<description>]
	 * : The description for this field group.
	 *
	 * [--can-delete=<can-delete>]
	 * : Whether the group can be deleted.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--silent]
	 * : Whether to silent the XProfile group creation.
	 *
	 * [--porcelain]
	 * : Output just the new group id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create XProfile field group.
	 *     $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
	 *     Success: Created XProfile field group "Group Name" (ID 123).
	 *
	 *     # Create XProfile field group that can't be deleted.
	 *     $ wp bp xprofile group add --name="Another Group" --can-delete=false
	 *     Success: Created XProfile field group "Another Group" (ID 21212).
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args(
			$assoc_args,
			[
				'name'        => '',
				'description' => '',
			]
		);

		$xprofile_group_id = xprofile_insert_field_group( $r );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $xprofile_group_id ) {
			WP_CLI::error( 'Could not create field group.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $xprofile_group_id );
		} else {
			$group = new \BP_XProfile_Group( $xprofile_group_id );

			WP_CLI::success(
				sprintf(
					'Created XProfile field group "%s" (ID %d).',
					$group->name,
					$group->id
				)
			);
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
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
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
	 *     # Get a specific field group.
	 *     $ wp bp xprofile group get 500
	 *     +-------------+---------------+
	 *     | Field       | Value         |
	 *     +-------------+---------------+
	 *     | id          | 2             |
	 *     | name        | Group         |
	 *     | description |               |
	 *     | can_delete  | 1             |
	 *     | group_order | 0             |
	 *     | fields      | null          |
	 *     +-------------+---------------+
	 *
	 *     # Get a specific field group in JSON format.
	 *     $ wp bp xprofile group see 56 --format=json
	 *     {"id":2,"name":"Group","description":"","can_delete":1,"group_order":0,"fields":null}
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$field_group_id = $args[0];

		if ( ! is_numeric( $field_group_id ) ) {
			WP_CLI::error( 'Please provide a numeric field group ID.' );
		}

		$object = xprofile_get_field_group( $field_group_id );

		if ( empty( $object->id ) && ! is_object( $object ) ) {
			WP_CLI::error( 'No XProfile field group found.' );
		}

		$object_arr = get_object_vars( $object );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $object_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $object_arr );
	}

	/**
	 * Delete specific XProfile field group(s).
	 *
	 * ## OPTIONS
	 *
	 * <field-group-id>...
	 * : ID or IDs of field groups to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a specific field group.
	 *     $ wp bp xprofile group delete 500 --yes
	 *     Success: Field group deleted 500.
	 *
	 *     $ wp bp xprofile group delete 55654 54564 --yes
	 *     Success: Field group deleted 55654.
	 *     Success: Field group deleted 54564.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$field_groups_ids = wp_parse_id_list( $args );

		if ( count( $field_groups_ids ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these field groups?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this field group?', $assoc_args );
		}

		parent::_delete(
			$field_groups_ids,
			$assoc_args,
			function ( $field_group_id ) {
				if ( xprofile_delete_field_group( $field_group_id ) ) {
					return [ 'success', sprintf( 'Field group deleted %d.', $field_group_id ) ];
				}

				return [ 'error', sprintf( 'Could not delete the field group %d.', $field_group_id ) ];
			}
		);
	}
}
