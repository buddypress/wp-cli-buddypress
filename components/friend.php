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
	 * [force-accept=<force-accept>]
	 * : Whether to force acceptance.
	 * ---
	 * default: true
	 * ---
	 *
	 * [--silent=<silent>]
	 * : Silent friendship creation.
	 * ---
	 * Default: false
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp friend create user1 another_use
	 *     Success: Friendship successfully created.
	 *
	 *     $ wp bp friend create user1 another_use force-accept
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

		$force_accept = ( (bool) $assoc_args['force-accept'] ) ? false : true;

		if ( friends_add_friend( $initiator->ID, $friend->ID, $force_accept ) ) {

			if ( $assoc_args['silent'] ) {
				return;
			}

			WP_CLI::success( 'Friendship successfully created.' );
		} else {
			WP_CLI::error( 'There was a problem while creating the friendship.' );
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
