Feature: Manage BuddyPress Tools

 Scenario: BuddyPress repair
    Given a BP install

    When I run `wp bp tool repair friend-count`
    Then STDOUT should contain:
      """
      Complete!
      """

  Scenario: BuddyPress reinstall emails
    Given a BP install

    When I run `wp bp tool reinstall_emails --yes`
    Then STDOUT should contain:
      """
      Success: Emails have been successfully reinstalled.
      """
