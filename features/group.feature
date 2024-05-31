Feature: Manage BuddyPress Groups

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/buddypress/archive/master.zip
      """
    And I run `wp bp component activate groups`

  Scenario: Group CRUD

    When I run `wp bp group create --name="Totally Cool Group" --slug=totally-cool-group --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group get {GROUP_ID}`
    Then STDOUT should be a table containing rows:
      | Field | Value              |
      | id    | {GROUP_ID}         |
      | name  | Totally Cool Group |

    When I run `wp bp group get totally-cool-group`
    Then STDOUT should be a table containing rows:
      | Field | Value              |
      | id    | {GROUP_ID}         |
      | name  | Totally Cool Group |

    When I try `wp bp group get i-do-not-exist`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: No group found by that slug or ID.
      """

    When I run `wp bp group update {GROUP_ID} --description=foo`
    Then STDOUT should not be empty

    When I run `wp bp group get {GROUP_ID}`
    Then STDOUT should be a table containing rows:
      | Field | Value              |
      | id    | {GROUP_ID}         |
      | name  | Totally Cool Group |

    When I run `wp bp group delete {GROUP_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Deleted group {GROUP_ID}.
      """

    When I try `wp bp group get {GROUP_ID}`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: No group found by that slug or ID.
      """

  Scenario: Group list

    When I run `wp bp group create --name="ZZZ Group 1" --slug=group1 --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ONE_ID}

    When I run `wp bp group meta add {GROUP_ONE_ID} invite_status 'public'`
    Then STDOUT should not be empty

    When I run `wp bp group create --name="AAA Group 2" --slug=group2 --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_TWO_ID}

    When I run `wp bp group meta add {GROUP_TWO_ID} invite_status 'public'`
    Then STDOUT should not be empty

    When I run `wp bp group list --fields=id,name,slug`
    Then STDOUT should be a table containing rows:
      | id             | name        | slug   |
      | {GROUP_ONE_ID} | ZZZ Group 1 | group1 |
      | {GROUP_TWO_ID} | AAA Group 2 | group2 |

    When I run `wp bp group list --fields=id,name,slug --orderby=name`
    Then STDOUT should be a table containing rows:
      | id             | name        | slug   |
      | {GROUP_TWO_ID} | AAA Group 2 | group2 |
      | {GROUP_ONE_ID} | ZZZ Group 1 | group1 |

    When I run `wp bp group list --fields=id,name,slug --orderby=name --order=DESC`
    Then STDOUT should be a table containing rows:
      | id             | name        | slug   |
      | {GROUP_ONE_ID} | ZZZ Group 1 | group1 |
      | {GROUP_TWO_ID} | AAA Group 2 | group2 |

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I try `wp bp group list --fields=id --user-id={MEMBER_ID}`
    Then the return code should be 1

    When I run `wp bp group member add --group-id={GROUP_ONE_ID} --user-id={MEMBER_ID}`
    Then the return code should be 0

    When I run `wp bp group list --fields=id --user-id={MEMBER_ID}`
    Then STDOUT should be a table containing rows:
      | id             |
      | {GROUP_ONE_ID} |
