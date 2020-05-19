Feature: Manage BuddyPress XProfile Data

  Background:
    Given a WP install
    And I run `wp plugin install https://github.com/buddypress/BuddyPress/archive/master.zip --activate`
    And I run `wp bp component activate xprofile`

  Scenario: XProfile Data CRUD

    When I run `wp bp xprofile group create --name="Group Name" --description="Group Description" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp xprofile field create --field-group-id={GROUP_ID} --name="Field Name" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FIELD_ID}

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I run `wp bp xprofile data set --field-id={FIELD_ID} --user-id={USER_ID} --value=foo`
    Then STDOUT should contain:
      """
      Updated
      """

    When I run `wp bp xprofile data get --user-id={USER_ID} --field-id={FIELD_ID}`
    Then STDOUT should be:
      """
      foo
      """

    When I run `wp bp xprofile data get --user-id={USER_ID}`
    Then STDOUT should be a table containing rows:
      | field_id   | field_name | value |
      | {FIELD_ID} | Field Name | "foo" |

    When I try `wp bp xprofile data delete --user-id={USER_ID} --yes`
    Then the return code should be 1
    Then STDERR should contain:
      """
      Either --field-id or --delete-all must be provided
      """

    When I run `wp bp xprofile data delete --user-id={USER_ID} --field-id={FIELD_ID} --yes`
    Then STDOUT should contain:
      """
      XProfile data removed
      """

    When I run `wp bp xprofile data get --user-id={USER_ID} --field-id={FIELD_ID}`
    Then STDOUT should not contain:
      """
      foo
      """
