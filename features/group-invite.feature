Feature: Manage BuddyPress Group Invites

  Scenario: Group Invite CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp user create inviter inviter@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {INVITER_ID}

    When I run `wp bp group create --name="Cool Group" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group invite add --group-id={GROUP_ID} --user-id={MEMBER_ID} --inviter-id={INVITER_ID}`
    Then STDOUT should contain:
      """
      Success: Member invited to the group.
      """

    When I run `wp bp group invite send --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Invitations by the user sent.
      """

    When I run `wp bp group invite remove --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User uninvited from the group.
      """

    When I run `wp bp group invite accept --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User is now a "member" of the group.
      """

    When I run `wp bp group invite reject --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member invitation rejected.
      """

    When I run `wp bp group invite delete --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member invitation deleted from the group.
      """
