Feature: Set the value of a constant or variable defined in wp-config.php file and wp-custom-config.php files

  Scenario: Update the value of an existing wp-config.php constant
    Given a WP install

    When I run `wp config set DB_HOST db.example.com`
    Then STDOUT should be:
      """
      Success: Updated the constant 'DB_HOST' in the 'wp-config.php' file with the value 'db.example.com'.
      """

    When I run `wp config get DB_HOST`
    Then STDOUT should be:
      """
      db.example.com
      """

  @custom-config-file
  Scenario: Update the value of an existing wp-custom-config.php constant
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config set DB_HOST db.example.com --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Updated the constant 'DB_HOST' in the 'wp-custom-config.php' file with the value 'db.example.com'.
      """

    When I run `wp config get DB_HOST --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      db.example.com
      """

  Scenario: Add a new value to wp-config.php
    Given a WP install
    When I run `wp config set NEW_CONSTANT constant_value --type=constant`
    Then STDOUT should be:
      """
      Success: Added the constant 'NEW_CONSTANT' to the 'wp-config.php' file with the value 'constant_value'.
      """

    When I run `wp config get NEW_CONSTANT`
    Then STDOUT should be:
      """
      constant_value
      """

    When I run `wp config set new_variable variable_value --type=variable`
    Then STDOUT should be:
      """
      Success: Added the variable 'new_variable' to the 'wp-config.php' file with the value 'variable_value'.
      """

    When I run `wp config get new_variable`
    Then STDOUT should be:
      """
      variable_value
      """

    When I run `wp config set DEFAULT_TO_CONSTANT some_value`
    Then STDOUT should be:
      """
      Success: Added the constant 'DEFAULT_TO_CONSTANT' to the 'wp-config.php' file with the value 'some_value'.
      """

    When I run `wp config get DEFAULT_TO_CONSTANT`
    Then STDOUT should be:
      """
      some_value
      """

  Scenario: Add a new value to wp-custom-config.php
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}  --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config set NEW_CONSTANT constant_value --type=constant --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the constant 'NEW_CONSTANT' to the 'wp-custom-config.php' file with the value 'constant_value'.
      """

    When I run `wp config get NEW_CONSTANT --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      constant_value
      """

    When I run `wp config set new_variable variable_value --type=variable --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the variable 'new_variable' to the 'wp-custom-config.php' file with the value 'variable_value'.
      """

    When I run `wp config get new_variable --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      variable_value
      """

    When I run `wp config set DEFAULT_TO_CONSTANT some_value --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the constant 'DEFAULT_TO_CONSTANT' to the 'wp-custom-config.php' file with the value 'some_value'.
      """

    When I run `wp config get DEFAULT_TO_CONSTANT --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      some_value
      """

  Scenario: Updating a non-existent value  in wp-config.php without --add
    Given a WP install

    When I try `wp config set NEW_CONSTANT constant_value --no-add`
    Then STDERR should be:
      """
      Error: The constant or variable 'NEW_CONSTANT' is not defined in the 'wp-config.php' file.
      """

    When I try `wp config set NEW_CONSTANT constant_value --type=constant --no-add`
    Then STDERR should be:
      """
      Error: The constant 'NEW_CONSTANT' is not defined in the 'wp-config.php' file.
      """

    When I try `wp config set NEW_CONSTANT constant_value --type=variable --no-add`
    Then STDERR should be:
      """
      Error: The variable 'NEW_CONSTANT' is not defined in the 'wp-config.php' file.
      """

    When I try `wp config set table_prefix new_prefix --type=constant --no-add`
    Then STDERR should be:
      """
      Error: The constant 'table_prefix' is not defined in the 'wp-config.php' file.
      """

    When I run `wp config set table_prefix new_prefix --type=variable --no-add`
    Then STDOUT should be:
      """
      Success: Updated the variable 'table_prefix' in the 'wp-config.php' file with the value 'new_prefix'.
      """

    When I try `wp config set DB_HOST db.example.com --type=variable --no-add`
    Then STDERR should be:
      """
      Error: The variable 'DB_HOST' is not defined in the 'wp-config.php' file.
      """

    When I run `wp config set DB_HOST db.example.com --type=constant --no-add`
    Then STDOUT should be:
      """
      Success: Updated the constant 'DB_HOST' in the 'wp-config.php' file with the value 'db.example.com'.
      """

  @custom-config-file
  Scenario: Updating a non-existent value  in wp-custom-config.php without --add
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}  --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I try `wp config set NEW_CONSTANT constant_value --no-add --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant or variable 'NEW_CONSTANT' is not defined in the 'wp-custom-config.php' file.
      """

    When I try `wp config set NEW_CONSTANT constant_value --type=constant --no-add --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant 'NEW_CONSTANT' is not defined in the 'wp-custom-config.php' file.
      """

    When I try `wp config set NEW_CONSTANT constant_value --type=variable --no-add --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The variable 'NEW_CONSTANT' is not defined in the 'wp-custom-config.php' file.
      """

    When I try `wp config set table_prefix new_prefix --type=constant --no-add --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The constant 'table_prefix' is not defined in the 'wp-custom-config.php' file.
      """

    When I run `wp config set table_prefix new_prefix --type=variable --no-add --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Updated the variable 'table_prefix' in the 'wp-custom-config.php' file with the value 'new_prefix'.
      """

    When I try `wp config set DB_HOST db.example.com --type=variable --no-add --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: The variable 'DB_HOST' is not defined in the 'wp-custom-config.php' file.
      """

    When I run `wp config set DB_HOST db.example.com --type=constant --no-add --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Updated the constant 'DB_HOST' in the 'wp-custom-config.php' file with the value 'db.example.com'.
      """

  Scenario: Update raw values in wp-config.php
    Given a WP install

    When I run `wp config set WP_TEST true --type=constant`
    Then STDOUT should be:
      """
      Success: Added the constant 'WP_TEST' to the 'wp-config.php' file with the value 'true'.
      """

    When I run `wp config list WP_TEST --strict --format=json`
    Then STDOUT should contain:
      """
      {"name":"WP_TEST","value":"true","type":"constant"}
      """

    When I run `wp config set WP_TEST true --raw`
    Then STDOUT should be:
      """
      Success: Updated the constant 'WP_TEST' in the 'wp-config.php' file with the raw value 'true'.
      """

    When I run `wp config list WP_TEST --strict --format=json`
    Then STDOUT should contain:
      """
      {"name":"WP_TEST","value":true,"type":"constant"}
      """

  @custom-config-file
  Scenario: Update raw values in wp-config.php
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}  --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config set WP_TEST true --type=constant --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the constant 'WP_TEST' to the 'wp-custom-config.php' file with the value 'true'.
      """

    When I run `wp config list WP_TEST --strict --format=json --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      {"name":"WP_TEST","value":"true","type":"constant"}
      """

    When I run `wp config set WP_TEST true --raw --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Updated the constant 'WP_TEST' in the 'wp-custom-config.php' file with the raw value 'true'.
      """

    When I run `wp config list WP_TEST --strict --format=json --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      {"name":"WP_TEST","value":true,"type":"constant"}
      """

  Scenario: Ambiguous change requests for wp-config.php throw errors
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

    When I try `wp config set SOME_NAME some_value`
    Then STDERR should be:
      """
      Error: Found both a constant and a variable 'SOME_NAME' in the 'wp-config.php' file. Use --type=<type> to disambiguate.
      """

  @custom-config-file
  Scenario: Ambiguous change requests for wp-custom-config.php throw errors
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}  --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config set SOME_NAME some_value --type=constant  --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the constant 'SOME_NAME' to the 'wp-custom-config.php' file with the value 'some_value'.
      """

    When I run `wp config set SOME_NAME some_value --type=variable  --config-file='wp-custom-config.php'`
    Then STDOUT should be:
      """
      Success: Added the variable 'SOME_NAME' to the 'wp-custom-config.php' file with the value 'some_value'.
      """

    When I run `wp config list --fields=name,type SOME_NAME --strict --config-file='wp-custom-config.php'`
    Then STDOUT should be a table containing rows:
      | name      | type     |
      | SOME_NAME | constant |
      | SOME_NAME | variable |

    When I try `wp config set SOME_NAME some_value --config-file='wp-custom-config.php'`
    Then STDERR should be:
      """
      Error: Found both a constant and a variable 'SOME_NAME' in the 'wp-custom-config.php' file. Use --type=<type> to disambiguate.
      """

  Scenario: Additions can be properly placed in wp-config.php
    Given a WP install
    And a wp-config.php file:
      """
      define( 'CONST_A', 'val-a' );
      /** ANCHOR */
      define( 'CONST_B', 'val-b' );
      require_once( ABSPATH . 'wp-settings.php' );
      """

    When I run `wp config set SOME_NAME some_value --type=constant --anchor="/** ANCHOR */" --placement=before --separator="\n"`
    Then STDOUT should be:
      """
      Success: Added the constant 'SOME_NAME' to the 'wp-config.php' file with the value 'some_value'.
      """
    And the wp-config.php file should be:
      """
      define( 'CONST_A', 'val-a' );
      define( 'SOME_NAME', 'some_value' );
      /** ANCHOR */
      define( 'CONST_B', 'val-b' );
      require_once( ABSPATH . 'wp-settings.php' );
      """

    When I run `wp config set ANOTHER_NAME another_value --type=constant --anchor="/** ANCHOR */" --placement=after --separator="\n"`
    Then STDOUT should be:
      """
      Success: Added the constant 'ANOTHER_NAME' to the 'wp-config.php' file with the value 'another_value'.
      """
    And the wp-config.php file should be:
      """
      define( 'CONST_A', 'val-a' );
      define( 'SOME_NAME', 'some_value' );
      /** ANCHOR */
      define( 'ANOTHER_NAME', 'another_value' );
      define( 'CONST_B', 'val-b' );
      require_once( ABSPATH . 'wp-settings.php' );
      """

    Scenario: Additions can be properly placed in wp-custom-config.php
    Given a WP install
    And a wp-custom-config.php file:
      """
      define( 'CONST_A', 'val-a' );
      /** ANCHOR */
      define( 'CONST_B', 'val-b' );
      require_once( ABSPATH . 'wp-settings.php' );
      """

    When I run `wp config set SOME_NAME some_value --type=constant --anchor="/** ANCHOR */" --placement=before --separator="\n" --config-file="wp-custom-config.php"`
    Then STDOUT should be:
      """
      Success: Added the constant 'SOME_NAME' to the 'wp-custom-config.php' file with the value 'some_value'.
      """
    And the wp-custom-config.php file should be:
      """
      define( 'CONST_A', 'val-a' );
      define( 'SOME_NAME', 'some_value' );
      /** ANCHOR */
      define( 'CONST_B', 'val-b' );
      require_once( ABSPATH . 'wp-settings.php' );
      """

    When I run `wp config set ANOTHER_NAME another_value --type=constant --anchor="/** ANCHOR */" --placement=after --separator="\n" --config-file="wp-custom-config.php"`
    Then STDOUT should be:
      """
      Success: Added the constant 'ANOTHER_NAME' to the 'wp-custom-config.php' file with the value 'another_value'.
      """
    And the wp-custom-config.php file should be:
      """
      define( 'CONST_A', 'val-a' );
      define( 'SOME_NAME', 'some_value' );
      /** ANCHOR */
      define( 'ANOTHER_NAME', 'another_value' );
      define( 'CONST_B', 'val-b' );
      require_once( ABSPATH . 'wp-settings.php' );
      """
