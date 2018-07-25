<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Notifications.
 *
 * @since 1.8.0
 */
class Notification extends BuddypressCommand {

	/**
	 * Create an notification item.
	 *
	 * ## OPTIONS
	 *
	 * [--component=<component>]
	 * : The component for the notification item (groups, activity, etc). If
	 * none is provided, a component will be randomly selected from the
	 * active components.
	 *
	 * [--action=<action>]
	 * : Action text (eg "Joe created a new group Foo"). If none is
	 * provided, one will be generated automatically based on other params.
	 *
	 * [--user-id=<user>]
	 * : ID of the user associated with the new notification. If none is provided,
	 * a user will be randomly selected.
	 *
	 * [--item-id=<item-id>]
	 * : ID of the associated notification. If none is provided, one will be
	 * generated automatically.
	 *
	 * [--secondary-item-id=<secondary-item-id>]
	 * : ID of the secondary associated notification. If none is provided, one will
	 * be generated automatically.
	 *
	 * [--date-notified=<date-notified>]
	 * : GMT timestamp, in Y-m-d h:i:s format.
	 * ---
	 * Default: Current time
	 * ---
	 *
	 * [--silent]
	 * : Whether to silent the notification creation.
	 *
	 * [--porcelain]
	 * : Output only the new notification id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp notification create
	 *     Success: Successfully created new notification. (ID #5464)
	 *
	 *     $ wp bp notification add --component=groups --user-id=10
	 *     Success: Successfully created new notification (ID #48949)
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'component'         => '',
			'action'            => '',
			'user-id'           => '',
			'item-id'           => '',
			'secondary-item-id' => '',
			'date-notified'     => bp_core_current_time(),
		) );

		// Fill in any missing information.
		if ( empty( $r['component'] ) ) {
			$r['component'] = $this->get_random_component();
		}

		$id = bp_notifications_add_notification( array(
			'component_name'    => $r['component'],
			'component_action'  => $r['action'],
			'user_id'           => $r['user-id'],
			'item_id'           => $r['item-id'],
			'secondary_item_id' => $r['secondary-item-id'],
			'date_notified'     => $r['date-notified'],
		) );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $id ) ) {
			WP_CLI::error( 'Could not create notification item.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new notification (ID #%d)', $id ) );
		}
	}

}
