Feature: Backwards compatibility

  Scenario: wp config get --constant=<constant> --> wp config get <name> --type=constant
    Given a WP install

    When I run `wp config get --constant=DB_NAME`
    Then STDOUT should be:
      """
      wp_cli_test
      """

  Scenario: wp config get --global=<global> --> wp config get <name> --type=variable
    Given a WP install

    When I run `wp config get --global=table_prefix`
    Then STDOUT should be:
      """
      wp_
      """

  Scenario: wp config get --> wp config list
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}`
    Then STDOUT should contain:
      """
      Generated 'wp-config.php' file.
      """

    When I run `wp config get --fields=name,type`
    Then STDOUT should be a table containing rows:
      | name               | type     |
      | DB_NAME            | constant |
      | DB_USER            | constant |
      | DB_PASSWORD        | constant |
      | DB_HOST            | constant |

    When I try `wp config get`
    Then STDOUT should be a table containing rows:
      | name | value | type |
