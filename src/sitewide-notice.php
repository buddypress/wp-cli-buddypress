<?php

namespace Buddypress\CLI\Command;

use BP_Messages_Notice;
use WP_CLI;

/**
 * Manage BuddyPress Sitewide Notices.
 *
 * ## EXAMPLES
 *
 *   # Get a sitewide notice.
 *   $ wp bp notice get 500
 *   +-----------+---------------------+
 *   | Field     | Value               |
 *   +-----------+---------------------+
 *   | id        | 4                   |
 *   | subject   | Important message   |
 *   | message   | Let's talk!         |
 *   | date_sent | 2023-01-11 12:47:00 |
 *   | is_active | 1                   |
 *   +-----------+---------------------+
 *
 *   # Get a sitewide notice in JSON format.
 *   $ wp bp notice get 56 --format=json
 *   {"id":4,"subject":"Important message","message":"Let's talk!","date_sent":"2023-01-11 12:47:00","is_active":1}
 *
 *   $ wp bp notice delete 55654 54564 --yes
 *   Success: Deleted notice 55654.
 *   Success: Deleted notice 54564.
 */
class Sitewide_Notice extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'subject',
		'message',
		'is_active',
		'date_sent',
	];

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'messages' ) ) {
			WP_CLI::error( 'The Messages component is not active.' );
		}
	}

	/**
	 * Create a sitewide notice.
	 *
	 * ## OPTIONS
	 *
	 * --subject=<subject>
	 * : Notice subject text.
	 *
	 * --message=<message>
	 * : Notice message text.
	 *
	 * [--silent]
	 * : Whether to silent the notice creation.
	 *
	 * [--porcelain]
	 * : Output the new notice id only.
	 *
	 * ## EXAMPLES
	 *
	 *    # Create a sitewide notice.
	 *    $ wp bp notice create --subject=Hello --message=Folks!
	 *    Success: Successfully created new sitewide notice. (ID #5464)
	 *
	 *    # Create a sitewide notice and return its ID.
	 *    $ wp bp notice create --subject=Hello --message=Folks! --porcelain
	 *    36565
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$notice            = new BP_Messages_Notice();
		$notice->subject   = $assoc_args['subject'];
		$notice->message   = $assoc_args['message'];
		$notice->date_sent = bp_core_current_time();
		$notice->is_active = 1;
		$retval            = $notice->save(); // Create it.

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $retval ) {
			WP_CLI::error( 'Could not create sitewide notice.' );
		}

		// The notice we just created is the active one.
		$active_notice = BP_Messages_Notice::get_active();

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $active_notice->id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new sitewide notice (ID #%d)', $active_notice->id ) );
		}
	}

	/**
	 * Get specific sitewide notice.
	 *
	 * ## OPTIONS
	 *
	 * <notice-id>
	 * : Identifier for the notice.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get a sitewide notice.
	 *     $ wp bp notice get 500
	 *     +-----------+---------------------+
	 *     | Field     | Value               |
	 *     +-----------+---------------------+
	 *     | id        | 4                   |
	 *     | subject   | Important message   |
	 *     | message   | Let's talk!         |
	 *     | date_sent | 2023-01-11 12:47:00 |
	 *     | is_active | 1                   |
	 *     +-----------+---------------------+
	 *
	 *     # Get a sitewide notice in JSON format.
	 *     $ wp bp notice get 56 --format=json
	 *     {"id":4,"subject":"Important message","message":"Let's talk!","date_sent":"2023-01-11 12:47:00","is_active":1}
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$notice_id = $args[0];

		if ( ! is_numeric( $notice_id ) ) {
			WP_CLI::error( 'Please provide a numeric notice ID.' );
		}

		$notice = new BP_Messages_Notice( $notice_id );

		if ( ! $notice->date_sent ) {
			WP_CLI::error( 'No sitewide notice found.' );
		}

		$notice_arr = get_object_vars( $notice );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $notice_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $notice_arr );
	}

	/**
	 * Delete sitewide notice(s).
	 *
	 * ## OPTIONS
	 *
	 * <notice-id>...
	 * : ID or IDs of sitewide notices to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a sitewide notice.
	 *     $ wp bp notice delete 520 --yes
	 *     Success: Sitewide notice deleted 520.
	 *
	 *     # Delete multiple sitewide notices.
	 *     $ wp bp notice delete 55654 54564 --yes
	 *     Success: Sitewide notice deleted 55654.
	 *     Success: Sitewide notice deleted 54564.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$notice_ids = wp_parse_id_list( $args );

		if ( count( $notice_ids ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these notices?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this notice?', $assoc_args );
		}

		parent::_delete(
			$notice_ids,
			$assoc_args,
			function ( $notice_id ) {
				$notice = new BP_Messages_Notice( $notice_id );

				if ( ! empty( $notice->date_sent ) && $notice->delete() ) {
					return [ 'success', sprintf( 'Sitewide notice deleted %d.', $notice_id ) ];
				}

				return [ 'error', sprintf( 'Could not delete sitewide notice %d.', $notice_id ) ];
			}
		);
	}

	/**
	 * Activate a sitewide notice.
	 *
	 * ## OPTIONS
	 *
	 * <notice-id>
	 * : Identifier for the notice.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp notice activate 123
	 *     Success: Sitewide notice activated.
	 */
	public function activate( $args ) {
		$notice = new BP_Messages_Notice( $args[0] );

		if ( ! $notice->date_sent ) {
			WP_CLI::error( 'No sitewide notice found by that ID.' );
		}

		$notice->is_active = 1;

		if ( ! $notice->save() ) {
			WP_CLI::error( 'Could not activate sitewide notice.' );
		}

		WP_CLI::success( 'Sitewide notice activated.' );
	}

	/**
	 * Deactivate a sitewide notice.
	 *
	 * ## OPTIONS
	 *
	 * <notice-id>
	 * : Identifier for the notice.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp notice deactivate 123
	 *     Success: Sitewide notice has been deactivated.
	 */
	public function deactivate( $args ) {
		$notice = new BP_Messages_Notice( $args[0] );

		if ( ! $notice->date_sent ) {
			WP_CLI::error( 'No sitewide notice found by that ID.' );
		}

		$notice->is_active = 0;

		if ( ! $notice->save() ) {
			WP_CLI::error( 'Could not deactivate sitewide notice.' );
		}

		WP_CLI::success( 'Sitewide notice has been deactivated.' );
	}

	/**
	 * Get a list of sitewide notices.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--count=<number>]
	 * : How many notices to list.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - count
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---

	 * ## EXAMPLES
	 *
	 *     # List all sitewide notices, and output only the IDs.
	 *     $ wp bp notice list --format=ids
	 *     15 25 34 37 198
	 *
	 *     # List all sitewide notices, and output the count.
	 *     $ wp bp notice list --format=count
	 *     10
	 *
	 *     # List all sitewide notices, and output the IDs.
	 *     $ wp bp notice list --fields=id
	 *     | id     |
	 *     | 66546  |
	 *     | 54554  |
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$query_args = [ 'pag_num' => (int) $assoc_args['count'] ];

		$query_args = self::process_csv_arguments_to_arrays( $query_args );
		$notices    = BP_Messages_Notice::get_notices( $query_args );

		if ( empty( $notices ) ) {
			WP_CLI::error( 'No sitewide notices found.' );
		}

		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $notices, 'id' ) : $notices );
	}
}
