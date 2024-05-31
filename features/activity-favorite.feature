Feature: Manage BuddyPress Activity Favorites

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """
    And I run `wp bp component activate activity`

  Scenario: Activity Favorite CRUD

    When I run `wp user create testuser1 testuser1@example.com --first_name=testuser1 --last_name=user --role=subscriber --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {MEMBER_ID}

    When I run `wp bp activity create --user-id={MEMBER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ACTIVITY_ID}

    When I run `wp bp activity favorite create {ACTIVITY_ID} {MEMBER_ID}`
    Then STDOUT should contain:
      """
      Success: Activity item added as a favorite for the user.
      """

    When I run `wp bp activity favorite remove {ACTIVITY_ID} {MEMBER_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Activity item removed as a favorite for the user.
      """
