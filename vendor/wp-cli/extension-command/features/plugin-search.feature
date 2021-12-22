Feature: Search WordPress.org plugins

  Scenario: Search for plugins with active_installs field
    Given a WP install

    When I run `wp plugin search foo --fields=name,slug,active_installs`
    Then STDOUT should contain:
      """
      Success: Showing
      """

    When I run `wp plugin search foo --fields=name,slug,active_installs --format=csv`
    Then STDOUT should not contain:
      """
      Success: Showing
      """
    And STDOUT should contain:
      """
      name,slug,active_installs
      """
