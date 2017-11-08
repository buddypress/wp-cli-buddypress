Feature: Manage BuddyPress signups

  Scenario: Signup CRUD Operations
    Given a BP install

    When I run `wp bp signup add --user-login=test_user --user-email=test@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SIGNUP_ID}

    When I run `wp bp signup list --fields=id,user_login,user_email`
    Then STDOUT should be a table containing rows:
      | id          | user_login | user_email       |
      | {SIGNUP_ID} | test_user  | test@example.com |

    When I run `wp bp signup delete {SIGNUP_ID}`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """

    When I run `wp bp signup list --format=ids`
    Then STDOUT should not contain:
      """
      {SIGNUP_ID}
      """

  Scenario: Resend activation email
    Given a BP install

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
