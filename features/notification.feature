Feature: Manage BuddyPress Notifications

  Scenario: Notifications CRUD Operations
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp notification create --component=groups --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_ID}

    When I try `wp bp notification get i-do-not-exist`
    Then the return code should be 1

    When I run `wp bp notification get {NOTIFICATION_ID} --fields=user_id,component_name`
    Then STDOUT should be a table containing rows:
      | Field           | Value                 |
      | id              | {NOTIFICATION_ID}     |
      | user_id         | {MEMBER_ID}           |
      | component_name  | groups                |

    When I run `wp bp notification delete {NOTIFICATION_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Notification deleted.
      """

    When I try `wp bp notification get {NOTIFICATION_ID}`
    Then the return code should be 1

  Scenario: Notification list
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ONE_ID}

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_TWO_ID}

    When I run `wp bp notification create --component=groups --user-id={MEMBER_ONE_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_ONE_ID}

    When I run `wp bp notification create --component=groups --user-id={MEMBER_TWO_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_TWO_ID}

    When I run `wp bp notification list --fields=id,user_id`
    Then STDOUT should be a table containing rows:
      | Field             | Value             |
      | user_id           | {MEMBER_TWO_ID}   |
      | component_name    | groups            |

    When I run `wp bp notification list --fields=id --user-id={MEMBER_ONE_ID}`
    Then STDOUT should be a table containing rows:
      | id                    |
      | {NOTIFICATION_ONE_ID} |
