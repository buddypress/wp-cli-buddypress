Feature: Manage BuddyPress XProfile Data

  Scenario: Set profile data for a user
    Given a WP install

    When I run `wp bp xprofile data set --user-id=45 --field-id=120 --value=teste`
    Then STDOUT should contain:
      """
      Success: Updated XProfile field "Field Name" (ID 120) with value  "teste" for user user_login (ID 45).
      """

  Scenario: Delete XProfile data for a user
    Given a WP install

    When I run `wp bp xprofile data delete --user-id=45 --field-id=120 --yes`
    Then STDOUT should contain:
      """
      Success: XProfile data removed.
      """

    When I run `wp bp xprofile data delete --user-id=user_test --delete-all --yes`
    Then STDOUT should contain:
      """
      Success: XProfile data removed.
      """
