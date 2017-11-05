Feature: Manage BuddyPress Groups

  Scenario: Create a group
    Given a WP install

    When I run `wp bp group create --name="Totally Cool Group"`
    Then STDOUT should contain:
      """
      Success: Group (ID 5465) created: https://site.com/group-slug/
      """

  Scenario: Delete a group
    Given a WP install

    When I run `wp bp group delete group-slug --yes`
    Then STDOUT should contain:
      """
      Success: Group successfully deleted.
      """

  Scenario: Get the permalink of a group
    Given a WP install

    When I run `wp bp group permalink 500`
    Then STDOUT should contain:
      """
      Success: Group Permalink: https://site.com/group-slug/
      """

    When I run `wp bp group url 4645`
    Then STDOUT should contain:
      """
      Success: Group Permalink: https://site.com/another-group-slug/
      """

  Scenario: Post an Activity update affiliated with a group
    Given a WP install

    When I run `wp bp group post_update 49 140`
    Then STDOUT should contain:
      """
      Success: Successfully updated with a new activity item (ID #54646).
      """

  Scenario: Promote a member to a new status within a group
    Given a WP install

    When I run `wp bp group promote 3 10 admin`
    Then STDOUT should contain:
      """
      Success: Member promoted to new role: admin
      """

  Scenario: Demote user to the 'member' status
    Given a WP install

    When I run `wp bp group demote 3 10`
    Then STDOUT should contain:
      """
      Success: User demoted to the "member" status.
      """

  Scenario: Ban a member from a group
    Given a WP install

    When I run `wp bp group ban 3 10`
    Then STDOUT should contain:
      """
      Success: Member banned from the group.
      """

  Scenario: Unban a member from a group
    Given a WP install

    When I run `wp bp group unban 3 10`
    Then STDOUT should contain:
      """
      Success: Member unbanned from the group.
      """
