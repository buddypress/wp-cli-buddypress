<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Tools.
 *
 * ## EXAMPLES
 *
 *     # Repair the friend count.
 *     $ wp bp tool repair friend-count
 *     Success: Counting the number of friends for each user. Complete!
 *
 *     # Display BuddyPress version.
 *     $ wp bp tool version
 *     BuddyPress: 6.0.0
 *
 *     # Activate the signup tool.
 *     $ wp bp tool signup 1
 *     Success: Signup tool updated.
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
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Repair the friend count.
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
	 *     # Display BuddyPress version.
	 *     $ wp bp tool version
	 *     BuddyPress: 6.0.0
	 */
	public function version() {
		WP_CLI::log( 'BuddyPress: ' . bp_get_version() );
	}

	/**
	 * (De)Activate the signup feature.
	 *
	 * <status>
	 * : Status of the feature.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate the signup tool.
	 *     $ wp bp tool signup 1
	 *     Success: Signup tool updated.
	 *
	 *     # Deactivate the signup tool.
	 *     $ wp bp tool signup 0
	 *     Success: Signup tool updated.
	 */
	public function signup( $args ) {
		$status = wp_validate_boolean( $args[0] );

		if ( is_multisite() ) {
			$retval = get_site_option( 'registration' );

			if ( 'all' === $retval && $status ) {
				WP_CLI::error( 'Both sites and user accounts registration is already allowed.' );
			}

			$current = $status ? 'all' : 'none';
			update_site_option( 'registration', $current );
		} else {
			if ( bp_get_signup_allowed() && $status ) {
				WP_CLI::error( 'The BuddyPress signup feature is already allowed.' );
			}

			bp_update_option( 'users_can_register', $status );
		}

		WP_CLI::success( 'Signup tool updated.' );
	}
}
