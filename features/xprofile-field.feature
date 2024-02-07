Feature: Manage BuddyPress XProfile Fields

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """
    And I run `wp bp component activate xprofile`

  Scenario: XProfile Field CRUD

    When I run `wp bp xprofile group create --name="Group Name" --description="Group Description" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp xprofile field create --type=checkbox --field-group-id={GROUP_ID} --name="Field Name" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FIELD_ID}

    When I run `wp bp xprofile field get {FIELD_ID}`
    Then STDOUT should be a table containing rows:
      | Field    | Value      |
      | id       | {FIELD_ID} |
      | group_id | {GROUP_ID} |
      | name     | Field Name |
      | type     | checkbox   |

    When I run `wp bp xprofile field delete {FIELD_ID} --yes`
    Then STDOUT should contain:
      """
      Deleted XProfile field
      """

    When I try `wp bp xprofile field delete {FIELD_ID} --yes`
    Then the return code should be 1
