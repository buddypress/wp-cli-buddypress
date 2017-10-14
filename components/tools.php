<?php
/**
 * Manage BuddyPress Tools.
 */
class BPCLI_Tools extends BPCLI_Component {

	/**
	 * Repair.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
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
	 *    wp bp tools repair --type=friend-count
	 *    wp bp tools repair --type=group-count
	 *
	 * @synopsis [--type=<type>]
	 *
	 * @since 1.5.0
	 */
	public function repair( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'type' => '',
		) );


		// If no type added, bail it.
		if ( empty( $r['type'] ) ) {
			WP_CLI::error( 'You need to add a name of the repair tool.' );
		}

		// Repair function.
		$repair = 'bp_admin_repair_' . sanitize_key( $r['type'] );

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
}

WP_CLI::add_command( 'bp tools', 'BPCLI_Tools' );
