<?php

namespace Buddypress\CLI\Command;

use WP_CLI\Fetchers\Base;

/**
 * Fetch a BuddyPress group based on one of its attributes.
 */
class Group_Fetcher extends Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided.
	 */
	protected $msg = 'Could not find the group with ID %d.';

	/**
	 * Get a group ID from its identifier (ID or slug).
	 *
	 * @param int|string $arg Group ID or slug.
	 * @return BP_Groups_Group|bool
	 */
	public function get( $arg ) {

		// Group ID or slug.
		if ( ! is_numeric( $arg ) ) {
			$arg = groups_get_id( $arg );
		}

		// Get group object.
		$group = groups_get_group(
			[ 'group_id' => $arg ]
		);

		if ( empty( $group->id ) ) {
			return false;
		}

		return $group;
	}
}
