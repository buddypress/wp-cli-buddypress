<?php

namespace Buddypress\CLI\Command;

/**
 * Manage BuddyPress through the command-line.
 *
 * ## EXAMPLES
 *
 *     # Create a user signup.
 *     $ wp bp signup create --user-login=test_user --user-email=teste@site.com
 *     Success: Successfully added new user signup (ID #345).
 *
 *     # Activate a component.
 *     $ wp bp component activate groups
 *     Success: The Groups component has been activated.
 *
 *     # List xprofile fields.
 *     $ wp bp xprofile field list
 *     +----+------+-------------+---------+----------+-------------+
 *     | id | name | description | type    | group_id | is_required |
 *     +----+------+-------------+---------+----------+-------------+
 *     | 1  | Name |             | textbox | 1        | 1           |
 *     +----+------+-------------+---------+----------+-------------+
 */
class BuddyPress extends BuddyPressCommand {}
