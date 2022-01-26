Feature: Disable auto-updates for WordPress themes

  Background:
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentysixteen`
    And I run `wp theme install twentyseventeen`
    And I run `wp theme install twentynineteen`
    And I try `wp theme auto-updates enable --all`

  @require-wp-5.5
  Scenario: Show an error if required params are missing
    When I try `wp theme auto-updates disable`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Error: Please specify one or more themes, or use --all.
      """

  @require-wp-5.5
  Scenario: Disable auto-updates for a single theme
    When I run `wp theme auto-updates disable twentysixteen`
    Then STDOUT should be:
      """
      Success: Disabled 1 of 1 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Disable auto-updates for multiple themes
    When I run `wp theme auto-updates disable twentysixteen twentyseventeen`
    Then STDOUT should be:
      """
      Success: Disabled 2 of 2 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Disable auto-updates for all themes
    When I run `wp theme auto-updates disable --all`
    Then STDOUT should be:
      """
      Success: Disabled 3 of 3 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Disable auto-updates for already disabled themes
    When I run `wp theme auto-updates disable twentysixteen`
    And I try `wp theme auto-updates disable --all`
    Then STDERR should contain:
      """
      Warning: Auto-updates already disabled for theme twentysixteen.
      """
    And STDERR should contain:
      """
      Error: Only disabled 2 of 3 theme auto-updates.
      """

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already disabled themes
    When I run `wp theme auto-updates disable twentysixteen`
    And I run `wp theme auto-updates disable --all --enabled-only`
    Then STDOUT should be:
      """
      Success: Disabled 2 of 2 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filter when enabling auto-updates for already disabled selection of themes
    When I run `wp theme auto-updates disable twentysixteen`
    And I run `wp theme auto-updates disable twentysixteen twentyseventeen --enabled-only`
    Then STDOUT should be:
      """
      Success: Disabled 1 of 1 theme auto-updates.
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Filtering everything away produces an error
    When I run `wp theme auto-updates disable twentysixteen`
    And I try `wp theme auto-updates disable twentysixteen --enabled-only`
    Then STDERR should be:
      """
      Error: No themes provided to disable auto-updates for.
      """
