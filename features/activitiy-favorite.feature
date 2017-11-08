Feature: Manage BuddyPress Activity Favorites

  Scenario: Activity Favorite CRUD Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --component=groups --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity list --fields=id,user_id`
    Then STDOUT should be a table containing rows:
      | id            | user_id      | component |
      | {ACTIVITY_ID} | {MEMBER_ID}  | groups    |

    When I run `wp bp activity favorite add {ACTIVITY_ID} {MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Activity item added as a favorite for the user.
      """

    When I run `wp bp activity favorite items {MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Favorite items for user #{MEMBER_ID}: {ACTIVITY_ID}
      """

    When I run `wp bp activity favorite remove {ACTIVITY_ID} {MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Activity item removed as a favorite for the user.
      """
