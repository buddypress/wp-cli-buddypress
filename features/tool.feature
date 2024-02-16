Feature: Manage BuddyPress Tools

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """
    And I run `wp bp component activate friends`

  Scenario: BuddyPress tool

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Complete!
      """

    When I run `wp bp tool signup 1`
    Then STDOUT should contain:
      """
      Success: Signup tool updated.
      """
