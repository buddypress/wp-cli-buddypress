<?php

class BPCLI_Activity extends BPCLI_Component {

	/**
	 * Add a member to a group.
	 *
	 * @synopsis --group=<group> --user=<user>
	 */
	public function group_add_member( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'group-id' => null,
			'user-id' => null,
			'role' => 'member',
		) );

		// Convert --group_id to group ID
		// @todo this'll be screwed up if the group has a numeric slug
		if ( ! is_numeric( $r['group-id'] ) ) {
			$group_id = groups_get_id( $r['group-id'] );
		} else {
			$group_id = $r['group-id'];
		}

		// Check that group exists
		$group_obj = groups_get_group( array( 'group_id' => $group_id ) );
		if ( empty( $group_obj->id ) ) {
			WP_CLI::error( 'No group found by that slug or id.' );
		}

		// Convert --user_id to user ID
		// @todo this'll be screwed up if user has a numeric user_login
		// @todo Have to use user-id because WP_CLI hijocks --user
		if ( ! is_numeric( $r['user-id'] ) ) {
			$user_id = (int) username_exists( $r['user-id'] );
		} else {
			$user_id = $r['user-id'];
			$user_obj = new WP_User( $user_id );
			$user_id = $user_obj->ID;
		}

		if ( empty( $user_id ) ) {
			WP_CLI::error( 'No user found by that username or id' );
		}

		// Sanitize role
		if ( ! in_array( $r['role'], array( 'member', 'mod', 'admin' ) ) ) {
			$r['role'] = 'member';
		}

		$joined = groups_join_group( $group_id, $user_id );

		if ( $joined ) {
			if ( 'member' !== $r['role'] ) {
				$the_member = new BP_Groups_Member( $user_id, $group_id );
				$member->promote( $r['role'] );
			}

			$success = sprintf(
				'Added user #%d (%s) to group #%d (%s) as %s',
				$user_id,
				$user_obj->user_login,
				$group_id,
				$group_obj->name,
				$r['role']
			);
			WP_CLI::success( $success );
		} else {
			WP_CLI::error( 'Could not add user to group.' );
		}
	}

	public function check_requirements() {
		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
	}
}
