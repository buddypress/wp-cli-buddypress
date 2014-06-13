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
	 * Verify a user ID by the passed identifier.
	 *
	 * Accepts a user_login or an ID.
	 *
	 * @since 1.2.0
	 *
	 * @return int
	 */
	protected function get_user_id_from_identifier( $i ) {
		// @todo this'll be screwed up if user has a numeric user_login
		if ( ! is_numeric( $i ) ) {
			$user_id = (int) username_exists( $i );
		} else {
			$user_id = $i;
			$user_obj = new WP_User( $user_id );
			$user_id = $user_obj->ID;
		}

		return intval( $i );
	}

}
