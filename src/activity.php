<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Activities.
 *
 * ## EXAMPLES
 *
 *     # Create an activity marked as spam.
 *     $ wp bp activity create --is-spam=1
 *     Success: Successfully created new activity item (ID #5464)
 *
 *     # Create an activity in a group.
 *     $ wp bp activity add --component=groups --item-id=2 --user-id=10
 *     Success: Successfully created new activity item (ID #48949)
 *
 * @since 1.5.0
 */
class Activity extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'user_id',
		'component',
		'type',
		'action',
		'item_id',
		'primary_link',
		'secondary_item_id',
		'date_recorded',
		'hide_sitewide',
		'is_spam',
	];

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
	}

	/**
	 * Create an activity item.
	 *
	 * ## OPTIONS
	 *
	 * [--component=<component>]
	 * : The component for the activity item (groups, activity, etc). If
	 * none is provided, a component will be randomly selected from the
	 * active components.
	 *
	 * [--type=<type>]
	 * : Activity type (activity_update, group_created, etc). If none is
	 * provided, a type will be randomly chose from those natively
	 * associated with your <component>.
	 *
	 * [--action=<action>]
	 * : Action text (eg "Joe created a new group Foo"). If none is
	 * provided, one will be generated automatically based on other params.
	 *
	 * [--content=<content>]
	 * : Activity content text. If none is provided, default text will be
	 * generated.
	 *
	 * [--primary-link=<primary-link>]
	 * : URL of the item, as used in RSS feeds. If none is provided, a URL
	 * will be generated based on passed parameters.
	 *
	 * [--user-id=<user>]
	 * : ID of the user associated with the new item. If none is provided,
	 * a user will be randomly selected.
	 *
	 * [--item-id=<item-id>]
	 * : ID of the associated item. If none is provided, one will be
	 * generated automatically, if your activity type requires it.
	 *
	 * [--secondary-item-id=<secondary-item-id>]
	 * : ID of the secondary associated item. If none is provided, one will
	 * be generated automatically, if your activity type requires it.
	 *
	 * [--date-recorded=<date-recorded>]
	 * : GMT timestamp, in Y-m-d h:i:s format.
	 *
	 * [--hide-sitewide=<hide-sitewide>]
	 * : Whether to hide in sitewide streams.
	 *
	 * [--is-spam=<is-spam>]
	 * : Whether the item should be marked as spam.
	 *
	 * [--silent]
	 * : Whether to silent the activity creation.
	 *
	 * [--porcelain]
	 * : Output only the new activity id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create an activity marked as spam.
	 *     $ wp bp activity create --is-spam=1
	 *     Success: Successfully created new activity item (ID #5464)
	 *
	 *     # Create an activity.
	 *     $ wp bp activity add --component=groups --item-id=564 --user-id=10
	 *     Success: Successfully created new activity item (ID #48949)
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args(
			$assoc_args,
			[
				'component'         => '',
				'type'              => '',
				'action'            => '',
				'content'           => '',
				'primary-link'      => '',
				'user-id'           => '',
				'item-id'           => '',
				'secondary-item-id' => '',
				'date-recorded'     => bp_core_current_time(),
				'hide-sitewide'     => 0,
				'is-spam'           => 0,
			]
		);

		// Fill in any missing information.
		if ( empty( $r['component'] ) ) {
			$r['component'] = $this->get_random_component();
		}

		if ( empty( $r['type'] ) ) {
			$r['type'] = $this->get_random_type_from_component( $r['component'] );
		}

		if ( 'groups' === $r['component'] ) {
			$r['item-id'] = $this->get_group_id_from_identifier( $r['item-id'] );
		}

		// If some data is not set, we have to generate it.
		if ( empty( $r['item-id'] ) || empty( $r['secondary-item-id'] ) ) {
			$r = $this->generate_item_details( $r );
		}

		$activity_id = bp_activity_add(
			[
				'action'            => $r['action'],
				'content'           => $r['content'],
				'component'         => $r['component'],
				'type'              => $r['type'],
				'primary_link'      => $r['primary-link'],
				'user_id'           => $r['user-id'],
				'item_id'           => $r['item-id'],
				'secondary_item_id' => $r['secondary-item-id'],
				'date_recorded'     => $r['date-recorded'],
				'hide_sitewide'     => (bool) $r['hide-sitewide'],
				'is_spam'           => (bool) $r['is-spam'],
			]
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Could not create activity item.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $activity_id );
		} else {
			WP_CLI::success( sprintf( 'Successfully created new activity item (ID #%d)', $activity_id ) );
		}
	}

	/**
	 * Retrieve a list of activities.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass to \BP_Activity_Activity::get()
	 *
	 * [--user-id=<user>]
	 * : Limit activities to a specific user id. Accepts a numeric ID.
	 *
	 * [--component=<component>]
	 * : Limit activities to a specific or certain components.
	 *
	 * [--type=<type>]
	 * : Type of the activity. Ex.: activity_update, profile_updated.
	 *
	 * [--primary-id=<primary-id>]
	 * : Object ID to filter the activities. Ex.: group_id or forum_id or blog_id, etc.
	 *
	 * [--secondary-id=<secondary-id>]
	 * : Secondary object ID to filter the activities. Ex.: a post_id.
	 *
	 * [--count=<number>]
	 * : How many activities to list.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
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
	 * These fields will be displayed by default for each activity:
	 *
	 * * id
	 * * user_id
	 * * component
	 * * type
	 * * action
	 * * content
	 * * item_id
	 * * secondary_item_id
	 * * primary_link
	 * * date_recorded
	 * * is_spam
	 * * user_email
	 * * user_nicename
	 * * user_login
	 * * display_name
	 * * user_fullname
	 *
	 * ## EXAMPLES
	 *
	 *     # List activities and get the count.
	 *     $ wp bp activity list --format=count
	 *     100
	 *
	 *     # List activities and get the IDs.
	 *     $ wp bp activity list --format=ids
	 *     70 71 72 73 74
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$r = wp_parse_args(
			$assoc_args,
			[
				'page'        => 1,
				'count'       => 50,
				'show_hidden' => true,
				'filter'      => false,
			]
		);

		// Activities to list.
		$r['per_page'] = $r['count'];

		if ( isset( $assoc_args['user-id'] ) && is_numeric( $assoc_args['user-id'] ) ) {
			$r['filter']['user_id'] = $assoc_args['user-id'];
		}

		if ( isset( $assoc_args['component'] ) ) {
			$r['filter']['object'] = $assoc_args['component'];
		}

		if ( isset( $assoc_args['type'] ) ) {
			$r['filter']['action'] = $assoc_args['type'];
		}

		if ( isset( $assoc_args['primary-id'] ) ) {
			$r['filter']['primary_id'] = $assoc_args['primary-id'];
		}

		if ( isset( $assoc_args['secondary-id'] ) ) {
			$r['filter']['secondary_id'] = $assoc_args['secondary-id'];
		}

		$r = self::process_csv_arguments_to_arrays( $r );

		// If count or ids, no need for activity objects.
		if ( in_array( $formatter->format, [ 'ids', 'count' ], true ) ) {
			$r['fields'] = 'ids';
		}

		$activities = bp_activity_get( $r );

		if ( empty( $activities['activities'] ) ) {
			WP_CLI::error( 'No activities found.' );
		}

		$formatter->display_items( $activities['activities'] );
	}

	/**
	 * Generate random activity items.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many activities to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--skip-activity-comments=<skip-activity-comments>]
	 * : Whether to skip activity comments. Recording activity_comment
	 * items requires a resource-intensive tree rebuild.
	 * ---
	 * default: 1
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
	 *     # Generate 5 activity items.
	 *     $ wp bp activity generate --count=5
	 *     Generating activities  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 activity items and output only the IDs.
	 *     $ wp bp activity generate --count=5 --format=ids
	 *     70 71 72 73 74
	 */
	public function generate( $args, $assoc_args ) {
		$this->generate_callback(
			'Generating activities',
			$assoc_args,
			function ( $assoc_args, $format ) {
				$component = $this->get_random_component();
				$type      = $this->get_random_type_from_component( $component );

				if ( (bool) $assoc_args['skip-activity-comments'] && 'activity_comment' === $type ) {
					$type = 'activity_update';
				}

				$params = [
					'component' => $component,
					'type'      => $type,
					'content'   => $this->generate_random_text(),
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
	 * Fetch specific activity.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : Identifier for the activity.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 *  ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     # Get activity by ID.
	 *     $ wp bp activity get 58
	 *     +-------------------+----------------------------------------------------------------------------------------------+
	 *     | Field             | Value                                                                                        |
	 *     +-------------------+----------------------------------------------------------------------------------------------+
	 *     | id                | 58                                                                                           |
	 *     | user_id           | 7                                                                                            |
	 *     | component         | xprofile                                                                                     |
	 *     | type              | updated_profile                                                                              |
	 *     | action            | <a href="https://wp.test/members/user_1_4/profile/">User 4</a>&#039;s profile was updated    |
	 *     | content           | Here is some random text                                                                     |
	 *     | primary_link      |                                                                                              |
	 *     | item_id           | 0                                                                                            |
	 *     | secondary_item_id | 0                                                                                            |
	 *     | date_recorded     | 2024-02-08 01:53:59                                                                          |
	 *     | hide_sitewide     | 0                                                                                            |
	 *     | mptt_left         | 0                                                                                            |
	 *     | mptt_right        | 0                                                                                            |
	 *     | is_spam           | 0                                                                                            |
	 *     | user_email        |                                                                                              |
	 *     | user_nicename     | user_1_4                                                                                     |
	 *     | user_login        | user_1_4                                                                                     |
	 *     | display_name      | User 4                                                                                       |
	 *     | user_fullname     | User 4                                                                                       |
	 *     | children          | []                                                                                           |
	 *     | url               | https://wp.test/activity/p/58/                                                               |
	 *     +-------------------+----------------------------------------------------------------------------------------------+
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$activity_id = $args[0];

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Please provide a numeric activity ID.' );
		}

		$activity = bp_activity_get_specific(
			[
				'activity_ids'     => $activity_id,
				'spam'             => null,
				'display_comments' => true,
			]
		);

		if ( ! isset( $activity['activities'][0] ) || ! is_object( $activity['activities'][0] ) ) {
			WP_CLI::error( 'No activity found.' );
		}

		$activity_arr        = get_object_vars( $activity['activities'][0] );
		$activity_arr['url'] = bp_activity_get_permalink( $activity_id );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $activity_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $activity_arr );
	}

	/**
	 * Delete an activity.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>...
	 * : ID or IDs of activities to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete an activity.
	 *     $ wp bp activity delete 958695 --yes
	 *     Success: Deleted activity 958695.
	 *
	 *     # Delete multiple activities.
	 *     $ wp bp activity delete 500 600 --yes
	 *     Success: Deleted activity 500.
	 *     Success: Deleted activity 600.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$activities = wp_parse_id_list( $args );

		if ( count( $activities ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these activities?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this activity?', $assoc_args );
		}

		parent::_delete(
			$activities,
			$assoc_args,
			function ( $activity_id ) {
				$id = $this->get_activity_id_from_identifier( $activity_id );

				if ( ! $id ) {
					return [ 'error', sprintf( 'No activity found by ID %d.', $activity_id ) ];
				}

				if ( bp_activity_delete( [ 'id' => $id ] ) ) {
					return [ 'success', sprintf( 'Deleted activity %d.', $activity_id ) ];
				}

				return [ 'error', sprintf( 'Could not delete the activity %d.', $activity_id ) ];
			}
		);
	}

	/**
	 * Spam an activity.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : Identifier for the activity.
	 *
	 * ## EXAMPLES
	 *
	 *     # Spam an activity.
	 *     $ wp bp activity spam 500
	 *     Success: Activity marked as spam.
	 *
	 *     # Spam an activity.
	 *     $ wp bp activity unham 165165
	 *     Success: Activity marked as spam.
	 *
	 * @alias unham
	 */
	public function spam( $args ) {
		$activity_id = $args[0];

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Please provide a numeric activity ID.' );
		}

		$activity = $this->get_activity_id_from_identifier( $activity_id, true );

		// Mark as spam.
		bp_activity_mark_as_spam( $activity );

		if ( $activity->save() ) {
			WP_CLI::success( 'Activity marked as spam.' );
		} else {
			WP_CLI::error( 'Could not mark the activity as spam.' );
		}
	}

	/**
	 * Ham an activity.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : Identifier for the activity.
	 *
	 * ## EXAMPLES
	 *
	 *     # Mark an activity as ham.
	 *     $ wp bp activity ham 500
	 *     Success: Activity marked as ham.
	 *
	 *     # Mark an activity as ham.
	 *     $ wp bp activity unspam 4679
	 *     Success: Activity marked as ham.
	 *
	 * @alias unspam
	 */
	public function ham( $args ) {
		$activity_id = $args[0];

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Please provide a numeric activity ID.' );
		}

		$activity = $this->get_activity_id_from_identifier( $activity_id, true );

		// Mark as ham.
		bp_activity_mark_as_ham( $activity );

		if ( $activity->save() ) {
			WP_CLI::success( 'Activity marked as ham.' );
		} else {
			WP_CLI::error( 'Could not mark the activity as ham.' );
		}
	}

	/**
	 * Post an activity update.
	 *
	 * ## OPTIONS
	 *
	 * --user-id=<user>
	 * : ID of the user.
	 *
	 * --content=<content>
	 * : Activity content text.
	 *
	 * [--silent]
	 * : Whether to silent the activity update.
	 *
	 * [--porcelain]
	 * : Output only the new activity id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Post an activity update.
	 *     $ wp bp activity post-update --user-id=50 --content="Content to update"
	 *     Success: Successfully updated with a new activity item (ID #13165)
	 *
	 *     # Post an activity update.
	 *     $ wp bp activity post-update --user-id=140
	 *     Success: Successfully updated with a new activity item (ID #4548)
	 *
	 * @alias post-update
	 */
	public function post_update( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		// Post the activity update.
		$activity_id = bp_activity_post_update(
			[
				'content' => $assoc_args['content'],
				'user_id' => $user->ID,
			]
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		// Activity ID returned on success update.
		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Could not post the activity update.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $activity_id );
		} else {
			WP_CLI::success( sprintf( 'Successfully updated with a new activity item (ID #%d)', $activity_id ) );
		}
	}

	/**
	 * Add an activity comment.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : ID of the activity to add the comment.
	 *
	 * --user-id=<user>
	 * : ID of the user. If none is provided, a user will be randomly selected.
	 *
	 * --content=<content>
	 * : Activity content text. If none is provided, default text will be generated.
	 *
	 * [--skip-notification]
	 * : Whether to skip notification.
	 *
	 * [--silent]
	 * : Whether to silent the activity comment.
	 *
	 * [--porcelain]
	 * : Output only the new activity comment id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Add an activity comment.
	 *     $ wp bp activity comment 560 --user-id=50 --content="New activity comment"
	 *     Success: Successfully added a new activity comment (ID #4645)
	 *
	 *     # Add an activity comment, skipping notification.
	 *     $ wp bp activity comment 459 --user-id=140 --skip-notification=1
	 *     Success: Successfully added a new activity comment (ID #494)
	 */
	public function comment( $args, $assoc_args ) {
		$activity_id       = $this->get_activity_id_from_identifier( $args[0] );
		$user              = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$skip_notification = WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-notification' );

		// Add activity comment.
		$activity_comment_id = bp_activity_new_comment( [
			'content'           => $assoc_args['content'],
			'user_id'           => $user->ID,
			'activity_id'       => $activity_id,
			'skip_notification' => $skip_notification,
		] );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		// Activity Comment ID returned on success.
		if ( ! is_numeric( $activity_comment_id ) ) {
			WP_CLI::error( 'Could not post a new activity comment.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $activity_comment_id );
		} else {
			WP_CLI::success( sprintf( 'Successfully added a new activity comment (ID #%d)', $activity_comment_id ) );
		}
	}

	/**
	 * Delete an activity comment.
	 *
	 * ## OPTIONS
	 *
	 * <activity-id>
	 * : Identifier for the activity.
	 *
	 * --comment-id=<comment-id>
	 * : ID of the comment to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete an activity comment.
	 *     $ wp bp activity delete-comment 100 --comment-id=500 --yes
	 *     Success: Activity comment deleted.
	 *
	 *     # Delete an activity comment.
	 *     $ wp bp activity delete-comment 165 --comment-id=35435 --yes
	 *     Success: Activity comment deleted.
	 *
	 * @alias remove-comment
	 * @alias delete-comment
	 */
	public function delete_comment( $args, $assoc_args ) {
		$activity_id = $args[0];

		if ( ! is_numeric( $activity_id ) ) {
			WP_CLI::error( 'Please provide a numeric activity ID.' );
		}

		WP_CLI::confirm( 'Are you sure you want to delete this activity comment?', $assoc_args );

		$activity_id = $this->get_activity_id_from_identifier( $activity_id );

		// Delete Comment. True if deleted.
		if ( bp_activity_delete_comment( $activity_id, $assoc_args['comment-id'] ) ) {
			WP_CLI::success( 'Activity comment deleted.' );
		} else {
			WP_CLI::error( 'Could not delete the activity comment.' );
		}
	}

	/**
	 * Get a random type from a component.
	 *
	 * @since 1.1
	 *
	 * @param string $component Component name.
	 * @return string
	 */
	protected function get_random_type_from_component( $component ) {
		$ca = $this->get_components_and_actions();
		return array_rand( array_flip( $ca[ $component ] ) );
	}

	/**
	 * Generate item details.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @since 1.1
	 *
	 * @param array $r Params.
	 * @return array
	 */
	protected function generate_item_details( $r ) {
		global $wpdb;

		$bp = buddypress();

		switch ( $r['type'] ) {
			case 'activity_update':
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				// Make group updates look more like actual group updates.
				// i.e. give them links to their groups.
				if ( 'groups' === $r['component'] ) {

					if ( empty( $r['item-id'] ) ) {
						WP_CLI::error( 'No group found by that ID.' );
					}

					// get the group.
					$group_obj = groups_get_group( [
						'group_id' => $r['item-id'],
					] );

					// make sure such a group exists.
					if ( empty( $group_obj->id ) ) {
						WP_CLI::error( 'No group found by that slug or id.' );
					}

					// stolen from groups_join_group.
					$r['action'] = sprintf( '%1$s posted an update in the group %2$s', bp_core_get_userlink( $r['user-id'] ), '<a href="' . bp_get_group_url( $group_obj ) . '">' . esc_attr( $group_obj->name ) . '</a>' );
				} else {
					// old way, for some other kind of update.
					$r['action'] = sprintf( '%s posted an update', bp_core_get_userlink( $r['user-id'] ) );
				}
				if ( empty( $r['content'] ) ) {
					$r['content'] = $this->generate_random_text();
				}

				$r['primary-link'] = bp_core_get_userlink( $r['user-id'] );

				break;

			case 'activity_comment':
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				$parent_item = $wpdb->get_row( "SELECT * FROM {$bp->activity->table_name} ORDER BY RAND() LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				if ( \is_object( $parent_item ) ) {
					if ( 'activity_comment' === $parent_item->type ) {
						$r['item-id']           = $parent_item->id;
						$r['secondary-item-id'] = $parent_item->secondary_item_id;
					} else {
						$r['item-id'] = $parent_item->id;
					}
				}

				$r['action']       = sprintf( '%s posted a new activity comment', bp_core_get_userlink( $r['user-id'] ) );
				$r['content']      = $this->generate_random_text();
				$r['primary-link'] = bp_core_get_userlink( $r['user-id'] );

				break;

			case 'new_blog':
			case 'new_blog_post':
			case 'new_blog_comment':
				if ( ! bp_is_active( 'blogs' ) ) {
					return $r;
				}

				if ( is_multisite() ) {
					$r['item-id'] = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} ORDER BY RAND() LIMIT 1" );
				} else {
					$r['item-id'] = 1;
				}

				// Need blog content for posts/comments.
				if ( 'new_blog_post' === $r['type'] || 'new_blog_comment' === $r['type'] ) {

					if ( is_multisite() ) {
						switch_to_blog( $r['item-id'] );
					}

					$comment_info = $wpdb->get_results( "SELECT comment_id, comment_post_id FROM {$wpdb->comments} ORDER BY RAND() LIMIT 1" );
					$comment_id   = $comment_info[0]->comment_id;
					$comment      = get_comment( $comment_id );

					$post_id = $comment_info[0]->comment_post_id;
					$post    = get_post( $post_id );

					if ( is_multisite() ) {
						restore_current_blog();
					}
				}

				// new_blog.
				if ( 'new_blog' === $r['type'] ) {
					if ( '' === $r['user-id'] ) {
						$r['user-id'] = $this->get_random_user_id();
					}

					if ( ! $r['action'] ) {
						$r['action'] = sprintf( '%s created the site %s', bp_core_get_userlink( $r['user-id'] ), '<a href="' . get_home_url( $r['item-id'] ) . '">' . esc_attr( get_blog_option( $r['item-id'], 'blogname' ) ) . '</a>' );
					}

					if ( ! $r['primary-link'] ) {
						$r['primary-link'] = get_home_url( $r['item-id'] );
					}

				// new_blog_post.
				} elseif ( 'new_blog_post' === $r['type'] ) {
					if ( '' === $r['user-id'] ) {
						$r['user-id'] = $post->post_author;
					}

					if ( '' === $r['primary-link'] ) {
						$r['primary-link'] = add_query_arg( 'p', $post->ID, trailingslashit( get_home_url( $r['item-id'] ) ) );
					}

					if ( '' === $r['action'] ) {
						$r['action'] = sprintf( '%1$s wrote a new post, %2$s', bp_core_get_userlink( (int) $post->post_author ), '<a href="' . $r['primary-link'] . '">' . $post->post_title . '</a>' );
					}

					if ( '' === $r['content'] ) {
						$r['content'] = $post->post_content;
					}

					if ( '' === $r['secondary-item-id'] ) {
						$r['secondary-item-id'] = $post->ID;
					}

				// new_blog_comment.
				} else {
					// groan - have to fake this.
					if ( '' === $r['user-id'] ) {
						$user         = get_user_by( 'email', $comment->comment_author_email );
						$r['user-id'] = ( empty( $user ) )
							? $this->get_random_user_id()
							: $user->ID;
					}

					$post_permalink = get_permalink( $comment->comment_post_ID );
					$comment_link   = get_comment_link( $comment->comment_ID );

					if ( '' === $r['primary-link'] ) {
						$r['primary-link'] = $comment_link;
					}

					if ( '' === $r['action'] ) {
						$r['action'] = sprintf( '%1$s commented on the post, %2$s', bp_core_get_userlink( $r['user-id'] ), '<a href="' . $post_permalink . '">' . apply_filters( 'the_title', $post->post_title ) . '</a>' );
					}

					if ( '' === $r['content'] ) {
						$r['content'] = $comment->comment_content;
					}

					if ( '' === $r['secondary-item-id'] ) {
						$r['secondary-item-id'] = $comment->ID;
					}
				}

				$r['content'] = '';

				break;

			case 'friendship_created':
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				if ( empty( $r['item-id'] ) ) {
					$r['item-id'] = $this->get_random_user_id();
				}

				$r['action'] = sprintf( '%1$s and %2$s are now friends', bp_core_get_userlink( $r['user-id'] ), bp_core_get_userlink( $r['item-id'] ) );

				break;

			case 'created_group':
				if ( empty( $r['item-id'] ) ) {
					$random_group = \BP_Groups_Group::get_random( 1, 1 );
					$r['item-id'] = $random_group['groups'][0]->slug;
				}

				$group = groups_get_group( [
					'group_id' => $r['item-id'],
				] );

				// @todo what if it's not a group? ugh
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $group->creator_id;
				}

				$group_permalink = bp_get_group_url( $group );

				if ( empty( $r['action'] ) ) {
					$r['action'] = sprintf( '%1$s created the group %2$s', bp_core_get_userlink( $r['user-id'] ), '<a href="' . $group_permalink . '">' . esc_attr( $group->name ) . '</a>' );
				}

				if ( empty( $r['primary-link'] ) ) {
					$r['primary-link'] = $group_permalink;
				}

				break;

			case 'joined_group':
				if ( empty( $r['item-id'] ) ) {
					$random_group = \BP_Groups_Group::get_random( 1, 1 );
					if ( ! empty( $random_group['groups'][0]->slug ) ) {
						$r['item-id'] = $random_group['groups'][0]->slug;
					}
				}

				$group = groups_get_group( [
					'group_id' => $r['item-id'],
				] );

				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				if ( empty( $r['action'] ) ) {
					$r['action'] = sprintf( '%1$s joined the group %2$s', bp_core_get_userlink( $r['user-id'] ), '<a href="' . bp_get_group_url( $group ) . '">' . esc_attr( $group->name ) . '</a>' );
				}

				if ( empty( $r['primary-link'] ) ) {
					$r['primary-link'] = bp_get_group_url( $group );
				}

				break;

			case 'new_avatar':
			case 'new_member':
			case 'updated_profile':
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				$userlink = bp_core_get_userlink( $r['user-id'] );

					// new_avatar.
				if ( 'new_avatar' === $r['type'] ) {
					$r['action'] = sprintf( '%s changed their profile picture', $userlink );

					// new_member.
				} elseif ( 'new_member' === $r['type'] ) {
					$r['action'] = sprintf( '%s became a registered member', $userlink );

					// updated_profile.
				} else {
					$r['action'] = sprintf( '%s updated their profile', $userlink );
				}

				break;
		}

		return $r;
	}
}
