Feature: Manage BuddyPress Group custom fields

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """
    And I run `wp bp component activate groups`

  Scenario: Group Meta CRUD

    When I run `wp bp group create --name="Totally Cool Group" --slug=totally-cool-group --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group meta add {GROUP_ID} foo 'bar'`
    Then STDOUT should not be empty

    When I run `wp bp group meta get {GROUP_ID} foo`
    Then STDOUT should be:
      """
      bar
      """

    When I try `wp bp group meta get 999999 foo`
    Then STDERR should be:
      """
      Error: Could not find the group with ID 999999.
      """
    And the return code should be 1

    When I run `wp bp group meta set {GROUP_ID} foo '[ "1", "2" ]' --format=json`
    Then STDOUT should not be empty

    When I run `wp bp group meta get {GROUP_ID} foo --format=json`
    Then STDOUT should be:
      """
      ["1","2"]
      """

    When I run `wp bp group meta delete {GROUP_ID} foo`
    Then STDOUT should not be empty

    When I try `wp bp group meta get {GROUP_ID} foo`
    Then the return code should be 1

  Scenario: Add group meta with JSON serialization

    When I run `wp bp group create --name="Totally Cool Group" --slug=totally-cool-group --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group meta add {GROUP_ID} foo '"-- hi"' --format=json`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp bp group meta get {GROUP_ID} foo`
    Then STDOUT should be:
      """
      -- hi
      """

  Scenario: List group meta

    When I run `wp bp group create --name="Totally Cool Group" --slug=totally-cool-group --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group meta add {GROUP_ID} apple banana`
    And I run `wp bp group meta add {GROUP_ID} apple banana`
    Then STDOUT should not be empty

    When I run `wp bp group meta set {GROUP_ID} banana '["apple", "apple"]' --format=json`
    Then STDOUT should not be empty

    When I run `wp bp group meta list {GROUP_ID}`
    Then STDOUT should be a table containing rows:
      | group_id | meta_key | meta_value                             |
      | 1        | apple    | banana                                 |
      | 1        | apple    | banana                                 |
      | 1        | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";} |

    When I run `wp bp group meta list 1 --unserialize`
    Then STDOUT should be a table containing rows:
      | group_id | meta_key | meta_value        |
      | 1        | apple    | banana            |
      | 1        | apple    | banana            |
      | 1        | banana   | ["apple","apple"] |
