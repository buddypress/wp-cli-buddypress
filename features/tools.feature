Feature: Manage BuddyPress Tools

 Scenario: Buddypress repair
    Given a WP install

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Success: Counting the number of friends for each user. Complete!
      """

  Scenario: BuddyPress reinstall emails
    Given a WP install

    When I run `wp bp tool reinstall_emails --yes`
    Then STDOUT should contain:
      """
      Success: Emails have been successfully reinstalled.
      """
