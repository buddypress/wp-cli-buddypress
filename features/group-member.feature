Feature: Manage BuddyPress Group Members

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/buddypress/archive/master.zip
      """
    And I run `wp bp component activate groups`

  Scenario: Group Member CRUD

    When I run `wp user create testuser1 testuser1@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CREATOR_ID}

    When I run `wp user create mod mod@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp user create randon randon@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {RANDON_MEMBER_ID}

    When I run `wp user create anothermod anothermod@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ANOTHER_MEMBER_ID}

    When I run `wp bp group create --name="Totally Cool Group" --creator-id={CREATOR_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {GROUP_ID}

    When I run `wp bp group meta add {GROUP_ID} invite_status 'public'`
    Then STDOUT should not be empty

    When I run `wp bp group member add --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Added user #{MEMBER_ID} to group #{GROUP_ID} as member.
      """

    When I run `wp bp group member create --group-id={GROUP_ID} --user-id={ANOTHER_MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Added user #{ANOTHER_MEMBER_ID} to group #{GROUP_ID} as member.
      """

    When I run `wp bp group member list {GROUP_ID} --fields=id`
    Then STDOUT should be a table containing rows:
      | id                  |
      | {CREATOR_ID}        |
      | {MEMBER_ID}         |
      | {ANOTHER_MEMBER_ID} |

    When I run `wp bp group member promote --group-id={GROUP_ID} --user-id={MEMBER_ID} --role=mod`
    Then STDOUT should contain:
      """
      Success: Member promoted to new role successfully.
      """

    When I run `wp bp group member list {GROUP_ID} --fields=id --role=mod`
    Then STDOUT should be a table containing rows:
      | id          |
      | {MEMBER_ID} |

    When I try `wp bp group member demote --group-id={GROUP_ID} --user-id={RANDON_MEMBER_ID}`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: User is not a member of the group.
      """

    When I run `wp bp group member demote --group-id={GROUP_ID} --user-id={MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User demoted to the "member" status.
      """

    When I try `wp bp group member list {GROUP_ID} --fields=user_id --role=mod`
    Then the return code should be 1

    When I run `wp bp group member promote --group-id={GROUP_ID} --user-id={MEMBER_ID} --role=admin`
    Then STDOUT should contain:
      """
      Success: Member promoted to new role successfully.
      """

    When I run `wp bp group member promote --group-id={GROUP_ID} --user-id={ANOTHER_MEMBER_ID} --role=admin`
    Then STDOUT should contain:
      """
      Success: Member promoted to new role successfully.
      """

    When I run `wp bp group member demote --group-id={GROUP_ID} --user-id={ANOTHER_MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: User demoted to the "member" status.
      """

    When I run `wp bp group member ban --group-id={GROUP_ID} --user-id={ANOTHER_MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member banned from the group.
      """

    When I run `wp bp group member list {GROUP_ID} --fields=user_id --role=banned`
    Then STDOUT should be a table containing rows:
      | user_id             |
      | {ANOTHER_MEMBER_ID} |

    When I run `wp bp group member unban --group-id={GROUP_ID} --user-id={ANOTHER_MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member unbanned from the group.
      """

    When I try `wp bp group member list {GROUP_ID} --fields=user_id --role=banned`
    Then the return code should be 1

    When I run `wp bp group member remove --group-id={GROUP_ID} --user-id={ANOTHER_MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Member #{ANOTHER_MEMBER_ID} removed from the group #{GROUP_ID}.
      """
