<?php

namespace Buddypress\CLI\Command;

use WP_CLI;
use WP_CLI\CommandWithDBObject;

/**
 * Base component class.
 *
 * @since 1.0
 */
abstract class BuddyPressCommand extends CommandWithDBObject {

	/**
	 * Default dependency check for a BuddyPress CLI command.
	 *
	 * @since 2.0
	 */
	public static function check_dependencies() {
		if ( ! class_exists( 'Buddypress' ) ) {
			WP_CLI::error( 'The BuddyPress plugin is not active.' );
		}
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @since 2.0
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 * @return \WP_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new WP_CLI\Formatter( $assoc_args, $this->obj_fields );
	}

	/**
	 * Get a random user id.
	 *
	 * @since 1.1
	 *
	 * @return int
	 */
	protected function get_random_user_id() {
		global $wpdb;
		return $wpdb->get_var( "SELECT ID FROM $wpdb->users ORDER BY RAND() LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get an activity ID.
	 *
	 * @since 2.0
	 *
	 * @param int  $activity_id Activity ID.
	 * @param bool $object      Return activity object.
	 * @return int|BP_Activity_Activity
	 */
	protected function get_activity_id_from_identifier( $activity_id, $object = false ) {
		$fetcher  = new Activity_Fetcher();
		$activity = $fetcher->get_check( $activity_id );

		if ( true === $object ) {
			return $activity;
		}

		return $activity->id;
	}

	/**
	 * Get a group ID from its identifier (ID or slug).
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $group_id Group ID or slug.
	 * @return int|bool
	 */
	protected function get_group_id_from_identifier( $group_id ) {
		// Group ID or slug.
		if ( ! is_numeric( $group_id ) ) {
			$group_id = groups_get_id( $group_id );
		}

		// Get group object.
		$group_obj = groups_get_group(
			array(
				'group_id' => $group_id,
			)
		);

		if ( empty( $group_obj->id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		return intval( $group_obj->id );
	}

	/**
	 * Verify a user ID by the passed identifier.
	 *
	 * @since 1.2.0
	 *
	 * @param mixed $identifier User ID, email or login.
	 * @return WP_User
	 */
	protected function get_user_id_from_identifier( $identifier ) {
		if ( is_numeric( $identifier ) ) {
			$user = get_user_by( 'id', $identifier );
		} elseif ( is_email( $identifier ) ) {
			$user = get_user_by( 'email', $identifier );
		} else {
			$user = get_user_by( 'login', $identifier );
		}

		if ( ! $user ) {
			WP_CLI::error( sprintf( 'No user found by that username or ID (%s).', $identifier ) );
		}

		return $user;
	}

	/**
	 * Generate random text
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	protected function generate_random_text() {
		return 'Here is some random text';
	}

	/**
	 * Get field from an ID.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $field_id Field ID or Field name.
	 * @return int Field ID.
	 */
	protected function get_field_id( $field_id ) {
		if ( ! is_numeric( $field_id ) ) {
			return xprofile_get_field_id_from_name( $field_id );
		}

		return absint( $field_id );
	}

	/**
	 * String sanitization.
	 *
	 * @since 1.5.0
	 *
	 * @param  string $type String to sanitize.
	 * @return string Sanitized string.
	 */
	protected function sanitize_string( $type ) {
		return strtolower( str_replace( '-', '_', $type ) );
	}

	/**
	 * Pull up a random active component.
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	protected function get_random_component() {
		$c  = buddypress()->active_components;
		$ca = $this->get_components_and_actions();

		return array_rand( array_flip( array_intersect( array_keys( $c ), array_keys( $ca ) ) ) );
	}

	/**
	 * Get a list of activity components and actions.
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	protected function get_components_and_actions() {
		return array_map(
			function( $component ) {
				return array_keys( (array) $component );
			},
			(array) bp_activity_get_actions()
		);
	}
}
