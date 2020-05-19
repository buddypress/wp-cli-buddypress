Feature: Manage BuddyPress Tools

  Background:
    Given a WP install
    And I run `wp plugin install https://github.com/buddypress/BuddyPress/archive/master.zip --activate`
    And I run `wp bp component activate friends`

  Scenario: BuddyPress repair

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Complete!
      """
