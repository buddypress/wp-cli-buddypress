<?php

namespace Buddypress\CLI\Command;

use WP_CLI\Fetchers\Base;

/**
 * Fetch a BuddyPress activity based on one of its attributes.
 */
class Activity_Fetcher extends Base {

	/**
	 * @var string $msg Error message to use when invalid data is provided.
	 */
	protected $msg = 'Could not find the activity with ID %d.';

	/**
	 * Get an activity ID.
	 *
	 * @param int $arg Activity ID.
	 * @return BP_Activity_Activity|bool
	 */
	public function get( $arg ) {
		$activity = new \BP_Activity_Activity( $arg );

		if ( empty( $activity->id ) ) {
			return false;
		}

		return $activity;
	}
}
