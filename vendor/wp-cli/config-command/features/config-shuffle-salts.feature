Feature: Refresh the salts in the wp-config.php file

  Scenario: Salts are created properly in wp-config.php when none initially exist
    Given a WP install

    When I try `wp config get AUTH_KEY --type=constant`
    Then STDERR should contain:
    """
    The constant 'AUTH_KEY' is not defined in the 'wp-config.php' file.
    """

    When I run `wp config shuffle-salts`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """

  @custom-config-file
  Scenario: Salts are created properly in wp-custom-config.php when none initially exist
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --skip-salts=true --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """
    When I try `wp config get AUTH_KEY --type=constant --config-file='wp-custom-config.php'`
    Then STDERR should contain:
    """
    The constant 'AUTH_KEY' is not defined in the 'wp-custom-config.php' file.
    """

    When I run `wp config shuffle-salts --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-custom-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """

  Scenario: Shuffle the salts
    Given a WP install

    When I run `wp config shuffle-salts`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """
    And the wp-config.php file should contain:
    """
    define( 'LOGGED_IN_SALT'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}
    When I run `wp config get LOGGED_IN_SALT --type=constant`
    Then save STDOUT as {LOGGED_IN_SALT_ORIG}

    When I run `wp config shuffle-salts`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should not contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """

  Scenario: Shuffle specific salts only
    Given a WP install
    When I run `wp config shuffle-salts`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """
    And the wp-config.php file should contain:
    """
    define( 'LOGGED_IN_SALT'
    """
    And the wp-config.php file should contain:
    """
    define( 'NONCE_KEY'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}
    When I run `wp config get LOGGED_IN_SALT --type=constant`
    Then save STDOUT as {LOGGED_IN_SALT_ORIG}
    When I run `wp config get NONCE_KEY --type=constant`
    Then save STDOUT as {NONCE_KEY_ORIG}

    When I run `wp config shuffle-salts AUTH_KEY NONCE_KEY`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """
    And the wp-config.php file should not contain:
    """
    {NONCE_KEY_ORIG}
    """

  @custom-config-file
  Scenario: Shuffle the salts in custom config file
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
      """
      Generated 'wp-custom-config.php' file.
      """

    When I run `wp config shuffle-salts --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-custom-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """
    And the wp-custom-config.php file should contain:
    """
    define( 'LOGGED_IN_SALT'
    """

    When I run `wp config get AUTH_KEY --type=constant --config-file='wp-custom-config.php'`
    Then save STDOUT as {AUTH_KEY_ORIG}
    When I run `wp config get LOGGED_IN_SALT --type=constant --config-file='wp-custom-config.php'`
    Then save STDOUT as {LOGGED_IN_SALT_ORIG}

    When I run `wp config shuffle-salts --config-file='wp-custom-config.php'`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-custom-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-custom-config.php file should not contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """

  @require-php-7.0
  Scenario: Force adding missing salts to shuffle
    Given a WP install
    When I run `wp config shuffle-salts`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """
    And the wp-config.php file should contain:
    """
    define( 'LOGGED_IN_SALT'
    """
    And the wp-config.php file should not contain:
    """
    define( 'NEW_KEY'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}
    When I run `wp config get LOGGED_IN_SALT --type=constant`
    Then save STDOUT as {LOGGED_IN_SALT_ORIG}

    When I try `wp config shuffle-salts AUTH_KEY NEW_KEY`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And STDERR should contain:
    """
    Warning: Could not shuffle the unknown key 'NEW_KEY'.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """
    And the wp-config.php file should not contain:
    """
    define( 'NEW_KEY'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}

    When I run `wp config shuffle-salts AUTH_KEY NEW_KEY --force`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """
    And the wp-config.php file should contain:
    """
    define( 'NEW_KEY'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}
    When I run `wp config get NEW_KEY --type=constant`
    Then save STDOUT as {NEW_KEY_ORIG}

    When I run `wp config shuffle-salts AUTH_KEY NEW_KEY --force`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """
    And the wp-config.php file should contain:
    """
    define( 'NEW_KEY'
    """
    And the wp-config.php file should not contain:
    """
    {NEW_KEY_ORIG}
    """

  @less-than-php-7.0
  Scenario: Force adding missing salts to shuffle fails on PHP < 7.0
    Given a WP install
    When I run `wp config shuffle-salts`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And the wp-config.php file should contain:
    """
    define( 'AUTH_KEY'
    """
    And the wp-config.php file should contain:
    """
    define( 'LOGGED_IN_SALT'
    """
    And the wp-config.php file should not contain:
    """
    define( 'NEW_KEY'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}
    When I run `wp config get LOGGED_IN_SALT --type=constant`
    Then save STDOUT as {LOGGED_IN_SALT_ORIG}

    When I try `wp config shuffle-salts AUTH_KEY NEW_KEY`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And STDERR should contain:
    """
    Warning: Could not shuffle the unknown key 'NEW_KEY'.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """
    And the wp-config.php file should not contain:
    """
    define( 'NEW_KEY'
    """

    When I run `wp config get AUTH_KEY --type=constant`
    Then save STDOUT as {AUTH_KEY_ORIG}

    When I try `wp config shuffle-salts AUTH_KEY NEW_KEY --force`
    Then STDOUT should contain:
    """
    Shuffled the salt keys.
    """
    And STDERR should contain:
    """
    Warning: Could not add the key 'NEW_KEY' because 'random_int()' is not supported.
    """
    And the wp-config.php file should not contain:
    """
    {AUTH_KEY_ORIG}
    """
    And the wp-config.php file should contain:
    """
    {LOGGED_IN_SALT_ORIG}
    """
    And the wp-config.php file should not contain:
    """
    define( 'NEW_KEY'
    """
