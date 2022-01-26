Feature: Create a wp-config file

  Scenario: No wp-config.php
    Given an empty directory
    And WP files

    When I try `wp core is-installed`
    Then the return code should be 1
    And STDERR should not be empty

    When I run `wp core version`
    Then STDOUT should not be empty

    When I try `wp core install`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: 'wp-config.php' not found.
      Either create one manually or use `wp config create`.
      """

    Given a wp-config-extra.php file:
      """
      define( 'WP_DEBUG_LOG', true );
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --extra-php < wp-config-extra.php`
    Then the wp-config.php file should contain:
      """
      'AUTH_SALT',
      """
    And the wp-config.php file should contain:
      """
      define( 'WP_DEBUG_LOG', true );
      """

    When I try the previous command again
    Then the return code should be 1
    And STDERR should not be empty

    Given a wp-config-extra.php file:
      """
      define( 'WP_DEBUG_LOG', true );
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php' --extra-php < wp-config-extra.php`
    Then the wp-custom-config.php file should contain:
      """
      'AUTH_SALT',
      """
    And the wp-custom-config.php file should contain:
      """
      define( 'WP_DEBUG_LOG', true );
      """

    When I try the previous command again
    Then the return code should be 1
    And STDERR should not be empty

    When I run `wp db create`
    Then STDOUT should not be empty

    When I try `wp option get option home`
    Then STDERR should contain:
      """
      Error: The site you have requested is not installed
      """

  @require-wp-4.0
  Scenario: No wp-config.php and WPLANG
    Given an empty directory
    And WP files
    And a wp-config-extra.php file:
      """
      define( 'WP_DEBUG_LOG', true );
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --extra-php < wp-config-extra.php`
    Then the wp-config.php file should not contain:
      """
      define( 'WPLANG', '' );
      """

  Scenario: Configure with existing salts
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --skip-salts --extra-php < /dev/null`
    Then the wp-config.php file should not contain:
      """
      define('AUTH_SALT',
      """
    And the wp-config.php file should not contain:
      """
      define( 'AUTH_SALT',
      """

  @require-php-7.0
  Scenario: Configure with salts generated
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}`
    Then the wp-config.php file should contain:
      """
      define( 'AUTH_SALT',
      """

  @less-than-php-7.0
  Scenario: Configure with salts fetched from WordPress.org
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS}`
    Then the wp-config.php file should contain:
      """
      define( 'AUTH_SALT',
      """

  Scenario: Define WPLANG when running WP < 4.0
    Given an empty directory
    And I run `wp core download --version=3.9 --force`

    When I run `wp core config {CORE_CONFIG_SETTINGS}`
    Then the wp-config.php file should contain:
      """
      define( 'WPLANG', '' );
      """

    When I try `wp core config {CORE_CONFIG_SETTINGS}`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: The 'wp-config.php' file already exists.
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --locale=ja --force`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Success: Generated 'wp-config.php' file.
      """
    And the wp-config.php file should contain:
      """
      define( 'WPLANG', 'ja' );
      """

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file=wp-custom-config.php --locale=ja --force`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Success: Generated 'wp-custom-config.php' file.
      """
    And the wp-custom-config.php file should contain:
      """
      define( 'WPLANG', 'ja' );
      """

  Scenario: Values are properly escaped to avoid creating invalid config files
    Given an empty directory
    And WP files

    When I run `wp config create --skip-check --dbname=somedb --dbuser=someuser --dbpass="PasswordWith'SingleQuotes'"`
    Then the wp-config.php file should contain:
      """
      define( 'DB_PASSWORD', 'PasswordWith\'SingleQuotes\'' )
      """

    When I run `wp config get DB_PASSWORD`
    Then STDOUT should be:
      """
      PasswordWith'SingleQuotes'
      """

