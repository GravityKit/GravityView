Feature: Install WP-CLI packages

  Background:
    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

  Scenario: Install a package requiring a WP-CLI version that doesn't match
    Given an empty directory
    And a new Phar with version "0.23.0"
    And a path-command/command.php file:
      """
      <?php
      WP_CLI::add_command( 'community-command', function(){
        WP_CLI::success( "success!" );
      }, array( 'when' => 'before_wp_load' ) );
      """
    And a path-command/composer.json file:
      """
      {
        "name": "wp-cli/community-command",
        "description": "A demo community command.",
        "license": "MIT",
        "minimum-stability": "dev",
        "autoload": {
          "files": [ "command.php" ]
        },
        "require": {
          "wp-cli/wp-cli": ">=0.24.0"
        },
        "require-dev": {
          "behat/behat": "~2.5"
        }
      }
      """

    When I try `{PHAR_PATH} package install path-command`
    Then STDOUT should contain:
      """
      wp-cli/community-command dev-master requires wp-cli/wp-cli >=0.24.0
      """
    And STDERR should contain:
      """
      Error: Package installation failed
      """
    And the return code should be 1

    When I run `cat {PACKAGE_PATH}composer.json`
    Then STDOUT should contain:
      """
      "version": "0.23.0",
      """

  Scenario: Install a package requiring a WP-CLI version that does match
    Given an empty directory
    And a new Phar with version "0.23.0"
    And a path-command/command.php file:
      """
      <?php
      WP_CLI::add_command( 'community-command', function(){
        WP_CLI::success( "success!" );
      }, array( 'when' => 'before_wp_load' ) );
      """
    And a path-command/composer.json file:
      """
      {
        "name": "wp-cli/community-command",
        "description": "A demo community command.",
        "license": "MIT",
        "minimum-stability": "dev",
        "autoload": {
          "files": [ "command.php" ]
        },
        "require": {
          "wp-cli/wp-cli": ">=0.22.0"
        },
        "require-dev": {
          "behat/behat": "~2.5"
        }
      }
      """

    # Allow for composer/ca-bundle using `openssl_x509_parse()` which throws PHP warnings on old versions of PHP.
    When I try `{PHAR_PATH} package install path-command`
    Then STDOUT should contain:
      """
      Success: Package installed.
      """
    And the return code should be 0

    When I run `cat {PACKAGE_PATH}composer.json`
    Then STDOUT should contain:
      """
      "version": "0.23.0",
      """

  Scenario: Install a package requiring a WP-CLI alpha version that does match
    Given an empty directory
    And a new Phar with version "0.23.0-alpha-90ecad6"
    And a path-command/command.php file:
      """
      <?php
      WP_CLI::add_command( 'community-command', function(){
        WP_CLI::success( "success!" );
      }, array( 'when' => 'before_wp_load' ) );
      """
    And a path-command/composer.json file:
      """
      {
        "name": "wp-cli/community-command",
        "description": "A demo community command.",
        "license": "MIT",
        "minimum-stability": "dev",
        "autoload": {
          "files": [ "command.php" ]
        },
        "require": {
          "wp-cli/wp-cli": ">=0.22.0"
        },
        "require-dev": {
          "behat/behat": "~2.5"
        }
      }
      """

    # Allow for composer/ca-bundle using `openssl_x509_parse()` which throws PHP warnings on old versions of PHP.
    When I try `{PHAR_PATH} package install path-command`
    Then STDOUT should contain:
      """
      Success: Package installed.
      """
    And the return code should be 0

    When I run `cat {PACKAGE_PATH}composer.json`
    Then STDOUT should contain:
      """
      "version": "0.23.0-alpha",
      """
