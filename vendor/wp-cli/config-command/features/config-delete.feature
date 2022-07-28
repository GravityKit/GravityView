Feature: Delete a constant or variable from the wp-config.php file

  Scenario: Delete an existing wp-config.php constant
    Given a WP install

    When I run `wp config delete DB_PASSWORD`
    Then STDOUT should be:
      """
      Success: Deleted the constant 'DB_PASSWORD' from the 'wp-config.php' file.
      """

    When I try `wp config get DB_PASSWORD`
    Then STDERR should be:
      """
      Error: The constant or variable 'DB_PASSWORD' is not defined in the 'wp-config.php' file.
      """
    And STDOUT should be empty

    When I run `wp config delete DB_HOST --type=constant`
    Then STDOUT should be:
      """
      Success: Deleted the constant 'DB_HOST' from the 'wp-config.php' file.
      """

    When I try `wp config get DB_HOST --type=constant`
    Then STDERR should be:
      """
      Error: The constant 'DB_HOST' is not defined in the 'wp-config.php' file.
      """
    And STDOUT should be empty

    When I run `wp config delete table_prefix --type=variable`
    Then STDOUT should be:
      """
      Success: Deleted the variable 'table_prefix' from the 'wp-config.php' file.
      """

    When I try `wp config get table_prefix --type=variable`
    Then STDERR should be:
      """
      Error: The variable 'table_prefix' is not defined in the 'wp-config.php' file.
      """
    And STDOUT should be empty

  @custom-config-file
  Scenario: Delete an existing wp-custom-config.php constant
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config delete DB_PASSWORD --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Deleted the constant 'DB_PASSWORD' from the 'wp-custom-config.php' file.
      """

    When I try `wp config get DB_PASSWORD --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant or variable 'DB_PASSWORD' is not defined in the 'wp-custom-config.php' file.
      """
    And STDOUT should be empty

    When I run `wp config delete DB_HOST --type=constant --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Deleted the constant 'DB_HOST' from the 'wp-custom-config.php' file.
      """

    When I try `wp config get DB_HOST --type=constant --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant 'DB_HOST' is not defined in the 'wp-custom-config.php' file.
      """
    And STDOUT should be empty

    When I run `wp config delete table_prefix --type=variable --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Deleted the variable 'table_prefix' from the 'wp-custom-config.php' file.
      """

    When I try `wp config get table_prefix --type=variable --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The variable 'table_prefix' is not defined in the 'wp-custom-config.php' file.
      """
    And STDOUT should be empty

  Scenario: Delete a non-existent wp-config.php constant or variable
    Given a WP install
    When I try `wp config delete NEW_CONSTANT`
    Then STDERR should be:
      """
      Error: The constant or variable 'NEW_CONSTANT' is not defined in the 'wp-config.php' file.
      """

    When I try `wp config delete NEW_CONSTANT --type=constant`
    Then STDERR should be:
      """
      Error: The constant 'NEW_CONSTANT' is not defined in the 'wp-config.php' file.
      """

    When I try `wp config delete NEW_CONSTANT --type=variable`
    Then STDERR should be:
      """
      Error: The variable 'NEW_CONSTANT' is not defined in the 'wp-config.php' file.
      """

  @custom-config-file
  Scenario: Delete a non-existent wp-custom-config.php constant or variable
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I try `wp config delete NEW_CONSTANT --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant or variable 'NEW_CONSTANT' is not defined in the 'wp-custom-config.php' file.
      """

    When I try `wp config delete NEW_CONSTANT --type=constant --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant 'NEW_CONSTANT' is not defined in the 'wp-custom-config.php' file.
      """

    When I try `wp config delete NEW_CONSTANT --type=variable --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The variable 'NEW_CONSTANT' is not defined in the 'wp-custom-config.php' file.
      """

  Scenario: Ambiguous delete requests for wp-config.php throw errors
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

    When I try `wp config delete SOME_NAME`
    Then STDERR should be:
      """
      Error: Found both a constant and a variable 'SOME_NAME' in the 'wp-config.php' file. Use --type=<type> to disambiguate.
      """

  @custom-config-file
  Scenario: Ambiguous delete requests for wp-custom-config.php throw errors
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

    When I try `wp config delete SOME_NAME --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: Found both a constant and a variable 'SOME_NAME' in the 'wp-custom-config.php' file. Use --type=<type> to disambiguate.
      """
