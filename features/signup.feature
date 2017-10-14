Feature: Manage BuddyPress signups

  Scenario: Signups delete
    Given a WP install

    When I run `wp bp signup delete bar`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """