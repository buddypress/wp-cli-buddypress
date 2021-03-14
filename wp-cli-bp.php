<?php

namespace Buddypress\CLI;

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

use WP_CLI;

WP_CLI::add_hook(
	'before_wp_load',
	function() {
		require_once __DIR__ . '/src/command.php';
		require_once __DIR__ . '/src/buddypress.php';
		require_once __DIR__ . '/src/signup.php';
		require_once __DIR__ . '/src/activity-fetcher.php';
		require_once __DIR__ . '/src/activity.php';
		require_once __DIR__ . '/src/activity-favorite.php';
		require_once __DIR__ . '/src/activity-meta.php';
		require_once __DIR__ . '/src/components.php';
		require_once __DIR__ . '/src/tool.php';
		require_once __DIR__ . '/src/notification.php';
		require_once __DIR__ . '/src/email.php';
		require_once __DIR__ . '/src/member.php';
		require_once __DIR__ . '/src/friends.php';
		require_once __DIR__ . '/src/messages.php';
		require_once __DIR__ . '/src/xprofile.php';
		require_once __DIR__ . '/src/xprofile-group.php';
		require_once __DIR__ . '/src/xprofile-field.php';
		require_once __DIR__ . '/src/xprofile-data.php';
		require_once __DIR__ . '/src/group-fetcher.php';
		require_once __DIR__ . '/src/group.php';
		require_once __DIR__ . '/src/group-member.php';
		require_once __DIR__ . '/src/group-invite.php';
		require_once __DIR__ . '/src/group-meta.php';

		// Load only if the Scaffold package is present.
		if ( class_exists( 'Scaffold_Command' ) ) {
			require_once __DIR__ . '/src/scaffold.php';

			WP_CLI::add_command(
				'bp scaffold',
				__NAMESPACE__ . '\\Command\\Scaffold',
				array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Scaffold::check_dependencies' )
			);
		}

		WP_CLI::add_command(
			'bp',
			__NAMESPACE__ . '\\Command\\BuddyPress',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\BuddyPress::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp signup',
			__NAMESPACE__ . '\\Command\\Signup',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Signup::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp tool',
			__NAMESPACE__ . '\\Command\\Tool',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Tool::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp notification',
			__NAMESPACE__ . '\\Command\\Notification',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Notification::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp email',
			__NAMESPACE__ . '\\Command\\Email',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Email::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp member',
			__NAMESPACE__ . '\\Command\\Member',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Member::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp message',
			__NAMESPACE__ . '\\Command\\Messages',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Messages::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp component',
			__NAMESPACE__ . '\\Command\\Components',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Components::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp friend',
			__NAMESPACE__ . '\\Command\\Friends',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Friends::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp activity',
			__NAMESPACE__ . '\\Command\\Activity',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Activity::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp activity favorite',
			__NAMESPACE__ . '\\Command\\Activity_Favorite',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Activity::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp activity meta',
			__NAMESPACE__ . '\\Command\\Activity_Meta',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Activity::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp group',
			__NAMESPACE__ . '\\Command\\Group',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Group::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp group member',
			__NAMESPACE__ . '\\Command\\Group_Member',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Group::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp group meta',
			__NAMESPACE__ . '\\Command\\Group_Meta',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Group::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp group invite',
			__NAMESPACE__ . '\\Command\\Group_Invite',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\Group::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp xprofile',
			__NAMESPACE__ . '\\Command\\XProfile',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\XProfile::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp xprofile group',
			__NAMESPACE__ . '\\Command\\XProfile_Group',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\XProfile::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp xprofile field',
			__NAMESPACE__ . '\\Command\\XProfile_Field',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\XProfile::check_dependencies' )
		);

		WP_CLI::add_command(
			'bp xprofile data',
			__NAMESPACE__ . '\\Command\\XProfile_Data',
			array( 'before_invoke' => __NAMESPACE__ . '\\Command\\XProfile::check_dependencies' )
		);
	}
);
