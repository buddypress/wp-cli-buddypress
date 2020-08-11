<?php

namespace Buddypress\CLI\Command;

use WP_CLI\Fetchers\Base;

/**
 * Fetch a BuddyPress activity based on one of its attributes.
 *
 * @since 2.0.0
 */
class Activity_Fetcher extends Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided.
	 */
	protected $msg = 'Could not find the activity with ID %d.';

	/**
	 * Get an activity ID.
	 *
	 * @param int $activity_id Activity ID.
	 * @return BP_Activity_Activity|bool
	 */
	public function get( $activity_id ) {
		$activity = new \BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			return false;
		}

		return $activity;
	}
}
