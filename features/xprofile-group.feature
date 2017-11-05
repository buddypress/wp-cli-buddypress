Feature: Manage BuddyPress XProfile Groups

  Scenario: Create an XProfile group
    Given a WP install

    When I run `wp bp xprofile group create --name="Group Name" --description="Xprofile Group Description"`
    Then STDOUT should contain:
      """
      Success: Created XProfile field group "Group Name" (ID 123).
      """

    When I run `wp bp xprofile group add --name="Another Group" --can-delete=false`
    Then STDOUT should contain:
      """
      Success: Created XProfile field group "Another Group" (ID 2455545).
      """

  Scenario: Delete a specific XProfile field group
    Given a WP install

    When I run `wp bp xprofile group delete 500 --yes`
    Then STDOUT should contain:
      """
      Success: Field group deleted.
      """
