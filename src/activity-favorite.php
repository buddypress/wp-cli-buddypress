<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress activity favorites.
 *
 * ## EXAMPLES
 *
 *     $ wp bp activity favorite add 100 500
 *     Success: Activity item added as a favorite for the user.
 *
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
	protected $obj_fields = array(
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
	);

	/**
	 * Add an activity item as a favorite for a user.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : ID of the activity to add an item to.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp activity favorite add 100 500
	 *     Success: Activity item added as a favorite for the user.
	 *
	 *     $ wp bp activity favorite create 100 user_test
	 *     Success: Activity item added as a favorite for the user.
	 *
	 * @alias add
	 */
	public function create( $args ) {
		$activity = bp_activity_get_specific(
			array(
				'activity_ids'     => $args[0],
				'spam'             => null,
				'display_comments' => true,
			)
		);

		$activity = $activity['activities'][0];

		if ( ! is_object( $activity ) ) {
			WP_CLI::error( 'Could not find the activity.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

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
	 * : ID of the activity to remove a item to.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp activity favorite remove 100 500
	 *     Success: Activity item removed as a favorite for the user.
	 *
	 *     $ wp bp activity favorite delete 100 user_test --yes
	 *     Success: Activity item removed as a favorite for the user.
	 *
	 * @alias delete
	 */
	public function remove( $args, $assoc_args ) {
		$activity = bp_activity_get_specific(
			array(
				'activity_ids'     => $args[0],
				'spam'             => null,
				'display_comments' => true,
			)
		);

		$activity = $activity['activities'][0];

		if ( ! is_object( $activity ) ) {
			WP_CLI::error( 'Could not find the activity.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

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
	 * [--count=<number>]
	 * : How many activity favorites to list.
	 * ---
	 * default: 50
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp activity favorite list 315
	 *
	 * @subcommand list
	 * @alias items
	 * @alias user_items
	 */
	public function list_( $args, $assoc_args ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$user      = $this->get_user_id_from_identifier( $args[0] );
		$favorites = bp_activity_get_user_favorites( $user->ID );

		if ( empty( $favorites ) ) {
			WP_CLI::error( 'No favorite found for this user.' );
		}

		$activities = bp_activity_get_specific(
			array(
				'activity_ids' => (array) $favorites,
				'per_page'     => $assoc_args['count'],
			)
		);

		// Sanity check.
		if ( empty( $activities['activities'] ) ) {
			WP_CLI::error( 'No favorite found for this user.' );
		}

		$this->get_formatter( $assoc_args )->display_items( $activities['activities'] );
	}
}
