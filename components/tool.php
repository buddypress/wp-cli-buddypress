<?php
namespace BuddyPress\CLI\Command;

/**
 * Manage BuddyPress Tools.
 *
 * @since 1.5.0
 */
class Tool extends Component {

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
	 * ## EXAMPLES
	 *
	 *     $ wp bp tool repair friend-count
	 *     $ wp bp tool fix friend-count
	 *     Success: Counting the number of friends for each user. Complete!
	 *
	 * @alias fix
	 */
	public function repair( $args, $assoc_args ) {
		$repair = 'bp_admin_repair_' . $this->sanitize_string( $args[0] );

		if ( ! function_exists( $repair ) ) {
			WP_CLI::error( 'There is no repair tool with that name.' );
		}

		$result = $repair();

		if ( 0 === $result[0] ) {
			WP_CLI::success( $result[1] );
		} else {
			WP_CLI::error( $result[1] );
		}
	}

	/**
	 * Reinstall BuddyPress default emails.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp tool reinstall_emails --yes
	 *     Success: Emails have been successfully reinstalled.
	 */
	public function reinstall_emails( $args, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to reinstall BuddyPress emails?', $assoc_args );

		$result = bp_admin_reinstall_emails();

		if ( 0 === $result[0] ) {
			WP_CLI::success( $result[1] );
		} else {
			WP_CLI::error( $result[1] );
		}
	}
}
