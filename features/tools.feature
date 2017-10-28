Feature: Manage BuddyPress Tools

 Scenario: Buddypress repair
    Given a WP install

    When I run `wp bp tools repair friend-count`
    Then STDOUT should contain:
      """
      Success: Counting the number of friends for each user&hellip; Complete!
      """

  Scenario: BuddyPress reinstall emails
    Given a WP install

    When I run `wp bp tools reinstall_emails`
    Then STDOUT should contain:
      """
      Success: Emails have been successfully reinstalled.
      """
