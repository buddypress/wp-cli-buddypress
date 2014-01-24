<?php

if ( ! class_exists( 'User_Command' ) ) {
	require_once( WP_CLI_ROOT . "/php/commands/user.php" );
}

class BPCLI_Member extends BPCLI_Component {
	/**
	 * Generate members. See documentation for `wp user generate`.
	 */
	public function member_generate( $args, $assoc_args ) {
		add_action( 'user_register', array( __CLASS__, 'update_user_last_activity_random' ) );
		User_Command::generate( $args, $assoc_args );
	}

	public static function update_user_last_activity_random( $user_id ) {
		$time = rand( 0, time() );
		$time = date( 'Y-m-d H:i:s', $time );
		bp_update_user_last_activity( $user_id, $time );
	}
}
