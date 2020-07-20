<?php
/**
 * PHPUnit bootstrap file for BuddyPress
 */

// Get codebase versions.
$bp_version = ( getenv( 'BP_VERSION' ) ) ? getenv( 'BP_VERSION' ) : 'latest';

// Get paths to codebase installed by install script.
$bp_tests_dir = "/tmp/buddypress/$bp_version/tests/phpunit";

// Set required environment variables.
putenv( 'BP_TESTS_DIR=' . $bp_tests_dir );

/**
 * Load BuddyPress.
 */
function _manually_load_buddypress() {
	require_once getenv( 'BP_TESTS_DIR' ) . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_buddypress', 0 );

// Bootstrap tests.
require_once $bp_tests_dir . '/includes/testcase.php';
