<?php
/**
 * Manage BuddyPress groups.
 *
 * @since 1.5.0
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
	 * [--silent=<silent>]
	 * : Whether to silent the group creation. Default: false.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group create --name="Totally Cool Group"
	 *     $ wp bp group create --name="Sports" --description="People who love sports" --creator-id=54 --status=private
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
			'silent'       => false,
		) );

		// Auto-generate some stuff.
		if ( empty( $r['slug'] ) ) {
			$r['slug'] = groups_check_slug( sanitize_title( $r['name'] ) );
		}

		if ( empty( $r['description'] ) ) {
			$r['description'] = sprintf( 'Description for group "%s"', $r['name'] );
		}

		// Fallback for group status.
		if ( ! in_array( $r['status'], $this->group_status(), true ) ) {
			$r['status'] = 'public';
		}

		$id = groups_create_group( $r );

		if ( is_numeric( $id ) ) {
			groups_update_groupmeta( $id, 'total_member_count', 1 );

			if ( $r['silent'] ) {
				return;
			}

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
	 * : How many groups to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--status=<status>]
	 * : The status of the generated groups. Specify public, private, hidden, or mixed.
	 * ---
	 * default: public
	 * ---
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums.
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group generate --count=50
	 *     $ wp bp group generate --count=5 --status=mixed
	 *     $ wp bp group generate --count=10 --status=hidden --creator-id=30
	 */
	public function generate( $args, $assoc_args ) {
		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating groups', $r['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->create( array(), array(
				'name'         => sprintf( 'Group - #%d', $i ),
				'creator_id'   => $assoc_args['creator-id'],
				'status'       => $this->random_group_status( $assoc_args['status'] ),
				'enable_forum' => $assoc_args['enable-forum'],
				'silent'       => true,
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Get a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group get 500
	 *     $ wp bp group get group-slug
	 */
	public function get( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$group     = groups_get_group( $group_id );
		$group_arr = get_object_vars( $group );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $group_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $group_arr );
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
	 *     $ wp bp group delete 500
	 *     $ wp bp group delete group-slug
	 */
	public function delete( $args, $assoc_args ) {
		$group_id = $args[0];

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
	 * [--<field>=<value>]
	 * : One or more fields to update. See groups_create_group()
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp group update 35 --description="What a cool group!" --name="Group of Cool People"
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
	 * Get the permalink of a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group permalink 500
	 *     $ wp bp group permalink group-slug
	 */
	public function permalink( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		// Get the group object.
		$group = groups_get_group( array(
			'group_id' => $group_id,
		) );
		$permalink = bp_get_group_permalink( $group );

		if ( is_string( $permalink ) ) {
			WP_CLI::success( sprintf( 'Group Permalink: %s', $permalink ) );
		} else {
			WP_CLI::error( 'No permalink found for the group.' );
		}
	}

	/**
	 * Post an Activity update affiliated with a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user-id>
	 * : ID of the user. If none is provided, a user will be randomly selected.
	 *
	 * [--content=<content>]
	 * : Activity content text. If none is provided, default text will be generated.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group post_update 40 50  --content="Content to update"
	 *     $ wp bp group post_update 49 140
	 */
	public function post_update( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		// If no content, let's add some.
		if ( empty( $assoc_args['content'] ) ) {
			$assoc_args['content'] = $this->generate_random_text();
		}

		// Post the activity update.
		$activity_id = groups_post_update( array(
			'group_id' => $group_id,
			'user_id'  => $user->ID,
			'content'  => $assoc_args['content'],
		) );

		if ( is_numeric( $activity_id ) ) {
			WP_CLI::success( sprintf( 'Successfully updated with a new activity item (ID #%d)', $activity_id ) );
		} else {
			WP_CLI::error( 'Could not post the activity update.' );
		}
	}

	/**
	 * Get a list of groups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_get_groups()
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group list --format=ids
	 *     $ wp bp group list --format=count
	 *     $ wp bp group list --per_page=5
	 *
	 * @subcommand list
	 */
	public function _list( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$query_args = wp_parse_args( $assoc_args, array(
			'type'        => 'active',
			'per_page'    => -1,
			'show_hidden' => true,
		) );

		$query_args = self::process_csv_arguments_to_arrays( $query_args );
		$groups     = groups_get_groups( $query_args );

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $groups['groups'], 'id' ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $groups['total'] );
		} else {
			$formatter->display_items( $groups['groups'] );
		}
	}

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
	 *     $ wp bp group add_member 3 10
	 *     $ wp bp group add_member bar 20
	 *     $ wp bp group add_member foo admin mod
	 */
	public function add_member( $args, $assoc_args ) {
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
				'Added user #%d (%s) to group #%d (%s) as %s',
				$user->ID,
				$user->user_login,
				$group_id,
				$group_obj->name,
				$role
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not add user to the group.' );
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
	 *     $ wp bp group remove_member 3 10
	 *     $ wp bp group remove_member foo admin
	 */
	public function remove_member( $args, $assoc_args ) {
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
	 *     $ wp bp group get_members --group-id=3
	 *     $ wp bp group get_members --group-id=slug
	 */
	public function get_members( $args, $assoc_args ) {
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
			WP_CLI::error( 'Could not find any users in the group.' );
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
	 *     $ wp bp group get_member_groups --user-id=30
	 *     $ wp bp group get_member_groups --user-id=90 --order=DESC
	 *     $ wp bp group get_member_groups --user-id=100 --order=DESC --is_mod=1
	 */
	public function get_member_groups( $args, $assoc_args ) {
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

	/**
	 * Promote a member to a new status within a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * <role>
	 * : Group role to promote the member (member, mod, admin).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group promote 3 10 admin
	 *     $ wp bp group promote foo admin mod
	 */
	public function promote( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		$role = $args[2];
		if ( ! in_array( $role, $this->group_roles(), true ) ) {
			WP_CLI::error( 'You need a valid role to promote the member.' );
		}

		if ( groups_promote_member( $user->ID, $group_id, $role ) ) {
			WP_CLI::success( sprintf( 'Member promoted to new role: %s', $role ) );
		} else {
			WP_CLI::error( 'Could not promote the member.' );
		}
	}

	/**
	 * Demote user to the 'member' status.
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
	 *     $ wp bp group demote 3 10
	 *     $ wp bp group demote foo admin
	 */
	public function demote( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_demote_member( $user->ID, $group_id ) ) {
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
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group ban 3 10
	 *     $ wp bp group ban foo admin
	 */
	public function ban( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_ban_member( $user->ID, $group_id ) ) {
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
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group unban 3 10
	 *     $ wp bp group unban foo admin
	 */
	public function unban( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$user = $this->get_user_id_from_identifier( $args[1] );

		if ( ! $user ) {
			WP_CLI::error( 'No user found by that username or ID' );
		}

		if ( groups_unban_member( $user->ID, $group_id ) ) {
			WP_CLI::success( 'Member unbanned from the group.' );
		} else {
			WP_CLI::error( 'Could not unban the member.' );
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
	 *     $ wp bp group invites_from_user --user-id=30
	 *     $ wp bp group invites_from_user --user-id=30 --limit=100 --exclude=100
	 */
	public function invites_from_user( $args, $assoc_args ) {
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
	 * --role=<role>
	 * : Group member role (member, mod, admin).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group invite_list --user-id=30 --group-id=56
	 *     $ wp bp group invite_list --user-id=30 --group-id=100 --role=member
	 */
	public function invite_list( $args, $assoc_args ) {
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
	 *     $ wp bp group invite --user-id=10 --group-id=40
	 *     $ wp bp group invite --user-id=admin --group-id=40 --inviter_id=804
	 *     $ wp bp group invite --user-id=user_login --group-id=60 --silent=1
	 */
	public function invite( $args, $assoc_args ) {
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
	 *     $ wp bp group uninvite --group-id=3 --user-id=10
	 *     $ wp bp group uninvite --group-id=foo --user-id=admin
	 */
	public function uninvite( $args, $assoc_args ) {
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
			WP_CLI::error( 'Could not uninvite the user.' );
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
	 *     $ wp bp group generate_invites --count=50
	 */
	public function generate_invites( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'count' => 100,
		) );

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating random group invitations', $r['count'] );

		for ( $i = 0; $i < $r['count']; $i++ ) {
			$this->invite( array(), array(
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
	 *     $ wp bp group accept_invite --group-id=3 --user-id=10
	 *     $ wp bp group accept_invite --group-id=foo --user-id=admin
	 */
	public function accept_invite( $args, $assoc_args ) {
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
	 *     $ wp bp group reject_invite --group-id=3 --user-id=10
	 *     $ wp bp group reject_invite --group-id=foo --user-id=admin
	 */
	public function reject_invite( $args, $assoc_args ) {
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
	 *     $ wp bp group delete_invite --group-id=3 --user-id=10
	 *     $ wp bp group delete_invite --group-id=foo --user-id=admin
	 */
	public function delete_invite( $args, $assoc_args ) {
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
	 *     $ wp bp group send_invites --group-id=3 --user-id=10
	 *     $ wp bp group send_invites --group-id=foo --user-id=admin
	 */
	public function send_invites( $args, $assoc_args ) {
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

	/**
	 * Group Roles.
	 *
	 * @since 1.5.0
	 *
	 * @return array An array of group roles.
	 */
	protected function group_roles() {
		return array( 'member', 'mod', 'admin' );
	}

	/**
	 * Group Status
	 *
	 * @since 1.5.0
	 *
	 * @return array An array of gruop status.
	 */
	protected function group_status() {
		return array( 'public', 'private', 'hidden' );
	}

	/**
	 * Gets a randon group status.
	 *
	 * @since 1.5.0
	 *
	 * @param  string $status Group status.
	 * @return string Group Status.
	 */
	protected function random_group_status( $status ) {
		$core_status = $this->group_status();

		$status = ( 'mixed' === $status )
			? $core_status[ array_rand( $core_status ) ]
			: $status;

		return $status;
	}

	/**
	 * Check if a group exists.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $group_id Group ID or slug.
	 * @return bool true|false
	 */
	protected function group_exists( $group_id ) {
		// Group ID or slug.
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
