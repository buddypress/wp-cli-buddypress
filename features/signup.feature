Feature: Manage BuddyPress signups

  Scenario: Add a signup
    Given a WP install

    When I run `wp bp signup add --user-login=test_user --user-email=teste@site.com`
    Then STDOUT should contain:
      """
      Success: Successfully added new user signup (ID #345).
      """

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

    When I run `wp bp signup resend 20 teste@site.com ee48ec319fef3nn4`
    Then STDOUT should contain:
      """
      Success: Email sent successfully.
      """

    When I run `wp bp signup send another_teste@site.com ee48ec319fef3nn4aasd`
    Then STDOUT should contain:
      """
      Success: Email sent successfully.
      """

  Scenario: List available signups
    Given a WP install

    When I run `wp bp signup list --format=count`
    Then STDOUT should be:
    """
    3
    """
