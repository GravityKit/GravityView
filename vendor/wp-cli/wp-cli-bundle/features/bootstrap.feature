Feature: Bootstrap WP-CLI

  Scenario: Override command bundled with freshly built PHAR

    Given an empty directory
    And a new Phar with the same version
    And a cli-override-command/cli.php file:
      """
      <?php
      if ( ! class_exists( 'WP_CLI' ) ) {
        return;
      }
      $autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
      if ( file_exists( $autoload ) ) {
        require_once $autoload;
      }
      WP_CLI::add_command( 'cli', 'CLI_Command', array( 'when' => 'before_wp_load' ) );
      """
    And a cli-override-command/src/CLI_Command.php file:
      """
      <?php
      class CLI_Command extends WP_CLI_Command {
        public function version() {
          WP_CLI::success( "WP-Override-CLI" );
        }
      }
      """
    And a cli-override-command/composer.json file:
      """
      {
        "name": "wp-cli/cli-override",
        "description": "A command that overrides the bundled 'cli' command.",
        "autoload": {
          "psr-4": { "": "src/" },
          "files": [ "cli.php" ]
        },
        "extra": {
          "commands": [
            "cli"
          ]
        }
      }
      """
    And I run `composer install --working-dir={RUN_DIR}/cli-override-command --no-interaction 2>&1`

    When I run `{PHAR_PATH} cli version`
      Then STDOUT should contain:
        """
        WP-CLI
        """

    When I run `{PHAR_PATH} --require=cli-override-command/cli.php cli version`
      Then STDOUT should contain:
        """
        WP-Override-CLI
        """
