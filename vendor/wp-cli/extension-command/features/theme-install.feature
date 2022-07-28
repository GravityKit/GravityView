Feature: Install WordPress themes

  Scenario: Return code is 1 when one or more theme installations fail
    Given a WP install

    When I try `wp theme install p2 p2-not-a-theme`
    Then STDERR should contain:
      """
      Warning:
      """
    And STDERR should contain:
      """
      p2-not-a-theme
      """
    And STDERR should contain:
      """
      Error: Only installed 1 of 2 themes.
      """
    And STDOUT should contain:
      """
      Installing P2
      """
    And STDOUT should contain:
      """
      Theme installed successfully.
      """
    And the return code should be 1

    When I try `wp theme install p2`
    Then STDOUT should be:
      """
      Success: Theme already installed.
      """
    And STDERR should be:
      """
      Warning: p2: Theme already installed.
      """
    And the return code should be 0

    When I try `wp theme install p2-not-a-theme`
    Then STDERR should contain:
      """
      Warning:
      """
    And STDERR should contain:
      """
      p2-not-a-theme
      """
    And STDERR should contain:
      """
      Error: No themes installed.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Ensure automatic parent theme installation uses http cacher
    Given a WP install
    And an empty cache

    When I run `wp theme install moina`
    Then STDOUT should contain:
      """
      Success: Installed 1 of 1 themes.
      """
    And STDOUT should not contain:
      """
      Using cached file
      """

    When I run `wp theme uninstall moina`
    Then STDOUT should contain:
      """
      Success: Deleted 1 of 1 themes.
      """

    When I run `wp theme install moina-blog`
    Then STDOUT should contain:
      """
      Success: Installed 1 of 1 themes.
      """
    And STDOUT should contain:
      """
      This theme requires a parent theme.
      """
    And STDOUT should contain:
      """
      Using cached file
      """

  Scenario: Verify installed theme activation
    Given a WP install

    When I run `wp theme install p2`
    Then STDOUT should not be empty

    When I try `wp theme install p2 --activate`
    Then STDERR should contain:
    """
    Warning: p2: Theme already installed.
    """

    And STDOUT should contain:
    """
    Activating 'p2'...
    Success: Switched to 'P2' theme.
    Success: Theme already installed.
    """
