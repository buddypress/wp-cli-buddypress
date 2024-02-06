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

    When I run `wp bp notice list --fields=id,subject,message`
    Then STDOUT should be a table containing rows:
      | id              | subject | message   |
      | {NOTICE_ONE_ID} | Test    | Content   |
      | {NOTICE_TWO_ID} | Test 2  | Content 2 |

    When I run `wp bp notice delete {NOTICE_ONE_ID} {NOTICE_TWO_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Sitewide notice deleted {NOTICE_ONE_ID}.
      Success: Sitewide notice deleted {NOTICE_TWO_ID}.
      """
