<?php

namespace Buddypress\CLI\Command;

/**
 * Manage BuddyPress Members.
 *
 * ## EXAMPLES
 *
 *   # Generate 50 members.
 *   $ wp bp member generate --count=50
 *
 *   # Add meta to every generated users.
 *   $ wp user generate --format=ids --count=3 | xargs -d ' ' -I % wp user meta add % foo bar
 *   Success: Added custom field.
 *   Success: Added custom field.
 *   Success: Added custom field.
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
	 * [--role=<role>]
	 * : The role of the generated users. Defaults to role from WP.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *   - progress
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate 50 members.
	 *     $ wp bp member generate --count=50
	 *     Generating users  100% [======================] 0:00 / 0:00
	 *
	 *     # Add meta to every generated users.
	 *     $ wp user generate --format=ids --count=3 | xargs -d ' ' -I % wp user meta add % foo bar
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 */
	public function generate( $args, $assoc_args ) {
		add_action( 'user_register', [ __CLASS__, 'update_user_last_activity_random' ] );

		$command_class = new \User_Command();
		$command_class->generate( $args, $assoc_args );

		remove_action( 'user_register', [ __CLASS__, 'update_user_last_activity_random' ] );
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
