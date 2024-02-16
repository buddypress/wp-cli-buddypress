<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Notifications.
 *
 * ## EXAMPLES
 *
 *     # Create notification item.
 *     $ wp bp notification create
 *     Success: Successfully created new notification. (ID #5464)
 *
 *     # Delete a notification item.
 *     $ wp bp notification delete 520
 *     Success: Notification deleted.
 *
 * @since 1.8.0
 */
class Notification extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'user_id',
		'item_id',
		'secondary_item_id',
		'component_name',
		'component_action',
		'date_notified',
		'is_new',
	];

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'notifications' ) ) {
			WP_CLI::error( 'The Notification component is not active.' );
		}
	}

	/**
	 * Create a notification.
	 *
	 * ## OPTIONS
	 *
	 * [--component=<component>]
	 * : The component for the notification item (groups, activity, etc). If
	 * none is provided, a component will be randomly selected from the
	 * active components.
	 *
	 * [--action=<action>]
	 * : Name of the action to associate the notification. (comment_reply, update_reply, etc).
	 *
	 * [--user-id=<user>]
	 * : ID of the user associated with the new notification.
	 *
	 * [--item-id=<item>]
	 * : ID of the associated notification.
	 *
	 * [--secondary-item-id=<item>]
	 * : ID of the secondary associated notification.
	 *
	 * [--date=<date>]
	 * : GMT timestamp, in Y-m-d h:i:s format.
	 *
	 * [--silent]
	 * : Whether to silent the notification creation.
	 *
	 * [--porcelain]
	 * : Output only the new notification id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a `update_reply` notification.
	 *     $ wp bp notification create --component=messages --action=update_reply --user-id=523
	 *     Success: Successfully created new notification. (ID #5464)
	 *
	 *     # Create a `comment_reply` notification.
	 *     $ wp bp notification add --component=groups --action=comment_reply --user-id=10
	 *     Success: Successfully created new notification (ID #48949)
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args(
			$assoc_args,
			[
				'component'         => '',
				'action'            => '',
				'user-id'           => 0,
				'item-id'           => 0,
				'secondary-item-id' => 0,
				'date'              => bp_core_current_time(),
			]
		);

		$notification_id = bp_notifications_add_notification(
			[
				'user_id'           => $r['user-id'],
				'item_id'           => $r['item-id'],
				'secondary_item_id' => $r['secondary-item-id'],
				'component_name'    => $r['component'],
				'component_action'  => $r['action'],
				'date_notified'     => $r['date'],
			]
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $notification_id ) ) {
			WP_CLI::error( 'Could not create notification.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $notification_id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new notification (ID #%d)', $notification_id ) );
		}
	}

	/**
	 * Get specific notification.
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
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get a notification by ID.
	 *     $ wp bp notification get 10071
	 *     +-------------------+---------------------+
	 *     | Field             | Value               |
	 *     +-------------------+---------------------+
	 *     | id                | 10071               |
	 *     | item_id           | 0                   |
	 *     | secondary_item_id | 0                   |
	 *     | user_id           | 7                   |
	 *     | component_name    | activity            |
	 *     | component_action  | comment_reply       |
	 *     | date_notified     | 2024-02-06 00:28:45 |
	 *     | is_new            | 1                   |
	 *     +-------------------+---------------------+
	 *
	 *     # Get a notification in JSON format.
	 *     $ wp bp notification get 10071 --format=json
	 *     {"id":10071,"item_id":0,"secondary_item_id":0,"user_id":7,"component_name":"activity","component_action":"comment_reply","date_notified":"2024-02-06 00:28:45","is_new":1}
	 *
	 *     # Get a notification using a invalid ID.
	 *     $ wp bp notification see buddypress
	 *     Error: Please provide a numeric notification ID.
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$notification_id = $args[0];

		if ( ! is_numeric( $notification_id ) ) {
			WP_CLI::error( 'Please provide a numeric notification ID.' );
		}

		$notification = bp_notifications_get_notification( $notification_id );

		if ( empty( $notification->id ) || ! is_object( $notification ) ) {
			WP_CLI::error( 'No notification found.' );
		}

		$notification_arr = get_object_vars( $notification );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $notification_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $notification_arr );
	}

	/**
	 * Delete a notification.
	 *
	 * ## OPTIONS
	 *
	 * <notification-id>...
	 * : ID or IDs of notification to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a notification.
	 *     $ wp bp notification delete 520 --yes
	 *     Success: Deleted notification 520.
	 *
	 *     # Delete multiple notifications.
	 *     $ wp bp notification delete 55654 54564 --yes
	 *     Success: Deleted notification 55654.
	 *     Success: Deleted notification 54564.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$notifications = wp_parse_id_list( $args );

		if ( count( $notifications ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these notifications?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this notification?', $assoc_args );
		}

		parent::_delete(
			$notifications,
			$assoc_args,
			function ( $notification_id ) {
				if ( \BP_Notifications_Notification::delete( [ 'id' => $notification_id ] ) ) {
					return [ 'success', sprintf( 'Deleted notification %d.', $notification_id ) ];
				}

				return [ 'error', sprintf( 'Could not delete notification %d.', $notification_id ) ];
			}
		);
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
	 * [--user-id=<user>]
	 * : ID of the user. Accepts either a user_login or a numeric ID.
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
	 *     # Generate 5 random notifications.
	 *     $ wp bp notification generate --count=5
	 *     Generating notifications  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 random notifications and output only the IDs.
	 *     $ wp bp notification generate --count=5 --format=ids
	 *     70 71 72 73 74
	 */
	public function generate( $args, $assoc_args ) {
		$user_id = null;

		if ( isset( $assoc_args['user-id'] ) ) {
			$user    = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
			$user_id = $user->ID;
		}

		$this->generate_callback(
			'Generating notifications',
			$assoc_args,
			function ( $assoc_args, $format ) use ( $user_id ) {
				$component = $this->get_random_component();

				if ( ! $user_id ) {
					$user_id = $this->get_random_user_id();
				}

				$params = [
					'user-id'   => $user_id,
					'component' => $component,
					'action'    => $this->get_random_action( $component ),
				];

				if ( 'ids' === $format ) {
					$params['porcelain'] = true;
				} else {
					$params['silent'] = true;
				}

				return $this->create( [], $params );
			}
		);
	}

	/**
	 * Get a list of notifications.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass.
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--user-id=<user>]
	 * : Limit results to a specific member. Accepts either a user_login or a numeric ID.
	 *
	 * [--component=<component>]
	 * : The component to fetch notifications (groups, activity, etc).
	 *
	 * [--action=<action>]
	 * : Name of the action to fetch notifications. (comment_reply, update_reply, etc).
	 *
	 * [--count=<number>]
	 * : How many notification items to list.
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
	 *     # List all notifications and output only the IDs.
	 *     $ wp bp notification list --format=ids
	 *     15 25 34 37 198
	 *
	 *     # List all notifications and output the count.
	 *     $ wp bp notification list --format=count
	 *     10
	 *
	 *     # List all notifications and output the IDs and user_id.
	 *     $ wp bp notification list --fields=id,user_id
	 *     | id     | user_id  |
	 *     | 66546  | 656      |
	 *     | 54554  | 646546   |
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$query_args = [
			'update_meta_cache' => false,
		];

		if ( isset( $assoc_args['user-id'] ) ) {
			$user                  = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
			$query_args['user_id'] = $user->ID;
		}

		if ( isset( $assoc_args['action'] ) ) {
			$query_args['component_action'] = $assoc_args['action'];
		}

		if ( isset( $assoc_args['component'] ) ) {
			$query_args['component_name'] = $assoc_args['component'];
		}

		$query_args['page']     = 1;
		$query_args['per_page'] = (int) $assoc_args['count'];

		unset( $query_args['count'] );

		$query_args    = self::process_csv_arguments_to_arrays( $query_args );
		$notifications = \BP_Notifications_Notification::get( $query_args );

		if ( empty( $notifications ) ) {
			WP_CLI::error( 'No notification items found.' );
		}

		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $notifications, 'id' ) : $notifications );
	}

	/**
	 * Get random notification actions based on component.
	 *
	 * @since 1.8.0
	 *
	 * @param string $component BuddyPress Component.
	 * @return string
	 */
	protected function get_random_action( $component ) {
		$bp      = buddypress();
		$actions = '';

		// Activity.
		if ( $bp->activity->id === $component ) {
			$actions = [ 'comment_reply', 'update_reply', 'new_at_mention' ];
		}

		// Friendship.
		if ( $bp->friends->id === $component ) {
			$actions = [
				'friendship_request',
				'friendship_accepted',
			];
		}

		// Groups.
		if ( $bp->groups->id === $component ) {
			$actions = [
				'new_membership_request',
				'membership_request_accepted',
				'membership_request_rejected',
				'member_promoted_to_admin',
				'member_promoted_to_mod',
				'group_invite',
			];
		}

		// Messages.
		if ( $bp->messages->id === $component ) {
			$actions = [ 'new_message' ];
		}

		return array_rand( $actions );
	}
}
