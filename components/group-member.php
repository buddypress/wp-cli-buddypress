<?php
/**
 * Manage BuddyPress group members.
 *
 * @since 1.5.0
 */
class BPCLI_Group_Members extends BPCLI_Component {

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
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [<role>]
	 * : Group role for the new member (member, mod, admin).
	 * ---
	 * default: member
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group member add 3 10
	 *     $ wp bp group member add bar 20
	 *     $ wp bp group member add foo admin mod
	 */
	public function add( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		// Sanitize role.
		$role = $args[2];
		if ( empty( $role ) || ! in_array( $role, $this->group_roles(), true ) ) {
			$role = 'member';
		}

		$joined = groups_join_group( $group_id, $user->ID );

		if ( $joined ) {
			if ( 'member' !== $role ) {
				groups_promote_member( $user->ID, $group_id, $role );
			}

			$success = sprintf(
				'Added member #%d (%s) to group #%d (%s) as %s',
				$user->ID,
				$user->user_login,
				$group_id,
				$group_obj->name,
				$role
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not add member to the group.' );
		}
	}

	/**
	 * Remove a member from a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group member remove 3 10
	 *     Success: Member (#10) removed from the group #3.
	 *
	 *     $ wp bp group member delete foo admin
	 *     Success: Member (#545) removed from the group #12.
	 *
	 * @alias delete
	 */
	public function remove( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		// True on sucess.
		if ( groups_remove_member( $group_id, $user->ID ) ) {
			WP_CLI::success( sprintf( 'Member (#%d) removed from the group #%d.', $user->ID, $group_id ) );
		} else {
			WP_CLI::error( 'Could not remove member from the group.' );
		}
	}

	/**
	 * Get a list of members of a group.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_get_group_members()
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group member list --group-id=3
	 *     $ wp bp group member list --group-id=slug
	 */
	public function list( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'group_id'            => '',
			'per_page'            => false,
			'page'                => false,
			'exclude_admins_mods' => true,
			'exclude_banned'      => true,
			'exclude'             => false,
			'group_role'          => array(),
			'search_terms'        => false,
			'type'                => 'last_joined',
		) );

		$group_id = $r['group_id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		// Get our members.
		$members = groups_get_group_members( $r );

		if ( $members['count'] ) {
			$found = sprintf(
				'Found %d members in group #%d',
				$members['count'],
				$group_id
			);
			WP_CLI::success( $found );

			$users = sprintf(
				'Current members for group #%d: %s',
				$group_id,
				implode( ', ', wp_list_pluck( $members['members'], 'user_login' ) )
			);
			WP_CLI::success( $users );
		} else {
			WP_CLI::error( 'Could not find any members in the group.' );
		}
	}

	/**
	 * Get a list of groups a user is a member of.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See bp_get_user_groups()
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group member get_groups --user-id=30
	 *     $ wp bp group member get_groups --user-id=90 --order=DESC
	 *     $ wp bp group member get_groups --user-id=100 --order=DESC --is_mod=1
	 *
	 * @alias list_groups
	 */
	public function get_groups( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'user_id'      => null,
			'is_confirmed' => true,
			'is_banned'    => false,
			'is_admin'     => null,
			'is_mod'       => null,
			'invite_sent'  => null,
			'orderby'      => 'group_id',
			'order'        => 'ASC',
		) );

		$user = $this->get_user_id_from_identifier( $r['user_id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$groups = bp_get_user_groups( $user_id, $r );

		if ( ! empty( $groups ) ) {
			$found = sprintf(
				'Found %d groups from member #%d',
				count( $groups ),
				$user->ID
			);
			WP_CLI::success( $found );

			$success = sprintf(
				'Current groups from member #%d: %s',
				$user->ID,
				implode( ', ', wp_list_pluck( $groups, 'group_id' ) )
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'This user is not a member of any group.' );
		}
	}
}

WP_CLI::add_command( 'bp group member', 'BPCLI_Group_Members', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );
