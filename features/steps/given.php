<?php

$steps->Given(
	'/^a BP (install|installation)$/',
	function ( $world ) {
		$world->install_wp();

		$dest_dir = $world->variables['RUN_DIR'] . '/wp-content/plugins/buddypress/';
		if ( ! is_dir( $dest_dir ) ) {
			mkdir( $dest_dir );
		}

		$bp_src_dir = getenv( 'BP_SRC_DIR' );
		if ( ! is_dir( $bp_src_dir ) ) {
			throw new Exception( 'BuddyPress not found in BP_SRC_DIR' );
		}

		$world->copy_dir( $bp_src_dir, $dest_dir );
		$world->proc( 'wp plugin activate buddypress' )->run_check();

		$components = [ 'friends', 'groups', 'xprofile', 'activity', 'messages', 'notifications' ];
		foreach ( $components as $component ) {
			$world->proc( "wp bp component activate $component" )->run_check();
		}
	}
);
