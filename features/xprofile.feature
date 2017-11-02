Feature: Manage BuddyPress xprofile fields.

  Scenario: Delete a field group
    Given a WP install

    When I run `wp bp xprofile delete_group 520`
    Then STDOUT should contain:
      """
      Success: Field group deleted.
      """

  Scenario: Delete profile data for a user
    Given a WP install

    When I run `wp bp xprofile delete_data --user-id=45 --field-id=120`
    Then STDOUT should contain:
      """
      Success: Profile data removed.
      """
