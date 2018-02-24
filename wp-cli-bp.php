<?php

// Bail if WP-CLI is not present.
if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_hook( 'before_wp_load', function() {
	require_once( __DIR__ . '/component.php' );
	require_once( __DIR__ . '/components/signup.php' );
	require_once( __DIR__ . '/components/activity.php' );
	require_once( __DIR__ . '/components/activity-favorite.php' );
	require_once( __DIR__ . '/components/component.php' );
	require_once( __DIR__ . '/components/group.php' );
	require_once( __DIR__ . '/components/group-member.php' );
	require_once( __DIR__ . '/components/group-invite.php' );
	require_once( __DIR__ . '/components/member.php' );
	require_once( __DIR__ . '/components/friend.php' );
	require_once( __DIR__ . '/components/xprofile-group.php' );
	require_once( __DIR__ . '/components/xprofile-field.php' );
	require_once( __DIR__ . '/components/xprofile-data.php' );
	require_once( __DIR__ . '/components/tool.php' );
	require_once( __DIR__ . '/components/message.php' );
} );

WP_CLI::add_command( 'bp signup', 'BPCLI_Signup' );

WP_CLI::add_command( 'bp activity', 'BPCLI_Activity', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp activity favorite', 'BPCLI_Activity_Favorite', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'activity' ) ) {
			WP_CLI::error( 'The Activity component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp component', 'BPCLI_Components' );

WP_CLI::add_command( 'bp group', 'BPCLI_Group', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp group member', 'BPCLI_Group_Member', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp group invite', 'BPCLI_Group_Invite', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'groups' ) ) {
			WP_CLI::error( 'The Groups component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp member', 'BPCLI_Member' );

WP_CLI::add_command( 'bp friend', 'BPCLI_Friend', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'friends' ) ) {
			WP_CLI::error( 'The Friends component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp xprofile group', 'BPCLI_XProfile_Group', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp xprofile field', 'BPCLI_XProfile_Field', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp xprofile data', 'BPCLI_XProfile_Data', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
	},
) );

WP_CLI::add_command( 'bp tool', 'BPCLI_Tool', array(
	'before_invoke' => function() {
		require_once( buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-tools.php' );
	},
) );

WP_CLI::add_command( 'bp message', 'BPCLI_Message', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'messages' ) ) {
			WP_CLI::error( 'The Message component is not active.' );
		}
	},
) );
