Feature: Delete a constant or variable from the wp-config.php file

  Background:
    Given a WP install

  Scenario: Delete an existing wp-config.php constant
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

  Scenario: Delete a non-existent constant or variable
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

  Scenario: Ambiguous delete requests throw errors
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
