Feature: Scaffold BuddyPress tests

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """

  Scenario: Scaffold plugin tests
    When I run `wp bp scaffold plugin-tests hello-world`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/hello-world/tests directory should contain:
      """
      bootstrap.php
      """
    And the {PLUGIN_DIR}/hello-world/tests/bootstrap.php file should contain:
      """
      require_once getenv( 'BP_TESTS_DIR' ) . '/includes/loader.php';
      """
    And the {PLUGIN_DIR}/hello-world/bin directory should contain:
      """
      install-bp-tests.sh
      """
    When I run `wp eval "if ( is_executable( '{PLUGIN_DIR}/hello-world/bin/install-bp-tests.sh' ) ) { echo 'executable'; } else { exit( 1 ); }"`
    Then STDOUT should be:
      """
      executable
      """
