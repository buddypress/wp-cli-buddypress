Feature: Manage BuddyPress Activity custom fields

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      buddypress
      """
    And I run `wp bp component activate activity`

  Scenario: Activity Meta CRUD

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity meta add {ACTIVITY_ID} foo 'bar'`
    Then STDOUT should not be empty

    When I run `wp bp activity meta get {ACTIVITY_ID} foo`
    Then STDOUT should be:
      """
      bar
      """

    When I try `wp bp activity meta get 999999 foo`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: Could not find the activity with ID 999999.
      """

    When I run `wp bp activity meta set {ACTIVITY_ID} foo '[ "1", "2" ]' --format=json`
    Then STDOUT should not be empty

    When I run `wp bp activity meta get {ACTIVITY_ID} foo --format=json`
    Then STDOUT should be:
      """
      ["1","2"]
      """

    When I run `wp bp activity meta delete {ACTIVITY_ID} foo`
    Then STDOUT should not be empty

    When I try `wp bp activity meta get {ACTIVITY_ID} foo`
    Then the return code should be 1

  Scenario: Add activity meta with JSON serialization

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity meta add {ACTIVITY_ID} foo '"-- hi"' --format=json`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp bp activity meta get {ACTIVITY_ID} foo`
    Then STDOUT should be:
      """
      -- hi
      """

  Scenario: List activity meta

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity meta add {ACTIVITY_ID} apple banana`
    And I run `wp bp activity meta add {ACTIVITY_ID} apple banana`
    Then STDOUT should not be empty

    When I run `wp bp activity meta set {ACTIVITY_ID} banana '["apple", "apple"]' --format=json`
    Then STDOUT should not be empty

    When I run `wp bp activity meta list {ACTIVITY_ID}`
    Then STDOUT should be a table containing rows:
      | activity_id | meta_key | meta_value                             |
      | 1           | apple    | banana                                 |
      | 1           | apple    | banana                                 |
      | 1           | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";} |

    When I run `wp bp activity meta list 1 --unserialize`
    Then STDOUT should be a table containing rows:
      | activity_id | meta_key | meta_value        |
      | 1           | apple    | banana            |
      | 1           | apple    | banana            |
      | 1           | banana   | ["apple","apple"] |
