<?php
/**
 * Manage BuddyPress Friends.
 *
 * @since 1.6.0
 */
class BPCLI_Friend extends BPCLI_Component {

	/**
	 * Create a new friendship.
	 *
	 * ## OPTIONS
	 *
	 * <initiator>
	 * : ID of the user who is sending the friendship request. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the user whose friendship is being requested. Accepts either a user_login or a numeric ID.
	 *
	 * [--force-accept=<force-accept>]
	 * : Whether to force acceptance.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--silent=<silent>]
	 * : Silent friendship creation.
	 * ---
	 * default: false
	 * ---
	 *
	 * [--porcelain]
	 * : Return only the friendship id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend create user1 another_use
	 *     Success: Friendship successfully created.
	 *
	 *     $ wp bp friend create user1 another_use --force-accept=true
	 *     Success: Friendship successfully created.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'force-accept' => false,
			'silent'       => false,
		) );

		// Members.
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $initiator || ! $friend ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		if ( ! friends_add_friend( $initiator->ID, $friend->ID, $r['force-accept'] ) ) {
			WP_CLI::error( 'There was a problem while creating the friendship.' );
		}

		if ( $r['silent'] ) {
			return;
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( BP_Friends_Friendship::get_friendship_id( $initiator->ID, $friend->ID ) );
		} else {
			WP_CLI::success( 'Friendship successfully created.' );
		}
	}

	/**
	 * Remove a friendship.
	 *
	 * ## OPTIONS
	 *
	 * <initiator>
	 * : ID of the friendship initiator. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the friend user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp friend remove user1 another_use
	 *     Success: Friendship successfully removed.
	 *
	 * @alias delete
	 */
	public function remove( $args, $assoc_args ) {
		// Members.
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $initiator || ! $friend ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		if ( friends_remove_friend( $initiator->ID, $friend->ID ) ) {
			WP_CLI::success( 'Friendship successfully removed.' );
		} else {
			WP_CLI::error( 'There was a problem while removing the friendship.' );
		}
	}

	/**
	 * Mark a friendship request as accepted.
	 *
	 * ## OPTIONS
	 *
	 * <friendship-id>
	 * : ID of the friendship.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend accept_invitation 2161
	 *     Success: Friendship successfully accepted.
	 *
	 *     $ wp bp friend accept 2161
	 *     Success: Friendship successfully accepted.
	 *
	 * @alias accept
	 */
	public function accept_invitation( $args, $assoc_args ) {
		if ( friends_accept_friendship( $args[0] ) ) {
			WP_CLI::success( 'Friendship successfully accepted.' );
		} else {
			WP_CLI::error( 'There was a problem accepting the friendship.' );
		}
	}

	/**
	 * Mark a friendship request as rejected.
	 *
	 * ## OPTIONS
	 *
	 * <friendship-id>...
	 * : ID(s) of the friendship(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend reject_invitation 2161
	 *     Success: Friendship successfully accepted.
	 *
	 *     $ wp bp friend reject 2161 151 2121
	 *     Success: Friendship successfully accepted.
	 *
	 * @alias reject
	 */
	public function reject_invitation( $args, $assoc_args ) {
		foreach ( $args as $friendship_id ) {
			if ( friends_reject_friendship( (int) $friendship_id ) ) {
				WP_CLI::success( 'Friendship successfully rejected.' );
			} else {
				WP_CLI::error( 'There was a problem rejecting the friendship.' );
			}
		}
	}

	/**
	 * Check whether two users are friends.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID of the first user. Accepts either a user_login or a numeric ID.
	 *
	 * <possible_friend>
	 * : ID of the other user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend check 2161 65465
	 *     Success: Yes, they are friends.
	 *
	 *     $ wp bp friend see 2121 65456
	 *     Success: Yes, they are friends.
	 *
	 * @alias see
	 */
	public function check( $args, $assoc_args ) {
		// Members.
		$user = $this->get_user_id_from_identifier( $args[0] );
		$friend = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user || ! $friend ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		if ( friends_check_friendship( $user->ID, $friend->ID ) ) {
			WP_CLI::success( 'Yes, they are friends.' );
		} else {
			WP_CLI::error( 'No, they are not friends.' );
		}
	}

	/**
	 * Get a list of user's friends.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID of the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - csv
	 *   - count
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend list 65465 --format=ids
	 *     $ wp bp friend list 2422 --format=count
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$user = $this->get_user_id_from_identifier( $args[0] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		$friends = BP_Friends_Friendship::get_friendships( $user->ID );

		if ( empty( $friends ) ) {
			WP_CLI::error( 'This member has no friends.' );
		}

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', BP_Friends_Friendship::get_friend_user_ids( $user->ID ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $friends );
		} else {
			$formatter->display_items( $friends );
		}
	}
}

WP_CLI::add_command( 'bp friend', 'BPCLI_Friend', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'friends' ) ) {
			WP_CLI::error( 'The Friends component is not active.' );
		}
	},
) );
