<?php
/**
 * Manage XProfile data.
 *
 * @since 1.2.0
 */
class BPCLI_XProfile_Data extends BPCLI_Component {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'field_id',
		'user_id',
		'value',
		'last_updated',
	);

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
	 *     $ wp bp xprofile data set --user-id=45 --field-id=120 --value=teste
	 *     $ wp bp xprofile data set --user-id=user_test --field-id=445 --value=another_test
	 *
	 * @since 1.2.0
	 */
	public function set( $args, $assoc_args ) {
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
	 *     $ wp bp xprofile data get --user-id=45 --field-id=120
	 *     $ wp bp xprofile data get --user-id=user_test --field-id=Hometown --multi-format=comma
	 *
	 * @since 1.5.0
	 */
	public function get( $args, $assoc_args ) {
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
	 *     $ wp bp xprofile data delete --user-id=45 --field-id=120
	 *     $ wp bp xprofile data delete --user-id=user_test --delete-all
	 *
	 * @since 1.5.0
	 */
	public function delete( $args, $assoc_args ) {
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

WP_CLI::add_command( 'bp xprofile data', 'BPCLI_XProfile_Data', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );
