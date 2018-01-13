Feature: Manage BuddyPress Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser3 testuser3@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SALLY_ID}

    When I run `wp bp message add --from={BOB_ID} --to={SALLY_ID} --content="Test" --porcelain`
    And STDOUT should be a number
    Then save STDOUT as {THREAD_ID}

    When I run `wp bp message list --fields=subject,message --user-id={BOB_ID}`
    Then STDOUT should be a table containing rows:
      | subject          | message |
      | Message Subject  | Test    |

    When I run `wp bp message delete {THREAD_ID} --user-id={BOB_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Thread successfully deleted.
      """

    When I run `wp bp message delete {THREAD_ID} --user-id={BOB_ID} --yes`
    Then the return code should be 1
