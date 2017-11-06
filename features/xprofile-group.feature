Feature: Manage BuddyPress XProfile Groups

  Scenario: XProfile Group CRUD operations
    Given a BP install

    When I run `wp bp xprofile group create --name="Group Name" --description="Group Description" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp xprofile group get {GROUP_ID}`
    Then STDOUT should be a table containing rows:
        | Field        | Value             |
        | id           | {GROUP_ID}        |
        | name         | Group Name        |
        | description  | Group Description |
        | can_delete   | 1                 |
        | group_order  | 0                 |
