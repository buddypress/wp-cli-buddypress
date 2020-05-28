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
 */
class BuddyPress extends BuddyPressCommand {

	/**
	 * Adds description and subcomands to the DOC.
	 *
	 * @param  object $command Command.
	 * @return array
	 */
	private function command_to_array( $command ) {
		$dump = array(
			'name'        => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc'    => $command->get_longdesc(),
		);

		foreach ( $command->get_subcommands() as $subcommand ) {
			$dump['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $dump['subcommands'] ) ) {
			$dump['synopsis'] = (string) $command->get_synopsis();
		}

		return $dump;
	}
}
