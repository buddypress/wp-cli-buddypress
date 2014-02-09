<?php

if ( ! class_exists( 'User_Command' ) ) {
	require_once( WP_CLI_ROOT . "/php/commands/user.php" );
}

/**
 * Manage BuddyPress members.
 */
class BPCLI_Member extends BPCLI_Component {

	/**
	 * Generate members. See documentation for `wp_user_generate`.
	 *
	 * This is a kludge workaround for setting last activity. Should fix.
	 *
	 * @since 1.0
	 */
	public function generate( $args, $assoc_args ) {
		add_action( 'user_register', array( __CLASS__, 'update_user_last_activity_random' ) );
		User_Command::generate( $args, $assoc_args );
	}

	public static function update_user_last_activity_random( $user_id ) {
		$time = rand( 0, time() );
		$time = date( 'Y-m-d H:i:s', $time );
		bp_update_user_last_activity( $user_id, $time );
	}
}

WP_CLI::add_command( 'bp member', 'BPCLI_Member' );

