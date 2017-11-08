Feature: Manage BuddyPress Components

  Scenario: Component activation and deactivation
    Given a BP install

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
