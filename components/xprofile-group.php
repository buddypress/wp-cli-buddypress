<?php
/**
 * Manage XProfile groups.
 *
 * @since 1.2.0
 */
class BPCLI_XProfile_Group extends BPCLI_Component {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'name',
		'description',
		'group_order',
		'can_delete',
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
	 *     $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
	 */
	public function create( $args, $assoc_args ) {
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
	 *     $ wp bp xprofile group get 500
	 *     $ wp bp xprofile group get 56 --format=json
	 *
	 * @since 1.5.0
	 */
	public function get( $args, $assoc_args ) {
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
	 *     $ wp bp xprofile group delete 500
	 */
	public function delete( $args, $assoc_args ) {
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
}

WP_CLI::add_command( 'bp xprofile group', 'BPCLI_XProfile_Group', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );
