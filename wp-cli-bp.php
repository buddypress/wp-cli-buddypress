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
	require_once( __DIR__ . '/components/core.php' );
	require_once( __DIR__ . '/components/group.php' );
	require_once( __DIR__ . '/components/group-member.php' );
	require_once( __DIR__ . '/components/group-invite.php' );
	require_once( __DIR__ . '/components/member.php' );
	require_once( __DIR__ . '/components/xprofile-group.php' );
	require_once( __DIR__ . '/components/xprofile-field.php' );
	require_once( __DIR__ . '/components/xprofile-data.php' );
	require_once( __DIR__ . '/components/tool.php' );
	require_once( __DIR__ . '/components/message.php' );
} );
