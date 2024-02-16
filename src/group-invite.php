<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress group invites.
 *
 * ## EXAMPLES
 *
 *     # Invite a member to a group.
 *     $ wp bp group invite add --group-id=40 --user-id=10 --inviter-id=1331
 *     Success: Member invited to the group.
 *
 *     # Invite a member to a group.
 *     $ wp bp group invite create --group-id=40 --user-id=user_slug --inviter-id=804
 *     Success: Member invited to the group.
 *
 * @since 1.5.0
 */
class Group_Invite extends BuddyPressCommand {

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
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * --inviter-id=<user>
	 * : Identifier for the inviter. Accepts either a user_login or a numeric ID.
	 *
	 * [--message=<value>]
	 * : Message to send with the invitation.
	 *
	 * [--porcelain]
	 * : Return only the invitation id.
	 *
	 * [--silent]
	 * : Whether to silent the invite creation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Invite a member to a group.
	 *     $ wp bp group invite add --group-id=40 --user-id=10 --inviter-id=1331
	 *     Success: Member invited to the group.
	 *
	 *     # Invite a member to a group.
	 *     $ wp bp group invite create --group-id=40 --user-id=user_slug --inviter-id=804
	 *     Success: Member invited to the group.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {

		// Bail early.
		if ( $assoc_args['user-id'] === $assoc_args['inviter-id'] ) {
			return;
		}

		$message   = isset( $assoc_args['message'] ) ? $assoc_args['message'] : '';
		$group_id  = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user      = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$inviter   = $this->get_user_id_from_identifier( $assoc_args['inviter-id'] );
		$invite_id = groups_invite_user(
			[
				'user_id'    => $user->ID,
				'group_id'   => $group_id,
				'inviter_id' => $inviter->ID,
				'content'    => $message,
			]
		);

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $invite_id ) {
			WP_CLI::error( 'Could not invite the member.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $invite_id );
		} else {
			WP_CLI::success( 'Member invited to the group.' );
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
	 *     # Uninvite a user from a group.
	 *     $ wp bp group invite uninvite --group-id=3 --user-id=10
	 *     Success: User uninvited from the group.
	 *
	 *     # Uninvite a user from a group.
	 *     $ wp bp group invite uninvite --group-id=foo --user-id=admin
	 *     Success: User uninvited from the group.
	 */
	public function uninvite( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( groups_uninvite_user( $user->ID, $group_id ) ) {
			WP_CLI::success( 'User uninvited from the group.' );
		} else {
			WP_CLI::error( 'Could not remove the user.' );
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
	 * [--count=<number>]
	 * : How many invitations to list.
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
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     # Get a list of invitations from a group.
	 *     $ wp bp group invite list --group-id=56 --user-id=30
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$user_id  = $user->ID;

		if ( $group_id ) {
			$invite_query = new \BP_Group_Member_Query( [
				'is_confirmed' => false,
				'group_id'     => $group_id,
				'per_page'     => $assoc_args['count'],
			] );

			$invites = $invite_query->results;

			// Manually filter out user ID - this is not supported by the API.
			if ( $user_id ) {
				$user_invites = [];

				foreach ( $invites as $invite ) {
					if ( $user_id === $invite->user_id ) {
						$user_invites[] = $invite;
					}
				}

				$invites = $user_invites;
			}

			if ( empty( $invites ) ) {
				WP_CLI::error( 'No invitations found.' );
			}

			if ( empty( $assoc_args['fields'] ) ) {
				$fields = [];

				if ( ! $user_id ) {
					$fields[] = 'user_id';
				}

				$fields[] = 'inviter_id';
				$fields[] = 'invite_sent';
				$fields[] = 'date_modified';

				$assoc_args['fields'] = $fields;
			}
		} else {
			$invite_query = groups_get_invites_for_user( $user_id, $assoc_args['count'], 1 );
			$invites      = $invite_query['groups'];

			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = [ 'id', 'name', 'slug' ];
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $invites, 'id' ) : $invites );
	}

	/**
	 * Generate group invitations.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many group invitations to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--user-id=<user>]
	 * : ID of the first user. Accepts either a user_login or a numeric ID.
	 *
	 * [--inviter-id=<user>]
	 * : ID for the inviter. Accepts either a user_login or a numeric ID.
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
	 *     # Generate random group invitations.
	 *     $ wp bp group invite generate --count=50
	 *     Generating group invitations  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate random group invitations with a specific user.
	 *     $ wp bp group invite generate --inviter-id=121 --count=5
	 *     Generating group invitations  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 random group invitations and output only the IDs.
	 *     $ wp bp group invite generate --count=5 --format=ids
	 *     70 71 72 73 74
	 */
	public function generate( $args, $assoc_args ) {
		$user_id    = null;
		$inviter_id = null;

		if ( isset( $assoc_args['user-id'] ) ) {
			$user    = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
			$user_id = $user->ID;
		}

		if ( isset( $assoc_args['inviter-id'] ) ) {
			$user_2     = $this->get_user_id_from_identifier( $assoc_args['inviter-id'] );
			$inviter_id = $user_2->ID;
		}

		$this->generate_callback(
			'Generating group invitations',
			$assoc_args,
			function ( $assoc_args, $format ) use ( $user_id, $inviter_id ) {
				$random_group = \BP_Groups_Group::get_random( 1, 1 );

				if ( ! $user_id ) {
					$user_id = $this->get_random_user_id();
				}

				if ( ! $inviter_id ) {
					$inviter_id = $this->get_random_user_id();
				}

				$params = [
					'user-id'    => $user_id,
					'group-id'   => $random_group['groups'][0]->slug,
					'inviter-id' => $inviter_id,
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
	 *     # Accept a group invitation.
	 *     $ wp bp group invite accept --group-id=3 --user-id=10
	 *     Success: User is now a "member" of the group.
	 *
	 *     # Accept a group invitation.
	 *     $ wp bp group invite accept --group-id=foo --user-id=admin
	 *     Success: User is now a "member" of the group.
	 */
	public function accept( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

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
	 *     # Reject a group invitation.
	 *     $ wp bp group invite reject --group-id=3 --user-id=10
	 *     Success: Member invitation rejected.
	 *
	 *     # Reject a group invitation.
	 *     $ wp bp group invite reject --group-id=foo --user-id=admin
	 *     Success: Member invitation rejected.
	 */
	public function reject( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

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
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a group invitation.
	 *     $ wp bp group invite delete --group-id=3 --user-id=10 --yes
	 *     Success: Group invitation deleted.
	 *
	 *     # Delete a group invitation.
	 *     $ wp bp group invite delete --group-id=foo --user-id=admin --yes
	 *     Success: Group invitation deleted.
	 *
	 * @alias delete
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		WP_CLI::confirm( 'Are you sure you want to delete this group invitation?', $assoc_args );

		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		if ( groups_delete_invite( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Group invitation deleted.' );
		} else {
			WP_CLI::error( 'Could not delete group invitation.' );
		}
	}
}
