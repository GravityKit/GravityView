Feature: Enable auto-updates for WordPress plugins

  Background:
    Given a WP install
    And I run `wp plugin install duplicate-post`

  @require-wp-5.5
  Scenario: Show an error if required params are missing
    When I try `wp plugin auto-updates enable`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: Please specify one or more plugins, or use --all.
      """

  @require-wp-5.5
  Scenario: Enable auto-updates for a single plugin
    When I run `wp plugin auto-updates enable hello`
    Then STDOUT should be:
      """
      Success: Enabled 1 of 1 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Enable auto-updates for multiple plugins
    When I run `wp plugin auto-updates enable hello duplicate-post`
    Then STDOUT should be:
      """
      Success: Enabled 2 of 2 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Enable auto-updates for all plugins
    When I run `wp plugin auto-updates enable --all`
    Then STDOUT should be:
      """
      Success: Enabled 3 of 3 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Enable auto-updates for already enabled plugins
    When I run `wp plugin auto-updates enable hello`
    And I try `wp plugin auto-updates enable --all`
    Then STDERR should contain:
      """
      Warning: Auto-updates already enabled for plugin hello.
      """
    And STDERR should contain:
      """
      Error: Only enabled 2 of 3 plugin auto-updates.
      """

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already enabled plugins
    When I run `wp plugin auto-updates enable hello`
    And I run `wp plugin auto-updates enable --all --disabled-only`
    Then STDOUT should be:
      """
      Success: Enabled 2 of 2 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already enabled selection of plugins
    When I run `wp plugin auto-updates enable hello`
    And I run `wp plugin auto-updates enable hello duplicate-post --disabled-only`
    Then STDOUT should be:
      """
      Success: Enabled 1 of 1 plugin auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filtering everything away produces an error
    When I run `wp plugin auto-updates enable hello`
    And I try `wp plugin auto-updates enable hello --disabled-only`
    Then STDERR should be:
      """
      Error: No plugins provided to enable auto-updates for.
      """
