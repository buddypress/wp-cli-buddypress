Feature: Manage BuddyPress Site Notices

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """
    And I run `wp bp component activate messages`

  Scenario: Site Notices CRUD

    When I run `wp bp notice create --subject="Test" --message="Content" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTICE_ID}

    When I run `wp bp notice deactivate {NOTICE_ID}`
    Then STDOUT should contain:
      """
      Success: Sitewide notice has been deactivated.
      """

    When I run `wp bp notice activate {NOTICE_ID}`
    Then STDOUT should contain:
      """
      Success: Sitewide notice activated.
      """

    When I run `wp bp notice get {NOTICE_ID} --fields=id,subject,message`
    Then STDOUT should be a table containing rows:
      | Field    | Value       |
      | id       | {NOTICE_ID} |
      | subject  | Test        |
      | message  | Content     |

    When I run `wp bp notice delete {NOTICE_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Sitewide notice deleted {NOTICE_ID}.
      """

  Scenario: Site Notices list

    When I run `wp bp notice create --subject="Test" --message="Content" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTICE_ONE_ID}

    When I run `wp bp notice create --subject="Test 2" --message="Content 2" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NOTICE_TWO_ID}

    When I run `wp bp notice list --fields=id,subject,message,is_active`
    Then STDOUT should be a table containing rows:
      | id              | subject | message   | is_active |
      | {NOTICE_ONE_ID} | Test    | Content   | 0         |
      | {NOTICE_TWO_ID} | Test 2  | Content 2 | 1         |

    When I try `wp bp notice activate 999999`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: No sitewide notice found by that ID.
      """

    When I run `wp bp notice activate {NOTICE_ONE_ID}`
    Then STDOUT should contain:
      """
      Success: Sitewide notice activated.
      """

    When I run `wp bp notice list --fields=id,subject,message,is_active`
    Then STDOUT should be a table containing rows:
      | id              | subject | message   | is_active |
      | {NOTICE_ONE_ID} | Test    | Content   | 1         |
      | {NOTICE_TWO_ID} | Test 2  | Content 2 | 0         |

    When I run `wp bp notice delete {NOTICE_ONE_ID} {NOTICE_TWO_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Sitewide notice deleted {NOTICE_ONE_ID}.
      Success: Sitewide notice deleted {NOTICE_TWO_ID}.
      """

    When I try `wp bp notice deactivate 999999`
    Then the return code should be 1
    Then STDERR should be:
      """
      Error: No sitewide notice found by that ID.
      """
