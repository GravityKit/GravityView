Feature: Enable auto-updates for WordPress themes

  Background:
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentysixteen`
    And I run `wp theme install twentyseventeen`
    And I run `wp theme install twentynineteen`
    And I try `wp theme auto-updates disable --all`

  @require-wp-5.5
  Scenario: Show an error if required params are missing
    When I try `wp theme auto-updates enable`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: Please specify one or more themes, or use --all.
      """

  @require-wp-5.5
  Scenario: Enable auto-updates for a single theme
    When I run `wp theme auto-updates enable twentysixteen`
    Then STDOUT should be:
      """
      Success: Enabled 1 of 1 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Enable auto-updates for multiple themes
    When I run `wp theme auto-updates enable twentysixteen twentyseventeen`
    Then STDOUT should be:
      """
      Success: Enabled 2 of 2 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Enable auto-updates for all themes
    When I run `wp theme auto-updates enable --all`
    Then STDOUT should be:
      """
      Success: Enabled 3 of 3 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Enable auto-updates for already enabled themes
    When I run `wp theme auto-updates enable twentysixteen`
    And I try `wp theme auto-updates enable --all`
    Then STDERR should contain:
      """
      Warning: Auto-updates already enabled for theme twentysixteen.
      """
    And STDERR should contain:
      """
      Error: Only enabled 2 of 3 theme auto-updates.
      """

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already enabled themes
    When I run `wp theme auto-updates enable twentysixteen`
    And I run `wp theme auto-updates enable --all --disabled-only`
    Then STDOUT should be:
      """
      Success: Enabled 2 of 2 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already enabled selection of themes
    When I run `wp theme auto-updates enable twentysixteen`
    And I run `wp theme auto-updates enable twentysixteen twentyseventeen --disabled-only`
    Then STDOUT should be:
      """
      Success: Enabled 1 of 1 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filtering everything away produces an error
    When I run `wp theme auto-updates enable twentysixteen`
    And I try `wp theme auto-updates enable twentysixteen --disabled-only`
    Then STDERR should be:
      """
      Error: No themes provided to enable auto-updates for.
      """
