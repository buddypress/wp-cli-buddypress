<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Tools.
 *
 * ## EXAMPLES
 *
 *     $ wp bp tool repair friend-count
 *     Success: Counting the number of friends for each user. Complete!
 *
 *     $ wp bp tool version
 *     BuddyPress: 6.0.0
 *
 * @since 1.5.0
 */
class Tool extends BuddyPressCommand {

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php';
	}

	/**
	 * Repair.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : Name of the repair tool.
	 * ---
	 * options:
	 *   - friend-count
	 *   - group-count
	 *   - blog-records
	 *   - count-members
	 *   - last-activity
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp tool repair friend-count
	 *     Success: Counting the number of friends for each user. Complete!
	 *
	 * @alias fix
	 */
	public function repair( $args ) {
		$repair = 'bp_admin_repair_' . $this->sanitize_string( $args[0] );

		if ( ! function_exists( $repair ) ) {
			WP_CLI::error( 'There is no repair tool with that name.' );
		}

		// Run the callable repair function.
		$result = $repair();

		if ( empty( $repair ) ) {
			WP_CLI::error( 'The component of the tool is not active.' );
		}

		if ( 0 === $result[0] ) {
			WP_CLI::success( $result[1] );
		} else {
			WP_CLI::error( $result[1] );
		}
	}

	/**
	 * Display BuddyPress version currently installed.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp tool version
	 *     BuddyPress: 6.0.0
	 */
	public function version() {
		WP_CLI::log( 'BuddyPress: ' . bp_get_version() );
	}

	/**
	 * (De)Activate the Signup feature.
	 *
	 * <status>
	 * : Status of the feature.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp tool signup 1
	 *     Success: Signup tool updated.
	 *
	 *     $ wp bp tool signup 0
	 *     Success: Signup tool updated.
	 */
	public function signup( $args ) {
		bp_update_option( 'users_can_register', $args[0] );

		WP_CLI::success( 'Signup tool updated.' );
	}
}
