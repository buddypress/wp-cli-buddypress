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
	 * [--force-accept]
	 * : Whether to force acceptance.
	 * ---
	 * default: true
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
	 *     $ wp bp friend create user1 another_use --force-accept=false
	 *     Success: Friendship successfully created.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		// Members.
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $initiator || ! $friend ) {
			WP_CLI::error( 'No user found by that username or ID.' );
		}

		$force_accept = ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'force-accept' ) ) ? false : true;

		if ( ! friends_add_friend( $initiator->ID, $friend->ID, $force_accept ) ) {
			WP_CLI::error( 'There was a problem while creating the friendship.' );
		}

		if ( $assoc_args['silent'] ) {
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
	 * <friendship-id>...
	 * : ID(s) of the friendship(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend accept_invitation 2161
	 *     Success: Friendship successfully accepted.
	 *
	 *     $ wp bp friend accept 2161 151 2121
	 *     Success: Friendship successfully accepted.
	 *
	 * @alias accept
	 */
	public function accept_invitation( $args, $assoc_args ) {
		foreach ( $args as $friendship_id ) {
			if ( friends_accept_friendship( (int) $friendship_id ) ) {
				WP_CLI::success( 'Friendship successfully accepted.' );
			} else {
				WP_CLI::error( 'There was a problem accepting the friendship.' );
			}
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
