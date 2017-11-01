<?php
/**
 * Manage BuddyPress Tools.
 *
 * @since 1.5.0
 */
class BPCLI_Tools extends BPCLI_Component {

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
	 *     $ wp bp tools repair friend-count
	 *     $ wp bp tools fix group-count
	 *
	 * @alias fix
	 */
	public function repair( $args, $assoc_args ) {
		$repair = 'bp_admin_repair_' . $this->sanitize_string( $args[0] );

		if ( function_exists( $repair ) ) {
			$result = $repair();

			if ( 0 === $result[0] ) {
				WP_CLI::success( $result[1] );
			} else {
				WP_CLI::error( sprintf( 'Error: %s', $result[1] ) );
			}
		} else {
			WP_CLI::error( 'There is no repair tool with that name.' );
		}
	}

	/**
	 * Reinstall BuddyPress default emails.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp tools reinstall_emails
	 */
	public function reinstall_emails( $args, $assoc_args ) {
		$result = bp_admin_reinstall_emails();

		if ( 0 === $result[0] ) {
			WP_CLI::success( $result[1] );
		} else {
			WP_CLI::error( sprintf( 'Error: %s', $result[1] ) );
		}
	}
}

WP_CLI::add_command( 'bp tools', 'BPCLI_Tools' );
