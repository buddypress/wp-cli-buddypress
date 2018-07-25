Feature: Manage BuddyPress Notifications

  Scenario: Notifications CRUD Operations
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp notification create --component=groups --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTIFICATION_ID}
