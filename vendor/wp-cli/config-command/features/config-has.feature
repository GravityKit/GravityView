Feature: Check whether the wp-config.php file or the wp-custom-config.php file has a certain constant or variable

  Scenario: Check the existence of an existing wp-config.php constant or variable
    Given a WP install

    When I run `wp config has DB_NAME`
    Then STDOUT should be empty
    And the return code should be 0

    When I run `wp config has DB_USER --type=constant`
    Then STDOUT should be empty
    And the return code should be 0

    When I run `wp config has table_prefix --type=variable`
    Then STDOUT should be empty
    And the return code should be 0

  @custom-config-file
  Scenario: Check the existence of an existing wp-custom-config.php constant or variable
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}  --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """
    When I run `wp config has DB_NAME --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And the return code should be 0

    When I run `wp config has DB_USER --type=constant  --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And the return code should be 0

    When I run `wp config has table_prefix --type=variable  --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And the return code should be 0

  Scenario: Check the existence of a non-existing wp-config.php constant or variable
    Given a WP install

    When I try `wp config has FOO`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has FOO --type=constant`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has FOO --type=variable`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has DB_HOST --type=variable`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has table_prefix --type=constant`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

  @custom-config-file
  Scenario: Check the existence of a non-existing wp-custom-config.php constant or variable
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I try `wp config has FOO --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has FOO --type=constant --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has FOO --type=variable --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has DB_HOST --type=variable --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

    When I try `wp config has table_prefix --type=constant --config-file='wp-custom-config.php'`
    Then STDOUT should be empty
    And STDERR should be empty
    And the return code should be 1

  Scenario: Ambiguous check for wp-config.php throw errors
    Given a WP install

    When I run `wp config set SOME_NAME some_value --type=constant`
    Then STDOUT should be:
      """
      Success: Added the constant 'SOME_NAME' to the 'wp-config.php' file with the value 'some_value'.
      """

    When I run `wp config set SOME_NAME some_value --type=variable`
    Then STDOUT should be:
      """
      Success: Added the variable 'SOME_NAME' to the 'wp-config.php' file with the value 'some_value'.
      """

    When I run `wp config list --fields=name,type SOME_NAME --strict`
    Then STDOUT should be a table containing rows:
      | name      | type     |
      | SOME_NAME | constant |
      | SOME_NAME | variable |

    When I try `wp config has SOME_NAME`
    Then STDERR should be:
      """
      Error: Found both a constant and a variable 'SOME_NAME' in the 'wp-config.php' file. Use --type=<type> to disambiguate.
      """

  @custom-config-file
  Scenario: Ambiguous check for wp-custom-config.php throw errors
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config set SOME_NAME some_value --type=constant --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the constant 'SOME_NAME' to the 'wp-custom-config.php' file with the value 'some_value'.
      """

    When I run `wp config set SOME_NAME some_value --type=variable --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the variable 'SOME_NAME' to the 'wp-custom-config.php' file with the value 'some_value'.
      """

    When I run `wp config list --fields=name,type SOME_NAME --strict --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name      | type     |
      | SOME_NAME | constant |
      | SOME_NAME | variable |

    When I try `wp config has SOME_NAME --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: Found both a constant and a variable 'SOME_NAME' in the 'wp-custom-config.php' file. Use --type=<type> to disambiguate.
      """
