Feature: Manage BuddyPress Friends

  Scenario: Friends CRUD Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SALLY_ID}

    When I run `wp bp friend create {BOB_ID} {SALLY_ID} --force-accept=false --porcerlain`
    Then STDOUT should be a number
    And save STDOUT as {FRIENDSHIP_ID}

    When I run `wp bp friend accept_invitation {FRIENDSHIP_ID}`
    Then STDOUT should contain:
      """
      Success: Friendship successfully accepted.
      """

    When I run `wp bp friend remove {BOB_ID} {SALLY_ID}`
    Then STDOUT should contain:
      """
      Success: Friendship successfully removed.
      """

