Feature: Manage BuddyPress Emails

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      buddypress
      """

  Scenario: BuddyPress reinstall emails

    When I run `wp bp email reinstall --yes`
    Then STDOUT should contain:
      """
      Success: Emails have been successfully reinstalled.
      """
