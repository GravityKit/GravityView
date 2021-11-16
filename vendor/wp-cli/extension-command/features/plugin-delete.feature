Feature: Delete WordPress plugins

  Background:
    Given a WP install

  Scenario: Delete an installed plugin
    When I run `wp plugin delete akismet`
    Then STDOUT should be:
      """
      Deleted 'akismet' plugin.
      Success: Deleted 1 of 1 plugins.
      """
    And the return code should be 0

  Scenario: Delete all installed plugins
    When I run `wp plugin delete --all`
    Then STDOUT should be:
      """
      Deleted 'akismet' plugin.
      Deleted 'hello' plugin.
      Success: Deleted 2 of 2 plugins.
      """
    And the return code should be 0

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: No plugins deleted.
      """

  Scenario: Attempting to delete a plugin that doesn't exist
    When I try `wp plugin delete edit-flow`
    Then STDOUT should be:
      """
      Success: Plugin already deleted.
      """
    And STDERR should be:
      """
      Warning: The 'edit-flow' plugin could not be found.
      """
    And the return code should be 0
