<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage XProfile Data.
 *
 * @since 1.5.0
 */
class XProfile_Data extends BuddyPressCommand {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'field_id',
		'user_id',
		'value',
		'last_updated',
	];

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
	 * [--silent]
	 * : Whether to silent the success message.
	 *
	 * ## EXAMPLE
	 *
	 *     # Set profile data for a user.
	 *     $ wp bp xprofile data set --user-id=45 --field-id=120 --value=test
	 *     Success: Updated XProfile field "Field Name" (ID 120) with value  "test" for user user_login (ID 45).
	 *
	 * @alias set
	 * @alias add
	 * @alias update
	 */
	public function create( $args, $assoc_args ) {
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$field_id = $this->get_field_id( $assoc_args['field-id'] );
		$field    = new \BP_XProfile_Field( $field_id );

		if ( empty( $field->name ) ) {
			WP_CLI::error( 'XProfile field not found.' );
		}

		$value = $assoc_args['value'];

		if ( 'checkbox' === $field->type ) {
			$value = explode( ',', $assoc_args['value'] );
		}

		$updated = xprofile_set_field_data( $field->id, $user->ID, $value );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $updated ) {
			WP_CLI::error( 'Could not set profile data.' );
		}

		WP_CLI::success(
			sprintf(
				'Updated XProfile field "%s" (ID %d) with value "%s" for user %s (ID %d).',
				$field->name,
				$field->id,
				$assoc_args['value'],
				$user->user_nicename,
				$user->ID
			)
		);
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
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * [--multi-format=<value>]
	 * : The format for the array data.
	 * ---
	 * default: array
	 * options:
	 *   - array
	 *   - comma
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get profile data for a user.
	 *     $ wp bp xprofile data get --user-id=45 --field-id=120
	 *
	 *     # Get profile data for a user, formatting the data.
	 *     $ wp bp xprofile data see --user-id=user_test --field-id=Hometown --multi-format=comma
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( isset( $assoc_args['field-id'] ) ) {
			$data = xprofile_get_field_data( $assoc_args['field-id'], $user->ID, $assoc_args['multi-format'] );
			WP_CLI::print_value( $data, $assoc_args );
		} else {
			$data           = \BP_XProfile_ProfileData::get_all_for_user( $user->ID );
			$formatted_data = [];

			foreach ( $data as $field_name => $field_data ) {
				// Omit WP core fields.
				if ( ! is_array( $field_data ) ) {
					continue;
				}

				$formatted_data[] = [
					'field_id'   => $field_data['field_id'],
					'field_name' => $field_name,
					'value'      => wp_json_encode( maybe_unserialize( $field_data['field_data'] ) ),
				];
			}

			$format_args           = $assoc_args;
			$format_args['fields'] = [
				'field_id',
				'field_name',
				'value',
			];

			$this->get_formatter( $format_args )->display_items( $formatted_data );
		}
	}

	/**
	 * Delete XProfile data for a user.
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
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a specific XProfile field data.
	 *     $ wp bp xprofile data delete --user-id=45 --field-id=120 --yes
	 *     Success: XProfile data removed.
	 *
	 *     # Delete all XProfile data for a user.
	 *     $ wp bp xprofile data remove --user-id=user_test --delete-all --yes
	 *     Success: XProfile data removed.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! isset( $assoc_args['field-id'] ) && ! isset( $assoc_args['delete-all'] ) ) {
			WP_CLI::error( 'Either --field-id or --delete-all must be provided.' );
		}

		if ( isset( $assoc_args['delete-all'] ) ) {
			WP_CLI::confirm( sprintf( 'Are you sure you want to delete all XProfile data for the user %s (#%d)?', $user->user_login, $user->ID ), $assoc_args );

			xprofile_remove_data( $user->ID );
			WP_CLI::success( 'XProfile data removed.' );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete that?', $assoc_args );

			if ( xprofile_delete_field_data( $assoc_args['field-id'], $user->ID ) ) {
				WP_CLI::success( 'XProfile data removed.' );
			} else {
				WP_CLI::error( 'Could not delete XProfile data.' );
			}
		}
	}
}
