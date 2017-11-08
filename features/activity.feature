Feature: Manage BuddyPress Activities

  Scenario: Activity CRUD Operations
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

    When I run `wp bp activity list --fields=id,user_id,component`
    Then STDOUT should be a table containing rows:
      | id            | user_id      | component |
      | {ACTIVITY_ID} | {MEMBER_ID}  | groups    |

    When I run `wp bp activity spam {ACTIVITY_ID}`
    Then STDOUT should contain:
      """
      Success: Activity marked as spam.
      """

    When I run `wp bp activity ham {ACTIVITY_ID}`
    Then STDOUT should contain:
      """
      Success: Activity marked as ham.
      """

    When I run `wp bp activity delete {ACTIVITY_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Activity deleted.
      """

    When I run `wp bp activity list --format=ids`
    Then STDOUT should not contain:
      """
      {ACTIVITY_ID}
      """

  Scenario: Activity Comment Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity post_update --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity list --fields=id,user_id,component`
    Then STDOUT should be a table containing rows:
      | id            | user_id       | component   |
      | {ACTIVITY_ID} | {MEMBER_ID}   | activity    |

    When I run `wp bp activity comment {ACTIVITY_ID} --user-id={MEMBER_ID} --skip-notification=1`
    Then STDOUT should be a number
    And save STDOUT as {COMMENT_ID}

    When I run `wp bp activity delete_comment {ACTIVITY_ID} --comment-id={COMMENT_ID}`
    Then STDOUT should contain:
      """
      Success: Activity comment deleted.
      """

    When I run `wp bp activity permalink {ACTIVITY_ID}`
    Then STDOUT should contain:
      """
      Success: Activity Permalink: http://example.com/activity/p/{ACTIVITY_ID}
      """

    When I run `wp bp activity list --format=ids`
    Then STDOUT should not contain:
      """
      {ACTIVITY_ID}
      """
