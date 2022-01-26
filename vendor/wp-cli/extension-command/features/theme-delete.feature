Feature: Delete WordPress themes

  Background:
    Given a WP install
    And I run `wp theme install p2`

  Scenario: Delete an installed theme
    When I run `wp theme delete p2`
    Then STDOUT should be:
      """
      Deleted 'p2' theme.
      Success: Deleted 1 of 1 themes.
      """
    And the return code should be 0

  Scenario: Delete an active theme
    When I run `wp theme activate p2`
    Then STDOUT should not be empty

    When I try `wp theme delete p2`
    Then STDERR should be:
    """
    Warning: Can't delete the currently active theme: p2
    Error: No themes deleted.
    """

    When I try `wp theme delete p2 --force`
    Then STDOUT should contain:
    """
    Deleted 'p2' theme.
    """

  Scenario: Delete all installed themes
    When I run `wp theme list --status=active --field=name --porcelain`
    And save STDOUT as {ACTIVE_THEME}

    When I try `wp theme delete --all`
    Then STDOUT should contain:
    """
    Success: Deleted
    """
    And STDERR should be empty

    When I run `wp theme delete --all --force`
    Then STDOUT should be:
      """
      Deleted '{ACTIVE_THEME}' theme.
      Success: Deleted 1 of 1 themes.
      """

    When I try the previous command again
    Then STDOUT should be:
    """
    Success: No themes deleted.
    """

  Scenario: Attempting to delete a theme that doesn't exist
    When I run `wp theme delete p2`
    Then STDOUT should not be empty

    When I try the previous command again
    Then STDOUT should be:
      """
      Success: Theme already deleted.
      """
    And STDERR should be:
      """
      Warning: The 'p2' theme could not be found.
      """
    And the return code should be 0
