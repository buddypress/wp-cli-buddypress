Feature: Manage BuddyPress XProfile Fields

  Scenario: Create an XProfile field
    Given a WP install

    When I run `wp bp xprofile field create --type=checkbox --field-group-id=508 --name="Field Name"`
    Then STDOUT should contain:
      """
      Success: Created XProfile field "Field Name" (ID 24564).
      """

    When I run `wp bp xprofile field add --type=checkbox --field-group-id=165 --name="Another Field"`
    Then STDOUT should contain:
      """
      Success: Created XProfile field "Another Field" (ID 5465).
      """

  Scenario: Delete an XProfile field
    Given a WP install

    When I run `wp bp xprofile field delete 500 --yes`
    Then STDOUT should contain:
      """
      Success: Deleted XProfile field "Field Name" (ID 500).
      """

    When I run `wp bp xprofile field delete 458 --delete-data --yes`
    Then STDOUT should contain:
      """
      Success: Deleted XProfile field "Another Field Name" (ID 458).
      """
