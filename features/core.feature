Feature: Manage BuddyPress Components

  Scenario: Component activation and deactivation
    Given a BP install

    When I run `wp bp core list --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp bp core list --type=required --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp bp core list --type=required`
    Then STDOUT should be a table containing rows:
      | id      | title             | description                                                                       |
      | core    | BuddyPress Core   | BuddyPress Core It&#8216;s what makes <del>time travel</del> BuddyPress possible! |
      | members | Community Members | Everything in a BuddyPress community revolves around its members.                 |

    When I run `wp bp core list --fields=id --type=required`
    Then STDOUT should be a table containing rows:
      | id      |
      | core    |
      | members |

    When I run `wp bp core deactivate groups`
    Then STDOUT should contain:
      """
      Success: The Groups component has been deactivated.
      """

    When I try `wp bp core deactivate groups`
    Then the return code should be 1

    When I run `wp bp core activate groups`
    Then STDOUT should contain:
      """
      Success: The Groups component has been activated.
      """

    When I try `wp bp core activate groups`
    Then the return code should be 1
