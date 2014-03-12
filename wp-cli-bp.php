<?php

// Bail if WP-CLI is not present
if ( !defined( 'WP_CLI' ) ) return;

require_once( __DIR__ . '/component.php' );
require_once( __DIR__ . '/components/activity.php' );
require_once( __DIR__ . '/components/core.php' );
require_once( __DIR__ . '/components/group.php' );
require_once( __DIR__ . '/components/member.php' );
require_once( __DIR__ . '/components/xprofile.php' );

