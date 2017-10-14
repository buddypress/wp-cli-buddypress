Feature: Manage BuddyPress signups

  Scenario: Delete a signup
    Given a WP install

    When I run `wp bp signup delete 520`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """

    When I run `wp bp signup delete`
    Then STDOUT should contain:
      """
      Error: Please specify a signup ID.
      """

    When I run `wp bp signup delete foo`
    Then STDOUT should contain:
      """
      Error: Invalid signup ID.
      """

  Scenario: Activate a signup
    Given a WP install

    When I run `wp bp signup activate`
    Then STDOUT should contain:
      """
      Error: Please specify an activation key.
      """

    When I run `wp bp signup activate foo`
    Then STDOUT should contain:
      """
      Error: Invalid activation key.
      """

    When I run `wp bp signup activate ee48ec319fef3nn4`
    Then STDOUT should contain:
      """
      Success: Signup activated, new user (ID #10).
      """

  Scenario: Resend activation email
    Given a WP install

    When I run `wp bp signup resend --user-id=20`
    Then STDOUT should contain:
      """
      Error: Please specify a user email.
      """

    When I run `wp bp signup resend --user-id=20 --user-email=teste@site.com`
    Then STDOUT should contain:
      """
      Error: Please specify an activation key.
      """

    When I run `wp bp signup resend --user-id=20 --user-email=teste@site.com --key=ee48ec319fef3nn4`
    Then STDOUT should contain:
      """
      Success: Email sent successfully.
      """

  Scenario: List available signups
    Given a WP install

    When I run `wp bp signup list --format=ids`
    Then STDOUT should be:
    """
    1 2 3
    """

    When I run `wp bp signup list --format=count`
    Then STDOUT should be:
    """
    3
    """