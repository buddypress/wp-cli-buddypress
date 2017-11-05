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
