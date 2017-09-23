Feature: Manage BuddyPress Groups

  Background:
    Given a BuddyPress install

  Scenario: Listing groups
    When I run `wp bp group list --format=ids`
    Then STDOUT should be a list of group ids
