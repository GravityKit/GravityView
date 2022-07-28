Feature: Activate WordPress plugins

  Background:
    Given a WP install

  Scenario: Activate a plugin that's already installed
    When I run `wp plugin activate akismet`
    Then STDOUT should be:
      """
      Plugin 'akismet' activated.
      Success: Activated 1 of 1 plugins.
      """
    And the return code should be 0

  Scenario: Attempt to activate a plugin that's not installed
    When I try `wp plugin activate edit-flow`
    Then STDERR should be:
      """
      Warning: The 'edit-flow' plugin could not be found.
      Error: No plugins activated.
      """
    And the return code should be 1

    When I try `wp plugin activate akismet hello edit-flow`
    Then STDERR should be:
      """
      Warning: The 'edit-flow' plugin could not be found.
      Error: Only activated 2 of 3 plugins.
      """
    And STDOUT should be:
      """
      Plugin 'akismet' activated.
      Plugin 'hello' activated.
      """
    And the return code should be 1

  Scenario: Activate all when one plugin is hidden by "all_plugins" filter
    Given I run `wp plugin install query-monitor`
    And a wp-content/mu-plugins/hide-qm-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Hide Query Monitor on Production
       * Description: Hides the Query Monitor plugin on production sites
       * Author: WP-CLI tests
       */

       add_filter( 'all_plugins', function( $all_plugins ) {
          unset( $all_plugins['query-monitor/query-monitor.php'] );
          return $all_plugins;
       } );
       """

    When I run `wp plugin activate --all`
    Then STDOUT should contain:
      """
      Plugin 'akismet' activated.
      Plugin 'hello' activated.
      """
    And STDOUT should not contain:
      """
      Plugin 'query-monitor' activated.
      """

  Scenario: Not giving a slug on activate should throw an error unless --all given
    When I try `wp plugin activate`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more plugins, or use --all.
      """
    And STDOUT should be empty

    # But don't give an error if no plugins and --all given for BC.
    Given I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And an empty {PLUGIN_DIR} directory
    When I run `wp plugin activate --all`
    Then STDOUT should be:
      """
      Success: No plugins activated.
      """

  @require-wp-5.2
  Scenario: Activating a plugin that does not meet PHP minimum throws a warning
    Given a wp-content/plugins/high-requirements.php file:
      """
      <?php
      /**
       * Plugin Name: High PHP Requirements
       * Description: This is meant to not activate because PHP version is too low.
       * Author: WP-CLI tests
       * Requires PHP: 99.99
       */
       """
    And I run `wp plugin deactivate --all`
    And I run `php -r 'echo PHP_VERSION;'`
    And save STDOUT as {PHP_VERSION}

    When I try `wp plugin activate high-requirements`
    Then STDERR should contain:
      """
      Failed to activate plugin. Current PHP version ({PHP_VERSION}) does not meet minimum requirements for High PHP Requirements. The plugin requires PHP 99.99.
      """
    And STDOUT should not contain:
      """
      1 out of 1
      """

  Scenario: Adding --exclude with plugin activate --all should exclude the plugins specified via --exclude
    When I try `wp plugin activate --all --exclude=hello`
    Then STDOUT should be:
      """
      Plugin 'akismet' activated.
      Success: Activated 1 of 1 plugins.
      """
    And the return code should be 0
