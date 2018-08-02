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
	 * [--content=<content>]
	 * : Content (eg "Joe created a new group Foo"). If none is provided, random content
	 * will be added.
	 *
	 * [--user-id=<user>]
	 * : ID of the user associated with the new notification.
	 *
	 * [--item-id=<item-id>]
	 * : ID of the associated notification.
	 *
	 * [--secondary-item-id=<secondary-item-id>]
	 * : ID of the secondary associated notification.
	 *
	 * [--date-notified=<date-notified>]
	 * : GMT timestamp, in Y-m-d h:i:s format.
	 * ---
	 * default: Current time
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
			'content'           => '',
			'user-id'           => '',
			'item-id'           => '',
			'secondary-item-id' => '',
			'date-notified'     => bp_core_current_time(),
		) );

		// Fill in the component.
		if ( empty( $r['component'] ) ) {
			$r['component'] = $this->get_random_component();
		}

		// Fill in the content.
		if ( empty( $r['content'] ) ) {
			$r['content'] = $this->generate_random_text();
		}

		$id = bp_notifications_add_notification( array(
			'component_name'    => $r['component'],
			'component_action'  => $r['content'],
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
			WP_CLI::error( 'Could not create notification.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new notification (ID #%d)', $id ) );
		}
	}

	/**
	 * Fetch specific notification.
	 *
	 * ## OPTIONS
	 *
	 * <notification-id>
	 * : Identifier for the notification.
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
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp notification get 500
	 *     $ wp bp notification get 56 --format=json
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$notification = bp_notifications_get_notification( $args[0] );

		if ( empty( $notification->id ) ) {
			WP_CLI::error( 'No notification found by that ID.' );
		}

		if ( ! is_object( $notification ) ) {
			WP_CLI::error( 'Could not find the notification.' );
		}

		$notification_arr = get_object_vars( $notification );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $notification_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $notification_arr );
	}

	/**
	 * Delete a notification.
	 *
	 * ## OPTIONS
	 *
	 * <notification-id>...
	 * : ID or IDs of notification.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp notification delete 520
	 *     Success: Notification deleted.
	 *
	 *     $ wp bp notification delete 55654 54564 --yes
	 *     Success: Notification deleted.
	 */
	public function delete( $args, $assoc_args ) {
		$notification_id = $args[0];

		WP_CLI::confirm( 'Are you sure you want to delete this notification?', $assoc_args );

		parent::_delete( array( $notification_id ), $assoc_args, function( $notification_id ) {

			$notification = bp_notifications_get_notification( $notification_id );

			if ( empty( $notification->id ) ) {
				WP_CLI::error( 'No notification found by that ID.' );
			}

			if ( BP_Notifications_Notification::delete( array( 'id' => $notification_id ) ) ) {
				return array( 'success', 'Notification deleted.' );
			} else {
				return array( 'error', 'Could not delete notification.' );
			}
		} );
	}

	/**
	 * Generate random notifications.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many notifications to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp notification generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating notifications', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->create( array(), array(
				'user-id' => $this->get_random_user_id(),
				'silent',
			) );

			$notify->tick();
		}

		$notify->finish();
	}
}
