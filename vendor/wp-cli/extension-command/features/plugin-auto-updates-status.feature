Feature: Show the status of auto-updates for WordPress plugins

  Background:
    Given a WP install
    And I run `wp plugin install duplicate-post`

  @require-wp-5.5
  Scenario: Show an error if required params are missing
    When I try `wp plugin auto-updates status`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: Please specify one or more plugins, or use --all.
      """

  @require-wp-5.5
  Scenario: Show the status of auto-updates of a single plugin
    When I run `wp plugin auto-updates status hello`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | hello          | disabled |
    And the return code should be 0

  @require-wp-5.5
  Scenario: Show the status of auto-updates multiple plugins
    When I run `wp plugin auto-updates status duplicate-post hello`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | duplicate-post | disabled |
      | hello          | disabled |
    And the return code should be 0

  @require-wp-5.5
  Scenario: Show the status of auto-updates all installed plugins
    When I run `wp plugin auto-updates status --all`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | akismet        | disabled |
      | duplicate-post | disabled |
      | hello          | disabled |
    And the return code should be 0

    When I run `wp plugin auto-updates enable --all`
    And I run `wp plugin auto-updates status --all`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | akismet        | enabled  |
      | duplicate-post | enabled  |
      | hello          | enabled  |
    And the return code should be 0

  @require-wp-5.5
  Scenario: The status can be filtered to only show enabled or disabled plugins
    Given I run `wp plugin auto-updates enable hello`

    When I run `wp plugin auto-updates status --all`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | akismet        | disabled |
      | duplicate-post | disabled |
      | hello          | enabled  |
    And the return code should be 0

    When I run `wp plugin auto-updates status --all --enabled-only`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | hello          | enabled  |
    And the return code should be 0

    When I run `wp plugin auto-updates status --all --disabled-only`
    Then STDOUT should be a table containing rows:
      | name           | status   |
      | akismet        | disabled |
      | duplicate-post | disabled |
    And the return code should be 0

    When I try `wp plugin auto-updates status --all --enabled-only --disabled-only`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: --enabled-only and --disabled-only are mutually exclusive and cannot be used at the same time.
      """

  @require-wp-5.5
  Scenario: The fields can be shown individually
    Given I run `wp plugin auto-updates enable hello`

    When I run `wp plugin auto-updates status --all --disabled-only --field=name`
    Then STDOUT should be:
      """
      akismet
      duplicate-post
      """

    When I run `wp plugin auto-updates status hello --field=status`
    Then STDOUT should be:
      """
      enabled
      """
