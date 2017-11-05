<?php
/**
 * Manage BuddyPress group invites.
 *
 * @since 1.5.0
 */
class BPCLI_Group_Invite extends BPCLI_Component {

	/**
	 * Group ID Object Key
	 *
	 * @var string
	 */
	protected $obj_id_key = 'group_id';

	/**
	 * Group Object Type
	 *
	 * @var string
	 */
	protected $obj_type = 'group';

	/**
	 * Invite a member to a group.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_invite_user()
	 *
	 * [--silent=<silent>]
	 * : Whether to silent the invite creation. Default: false.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite add --user-id=10 --group-id=40
	 *     Success: Member invited to the group.
	 *
	 *     $ wp bp group invite add --user-id=admin --group-id=40 --inviter_id=804
	 *     Success: Member invited to the group.
	 *
	 *     $ wp bp group invite add --user-id=user_login --group-id=60 --silent=1
	 *     Success: Member invited to the group.
	 */
	public function add( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'group_id'      => '',
			'user_id'       => '',
			'inviter_id'    => bp_loggedin_user_id(),
			'date_modified' => bp_core_current_time(),
			'is_confirmed'  => 0,
			'silent'        => false,
		) );

		// Group ID.
		$group_id = $r['group_id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $r['user_id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$invite = groups_invite_user( $r );

		if ( $r['silent'] ) {
			return;
		}

		if ( $invite ) {
			WP_CLI::success( 'Member invited to the group.' );
		} else {
			WP_CLI::error( 'Could not invite the member.' );
		}
	}

	/**
	 * Uninvite a user from a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite remove --group-id=3 --user-id=10
	 *     Success: User uninvited from the group.
	 *
	 *     $ wp bp group invite remove --group-id=foo --user-id=admin
	 *     Success: User uninvited from the group.
	 *
	 * @alias uninvite
	 */
	public function remove( $args, $assoc_args ) {
		$group_id = $assoc_args['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_uninvite_user( $user->ID, $group_id ) ) {
			WP_CLI::success( 'User uninvited from the group.' );
		} else {
			WP_CLI::error( 'Could not remove the user.' );
		}
	}

	/**
	 * Get a list of a user's outstanding group invitations.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_get_invites_for_user()
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite users --user-id=30
	 *     $ wp bp group invite users --user-id=30 --limit=100 --exclude=100
	 */
	public function users( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'user_id' => '',
			'limit'   => false,
			'page'    => false,
			'exclude' => false,
		) );

		$user = $this->get_user_id_from_identifier( $r['user_id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$invites = groups_get_invites_for_user( $user->ID, $r['limit'], $r['page'], $r['exclude'] );

		if ( $invites ) {
			$found = sprintf(
				'Found %d group invitations from member #%d',
				$invites['total'],
				$user->ID
			);
			WP_CLI::success( $found );

			$success = sprintf(
				'Group invitations from member #%d: %s',
				$user->ID,
				implode( ', ', wp_list_pluck( $invites, 'group_id' ) )
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not find any group invitation for this member.' );
		}
	}

	/**
	 * Get a list of invitations from a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--role=<role>]
	 * : Group member role (member, mod, admin).
	 * ---
	 * Default: member
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite list --user-id=30 --group-id=56
	 *     $ wp bp group invite list --user-id=30 --group-id=100 --role=member
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$group_id = $assoc_args['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$invites = groups_get_invites_for_group( $user->ID, $group_id, $assoc_args['role'] );

		if ( $invites ) {
			$found = sprintf(
				'Found %d invitations from group #%d.',
				$invites['total'],
				$group_id
			);
			WP_CLI::success( $found );

			$success = sprintf(
				'Current invitations from group #%d: %s',
				$group_id,
				implode( ', ', wp_list_pluck( $invites, 'id' ) )
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not find any invitation for this group.' );
		}
	}

	/**
	 * Generate random group invitations.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many groups invitations to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp group invite generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating random group invitations', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->add( array(), array(
				'user_id'  => $this->get_random_user_id(),
				'group_id' => $this->get_random_group_id(),
				'silent'   => true,
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Accept a group invitation.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite accept --group-id=3 --user-id=10
	 *     Success: User is not a "member" of the group.
	 *
	 *     $ wp bp group invite accept --group-id=foo --user-id=admin
	 *     Success: User is not a "member" of the group.
	 */
	public function accept( $args, $assoc_args ) {
		$group_id = $assoc_args['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_accept_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'User is now a "member" of the group.' );
		} else {
			WP_CLI::error( 'Could not accept user invitation to the group.' );
		}
	}

	/**
	 * Reject a group invitation.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite reject --group-id=3 --user-id=10
	 *     Success: Member invitation rejected.
	 *
	 *     $ wp bp group invite reject --group-id=foo --user-id=admin
	 *     Success: Member invitation rejected.
	 */
	public function reject( $args, $assoc_args ) {
		$group_id = $assoc_args['group_id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_reject_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Member invitation rejected.' );
		} else {
			WP_CLI::error( 'Could not reject member invitation.' );
		}
	}

	/**
	 * Delete a group invitation.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite delete --group-id=3 --user-id=10
	 *     Success: Member invitation deleted from the group.
	 *
	 *     $ wp bp group invite delete --group-id=foo --user-id=admin
	 *     Success: Member invitation deleted from the group.
	 */
	public function delete( $args, $assoc_args ) {
		$group_id = $assoc_args['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_delete_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Member invitation deleted from the group.' );
		} else {
			WP_CLI::error( 'Could not delete member invitation from the group.' );
		}
	}

	/**
	 * Send pending invites by a user to a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite send --group-id=3 --user-id=10
	 *     Success: Invitations by the user sent.
	 *
	 *     $ wp bp group invite send --group-id=foo --user-id=admin
	 *     Success: Invitations by the user sent.
	 */
	public function send( $args, $assoc_args ) {
		$group_id = $assoc_args['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_send_invites( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Invitations by the user sent.' );
		} else {
			WP_CLI::error( 'Could not send the invitations.' );
		}
	}
}

WP_CLI::add_command( 'bp group invite', 'BPCLI_Group_Invite', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );
