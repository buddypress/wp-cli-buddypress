Feature: Manage BuddyPress Emails

  Background:
    Given a WP install
    And I run `wp plugin install https://github.com/buddypress/BuddyPress/archive/master.zip --activate`

  Scenario: BuddyPress reinstall emails

    When I run `wp bp email reinstall --yes`
    Then STDOUT should contain:
      """
      Success: Emails have been successfully reinstalled.
      """
