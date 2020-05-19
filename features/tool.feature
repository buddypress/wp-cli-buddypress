Feature: Manage BuddyPress Tools

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """
    And I run `wp bp component activate friends`

  Scenario: BuddyPress repair

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Complete!
      """
