Feature: Manage BuddyPress Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID_2}

    When I run `wp bp message create --from={USER_ID} --to={USER_ID_2} --content="Test" --porcelain`
    And STDOUT should be a number
    Then save STDOUT as {THREAD_ID}

    When I run `wp bp message list --fields=id,subject,message --user-id={USER_ID}`
    Then STDOUT should be a table containing rows:
      | id          | subject          | message |
      | {THREAD_ID} | Message Subject  | Test    |

    When I run `wp bp message delete {THREAD_ID} --user-id={USER_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Thread(s) successfully deleted.
      """
