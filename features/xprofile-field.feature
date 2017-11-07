Feature: Manage BuddyPress XProfile Fields

  Scenario: XProfile Field CRUD Operations
    Given a BP install

    When I run `wp bp xprofile group create --name="Group Name" --description="Group Description" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp xprofile field create --type=checkbox --field-group-id={GROUP_ID} --name="Field Name" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {FIELD_ID}
