<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress group members.
 *
 * ## EXAMPLES
 *
 *     # Add a user to a group as a member.
 *     $ wp bp group member add --group-id=3 --user-id=10
 *     Success: Added user #3 to group #3 as member.
 *
 *     # Add a user to a group as a mod.
 *     $ wp bp group member create --group-id=bar --user-id=20 --role=mod
 *     Success: Added user #20 to group #45 as mod.
 *
 * @since 1.5.0
 */
class Group_Member extends BuddyPressCommand {

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
	 * Add a member to a group.
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
	 * default: member
	 * options:
	 *   - member
	 *   - mod
	 *   - admin
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a user to a group as a member.
	 *     $ wp bp group member add --group-id=3 --user-id=10
	 *     Success: Added user #3 to group #3 as member.
	 *
	 *     # Add a user to a group as a moderator.
	 *     $ wp bp group member create --group-id=bar --user-id=20 --role=mod
	 *     Success: Added user #20 to group #45 as mod.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$role     = $assoc_args['role'];
		$joined   = groups_join_group( $group_id, $user->ID );

		if ( ! $joined ) {
			WP_CLI::error( 'Could not add user to the group.' );
		}

		if ( 'member' !== $role ) {
			$group_member = new \BP_Groups_Member( $user->ID, $group_id );
			$group_member->promote( $role );
		}

		WP_CLI::success(
			sprintf(
				'Added user #%d to group #%d as %s.',
				$user->ID,
				$group_id,
				$role
			)
		);
	}

	/**
	 * Remove a member from a group.
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
	 *     # Remove a member from a group.
	 *     $ wp bp group member remove --group-id=3 --user-id=10
	 *     Success: Member #10 removed from the group #3.
	 *
	 *     # Remove a member from a group.
	 *     $ wp bp group member delete --group-id=foo --user-id=admin
	 *     Success: Member #545 removed from the group #12.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$group_id     = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user         = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$group_member = new \BP_Groups_Member( $user->ID, $group_id );

		// Check if the user is the only admin of the group.
		if ( (bool) $group_member->is_admin ) {
			$group_admins = groups_get_group_admins( $group_id );
			if ( 1 === count( $group_admins ) ) {
				WP_CLI::error( 'Cannot remove the only admin of the group.' );
			}
		}

		// True on success.
		if ( $group_member->remove() ) {
			WP_CLI::success( sprintf( 'Member #%d removed from the group #%d.', $user->ID, $group_id ) );
		} else {
			WP_CLI::error( 'Could not remove member from the group.' );
		}
	}

	/**
	 * Get a list of group memberships.
	 *
	 * This command can be used to fetch a list of a user's groups (using the --user-id
	 * parameter) or a group's members (using the --group-id flag).
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific signup fields.
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_get_group_members()
	 *
	 * [--role=<role>]
	 * : Limit the output to members with a specific role.
	 * ---
	 * default: members
	 * options:
	 *  - members
	 *  - mod
	 *  - admin
	 *  - banned
	 * ---
	 *
	 * [--count=<number>]
	 * : How many members to list.
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
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each group member:
	 *
	 * * id
	 * * user_login
	 * * fullname
	 * * date_modified
	 * * role
	 *
	 * ## EXAMPLE
	 *
	 *     # Get a list of group members.
	 *     $ wp bp group member list 3
	 *     +---------+------------+----------+---------------------+-------+
	 *     | id      | user_login | fullname | date_modified       | role  |
	 *     +---------+------------+----------+---------------------+-------+
	 *     | 1       | user       | User     | 2022-07-04 02:12:02 | admin |
	 *     +---------+------------+----------+---------------------+-------+
	 *
	 *     # Get a list of group members and get the count.
	 *     $ wp bp group member list 65465 --format=count
	 *     100
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $args[0] );

		// Get our members.
		$members_query = groups_get_group_members(
			[
				'per_page'            => $assoc_args['count'],
				'group_id'            => $group_id,
				'exclude_admins_mods' => false,
				'group_role'          => [ $assoc_args['role'] ],
			]
		);

		$members = $members_query['members'];

		if ( empty( $members ) ) {
			WP_CLI::error( 'No group members found.' );
		}

		// Make 'role' human-readable.
		foreach ( $members as &$member ) {
			$role = 'member';
			if ( $member->is_mod ) {
				$role = 'mod';
			} elseif ( $member->is_admin ) {
				$role = 'admin';
			}

			$member->role = $role;
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = [
				'id',
				'user_login',
				'fullname',
				'date_modified',
				'role',
			];
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $members, 'user_id' ) : $members );
	}

	/**
	 * Promote a member to a new status within a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * --role=<role>
	 * : Group role to promote the member.
	 * ---
	 * options:
	 *   - mod
	 *   - admin
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Promote a member to a new role.
	 *     $ wp bp group member promote --group-id=3 --user-id=10 --role=admin
	 *     Success: Member promoted to new role successfully.
	 *
	 *     # Promote a member to a new role.
	 *     $ wp bp group member promote --group-id=foo --user-id=admin --role=mod
	 *     Success: Member promoted to new role successfully.
	 */
	public function promote( $args, $assoc_args ) {
		$group_id     = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user         = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$group_member = new \BP_Groups_Member( $user->ID, $group_id );

		if ( $group_member->promote( $assoc_args['role'] ) ) {
			WP_CLI::success( 'Member promoted to new role successfully.' );
		} else {
			WP_CLI::error( 'Could not promote the member.' );
		}
	}

	/**
	 * Demote user to the 'member' status.
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
	 *     # Demote a user to the "member" status using numeric IDs.
	 *     $ wp bp group member demote --group-id=3 --user-id=10
	 *     Success: User demoted to the "member" status.
	 *
	 *     # Demote a user to the "member" status using slugs.
	 *     $ wp bp group member demote --group-id=foo --user-id=admin
	 *     Success: User demoted to the "member" status.
	 *
	 *     # Demote a user not part of the group.
	 *     $ wp bp group member demote --group-id=foo --user-id=admin
	 *     Error: User is not a member of the group.
	 */
	public function demote( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		// Check if the user is a member of the group.
		if ( ! groups_is_user_member( $user->ID, $group_id ) ) {
			WP_CLI::error( 'User is not a member of the group.' );
		}

		$group_member = new \BP_Groups_Member( $user->ID, $group_id );

		// Check if the user is the only admin of the group.
		if ( (bool) $group_member->is_admin ) {
			$group_admins = groups_get_group_admins( $group_id );
			if ( 1 === count( $group_admins ) ) {
				WP_CLI::error( 'Cannot demote the only admin of the group.' );
			}
		}

		if ( $group_member->demote() ) {
			WP_CLI::success( 'User demoted to the "member" status.' );
		} else {
			WP_CLI::error( 'Could not demote the member.' );
		}
	}

	/**
	 * Ban a member from a group.
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
	 *     # Ban a member from a group.
	 *     $ wp bp group member ban --group-id=3 --user-id=10
	 *     Success: Member banned from the group.
	 *
	 *     # Ban a member from a group.
	 *     $ wp bp group member ban --group-id=foo --user-id=admin
	 *     Success: Member banned from the group.
	 */
	public function ban( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		// Check if the user is a member of the group.
		if ( ! groups_is_user_member( $user->ID, $group_id ) ) {
			WP_CLI::error( 'User is not a member of the group.' );
		}

		$group_member = new \BP_Groups_Member( $user->ID, $group_id );

		if ( $group_member->ban() ) {
			WP_CLI::success( 'Member banned from the group.' );
		} else {
			WP_CLI::error( 'Could not ban the member.' );
		}
	}

	/**
	 * Unban a member from a group.
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
	 *     # Unban a member from a group.
	 *     $ wp bp group member unban --group-id=3 --user-id=10
	 *     Success: Member unbanned from the group.
	 *
	 *     # Unban a member from a group.
	 *     $ wp bp group member unban --group-id=foo --user-id=admin
	 *     Success: Member unbanned from the group.
	 */
	public function unban( $args, $assoc_args ) {
		$group_id = $this->get_group_id_from_identifier( $assoc_args['group-id'] );
		$user     = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$member   = new \BP_Groups_Member( $user->ID, $group_id );

		if ( $member->unban() ) {
			WP_CLI::success( 'Member unbanned from the group.' );
		} else {
			WP_CLI::error( 'Could not unban the member.' );
		}
	}
}
