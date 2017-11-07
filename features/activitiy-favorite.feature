Feature: Manage BuddyPress Activity Favorites

  Scenario: Add an activity item as a favorite for a user.
    Given a BP install

    When I run `wp bp activity favorite add 100 500`
    Then STDOUT should contain:
      """
      Success: Activity item added as a favorite for the user.
      """

  Scenario: Remove an activity item as a favorite for a user.
    Given a BP install

    When I run `wp bp activity favorite remove 100 500`
    Then STDOUT should contain:
      """
      Success: Activity item removed as a favorite for the user.
      """

  Scenario: Get a users favorite activity items.
    Given a BP install

    When I run `wp bp activity favorite items 315`
    Then STDOUT should contain:
      """
      Success: Favorites items for user #315: 166,1561,6516
      """
