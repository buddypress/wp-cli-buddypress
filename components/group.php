<?php

/**
 * Manage BuddyPress groups.
 */
class BPCLI_Group extends BPCLI_Component {

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
	protected $obj_type   = 'group';

	/**
	 * Create a group.
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : Name of the group.
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group. If not provided, one will be generated automatically.
	 *
	 * [--description=<description>]
	 * : Group description. Default: 'Description for group "[name]"'
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator. Default: 1.
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group.
	 *
	 * [--status=<status>]
	 * : Group status (public, private, hidden). Default: public.
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums. Default: 0.
	 *
	 * [--date-created=<date-created>]
	 * : MySQL-formatted date. Default: current date.
	 *
	 * ## EXAMPLES
	 *
	 *    wp bp group create --name="Totally Cool Group"
	 *    wp bp group create --name="Sports" --description="People who love sports" --creator-id=54 --status=private
	 *
	 * @synopsis --name=<name> [--slug=<slug>] [--description=<description>] [--creator-id=<creator-id>] [--status=<status>] [--enable-forum=<enable-forum>] [--date-created=<date-created>]
	 *
	 * @since 1.0
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'name'         => '',
			'slug'         => '',
			'description'  => '',
			'creator_id'   => 1,
			'status'       => 'public',
			'enable_forum' => 0,
			'date_created' => bp_core_current_time(),
		) );

		if ( empty( $r['name'] ) ) {
			WP_CLI::error( 'You must provide a --name parameter when creating a group.' );
		}

		// Auto-generate some stuff.
		if ( empty( $r['slug'] ) ) {
			$r['slug'] = groups_check_slug( sanitize_title( $r['name'] ) );
		}

		if ( empty( $r['description'] ) ) {
			$r['description'] = sprintf( 'Description for group "%s"', $r['name'] );
		}

		$id = groups_create_group( $r );
		if ( $id ) {
			groups_update_groupmeta( $id, 'total_member_count', 1 );
			$group = groups_get_group( array(
				'group_id' => $id,
			) );
			$permalink = bp_get_group_permalink( $group );
			WP_CLI::success( sprintf( 'Group %d created: %s', $id, $permalink ) );
		} else {
			WP_CLI::error( 'Could not create group.' );
		}
	}

	/**
	 * Generate random groups.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many groups to generate. Default: 100
	 *
	 * ## EXAMPLES
	 *
	 *  wp bp group generate --count=50
	 *
	 * @synopsis [--count=<number>]
	 *
	 * @since 1.3.0
	 */
	public function generate( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'count' => 100,
		) );

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating groups', $r['count'] );

		for ( $i = 0; $i < $r['count']; $i++ ) {
			$this->create( array(), array(
				'name' => sprintf( 'Test Group - #%d', $i ),
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Delete a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * ## EXAMPLES
	 *
	 *   wp bp group delete 500
	 *   wp bp group delete group-slug
	 *
	 * @synopsis <group-id>
	 *
	 * @since 1.3.0
	 */
	public function delete( $args, $assoc_args ) {
		$group_id = isset( $args[0] ) ? $args[0] : false;

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		// Delete group. True if deleted.
		if ( groups_delete_group( $group_id ) ) {
			WP_CLI::success( 'Group deleted.' );
		} else {
			WP_CLI::error( 'Could not delete the group.' );
		}
	}

	/**
	 * Update a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * --<field>=<value>
	 * : One or more fields to update. See groups_create_group()
	 *
	 * ## EXAMPLES
	 *
	 *   wp bp group update 35 --description="What a cool group!" --name="Group of Cool People"
	 *
	 * @synopsis <group-id> [--field=<value>]
	 *
	 * @since 1.0
	 */
	public function update( $args, $assoc_args ) {
		$clean_group_ids = array();

		foreach ( $args as $group_id ) {

			// Check that group exists.
			if ( ! $this->group_exists( $group_id ) ) {
				WP_CLI::error( 'No group found by that slug or ID.' );
			}

			$clean_group_ids[] = $group_id;
		}

		parent::_update( $clean_group_ids, $assoc_args, function( $params ) {
			return groups_create_group( $params );
		} );
	}

	/**
	 * Get a list of groups.
	 *
	 * ## OPTIONS
	 *
	 * --<field>=<value>
	 * : One or more parameters to pass. See groups_get_groups()
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
	 * ## EXAMPLES
	 *
	 *   wp bp group list --format=ids
	 *   wp bp group list --format=count
	 *   wp bp group list --per_page=5
	 *
	 * @synopsis [--field=<value>] [--format=<format>]
	 *
	 * @since 1.3.0
	 */
	public function list_( $args, $assoc_args ) {

		$formatter = $this->get_formatter( $assoc_args );

		$query_args = wp_parse_args( $assoc_args, array(
			'type'        => 'active',
			'per_page'    => -1,
			'show_hidden' => true,
		) );

		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		if ( 'ids' === $formatter->format ) {
			$groups = groups_get_groups( $query_args );
			$ids    = array_search( 'id', $groups['groups'], true );

			echo implode( ' ', $ids ); // XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$groups = groups_get_groups( $query_args );

			$formatter->display_items( $groups['total'] );
		} else {
			$groups  = groups_get_groups( $query_args );
			$formatter->display_items( $groups['groups'] );
		}
	}

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
	 * : Group role for the new member (member, mod, admin). Default: member.
	 *
	 * ## EXAMPLES
	 *
	 *   wp bp group add_member --group-id=3 --user-id=10
	 *   wp bp group add_member --group-id="group-slug" --user-id=20
	 *   wp bp group add_member --group-id=foo --user-id=admin --role=mod
	 *
	 * @synopsis [--group-id=<group-id>] [--user-id=<user-id>] [--role=<role>]
	 *
	 * @since 1.0
	 */
	public function add_member( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'group-id' => null,
			'user-id'  => null,
			'role'     => 'member',
		) );

		// Group ID.
		$group_id = $r['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $r['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		// Sanitize role.
		$role = $r['role'];
		if ( ! in_array( $role, $this->group_roles(), true ) ) {
			$role = 'member';
		}

		$joined = groups_join_group( $group_id, $user->ID );

		if ( $joined ) {
			if ( 'member' !== $role ) {
				$the_member = new BP_Groups_Member( $user->ID, $group_id );
				$the_member->promote( $role );
			}

			$success = sprintf(
				'Added user #%d (%s) to group #%d (%s) as %s',
				$user->ID,
				$user->user_login,
				$group_id,
				$group_obj->name,
				$role
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not add user to group.' );
		}
	}

	/**
	 * Get a list of members for a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *   wp bp group get_members 3
	 *   wp bp group get_members group-slug
	 *
	 * @synopsis <group-id>
	 *
	 * @since 1.3.0
	 */
	public function get_members( $args, $assoc_args ) {
		$group_id = isset( $args[0] ) ? $args[0] : false;

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		// Get our members.
		$members = groups_get_group_members( array(
			'group_id' => $group_id,
		) );

		if ( $members['count'] ) {
			$found = sprintf(
				'Found %d members in group #%d',
				$members['count'],
				$group_id
			);
			WP_CLI::success( $found );

			$member_list = implode( ', ', wp_list_pluck( $members['members'], 'user_login' ) );

			$users = sprintf(
				'Current members for group #%d: %s',
				$group_id,
				$member_list
			);

			WP_CLI::success( $users );
		} else {
			WP_CLI::error( 'Could not find any users in the group.' );
		}
	}

	/**
	 * Promote a member of a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--role=<role>]
	 * : Group role to promote the member (member, mod, admin).
	 *
	 * ## EXAMPLES
	 *
	 *    wp bp group promote --group-id=3 --user-id=10
	 *    wp bp group promote --group-id="group-slug" --user-id=20
	 *    wp bp group promote --group-id=foo --user-id=admin --role=mod
	 *
	 * @synopsis [--group-id=<group-id>] [--user-id=<user-id>] [--role=<role>]
	 *
	 * @since 1.3.0
	 */
	public function promote( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'group-id' => '',
			'user-id'  => '',
			'role'     => '',
		) );

			// Group ID.
		$group_id = $r['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $r['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$role = $r['role'];
		if ( empty( $role ) && ! in_array( $role, $this->group_roles(), true ) ) {
			WP_CLI::error( 'You need a role to promote the user.' );
		}

		$member = new BP_Groups_Member( $user->ID, $group_id );

		if ( $member->promote( $role ) ) {
			WP_CLI::success( sprintf( 'User promoted to %s', $role ) );
		} else {
			WP_CLI::error( 'Could not promote the user.' );
		}
	}

	/**
	 * Demote user to member.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *    wp bp group demote --group-id=3 --user-id=10
	 *    wp bp group demote --group-id="group-slug" --user-id=20
	 *    wp bp group demote --group-id=foo --user-id=admin
	 *
	 * @synopsis [--group-id=<group-id>] [--user-id=<user-id>]
	 *
	 * @since 1.3.0
	 */
	public function demote( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'group-id' => '',
			'user-id'  => '',
		) );

		// Group ID.
		$group_id = $r['group-id'];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $r['user-id'] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$member = new BP_Groups_Member( $user->ID, $group_id );

		if ( $member->demote() ) {
			WP_CLI::success( sprintf( 'User demoted to user.' ) );
		} else {
			WP_CLI::error( 'Could not demote the user.' );
		}
	}

	/**
	 * Group Roles
	 *
	 * @since 1.3.0
	 *
	 * @return array An array of group roles
	 */
	protected function group_roles() {
		return array( 'member', 'mod', 'admin' );
	}

	/**
	 * Check if a group exists
	 *
	 * @since 1.3.0
	 *
	 * @param int $group_id Group ID or slug.
	 * @return bool true|false
	 */
	protected function group_exists( $group_id ) {
		// ID or group slug.
		$group_id = ( ! is_numeric( $group_id ) )
			? groups_get_id( $group_id )
			: $group_id;

		// Get group object.
		$group_obj = groups_get_group( array(
			'group_id' => $group_id,
		) );

		if ( empty( $group_obj->id ) ) {
			return false;
		}
		return true;
	}
}

WP_CLI::add_command( 'bp group', 'BPCLI_Group', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );

