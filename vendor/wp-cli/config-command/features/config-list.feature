Feature: List the values of a wp-config.php file

  Scenario: List constants, variables and files included from wp-config.php
    Given an empty directory
    And WP files
    And a wp-config-extra.php file:
      """
      require_once 'custom-include.php';
      """
    And a custom-include.php file:
      """
      <?php // This won't work without this file being empty. ?>
      """
    When I run `wp core config {CORE_CONFIG_SETTINGS} --extra-php < wp-config-extra.php`
    Then STDOUT should contain:
      """
      Generated 'wp-config.php' file.
      """

    When I run `wp config list --fields=name,type`
    Then STDOUT should be a table containing rows:
      | name               | type     |
      | DB_NAME            | constant |
      | DB_USER            | constant |
      | DB_PASSWORD        | constant |
      | DB_HOST            | constant |
      | custom-include.php | includes |

    When I try `wp config list`
    Then STDOUT should be a table containing rows:
      | name | value | type |

  Scenario: Filter the list of values of a wp-config.php file
    Given an empty directory
    And WP files
    When I run `wp core config {CORE_CONFIG_SETTINGS}`
    Then STDOUT should contain:
      """
      Generated 'wp-config.php' file.
      """

    When I run `wp config list --fields=name`
    Then STDOUT should be a table containing rows:
      | name             |
      | table_prefix     |
      | DB_NAME          |
      | DB_USER          |
      | DB_PASSWORD      |
      | DB_HOST          |
      | DB_CHARSET       |
      | DB_COLLATE       |
      | AUTH_KEY         |
      | SECURE_AUTH_KEY  |
      | LOGGED_IN_KEY    |
      | NONCE_KEY        |
      | AUTH_SALT        |
      | SECURE_AUTH_SALT |
      | LOGGED_IN_SALT   |
      | NONCE_SALT       |

    When I run `wp config list --fields=name DB_`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_NAME     |
      | DB_USER     |
      | DB_PASSWORD |
      | DB_HOST     |
      | DB_CHARSET  |
      | DB_COLLATE  |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """

    When I run `wp config list --fields=name DB_HOST`
    Then STDOUT should be a table containing rows:
      | name    |
      | DB_HOST |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_NAME
      """

    When I try `wp config list --fields=name --strict`
    Then STDERR should be:
      """
      Error: The --strict option can only be used in combination with a filter.
      """

    When I try `wp config list --fields=name DB_ --strict`
    Then STDERR should be:
      """
      Error: No matching entries found in 'wp-config.php'.
      """

    When I run `wp config list --fields=name DB_USER DB_PASSWORD`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_USER     |
      | DB_PASSWORD |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

    When I run `wp config list --fields=name DB_USER DB_PASSWORD --strict`
    Then STDOUT should be a table containing rows:
      | name        |
      | DB_USER     |
      | DB_PASSWORD |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      AUTH_KEY
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """

    When I run `wp config list --fields=name _KEY _SALT`
    Then STDOUT should be a table containing rows:
      | name             |
      | AUTH_KEY         |
      | SECURE_AUTH_KEY  |
      | LOGGED_IN_KEY    |
      | NONCE_KEY        |
      | AUTH_SALT        |
      | SECURE_AUTH_SALT |
      | LOGGED_IN_SALT   |
      | NONCE_SALT       |
    Then STDOUT should not contain:
      """
      table_prefix
      """
    Then STDOUT should not contain:
      """
      DB_HOST
      """
