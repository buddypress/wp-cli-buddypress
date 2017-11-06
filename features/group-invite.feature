Feature: Manage BuddyPress Group Invites

  Scenario: Invite a member to a group
    Given a BP install

    When I run `wp bp group invite add --user-id=10 --group-id=40`
    Then STDOUT should contain:
      """
      Success: Member invited to the group.
      """

  Scenario: Uninvite a user from a group
    Given a BP install

    When I run `wp bp group invite remove --group-id=3 --user-id=10`
    Then STDOUT should contain:
      """
      Success: User uninvited from the group.
      """

  Scenario: Accept a group invitation
    Given a BP install

    When I run `wp bp group invite accept --group-id=3 --user-id=10`
    Then STDOUT should contain:
      """
      Success: User is not a "member" of the group.
      """

  Scenario: Reject a group invitation
    Given a BP install

    When I run `wp bp group invite reject --group-id=3 --user-id=10`
    Then STDOUT should contain:
      """
      Success: Member invitation rejected.
      """

  Scenario: Delete a group invitation
    Given a BP install

    When I run `wp bp group invite delete --group-id=3 --user-id=10`
    Then STDOUT should contain:
      """
      Success: Member invitation deleted from the group.
      """

  Scenario: Send pending invites by a user to a group.
    Given a BP install

    When I run `wp bp group invite send --group-id=3 --user-id=10`
    Then STDOUT should contain:
      """
      Success: Invitations by the user sent.
      """
