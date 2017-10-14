Feature: Manage BuddyPress signups

  Scenario: Delete a signup
    Given a WP install
    And a BuddyPress install

    When I run `wp bp signup delete 520`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """

    When I run `wp bp signup delete`
    Then STDERR should contain:
      """
      Error: Please specify a signup ID.
      """

    When I run `wp bp signup delete foo`
    Then STDERR should contain:
      """
      Error: Invalid signup ID.
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

    When I run `wp bp signup resend --user-id=20 --user-email=teste@site.com --key=ee48ec319fef3nn4`
    Then STDOUT should contain:
      """
      Success: Email sent successfully.
      """

    When I run `wp bp signup resend --user-id=30 --user-email=teste_2@site.com`
    Then STDERR should contain:
      """
      Error: Please specify an activation key.
      """

    When I run `wp bp signup resend --user-id=40`
    Then STDERR should contain:
      """
      Error: Please specify a user email.
      """

  Scenario: List available signups
    Given a WP install

    When I run `wp bp signup list_ --format=ids`
    Then STDOUT should be:
    """
    1 2 3
    """

    When I run `wp bp signup list_ --format=count`
    Then STDOUT should be:
    """
    3
    """