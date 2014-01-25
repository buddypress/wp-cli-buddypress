<?php

class BPCLI_Activity extends BPCLI_Component {

	/**
	 * Create an activity item.
	 */
	public function activity_create( $args, $assoc_args ) {
		$this->check_requirements();

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
		);

		$r = wp_parse_args( $assoc_args, $defaults );

		// Fill in any missing information
		if ( empty( $r['component'] ) ) {
			$r['component'] = $this->get_random_component();
		}

		if ( empty( $r['type'] ) ) {
			$r['type'] = $this->get_random_type_from_component( $r['component'] );
		}

		// If some data is not set, we have to generate it
		if ( empty( $r['item_id'] ) || empty( $r['secondary_item_id'] ) ) {
			$r = $this->generate_item_details( $r );
		}

print_r( $r );


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

		if ( $id ) {
			WP_CLI::success( sprintf( 'Successfully created new activity item (id #%d)', $id ) );
		} else {
			WP_CLI::error( 'Could not create activity item.' );
		}
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
				'friendship_accepted',
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

				$r['action'] = sprintf( __( '%s posted an update', 'buddypress' ), bp_core_get_userlink( $r['user-id'] ) );
				$r['content'] = $this->generate_random_text();
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

		}

		return $r;
	}

	/**
	 * Generate an action string from values
	 *
	 * @since 1.1
	 *
	 * @return string
	 */
	protected function generate_action( $r ) {

	}

	/**
	 * Generate random text
	 *
	 * @since 1.1
	 */
	protected function generate_random_text() {
		return 'foo';
	}

	public function check_requirements() {
		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
	}
}
