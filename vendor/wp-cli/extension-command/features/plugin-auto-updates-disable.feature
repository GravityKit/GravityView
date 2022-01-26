Feature: Disable auto-updates for WordPress plugins

  Background:
    Given a WP install
    And I run `wp plugin install duplicate-post`
    And I run `wp plugin auto-updates enable --all`

  @require-wp-5.5
  Scenario: Show an error if required params are missing
    When I try `wp plugin auto-updates disable`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: Please specify one or more plugins, or use --all.
      """

  @require-wp-5.5
  Scenario: Disable auto-updates for a single plugin
    When I run `wp plugin auto-updates disable hello`
    Then STDOUT should be:
      """
      Success: Disabled 1 of 1 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Disable auto-updates for multiple plugins
    When I run `wp plugin auto-updates disable hello duplicate-post`
    Then STDOUT should be:
      """
      Success: Disabled 2 of 2 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Disable auto-updates for all plugins
    When I run `wp plugin auto-updates disable --all`
    Then STDOUT should be:
      """
      Success: Disabled 3 of 3 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Disable auto-updates for already disabled plugins
    When I run `wp plugin auto-updates disable hello`
    And I try `wp plugin auto-updates disable --all`
    Then STDERR should contain:
      """
      Warning: Auto-updates already disabled for plugin hello.
      """
    And STDERR should contain:
      """
      Error: Only disabled 2 of 3 plugin auto-updates.
      """

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already disabled plugins
    When I run `wp plugin auto-updates disable hello`
    And I run `wp plugin auto-updates disable --all --enabled-only`
    Then STDOUT should be:
      """
      Success: Disabled 2 of 2 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already disabled selection of plugins
    When I run `wp plugin auto-updates disable hello`
    And I run `wp plugin auto-updates disable hello duplicate-post --enabled-only`
    Then STDOUT should be:
      """
      Success: Disabled 1 of 1 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filtering everything away produces an error
    When I run `wp plugin auto-updates disable hello`
    And I try `wp plugin auto-updates disable hello --enabled-only`
    Then STDERR should be:
      """
      Error: No plugins provided to disable auto-updates for.
      """
