<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Groups.
 *
 * ## EXAMPLES
 *
 *     # Create a public group.
 *     $ wp bp group create --name="Totally Cool Group"
 *     Success: Group (ID 5465) created: http://example.com/groups/totally-cool-group/
 *
 *     # Create a private group.
 *     $ wp bp group create --name="Another Cool Group" --description="Cool Group" --creator-id=54 --status=private
 *     Success: Group (ID 6454)6 created: http://example.com/groups/another-cool-group/
 *
 * @since 1.5.0
 */
class Group extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'name',
		'slug',
		'status',
		'date_created',
	];

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
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	}

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
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group.
	 *
	 * [--status=<status>]
	 * : Group status.
	 * ---
	 * default: public
	 * options:
	 *   - public
	 *   - private
	 *   - hidden
	 * ---
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums.
	 *
	 * [--date-created=<date-created>]
	 * : GMT timestamp, in Y-m-d h:i:s format.
	 *
	 * [--silent]
	 * : Whether to silent the group creation.
	 *
	 * [--porcelain]
	 * : Return only the new group id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a public group.
	 *     $ wp bp group create --name="Totally Cool Group"
	 *     Success: Successfully created new group (ID 5465)
	 *
	 *     # Create a private group.
	 *     $ wp bp group create --name="Another Cool Group" --description="Cool Group" --creator-id=54 --status=private
	 *     Success: Successfully created new group (ID 6454)
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args(
			$assoc_args,
			[
				'name'         => '',
				'slug'         => '',
				'description'  => '',
				'creator-id'   => 1,
				'enable-forum' => 0,
				'date-created' => bp_core_current_time(),
			]
		);

		// Auto-generate slug.
		if ( empty( $r['slug'] ) ) {
			$r['slug'] = groups_check_slug( sanitize_title( $r['name'] ) );
		}

		// Auto-generate description.
		if ( empty( $r['description'] ) ) {
			$r['description'] = sprintf( 'Description for group "%s"', $r['name'] );
		}

		$group_id = groups_create_group(
			[
				'name'         => $r['name'],
				'slug'         => $r['slug'],
				'description'  => $r['description'],
				'creator_id'   => $r['creator-id'],
				'status'       => $r['status'],
				'enable_forum' => $r['enable-forum'],
				'date_created' => $r['date-created'],
			]
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $group_id ) ) {
			WP_CLI::error( 'Could not create group.' );
		}

		groups_update_groupmeta( $group_id, 'total_member_count', 1 );

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $group_id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new group (ID #%d)', $group_id ) );
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
	 * : The status of the generated groups.
	 * ---
	 * default: mixed
	 * options:
	 *   - public
	 *   - private
	 *   - hidden
	 *   - mixed
	 * ---
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator.
	 * ---
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
	 *     # Generate 50 random groups.
	 *     $ wp bp group generate --count=50
	 *     Generating groups  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 groups with mixed status.
	 *     $ wp bp group generate --count=5 --status=mixed
	 *     Generating groups  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 10 hidden groups with a specific creator.
	 *     $ wp bp group generate --count=10 --status=hidden --creator-id=30
	 *     Generating groups  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 random groups and output only the IDs.
	 *     $ wp bp group generate --count=5 --format=ids
	 *     70 71 72 73 74
	 */
	public function generate( $args, $assoc_args ) {
		$creator_id = null;

		if ( isset( $assoc_args['creator-id'] ) ) {
			$user       = $this->get_user_id_from_identifier( $assoc_args['creator-id'] );
			$creator_id = $user->ID;
		}

		$this->generate_callback(
			'Generating groups',
			$assoc_args,
			function ( $assoc_args, $format ) use ( $creator_id ) {

				if ( ! $creator_id ) {
					$creator_id = $this->get_random_user_id();
				}

				$params = [
					'name'       => sprintf( 'Group name - #%d', wp_rand() ),
					'creator-id' => $creator_id,
					'status'     => $this->random_group_status( $assoc_args['status'] ),
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
	 * Get a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>
	 * : Identifier for the group. Can be a numeric ID or the group slug.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get group by ID.
	 *     $ wp bp group get 500
	 *
	 *     # Get group by group slug.
	 *     $ wp bp group get group-slug
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$group_id         = $this->get_group_id_from_identifier( $args[0] );
		$group            = groups_get_group( $group_id );
		$group_arr        = get_object_vars( $group );
		$group_arr['url'] = bp_get_group_url( $group );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $group_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $group_arr );
	}

	/**
	 * Delete a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>...
	 * : ID or IDs of group(s) to delete. Can be a numeric ID or the group slug.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a group.
	 *     $ wp bp group delete 500 --yes
	 *     Success: Deleted group 500.
	 *
	 *     # Delete a group and its metadata.
	 *     $ wp bp group delete group-slug --yes
	 *     Success: Deleted group group-slug.
	 *
	 *     # Delete multiple groups.
	 *     $ wp bp group delete 55654 54564 --yes
	 *     Success: Deleted group 55654.
	 *     Success: Deleted group 54564.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$groups = wp_parse_id_list( $args );

		if ( count( $groups ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these groups and their metadata?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this group and its metadata?', $assoc_args );
		}

		parent::_delete(
			$groups,
			$assoc_args,
			function ( $group_id ) {
				if ( groups_delete_group( $group_id ) ) {
					return [ 'success', sprintf( 'Deleted group %d.', $group_id ) ];
				}

				return [ 'error', sprintf( 'Could not delete group %s.', $group_id ) ];
			}
		);
	}

	/**
	 * Update a group.
	 *
	 * ## OPTIONS
	 *
	 * <group-id>...
	 * : Identifier(s) for the group(s). Can be a numeric ID or the group slug.
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update. See groups_create_group()
	 *
	 * ## EXAMPLES
	 *
	 *     # Update a group.
	 *     $ wp bp group update 35 --description="What a cool group!" --name="Group of Cool People"
	 *     Success: Group updated.
	 */
	public function update( $args, $assoc_args ) {
		parent::_update(
			$args,
			$assoc_args,
			function ( $group_id, $fields = [] ) {
				$fields['group_id'] = $group_id;

				if ( groups_create_group( $fields ) ) {
					return [ 'success', 'Group updated.' ];
				}

				return [ 'error', 'Group could not be updated.' ];
			}
		);
	}

	/**
	 * Get a list of groups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See groups_get_groups()
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--user-id=<user>]
	 * : Limit results to groups of which a specific user is a member. Accepts either a user_login or a numeric ID.
	 *
	 * [--orderby=<orderby>]
	 * : Sort order for results.
	 * ---
	 * default: name
	 * options:
	 *   - name
	 *   - date_created
	 *   - last_activity
	 *   - total_member_count
	 *
	 * [--count=<number>]
	 * : Number of group to list.
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
	 *   - ids
	 *   - count
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each group:
	 *
	 * * id
	 * * name
	 * * slug
	 * * status
	 * * date_created
	 *
	 * ## EXAMPLES
	 *
	 *     # List groups and get the count.
	 *     $ wp bp group list --format=count
	 *     100
	 *
	 *     # List groups and get the IDs.
	 *     $ wp bp group list --format=ids
	 *     70 71 72 73 74
	 *
	 *     # List groups.
	 *     $ wp bp group list
	 *     +----+------------+---------+---------+---------------------+
	 *     | id | name       | slug    | status  | date_created        |
	 *     +----+------------+---------+---------+---------------------+
	 *     | 1  | Group - #0 | group-0 | hidden  | 2022-07-04 02:12:02 |
	 *     | 2  | Group - #1 | group-1 | hidden  | 2022-07-04 02:12:02 |
	 *     | 4  | Group - #3 | group-3 | private | 2022-07-04 02:12:02 |
	 *     | 5  | Group - #4 | group-4 | private | 2022-07-04 02:12:02 |
	 *     | 3  | Group – #2 | group-2 | public  | 2022-07-04 02:12:02 |
	 *     +----+------------+---------+---------+---------------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$query_args = [
			'show_hidden' => true,
			'orderby'     => $assoc_args['orderby'],
			'per_page'    => $assoc_args['count'],
		];

		if ( isset( $assoc_args['user-id'] ) ) {
			$user                  = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
			$query_args['user_id'] = $user->ID;
		}

		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		// If count or ids, no need for group objects.
		if ( in_array( $formatter->format, [ 'ids', 'count' ], true ) ) {
			$query_args['fields'] = 'ids';
		}

		$groups = groups_get_groups( $query_args );

		if ( empty( $groups['groups'] ) ) {
			WP_CLI::error( 'No groups found.' );
		}

		$formatter->display_items( $groups['groups'] );
	}

	/**
	 * Gets a randon group status.
	 *
	 * @since 1.5.0
	 *
	 * @param string $status Group status.
	 * @return string
	 */
	protected function random_group_status( $status ) {
		$core_status = [ 'public', 'private', 'hidden' ];

		if ( 'mixed' === $status ) {
			$status = $core_status[ array_rand( $core_status ) ];
		}

		return $status;
	}
}
