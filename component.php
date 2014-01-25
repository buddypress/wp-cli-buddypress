<?php

/**
 * Base component class.
 *
 * @since 1.0
 */
class BPCLI_Component {
	final public function run( $method, $args, $assoc_args ) {
		call_user_func_array( array( $this, $method ), array( $args, $assoc_args ) );
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
		return $wpdb->get_var( "SELECT ID FROM $wpdb->users ORDER BY RAND() LIMIT 1" );
	}
}
