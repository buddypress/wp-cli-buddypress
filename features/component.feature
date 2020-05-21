Feature: Manage BuddyPress Components

  Background:
    Given a WP install
    And these installed and active plugins:
      """
      https://github.com/buddypress/BuddyPress/archive/master.zip
      """

  Scenario: Component CRUD Operations

    When I run `wp bp component list --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp bp component list --type=required --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp bp component list --type=required`
    Then STDOUT should be a table containing rows:
      | number | id      | status | title             | description                                                       |
      | 1      | core    | Active | BuddyPress Core   | It&#8216;s what makes <del>time travel</del> BuddyPress possible! |
      | 2      | members | Active | Community Members | Everything in a BuddyPress community revolves around its members. |

    When I run `wp bp component list --fields=id --type=required`
    Then STDOUT should be a table containing rows:
      | id      |
      | core    |
      | members |

    When I run `wp bp component activate groups`
    Then STDOUT should contain:
      """
      Success: The Groups component has been activated.
      """

    When I run `wp bp component list --fields=id`
    Then STDOUT should be a table containing rows:
      | id      |
      | core    |
      | members |
      | groups  |

    When I run `wp bp component deactivate groups`
    Then STDOUT should contain:
      """
      Success: The Groups component has been deactivated.
      """

    When I run `wp bp component list --fields=id`
    Then STDOUT should be a table containing rows:
      | id      |
      | core    |
      | members |
