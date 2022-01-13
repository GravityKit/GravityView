Feature: Edit a wp-config file

  Scenario: Edit a wp-config.php file
    Given a WP install

    When I try `EDITOR='ex -i NONE -c q!' wp config edit;`
    Then STDERR should contain:
      """
      Warning: No changes made to wp-config.php, aborted.
      """
    And the return code should be 0

  @custom-config-file
  Scenario: Edit a wp-custom-config.php file
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}  --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I try `EDITOR='ex -i NONE -c q!' wp config edit --config-file=wp-custom-config.php`
    Then STDERR should contain:
      """
      No changes made to wp-custom-config.php, aborted.
      """
    And the return code should be 0
