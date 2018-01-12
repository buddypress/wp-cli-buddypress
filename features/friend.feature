Feature: Manage BuddyPress Friends

  Scenario: Friends CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID_2}

    When I run `wp bp friend create {MEMBER_ID} {MEMBER_ID_2}`
    Then STDOUT should contain:
      """
      Success: Friendship successfully created.
      """
