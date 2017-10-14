Feature: Manage BuddyPress signups

  Scenario: Signups delete
    Given a WP install

    When I try `wp bp signup delete`
    Then STDERR should be:
      """
      Error: Please specify a signup ID.
      """

    When I run `wp bp signup delete bar`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """