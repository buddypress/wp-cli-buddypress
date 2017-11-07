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
	protected $obj_type = 'group';

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
	 * : Group description.
	 * ---
	 * Default: 'Description for group "[name]"'
	 * ---
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator.
	 * ---
	 * Default: 1
	 * ---
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group.
	 *
	 * [--status=<status>]
	 * : Group status (public, private, hidden).
	 * ---
	 * Default: public
	 * ---
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums.
	 * ---
	 * Default: 0
	 * ---
	 *
	 * [--date-created=<date-created>]
	 * : MySQL-formatted date.
	 * ---
	 * Default: current date.
	 * ---
	 *
	 * [--silent=<silent>]
	 * : Whether to silent the group creation.
	 *
	 * [--porcelain]
	 * : Return only the new group id.
	 *
	 * ---
	 * Default: false.
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group create --name="Totally Cool Group"
	 *     Success: Group (ID 5465) created: https://site.com/group-slug/
	 *
	 *     $ wp bp group create --name="Sports" --description="People who love sports" --creator-id=54 --status=private
	 *     Success: Group (ID 6454)6 created: https://site.com/another-group-slug/
	 *
	 * @alias add
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

		$group_id = groups_create_group( $r );

		if ( ! is_numeric( $group_id ) ) {
			WP_CLI::error( 'Could not create group.' );
		}

		groups_update_groupmeta( $group_id, 'total_member_count', 1 );

		if ( $r['silent'] ) {
			return;
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $group_id );
		} else {
			$group = groups_get_group( array(
				'group_id' => $group_id,
			) );
			$permalink = bp_get_group_permalink( $group );
			WP_CLI::success( sprintf( 'Group (ID %d) created: %s', $group_id, $permalink ) );
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
		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating groups', $assoc_args['count'] );

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
	 *   - json
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group get 500
	 *     $ wp bp group get group-slug
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		$group = groups_get_group( $group_id );
		$group_arr = get_object_vars( $group );
		$group_arr['url'] = bp_get_group_permalink( $group );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $group_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $group_arr );
	}

	/**
	 * Delete a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group delete 500
	 *     Success: Group successfully deleted.
	 *
	 *     $ wp bp group delete group-slug --yes
	 *     Success: Group successfully deleted.
	 */
	public function delete( $args, $assoc_args ) {
		$group_id = $args[0];

		// Check that group exists.
		if ( ! $this->group_exists( $group_id ) ) {
			WP_CLI::error( 'No group found by that slug or ID.' );
		}

		WP_CLI::confirm( 'Are you sure you want to delete this group?', $assoc_args );

		// Delete group. True if deleted.
		if ( groups_delete_group( $group_id ) ) {
			WP_CLI::success( 'Group successfully deleted.' );
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
	 * Post an Activity update affiliated with a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * <user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--content=<content>]
	 * : Activity content text. If none is provided, default text will be generated.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp group post_update 40 50  --content="Content to update"
	 *     Success: Successfully updated with a new activity item (ID #1654).
	 *
	 *     $ wp bp group post_update 49 140
	 *     Success: Successfully updated with a new activity item (ID #54646).
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
			WP_CLI::success( sprintf( 'Successfully updated with a new activity item (ID #%d).', $activity_id ) );
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
	 *   - csv
	 *   - count
	 *   - haml
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
		$groups = groups_get_groups( $query_args );

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $groups['groups'], 'id' ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $groups['total'] );
		} else {
			$formatter->display_items( $groups['groups'] );
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
	 *     Success: Member promoted to new role: admin
	 *
	 *     $ wp bp group promote foo admin mod
	 *     Success: Member promoted to new role: mod
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
	 *     Success: User demoted to the "member" status.
	 *
	 *     $ wp bp group demote foo admin
	 *     Success: User demoted to the "member" status.
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
	 *     Success: Member banned from the group.
	 *
	 *     $ wp bp group ban foo admin
	 *     Success: Member banned from the group.
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
	 *     Success: Member unbanned from the group.
	 *
	 *     $ wp bp group unban foo admin
	 *     Success: Member unbanned from the group.
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
}

WP_CLI::add_command( 'bp group', 'BPCLI_Group', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );
