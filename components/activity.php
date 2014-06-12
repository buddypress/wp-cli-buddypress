<?php

/**
 * Manage BuddyPress activity items.
 */
class BPCLI_Activity extends BPCLI_Component {

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
	 * [--user-id=<user-id>]
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
	 * : GMT timestamp, in Y-m-d h:i:s format. Defaults to current time.
	 *
	 * [--hide-sitewide=<hide-sitewide>]
	 * : Whether to hide in sitewide streams. Default: 0.
	 *
	 * [--is-spam=<is-spam>]
	 * : Whether the item should be marked as spam. Default: 0.
	 *
	 * @synopsis [--component=<component>] [--type=<type>] [--action=<action>] [--content=<content>] [--primary-link=<primary-link>] [--user-id=<user-id>] [--item-id=<item-id>] [--secondary-item-id=<secondary-item-id>] [--date-recorded=<date-recorded>] [--hide-sitewide=<hide-sitewide>] [--is-spam=<is-spam>]
	 *
	 * @since 1.1
	 */
	public function create( $args, $assoc_args ) {
		$defaults = array(
			'component' => '',
			'type' => '',
			'action' => '',
			'content' => '',
			'primary-link' => '',
			'user-id' => '',
			'item-id' => '',
			'secondary-item-id' => '',
			'date-recorded' => bp_core_current_time(),
			'hide-sitewide' => 0,
			'is-spam' => 0,
			'silent' => false,
		);

		$r = wp_parse_args( $assoc_args, $defaults );

		// Fill in any missing information
		if ( empty( $r['component'] ) ) {
			$r['component'] = $this->get_random_component();
		}

		if ( empty( $r['type'] ) ) {
			$r['type'] = $this->get_random_type_from_component( $r['component'] );
		}

		if ( $r['component'] == 'groups' ) { 
			// Item ID for groups is a group ID. 
			// Therefore, handle group slugs, too. 
			// Convert --item-id to group ID. 
			// @todo this'll be screwed up if the group has a numeric slug
			if ( $r['item-id'] && ! is_numeric( $r['item-id'] ) ) {
				$r['item-id'] = groups_get_id( $r['item-id'] );
			} 
		} 
					
		// If some data is not set, we have to generate it
		if ( empty( $r['item_id'] ) || empty( $r['secondary_item_id'] ) ) {
			$r = $this->generate_item_details( $r );
		}

		$id = bp_activity_add( array(
			'action' => $r['action'],
			'content' => $r['content'],
			'component' => $r['component'],
			'type' => $r['type'],
			'primary_link' => $r['primary-link'],
			'user_id' => $r['user-id'],
			'item_id' => $r['item-id'],
			'secondary_item_id' => $r['secondary-item-id'],
			'date_recorded' => $r['date-recorded'],
			'hide_sitewide' => (bool) $r['hide-sitewide'],
			'is_spam' => (bool) $r['is-spam'],
		) );

		if ( $r['silent'] ) {
			return;
		}

		if ( $id ) {
			WP_CLI::success( sprintf( 'Successfully created new activity item (id #%d)', $id ) );
		} else {
			WP_CLI::error( 'Could not create activity item.' );
		}
	}

	/**
	 * Generate random activity items.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many activity items to generate. Default: 100
	 *
	 * [--skip-activity-comments=<skip-activity-comments>
	 * : Whether to skip activity comments. Recording activity_comment
	 * items requires a resource-intensive tree rebuild. Default: 1
	 *
	 * @synopsis [--count=<number>] [--skip-activity-comments=<skip-activity-comments>]
	 */
	public function generate( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'count' => 100,
			'skip-activity-comments' => 1,
		) );

		$component = $this->get_random_component();
		$type = $this->get_random_type_from_component( $component );

		if ( (bool) $r['skip-activity-comments'] && 'activity_comment' === $type ) {
			$type = 'activity_update';
		}

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating activity items', $r['count'] );

		for ( $i = 0; $i < $r['count']; $i++ ) {
			$this->create( array(), array(
				'component' => $component,
				'type' => $type,
				'silent' => true,
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Pull up a random active component for use in activity items.
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	protected function get_random_component() {
		$c = buddypress()->active_components;

		// Core components that accept activity items
		$ca = $this->get_components_and_actions();

		return array_rand( array_flip( array_intersect( array_keys( $c ), array_keys( $ca ) ) ) );
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
	 * Get a list of activity components and actions
	 *
	 * @since 1.1
	 *
	 * @return array
	 */
	protected function get_components_and_actions() {
		return array(
			'activity' => array(
				'activity_update',
				'activity_comment',
			),
			'blogs' => array(
				'new_blog',
				'new_blog_post',
				'new_blog_comment',
			),
			'friends' => array(
				'friendship_created',
			),
			'groups' => array(
				'joined_group',
				'created_group',
			),
			'profile' => array(
				'new_avatar',
				'new_member',
				'updated_profile',
			),
		);
	}

	/**
	 * Generate item details.
	 *
	 * @since 1.1
	 */
	protected function generate_item_details( $r ) {
		global $wpdb, $bp;

		switch ( $r['type'] ) {
			case 'activity_update' :
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				// Make group updates look more like actual group updates. 
				// i.e. give them links to their groups. 
				if ( $r['component'] == 'groups' ) { 

					if ( empty ( $r['item-id'] ) ) { 
						WP_CLI::error( 'No group found by that id.' );
					} 

					// get the group
					$group_obj = groups_get_group( array( 'group_id' => $r['item-id'] ) );

					// make sure such a group exists
					if ( empty( $group_obj->id ) ) {
						WP_CLI::error( 'No group found by that slug or id.' );
					}

					// stolen from groups_join_group
					$r['action']  = sprintf( __( '%1$s posted an update in the group %2$s', 'buddypress'), bp_core_get_userlink( $r['user-id'] ), '<a href="' . bp_get_group_permalink( $group_obj ) . '">' . esc_attr( $group_obj->name ) . '</a>' );
				} else { 
					// old way, for some other kind of update
					$r['action'] = sprintf( __( '%s posted an update', 'buddypress' ), bp_core_get_userlink( $r['user-id'] ) );
				} 
				if ( empty( $r['content'] ) ) {
					$r['content'] = $this->generate_random_text();
				} 

				$r['primary-link'] = bp_core_get_userlink( $r['user-id'] );

				break;

			case 'activity_comment' :
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				$parent_item = $wpdb->get_row( "SELECT * FROM {$bp->activity->table_name} ORDER BY RAND() LIMIT 1" );

				if ( 'activity_comment' == $parent_item->type ) {
					$r['item-id'] = $parent_item->id;
					$r['secondary-item-id'] = $parent_item->secondary_item_id;
				} else {
					$r['item-id'] = $parent_item->id;
				}

				$r['action'] = sprintf( __( '%s posted a new activity comment', 'buddypress' ), bp_core_get_userlink( $r['user-id'] ) );
				$r['content'] = $this->generate_random_text();
				$r['primary-link'] = bp_core_get_userlink( $r['user-id'] );

				break;

			case 'new_blog' :
			case 'new_blog_post' :
			case 'new_blog_comment' :
				if ( ! bp_is_active( 'blogs' ) ) {
					return $r;
				}

				if ( is_multisite() ) {
					$r['item-id'] = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} ORDER BY RAND() LIMIT 1" );
				} else {
					$r['item-id'] = 1;
				}

				// Need blog content for posts/comments
				if ( 'new_blog_post' === $r['type'] || 'new_blog_comment' === $r['type'] ) {

					if ( is_multisite() ) {
						switch_to_blog( $r['item-id'] );
					}

					$comment_info = $wpdb->get_results( "SELECT comment_id, comment_post_id FROM {$wpdb->comments} ORDER BY RAND() LIMIT 1" );
					$comment_id = $comment_info[0]->comment_id;
					$comment = get_comment( $comment_id );

					$post_id = $comment_info[0]->comment_post_id;
					$post = get_post( $post_id );

					if ( is_multisite() ) {
						restore_current_blog();
					}
				}

				// new_blog
				if ( 'new_blog' === $r['type'] ) {
					if ( '' === $r['user-id'] ) {
						$r['user-id'] = $this->get_random_user_id();
					}

					if ( ! $r['action'] ) {
						$r['action'] = sprintf( __( '%s created the site %s', 'buddypress'), bp_core_get_userlink( $r['user-id'] ), '<a href="' . get_home_url( $r['item-id'] ) . '">' . esc_attr( get_blog_option( $r['item-id'], 'blogname' ) ) . '</a>' );
					}

					if ( ! $r['primary-link'] ) {
						$r['primary-link'] = get_home_url( $r['item-id'] );
					}

				// new_blog_post
				} else if ( 'new_blog_post' === $r['type'] ) {
					if ( '' === $r['user-id'] ) {
						$r['user-id'] = $post->post_author;
					}

					if ( '' === $r['primary-link'] ) {
						$r['primary-link'] = add_query_arg( 'p', $post->ID, trailingslashit( get_home_url( $r['item-id'] ) ) );
					}

					if ( '' === $r['action'] ) {
						$r['action'] = sprintf( __( '%1$s wrote a new post, %2$s', 'buddypress' ), bp_core_get_userlink( (int) $post->post_author ), '<a href="' . $r['primary-link'] . '">' . $post->post_title . '</a>' );
					}

					if ( '' === $r['content'] ) {
						$r['content'] = $post->post_content;
					}

					if ( '' === $r['secondary-item-id'] ) {
						$r['secondary-item-id'] = $post->ID;
					}

				// new_blog_comment
				} else {
					// groan - have to fake this
					if ( '' === $r['user-id'] ) {
						$user = get_user_by( 'email', $comment->comment_author_email );
						if ( empty( $user ) ) {
							$r['user-id'] = $this->get_random_user_id();
						} else {
							$r['user-id'] = $user->ID;
						}
					}

					$post_permalink = get_permalink( $comment->comment_post_ID );
					$comment_link   = get_comment_link( $comment->comment_ID );

					if ( '' === $r['primary-link'] ) {
						$r['primary-link'] = $comment_link;
					}

					if ( '' === $r['action'] ) {
						$r['action'] = sprintf( __( '%1$s commented on the post, %2$s', 'buddypress' ), bp_core_get_userlink( $r['user-id'] ), '<a href="' . $post_permalink . '">' . apply_filters( 'the_title', $post->post_title ) . '</a>' );
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

			case 'friendship_created' :
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				if ( empty( $r['item-id'] ) ) {
					$r['item-id'] = $this->get_random_user_id();
				}

				$r['action'] = sprintf( __( '%1$s and %2$s are now friends', 'buddypress' ), bp_core_get_userlink( $r['user-id'] ), bp_core_get_userlink( $r['item-id'] ) );

				break;

			case 'created_group' :
				if ( empty( $r['item-id'] ) ) {
					$r['item-id'] = $this->get_random_group_id();
				}

				$group = groups_get_group( array( 'group_id' => $r['item-id'] ) );

				// @todo what if it's not a group? ugh
				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $group->creator_id;
				}

				$group_permalink = bp_get_group_permalink( $group );

				if ( empty( $r['action'] ) ) {
					$r['action'] = sprintf( __( '%1$s created the group %2$s', 'buddypress'), bp_core_get_userlink( $r['user-id'] ), '<a href="' . $group_permalink . '">' . esc_attr( $group->name ) . '</a>' );
				}

				if ( empty( $r['primary-link'] ) ) {
					$r['primary-link'] = $group_permalink;
				}

				break;

			case 'joined_group' :

				if ( empty( $r['item-id'] ) ) {
					$r['item-id'] = $this->get_random_group_id();
				}

				$group = groups_get_group( array( 'group_id' => $r['item-id'] ) );

				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				if ( empty( $r['action'] ) ) {
					$r['action'] = sprintf( __( '%1$s joined the group %2$s', 'buddypress' ), bp_core_get_userlink( $r['user-id'] ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );
				}

				if ( empty( $r['primary-link'] ) ) {
					$r['primary-link'] = bp_get_group_permalink( $group );
				}

				break;

			case 'new_avatar' :
			case 'new_member' :
			case 'updated_profile' :

				if ( empty( $r['user-id'] ) ) {
					$r['user-id'] = $this->get_random_user_id();
				}

				$userlink = bp_core_get_userlink( $r['user-id'] );

				// new_avatar
				if ( 'new_avatar' === $r['type'] ) {
					$r['action'] = sprintf( __( '%s changed their profile picture', 'buddypress' ), $userlink );

				// new_member
				} else if ( 'new_member' === $r['type'] ) {
					$r['action'] = sprintf( __( '%s became a registered member', 'buddypress' ), $userlink );

				// updated_profile
				} else {
					$r['action'] = sprintf( __( '%s updated their profile', 'buddypress' ), $userlink );
				}

				break;
		}

		return $r;
	}

	/**
	 * Generate random text
	 *
	 * @todo
	 *
	 * @since 1.1
	 */
	protected function generate_random_text() {
		return 'Here is some random text';
	}
}

WP_CLI::add_command( 'bp activity', 'BPCLI_Activity', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
} ) );

