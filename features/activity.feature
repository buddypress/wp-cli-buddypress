Feature: Manage BuddyPress Activities

  Scenario: Create an activity item
    Given a BP install

    When I run `wp bp activity create --component=groups --user-id=10`
    Then STDOUT should contain:
      """
      Success: Successfully created new activity item (ID #5464)
      """

  Scenario: Delete an activity
    Given a BP install

    When I run `wp bp activity delete 500 --yes`
    Then STDOUT should contain:
      """
      Success: Activity deleted.
      """

  Scenario: Spam an activity
    Given a BP install

    When I run `wp bp activity spam 500`
    Then STDOUT should contain:
      """
      Success: Activity marked as spam.
      """

  Scenario: Ham an activity
    Given a BP install

    When I run `wp bp activity ham 500`
    Then STDOUT should contain:
      """
      Success: Activity marked as ham.
      """

  Scenario: Post an activity update
    Given a BP install

    When I run `wp bp activity post_update --user-id=140`
    Then STDOUT should contain:
      """
      Success: Successfully updated with a new activity item (ID #4548)
      """

  Scenario: Add an activity comment
    Given a BP install

    When I run `wp bp activity comment 459 --user-id=140 --skip-notification=1`
    Then STDOUT should contain:
      """
      Success: Successfully added a new activity comment (ID #494)
      """

  Scenario: Delete an activity comment
    Given a BP install

    When I run `wp bp activity delete_comment 100 500`
    Then STDOUT should contain:
      """
      Success: Activity comment deleted.
      """

  Scenario: Get the permalink for a single activity item
    Given a BP install

    When I run `wp bp activity permalink 6465`
    Then STDOUT should contain:
      """
      Success: Activity Permalink: https://site.com/activity/p/6465
      """

    When I run `wp bp activity url 16516`
    Then STDOUT should contain:
      """
      Success: Activity Permalink: https://site.com/activity/p/16516
      """
