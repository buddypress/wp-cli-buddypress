<?php

/**
 * Base component class.
 *
 * @since 1.0
 */
class BPCLI_Component extends \WP_CLI\CommandWithDBObject {

	/**
	 * Get a random user id.
	 *
	 * @since 1.1
	 *
	 * @return int
	 */
	protected function get_random_user_id() {
		global $wpdb;
		return $wpdb->get_var( "SELECT ID FROM $wpdb->users ORDER BY RAND() LIMIT 1" );
	}

	/**
	 * Get a random group id.
	 *
	 * @since 1.1
	 *
	 * @return int
	 */
	protected function get_random_group_id() {
		global $wpdb, $bp;
		return $wpdb->get_var( "SELECT id FROM {$bp->groups->table_name} ORDER BY RAND() LIMIT 1" );
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
		$group_obj = groups_get_group( array(
			'group_id' => $group_id,
		) );

		if ( empty( $group_obj->id ) ) {
			return false;
		}

		return intval( $group_obj->id );
	}

	/**
	 * Generates a random user login
	 *
	 * @todo Improve for a more elegant solution.
	 *
	 * @since 1.3.0
	 *
	 * @param  int $length Length of the user login. Default: 6.
	 * @return string
	 */
	protected function get_random_login( $length = 6 ) {
		$char        = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$char_length = strlen( $char );
		$login       = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$login .= $char[ rand( 0, $char_length - 1 ) ];
		}

		return $login;
	}

	/**
	 * Verify a user ID by the passed identifier.
	 *
	 * @since 1.2.0
	 *
	 * @param mixed $i User ID, email or login.
	 * @return WP_User|false
	 */
	protected function get_user_id_from_identifier( $i ) {
		if ( is_numeric( $i ) ) {
			$user = get_user_by( 'id', $i );
		} elseif ( is_email( $i ) ) {
			$user = get_user_by( 'email', $i );
		} else {
			$user = get_user_by( 'login', $i );
		}

		if ( ! $user ) {
			WP_CLI::error( sprintf( 'No user found by that username or ID (%s).', $i ) );
		}

		return $user;
	}

	/**
	 * Generate random text
	 *
	 * @since 1.1
	 */
	protected function generate_random_text() {
		return 'Here is some random text';
	}

	/**
	 * Get field ID.
	 *
	 * @since 1.5.0
	 *
	 * @param  int $field_id Field ID.
	 * @return int
	 */
	protected function get_field_id( $field_id ) {
		if ( ! is_numeric( $field_id ) ) {
			return xprofile_get_field_id_from_name( $field_id );
		}

		return absint( $field_id );
	}

	/**
	 * String Sanitization.
	 *
	 * @since 1.5.0
	 *
	 * @param  string $type String to sanitize.
	 * @return string Sanitized string.
	 */
	protected function sanitize_string( $type ) {
		return strtolower( str_replace( '-', '_', $type ) );
	}
}
