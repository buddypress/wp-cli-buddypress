<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress activity favorites.
 *
 * ## EXAMPLES
 *
 *     # Add an activity item as a favorite for a user.
 *     $ wp bp activity favorite add 100 500
 *     Success: Activity item added as a favorite for the user.
 *
 *     # Add an activity item as a favorite for a user using user_login.
 *     $ wp bp activity favorite create 100 user_test
 *     Success: Activity item added as a favorite for the user.
 *
 * @since 1.5.0
 */
class Activity_Favorite extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'user_id',
		'component',
		'type',
		'action',
		'item_id',
		'primary_link',
		'secondary_item_id',
		'date_recorded',
		'hide_sitewide',
		'is_spam',
	];

	/**
	 * Add an activity item as a favorite for a user.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : ID of the activity.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Add an activity item as a favorite.
	 *     $ wp bp activity favorite add 100 500
	 *     Success: Activity item added as a favorite for the user.
	 *
	 *     # Add an activity item as a favorite using a user_login identifier.
	 *     $ wp bp activity favorite create 100 user_test
	 *     Success: Activity item added as a favorite for the user.
	 *
	 * @alias add
	 */
	public function create( $args ) {
		$activity_id = $args[0];

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Please provide a numeric activity ID.' );
		}

		$activity = bp_activity_get_specific(
			[
				'activity_ids'     => $activity_id,
				'spam'             => null,
				'display_comments' => true,
			]
		);

		if ( ! isset( $activity['activities'][0] ) || ! is_object( $activity['activities'][0] ) ) {
			WP_CLI::error( 'No activity found.' );
		}

		$activity = $activity['activities'][0];
		$user     = $this->get_user_id_from_identifier( $args[1] );

		if ( bp_activity_add_user_favorite( $activity->id, $user->ID ) ) {
			WP_CLI::success( 'Activity item added as a favorite for the user.' );
		} else {
			WP_CLI::error( 'Could not add the activity item.' );
		}
	}

	/**
	 * Remove an activity item as a favorite for a user.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : ID of the activity.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove an activity item as a favorite for a user.
	 *     $ wp bp activity favorite remove 100 500
	 *     Success: Activity item removed as a favorite for the user.
	 *
	 *     # Remove an activity item as a favorite for a user.
	 *     $ wp bp activity favorite delete 100 user_test --yes
	 *     Success: Activity item removed as a favorite for the user.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$activity_id = $args[0];

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Please provide a numeric activity ID.' );
		}

		$activity = bp_activity_get_specific(
			[
				'activity_ids'     => $activity_id,
				'spam'             => null,
				'display_comments' => true,
			]
		);

		if ( ! isset( $activity['activities'][0] ) || ! is_object( $activity['activities'][0] ) ) {
			WP_CLI::error( 'No activity found.' );
		}

		$activity = $activity['activities'][0];
		$user     = $this->get_user_id_from_identifier( $args[1] );

		WP_CLI::confirm( 'Are you sure you want to remove this activity item?', $assoc_args );

		if ( bp_activity_remove_user_favorite( $activity->id, $user->ID ) ) {
			WP_CLI::success( 'Activity item removed as a favorite for the user.' );
		} else {
			WP_CLI::error( 'Could not remove the activity item.' );
		}
	}

	/**
	 * Get a user's favorite activity items.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass to \BP_Activity_Activity::get()
	 *
	 * [--count=<number>]
	 * : How many activity favorites to list.
	 * ---
	 * default: 50
	 * ---
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
	 * ## EXAMPLE
	 *
	 *     # Get a user's favorite activity items.
	 *     $ wp bp activity favorite list 315
	 *
	 * @subcommand list
	 * @alias user-items
	 */
	public function list_( $args, $assoc_args ) {
		$user      = $this->get_user_id_from_identifier( $args[0] );
		$favorites = bp_activity_get_user_favorites( $user->ID );

		if ( empty( $favorites ) ) {
			WP_CLI::error( 'No favorite found for this user.' );
		}

		$activities = bp_activity_get_specific(
			[
				'activity_ids' => (array) $favorites,
				'per_page'     => $assoc_args['count'],
			]
		);

		// Sanity check.
		if ( empty( $activities['activities'] ) ) {
			WP_CLI::error( 'No favorite found for this user.' );
		}

		$activities = $activities['activities'];
		$formatter  = $this->get_formatter( $assoc_args );
		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $activities, 'id' ) : $activities );
	}
}
