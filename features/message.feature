Feature: Manage BuddyPress Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I run `wp user create frommember frommember1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FROM_MEMBER}

    When I run `wp user create tomember tomember2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TO_MEMBER}

    When I run `wp bp message create --from={FROM_MEMBER} --to={TO_MEMBER} --content="Test" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message list --fields=id,subject,message --user-id={FROM_MEMBER}`
    Then STDOUT should be a table containing rows:
      | id          | subject          | message |
      | {THREAD_ID} | Message Subject  | Test    |

    When I run `wp bp message delete {THREAD_ID} --user-id={FROM_MEMBER} --yes`
    Then STDOUT should contain:
      """
      Success: Thread(s) successfully deleted.
      """

    When I try `wp bp message get {THREAD_ID}`
    Then the return code should be 1
