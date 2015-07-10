<?php

/**
 * Manage xprofile data.
 *
 * @since 1.2.0
 */
class BPCLI_XProfile extends BPCLI_Component {
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
	 * --name=<name>
	 * : The name for this field group.
	 *
	 * [--description=<description>]
	 * : The description for this field group.
	 *
	 * [--can-delete=<can-delete>]
	 * : Whether the group can be deleted. Default: true.
	 *
	 * @since 1.2.0
	 */
	public function create_group( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'name'        => '',
			'description' => '',
			'can_delete'  => true,
		) );

		$group = xprofile_insert_field_group( $r );

		if ( ! $group ) {
			WP_CLI::error( 'Could not create field group.' );
		} else {
			$group = new BP_XProfile_Group( $group );
			$success = sprintf(
				'Created XProfile field group "%s" (id %d)',
				$group->name,
				$group->id
			);
			WP_CLI::success( $success );
		}
	}

	/**
	 * Get a list of xprofile fields.
	 *
	 * ## OPTIONS
	 *
	 * ## EXAMPLES
	 *
	 *        wp bp group get_members 3
	 *
	 * @since 1.4.0
	 *
	 * @todo Should probably be broken into separate methods for groups etc.
	 *
	 * @subcommand list_fields
	 */
	public function list_fields_( $_, $assoc_args ) {
		$defaults = array(
			'fields' => 'id,name',
		);
		$args = array_merge( $defaults, $assoc_args );

		$formatter = $this->get_formatter( $args );

		$args['fetch_fields'] = true;
		$groups = bp_xprofile_get_groups( $args );

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
	 * --field_group_id=<field-group-id>
	 * : ID of the associated field group.
	 *
	 * --name=<name>
	 * : Name of the new field.
	 *
	 * [--description=<description>]
	 * : Description of the new field.
	 *
	 * [--parent_id=<parent-id>]
	 * : ID of the parent field. For use when defining options for radio
	 * buttons, etc.
	 *
	 * [--type=<type>]
	 * : Field type. 'textbox', 'textarea', 'radio', 'checkbox',
	 * 'selectbox', 'multiselectbox', 'datebox'. Default: 'textbox'.
	 *
	 * [--can_delete=<can-delete>]
	 * : Whether the field can be deleted. Default: true.
	 *
	 * [--field_order=<field-order>]
	 * : The position of the field in the field order.
	 *
	 * [--order_by=<order-by>]
	 * : Order for the field.
	 *
	 * [--is_default_option=<is-default-option>]
	 * : For suboptions of radio buttons, etc. Whether the field is the
	 * default option. Default: false.
	 *
	 * [--option_order=<option-order>]
	 * : Order for the options.
	 *
	 * @since 1.2.0
	 */
	public function create_field( $args, $assoc_args ) {
		// Rest of arguments are passed through
		$r = wp_parse_args( $assoc_args, array(
			'type'  => 'textbox',
		) );

		$field_id = xprofile_insert_field( $r );

		if ( ! $field_id ) {
			WP_CLI::error( 'Could not create field.' );
		} else {
			$field = new BP_XProfile_Field( $field_id );
			$success = sprintf(
				'Created XProfile field "%s" (id %d)',
				$field->name,
				$field->id
			);
			WP_CLI::success( $success );
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
	 * [--delete_data=<delete-data>]
	 * : Whether to delete user data for the field as well.
	 *
	 * @since 1.4.0
	 */
	public function delete_field( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'delete_data' => false,
		) );

		// Validate field
		// We need this info anyway for the success message
		$field_id = $args[0];
		if ( ! is_numeric( $field_id ) ) {
			$field_id = xprofile_get_field_id_from_name( $field_id );
		} else {
			$field_id = intval( $field_id );
		}

		parent::_delete( array( $field_id ), $assoc_args, function ( $field_id ) use ( $r ) {
			$field = new BP_XProfile_Field( $field_id );
			$name = $field->name;
			$id = $field->id;
			$deleted = $field->delete( $r['delete_data'] );

			if ( $deleted ) {
				$success = sprintf(
					'Deleted XProfile field "%s" (id %d)',
					$name,
					$id
				);
				return array( 'success', $success );
			} else {
				return array( 'error', "Failed deleting field $field_id." );
			}
		} );
	}

	/**
	 * Set profile data for a user.
	 *
	 * ## OPTIONS
	 *
	 * --user_id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * --field_id=<field-id>
	 * : Field ID. Accepts either the name of the field or a numeric ID.
	 *
	 * --value=<value>
	 * : Value to set.
	 *
	 * [--is_required=<is-required>]
	 * : Whether a non-empty value is required. Default: false
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
			WP_CLI::error( 'No user found by that username or id' );
			return;
		}

		// Validate field
		// We need this info anyway for the success message
		if ( ! is_numeric( $r['field_id'] ) ) {
			$field_id = xprofile_get_field_id_from_name( $r['field_id'] );
		} else {
			$field_id = intval( $r['field_id'] );
		}

		$field = new BP_XProfile_Field( $field_id );

		if ( empty( $field->name ) ) {
			WP_CLI::error( 'No field found by that name' );
			return;
		}

		$updated = xprofile_set_field_data( $field->id, $user->ID, $r['value'], $r['is_required'] );

		if ( ! $updated ) {
			WP_CLI::error( 'Could not set profile data.' );
		} else {
			$success = sprintf(
				'Updated field "%s" (id %d) with value "%s" for user %s (id %d)',
				$field->name,
				$field->id,
				$r['value'],
				$user->user_nicename,
				$user->ID
			);

			WP_CLI::success( $success );
		}
	}
}

WP_CLI::add_command( 'bp xprofile', 'BPCLI_XProfile', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
} ) );

