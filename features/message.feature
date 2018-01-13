Feature: Manage BuddyPress Messages

  Scenario: Message CRUD Operations
    Given a BP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser3 testuser3@example.com --porcelain`
    And save STDOUT as {SALLY_ID}

    When I run `wp bp message create --from={BOB_ID} --to={SALLY_ID} --subject="Thread Test" --content="Test"`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message delete {THREAD_ID} --user-id={BOB_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Thread successfully deleted.
      """

    When I run `wp bp message delete {THREAD_ID} --user-id={BOB_ID} --yes`
    Then the return code should be 1

  Scenario: Message list
    Given a BP install

    When I run `wp user create testuser2 testuser2@example.com --porcelain`
    And save STDOUT as {BOB_ID}

    When I run `wp user create testuser3 testuser3@example.com --porcelain`
    And save STDOUT as {SALLY_ID}

    When I try `wp bp message list --fields=id --user-id={BOB_ID}`
    Then the return code should be 1

    When I try `wp bp message list --fields=id --user-id={SALLY_ID}`
    Then the return code should be 1

    When I run `wp bp message create --from={BOB_ID} --to={SALLY_ID} --subject="Test Thread" --content="Message one" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {THREAD_ID}

    When I run `wp bp message create --from={SALLY_ID} --to={BOB_ID} --thread-id={THREAD_ID} --subject="Test Answer" --content="Message two"`
    Then STDOUT should contain:
      """
      Success: Message successfully created.
      """

    When I run `wp bp message create --from={BOB_ID} --to={SALLY_ID} --thread-id={THREAD_ID} --subject="Another Answer" --content="Message three"`
    Then STDOUT should contain:
      """
      Success: Message successfully created.
      """

    When I run `wp bp message list --fields=sender_id --user-id={BOB_ID}`
    Then STDOUT should be a table containing rows:
      | sender_id  |
      | {BOB_ID}   |
      | {SALLY_ID} |
      | {BOB_ID}   |

    When I run `wp bp message list --fields=thread_id,sender_id,subject,message --user-id={BOB_ID}`
    Then STDOUT should be a table containing rows:
      | thread_id   | sender_id  | subject         | message        |
      | {THREAD_ID} | {BOB_ID}   | Test Thread     | Message one    |
      | {THREAD_ID} | {SALLY_ID} | Test Answer     | Message two    |
      | {THREAD_ID} | {BOB_ID}   | Another Answer  | Message three  |

    When I run `wp user create testuser4 testuser4@example.com --porcelain`
    And save STDOUT as {JOHN_ID}

    When I try `wp bp message list --fields=id --user-id={JOHN_ID}`
    Then the return code should be 1

    When I run `wp bp message create --from={JOHN_ID} --to={SALLY_ID} --subject="Second Thread" --content="Message one" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ANOTHER_THREAD_ID}

    When I run `wp bp message list --fields=thread_id,sender_id,subject,message --user-id={JOHN_ID}`
    Then STDOUT should be a table containing rows:
      | thread_id           | sender_id  | subject         | message     |
      | {ANOTHER_THREAD_ID} | {JOHN_ID}  | Another Thread  | Message one |

    When I run `wp bp message list --fields=thread_id,sender_id,subject,message --user-id={SALLY_ID}`
    Then STDOUT should be a table containing rows:
      | thread_id           | sender_id  | subject         | message       |
      | {THREAD_ID}         | {BOB_ID}   | Test Thread     | Message one   |
      | {THREAD_ID}         | {SALLY_ID} | Test Answer     | Message two   |
      | {THREAD_ID}         | {BOB_ID}   | Another Answer  | Message three |
      | {ANOTHER_THREAD_ID} | {JOHN_ID}  | Second Thread   | Message one   |
