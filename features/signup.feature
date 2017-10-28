Feature: Manage BuddyPress signups

  Scenario: Delete a signup
    Given a WP install

    When I run `wp bp signup delete 520`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """

  Scenario: Activate a signup
    Given a WP install

    When I run `wp bp signup activate ee48ec319fef3nn4`
    Then STDOUT should contain:
      """
      Success: Signup activated, new user (ID #10).
      """

  Scenario: Resend activation email
    Given a WP install

    When I run `wp bp signup resend --user-id=20 --email=teste@site.com --key=ee48ec319fef3nn4`
    Then STDOUT should contain:
      """
      Success: Email sent successfully.
      """

  Scenario: List available signups
    Given a WP install

    When I run `wp bp signup list_ --format=count`
    Then STDOUT should be:
    """
    3
    """
