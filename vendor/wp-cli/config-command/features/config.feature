Feature: Manage wp-config.php file

  Scenario: Getting config should produce error when no config is found
    Given an empty directory

    When I try `wp config list`
    Then STDERR should be:
      """
      Error: 'wp-config.php' not found.
      Either create one manually or use `wp config create`.
      """

    When I try `wp config list --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: 'wp-custom-config.php' not found.
      Either create one manually or use `wp config create`.
      """

    When I try `wp config get SOME_NAME`
    Then STDERR should be:
      """
      Error: 'wp-config.php' not found.
      Either create one manually or use `wp config create`.
      """

    When I try `wp config get SOME_NAME --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: 'wp-custom-config.php' not found.
      Either create one manually or use `wp config create`.
      """

    When I try `wp config path`
    Then STDERR should be:
      """
      Error: 'wp-config.php' not found.
      Either create one manually or use `wp config create`.
      """

  Scenario: Get a wp-config.php file path
    Given a WP install

    When I run `wp config path`
    Then STDOUT should contain:
      """
      wp-config.php
      """
