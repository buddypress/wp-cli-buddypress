<?php
/**
 * Manage BuddyPress Tools.
 *
 * @since 1.5.0
 */
class BPCLI_Tool extends BPCLI_Component {

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
}

WP_CLI::add_command( 'bp tool', 'BPCLI_Tool', array(
	'before_invoke' => function() {
		require_once( buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php' );
	},
) );
