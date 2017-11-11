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
		if ( $this->add_user_favorite( $activity_id, $user->ID ) ) {
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
		$activity_id = $args[0];

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			WP_CLI::error( 'No activity found by that ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		WP_CLI::confirm( 'Are you sure you want to remove this activity item?', $assoc_args );

		// True if removed.
		if ( $this->remove_user_favorite( $activity_id, $user->ID ) ) {
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
				implode( ', ', $favorites )
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'No favorite found for this user.' );
		}
	}

	/**
	 * Add user favorite
	 *
	 * @todo Remove after https://buddypress.trac.wordpress.org/ticket/7623
	 *
	 * @return bool
	 */
	protected function add_user_favorite( $activity_id, $user_id ) {

		$my_favs = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );
		if ( empty( $my_favs ) || ! is_array( $my_favs ) ) {
			$my_favs = array();
		}

		// Bail if the user has already favorited this activity item.
		if ( in_array( $activity_id, $my_favs ) ) {
			return false;
		}

		// Add to user's favorites.
		$my_favs[] = $activity_id;

		// Update the total number of users who have favorited this activity.
		$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );
		$fav_count = !empty( $fav_count ) ? (int) $fav_count + 1 : 1;

		// Update user meta.
		bp_update_user_meta( $user_id, 'bp_favorite_activities', $my_favs );

		// Update activity meta counts.
		if ( bp_activity_update_meta( $activity_id, 'favorite_count', $fav_count ) ) {
			// Success.
			return true;

		// Saving meta was unsuccessful for an unknown reason.
		} else {
			return false;
		}
	}

	/**
	 * Remove user favorite
	 *
	 * @todo Remove after https://buddypress.trac.wordpress.org/ticket/7623
	 *
	 * @return bool
	 */
	protected function remove_user_favorite( $activity_id, $user_id ) {

		$my_favs = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );
		$my_favs = array_flip( (array) $my_favs );

		// Bail if the user has not previously favorited the item.
		if ( ! isset( $my_favs[ $activity_id ] ) ) {
			return false;
		}

		// Remove the fav from the user's favs.
		unset( $my_favs[$activity_id] );
		$my_favs = array_unique( array_flip( $my_favs ) );

		// Update the total number of users who have favorited this activity.
		$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );
		if ( ! empty( $fav_count ) ) {

			// Deduct from total favorites.
			if ( bp_activity_update_meta( $activity_id, 'favorite_count', (int) $fav_count - 1 ) ) {

				// Update users favorites.
				if ( bp_update_user_meta( $user_id, 'bp_favorite_activities', $my_favs ) ) {

					// Success.
					return true;

				// Error updating.
				} else {
					return false;
				}

			// Error updating favorite count.
			} else {
				return false;
			}

		// Error getting favorite count.
		} else {
			return false;
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
