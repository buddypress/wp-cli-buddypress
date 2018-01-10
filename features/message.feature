Feature: Manage BuddyPress Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    And save STDOUT as {MEMBER_ID}

    When I run `wp user create anothermember anothermember@example.com --porcelain`
    And save STDOUT as {ANOTHER_MEMBER}

    When I run `wp bp message create --from={MEMBER_ID} --to={ANOTHER_MEMBER} --content="Test" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message list --fields=id,subject,message --user-id={MEMBER_ID}`
    Then STDOUT should be a table containing rows:
      | id          | subject          | message |
      | {THREAD_ID} | Message Subject  | Test    |

    When I run `wp bp message delete {THREAD_ID} --user-id={MEMBER_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Thread(s) successfully deleted.
      """

    When I try `wp bp message get {THREAD_ID}`
    Then the return code should be 1
