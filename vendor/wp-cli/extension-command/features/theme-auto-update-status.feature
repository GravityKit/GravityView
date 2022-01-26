Feature: Show the status of auto-updates for WordPress themes

  Background:
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentysixteen`
    And I run `wp theme install twentyseventeen`
    And I run `wp theme install twentynineteen`

  @require-wp-5.5
  Scenario: Show an error if required params are missing
    When I try `wp theme auto-updates status`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: Please specify one or more themes, or use --all.
      """

  @require-wp-5.5
  Scenario: Show the status of auto-updates of a single theme
    When I run `wp theme auto-updates status twentysixteen`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentysixteen   | disabled |
    And the return code should be 0

  @require-wp-5.5
  Scenario: Show the status of auto-updates multiple themes
    When I run `wp theme auto-updates status twentyseventeen twentysixteen`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentyseventeen | disabled |
      | twentysixteen   | disabled |
    And the return code should be 0

  @require-wp-5.5
  Scenario: Show the status of auto-updates all installed themes
    When I run `wp theme auto-updates status --all`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentynineteen  | disabled |
      | twentyseventeen | disabled |
      | twentysixteen   | disabled |
    And the return code should be 0

    When I run `wp theme auto-updates enable --all`
    And I run `wp theme auto-updates status --all`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentynineteen  | enabled  |
      | twentyseventeen | enabled  |
      | twentysixteen   | enabled  |
    And the return code should be 0

  @require-wp-5.5
  Scenario: The status can be filtered to only show enabled or disabled themes
    Given I run `wp theme auto-updates enable twentysixteen`

    When I run `wp theme auto-updates status --all`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentynineteen  | disabled |
      | twentyseventeen | disabled |
      | twentysixteen   | enabled  |
    And the return code should be 0

    When I run `wp theme auto-updates status --all --enabled-only`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentysixteen   | enabled  |
    And the return code should be 0

    When I run `wp theme auto-updates status --all --disabled-only`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentynineteen  | disabled |
      | twentyseventeen | disabled |
    And the return code should be 0

    When I try `wp theme auto-updates status --all --enabled-only --disabled-only`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: --enabled-only and --disabled-only are mutually exclusive and cannot be used at the same time.
      """

  @require-wp-5.5
  Scenario: The fields can be shown individually
    Given I run `wp theme auto-updates enable twentysixteen`

    When I run `wp theme auto-updates status --all --disabled-only --field=name`
    Then STDOUT should be:
      """
      twentynineteen
      twentyseventeen
      """

    When I run `wp theme auto-updates status twentysixteen --field=status`
    Then STDOUT should be:
      """
      enabled
      """
