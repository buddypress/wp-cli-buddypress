Feature: Manage BuddyPress Group Members

  Scenario: Group Member CRUD Operations
    Given a BP install

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CREATOR_ID}

    When I run `wp user create mod mod@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp group create --name="Totally Cool Group" --creator-id={CREATOR_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group member add --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Added user #{MEMBER_ID} to group #{GROUP_ID} as member.
      """

    When I run `wp bp group member get_groups --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Found 1 group(s) from member #{MEMBER_ID}.
      Success: Current group(s) from member #{MEMBER_ID}: {GROUP_ID}
      """

    When I run `wp bp group member promote --group-id={GROUP_ID} --user-id={MEMBER_ID} --role=mod`
    Then STDOUT should contain:
      """
      Success: Member promoted to new role successfully.
      """

    When I run `wp bp group member demote --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User demoted to the "member" status.
      """

    When I run `wp bp group member ban --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member banned from the group.
      """

    When I run `wp bp group member unban --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member unbanned from the group.
      """

    When I run `wp bp group member remove --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member #{MEMBER_ID} removed from the group #{GROUP_ID}.
      """