<?php
/**
 * Manage BuddyPress activity favorites.
 *
 * @since 1.5.0
 */
class BPCLI_Activity_Favorites extends BPCLI_Component {

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
	 * ## EXAMPLE
	 *
	 *     $ wp bp activity favorite add 100 500
	 *     Success: Activity item added as a favorite for the user.
	 *
	 *     $ wp bp activity favorite create 100 user_test
	 *     Success: Activity item added as a favorite for the user.
	 *
	 * @alias create
	 */
	public function add( $args, $assoc_args ) {
		$activity_id = $args[0];

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			WP_CLI::error( 'No activity found by that ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		// True if added.
		if ( bp_activity_add_user_favorite( $activity_id, $user->ID ) ) {
			WP_CLI::success( 'Activity item added as a favorite for the user.' );
		} else {
			WP_CLI::error( 'Could not add the activity item.' );
		}
	}

	/**
	 * Remove an activity item as a favorite for a user.
	 *
	 * @todo Test is_user_logged_in() possible error.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : ID of the activity to remove a item to.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp activity favorite remove 100 500
	 *     Success: Activity item removed as a favorite for the user.
	 *
	 *     $ wp bp activity favorite delete 100 user_test
	 *     Success: Activity item removed as a favorite for the user.
	 *
	 * @alias delete
	 */
	public function remove( $args, $assoc_args ) {
		$activity_id = $args[0];

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			WP_CLI::error( 'No activity found by that ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		// True if removed.
		if ( bp_activity_remove_user_favorite( $activity_id, $user->ID ) ) {
			WP_CLI::success( 'Activity item removed as a favorite for the user.' );
		} else {
			WP_CLI::error( 'Could not remove the activity item.' );
		}
	}

	/**
	 * Get a users favorite activity items.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp activity favorite items 315
	 *     Success: Favorite item(s) for user #315: 166,1561,6516
	 *
	 *     $ wp bp activity favorite user_items 156165
	 *     Success: Favorite item(s) for user #156165: 64494,65465,4645
	 *
	 * @alias user_items
	 */
	public function items( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $args[0] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		$favorites = bp_activity_get_user_favorites( $user->ID );

		if ( $favorites ) {
			$success = sprintf(
				'Favorite item(s) for user #%d: %s',
				$user->ID,
				implode( ', ', wp_list_pluck( $favorites ) )
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'No favorite found for this user.' );
		}
	}
}

WP_CLI::add_command( 'bp activity favorite', 'BPCLI_Activity_Favorites', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
	},
) );
