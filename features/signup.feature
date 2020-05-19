Feature: Manage BuddyPress Signups

  Background:
    Given a WP install
    And I run `wp plugin install https://github.com/buddypress/BuddyPress/archive/master.zip --activate`
    And I run `wp bp tool signup 1`

  Scenario: Signups CRUD

    When I run `wp bp signup add --user-login=test_user --user-email=test@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SIGNUP_ID}

    When I run `wp bp signup list --fields=signup_id,user_login,user_email`
    Then STDOUT should be a table containing rows:
      | signup_id   | user_login | user_email       |
      | {SIGNUP_ID} | test_user  | test@example.com |

    When I run `wp bp signup delete {SIGNUP_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Signup deleted.
      """

  Scenario: Signup fetching by identifier

    When I run `wp bp signup add --user-login=signup1 --user-email=signup1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SIGNUP_ONE_ID}

    When I run `wp bp signup get {SIGNUP_ONE_ID} --fields=signup_id,user_login,user_email`
    Then STDOUT should be a table containing rows:
      | Field      | Value               |
      | signup_id  | {SIGNUP_ONE_ID}     |
      | user_login | signup1             |
      | user_email | signup1@example.com |

    When I run `wp bp signup add --user-login={SIGNUP_ONE_ID} --user-email=signup2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SIGNUP_TWO_ID}

    When I run `wp bp signup get {SIGNUP_ONE_ID} --fields=signup_id,user_login,user_email`
    Then STDOUT should be a table containing rows:
      | Field      | Value               |
      | signup_id  | {SIGNUP_ONE_ID}     |
      | user_login | signup1             |
      | user_email | signup1@example.com |

    When I run `wp bp signup get {SIGNUP_ONE_ID} --fields=signup_id,user_login,user_email --match-field=user_login`
    Then STDOUT should be a table containing rows:
      | Field      | Value               |
      | signup_id  | {SIGNUP_TWO_ID}     |
      | user_login | {SIGNUP_ONE_ID}     |
      | user_email | signup2@example.com |

  Scenario: Signup activation

    When I run `wp bp signup add --user-login=test_user --user-email=test@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SIGNUP_ID}

    When I run `wp bp signup activate {SIGNUP_ID}`
    Then STDOUT should contain:
      """
      Signup activated
      """

    When I run `wp user get test_user --field=user_email`
    Then STDOUT should contain:
      """
      test@example.com
      """

  Scenario: Signup resending

    When I run `wp bp signup add --user-login=test_user --user-email=test@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SIGNUP_ID}

    When I run `wp bp signup resend {SIGNUP_ID}`
    Then STDOUT should contain:
      """
      success
      """
