Feature: Manage BuddyPress Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID_2}

    When I run `wp bp message add --from={MEMBER_ID} --to={MEMBER_ID_2} --subject="Message Title" --content="Test" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message list --fields=id,subject,message --user-id={MEMBER_ID}`
    Then STDOUT should be a table containing rows:
      | id          | subject       | message |
      | {THREAD_ID} | Message Title | Test    |

    When I run `wp bp message delete {THREAD_ID} --user-id={MEMBER_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Thread(s) successfully deleted.
      """
