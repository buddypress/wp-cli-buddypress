<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress XProfile.
 *
 * ## EXAMPLES
 *
 *     # Save a xprofile data to a user with its field and value.
 *     $ wp bp xprofile data set --user-id=45 --field-id=120 --value=test
 *     Success: Updated XProfile field "Field Name" (ID 120) with value "test" for user user_login (ID 45).
 *
 *     # Create a xprofile group.
 *     $ wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"
 *     Success: Created XProfile field group "Group Name" (ID 123).
 *
 *     # List xprofile fields.
 *     $ wp bp xprofile field list
 *     +----+------+-------------+---------+----------+-------------+
 *     | id | name | description | type    | group_id | is_required |
 *     +----+------+-------------+---------+----------+-------------+
 *     | 1  | Name |             | textbox | 1        | 1           |
 *     +----+------+-------------+---------+----------+-------------+
 */
class XProfile extends BuddyPressCommand {

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	}
}
