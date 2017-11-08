Feature: Manage BuddyPress Components

  Scenario: Deactivate a component
    Given a BP install

    When I run `wp bp core deactive groups`
    Then STDOUT should contain:
      """
      Success: The Groups component has been deactivated.
      """

  Scenario: Activate a component
    Given a BP install

    When I run `wp bp core activate groups`
    Then STDOUT should contain:
      """
      Success: The Groups component has been activated.
      """
