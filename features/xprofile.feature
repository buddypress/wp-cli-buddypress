Feature: Manage BuddyPress xprofile fields.

 Scenario: Delete a field group
    Given a WP install

    When I run `wp bp xprofile delete_group 520`
    Then STDOUT should contain:
      """
      Success: Field group deleted.
      """