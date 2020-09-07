<?php

namespace Buddypress\CLI\Command;

/**
 * Manage BuddyPress Members
 *
 * ## EXAMPLES
 *
 *   $ wp bp member generate
 *   $ wp bp member generate --count=50
 *
 * @since 1.0.0
 */
class Member extends BuddyPressCommand {

	/**
	 * Generate BuddyPress members. See documentation for `wp_user_generate`.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many members to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   $ wp bp member generate
	 *   $ wp bp member generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		add_action( 'user_register', array( __CLASS__, 'update_user_last_activity_random' ) );

		$command_class = new \User_Command();
		$command_class->generate( $args, $assoc_args );
	}

	/**
	 * Update the last user activity with a random date.
	 *
	 * @since 1.0
	 *
	 * @param int $user_id User ID.
	 */
	public static function update_user_last_activity_random( $user_id ) {
		bp_update_user_last_activity(
			$user_id,
			gmdate( 'Y-m-d H:i:s', wp_rand( 0, time() ) )
		);
	}
}
