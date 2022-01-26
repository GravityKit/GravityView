Feature: Manage WordPress plugins

  Scenario: Create, activate and check plugin status
    Given a WP install
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}

    When I run `wp plugin scaffold --skip-tests plugin1`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/plugin1/plugin1.php file should exist
    And the {PLUGIN_DIR}/zombieland/phpunit.xml.dist file should not exist

    When I run `wp plugin path plugin1`
    Then STDOUT should be:
      """
      {PLUGIN_DIR}/plugin1/plugin1.php
      """

    When I run `wp plugin path plugin1 --dir`
    Then STDOUT should be:
      """
      {PLUGIN_DIR}/plugin1
      """

    When I run `wp plugin scaffold Zombieland`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/Zombieland/Zombieland.php file should exist
    And the {PLUGIN_DIR}/Zombieland/phpunit.xml.dist file should exist

    # Ensure case sensitivity
    When I try `wp plugin status zombieLand`
    Then STDERR should contain:
      """
      The 'zombieLand' plugin could not be found.
      """
    And STDOUT should be empty
    And the return code should be 1

    # Check that the inner-plugin is not picked up
    When I run `mv {PLUGIN_DIR}/plugin1 {PLUGIN_DIR}/Zombieland/`
    And I run `wp plugin status Zombieland`
    Then STDOUT should contain:
      """
      Plugin Zombieland details:
          Name: Zombieland
          Status: Inactive
          Version: 0.1.0
          Author: YOUR NAME HERE
          Description: PLUGIN DESCRIPTION HERE
      """

    When I run `wp plugin activate Zombieland`
    Then STDOUT should not be empty

    When I run `wp plugin status Zombieland`
    Then STDOUT should contain:
      """
          Status: Active
      """

    When I run `wp plugin status`
    Then STDOUT should not be empty

    When I run `wp plugin list`
    Then STDOUT should be a table containing rows:
      | name       | status | update | version |
      | Zombieland | active | none   | 0.1.0   |

    When I try `wp plugin uninstall Zombieland`
    Then STDERR should be:
      """
      Warning: The 'Zombieland' plugin is active.
      Error: No plugins uninstalled.
      """
    And the return code should be 1

    When I run `wp plugin deactivate Zombieland`
    Then STDOUT should not be empty

    When I run `wp option get recently_activated`
    Then STDOUT should contain:
      """
      Zombieland/Zombieland.php
      """

    When I run `wp plugin uninstall Zombieland`
    Then STDOUT should be:
      """
      Uninstalled and deleted 'Zombieland' plugin.
      Success: Uninstalled 1 of 1 plugins.
      """
    And the {PLUGIN_DIR}/zombieland file should not exist

    When I try the previous command again
    Then STDERR should contain:
      """
      Warning:
      """
    And STDERR should contain:
      """
      Zombieland
      """
    And STDERR should contain:
      """
      Error: No plugins uninstalled.
      """
    And STDOUT should be empty
    And the return code should be 1

  @require-wp-4.0
  Scenario: Install a plugin, activate, then force install an older version of the plugin
    Given a WP install

    When I run `wp plugin install wordpress-importer --version=0.5 --force`
    Then STDOUT should not be empty

    When I run `wp plugin list --name=wordpress-importer --field=update_version`
    Then STDOUT should not be empty
    And save STDOUT as {UPDATE_VERSION}

    When I run `wp plugin list --fields=name,status,update,version,update_version`
    Then STDOUT should be a table containing rows:
      | name               | status   | update    | version | update_version   |
      | wordpress-importer | inactive | available | 0.5     | {UPDATE_VERSION} |

    When I run `wp plugin activate wordpress-importer`
    Then STDOUT should not be empty

    When I run `wp plugin install wordpress-importer --version=0.5 --force`
    Then STDOUT should not be empty

    When I run `wp plugin list`
    Then STDOUT should be a table containing rows:
      | name               | status   | update    | version |
      | wordpress-importer | active   | available | 0.5     |

    When I try `wp plugin update`
    Then STDERR should be:
      """
      Error: Please specify one or more plugins, or use --all.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp plugin update --all --format=summary | grep 'updated successfully from'`
    Then STDOUT should contain:
      """
      WordPress Importer updated successfully from version 0.5 to version
      """

    When I try `wp plugin update xxx yyy`
    Then STDERR should contain:
      """
      Warning: The 'xxx' plugin could not be found.
      """
    And STDERR should contain:
      """
      Warning: The 'yyy' plugin could not be found.
      """
    And STDERR should contain:
      """
      Error: No plugins updated.
      """
    And the return code should be 1

    When I run `wp plugin install wordpress-importer --version=0.5 --force`
    Then STDOUT should not be empty

    When I try `wp plugin update xxx wordpress-importer yyy`
    Then STDERR should contain:
      """
      Warning: The 'xxx' plugin could not be found.
      """
    And STDERR should contain:
      """
      Warning: The 'yyy' plugin could not be found.
      """
    And STDERR should contain:
      """
      Error: Only updated 1 of 3 plugins.
      """
    And the return code should be 1

  Scenario: Activate a network-only plugin on single site
    Given a WP install
    And a wp-content/plugins/network-only.php file:
      """
      <?php
      // Plugin Name: Example Plugin
      // Network: true
      """

    When I run `wp plugin activate network-only`
    Then STDOUT should be:
      """
      Plugin 'network-only' activated.
      Success: Activated 1 of 1 plugins.
      """

    When I run `wp plugin status network-only`
    Then STDOUT should contain:
      """
          Status: Active
      """

  Scenario: Activate a network-only plugin on multisite
    Given a WP multisite install
    And a wp-content/plugins/network-only.php file:
      """
      <?php
      // Plugin Name: Example Plugin
      // Network: true
      """

    When I run `wp plugin activate network-only`
    Then STDOUT should be:
      """
      Plugin 'network-only' network activated.
      Success: Activated 1 of 1 plugins.
      """

    When I run `wp plugin status network-only`
    Then STDOUT should contain:
      """
          Status: Network Active
      """

  Scenario: Network activate a plugin
    Given a WP multisite install

    When I run `wp plugin activate akismet`
    Then STDOUT should be:
      """
      Plugin 'akismet' activated.
      Success: Activated 1 of 1 plugins.
      """

    When I run `wp plugin list --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name            | status           | file                |
      | akismet         | active           | akismet/akismet.php |

    When I try `wp plugin activate akismet`
    Then STDERR should contain:
      """
      Warning: Plugin 'akismet' is already active.
      """
    And STDOUT should be:
      """
      Success: Plugin already activated.
      """
    And the return code should be 0

    When I run `wp plugin activate akismet --network`
    Then STDOUT should be:
      """
      Plugin 'akismet' network activated.
      Success: Network activated 1 of 1 plugins.
      """

    When I try `wp plugin activate akismet --network`
    Then STDERR should be:
      """
      Warning: Plugin 'akismet' is already network active.
      """
    And STDOUT should be:
      """
      Success: Plugin already network activated.
      """
    And the return code should be 0

    When I try `wp plugin deactivate akismet`
    Then STDERR should be:
      """
      Warning: Plugin 'akismet' is network active and must be deactivated with --network flag.
      Error: No plugins deactivated.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp plugin deactivate akismet --network`
    Then STDOUT should be:
      """
      Plugin 'akismet' network deactivated.
      Success: Network deactivated 1 of 1 plugins.
      """
    And the return code should be 0

    When I try `wp plugin deactivate akismet`
    Then STDERR should be:
      """
      Warning: Plugin 'akismet' isn't active.
      """
    And STDOUT should be:
      """
      Success: Plugin already deactivated.
      """
    And the return code should be 0

  Scenario: List plugins
    Given a WP install

    When I run `wp plugin activate akismet hello`
    Then STDOUT should not be empty

    When I run `wp plugin list --status=inactive --field=name`
    Then STDOUT should be empty

    When I run `wp plugin list --status=active --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name       | status   | file                |
      | akismet    | active   | akismet/akismet.php |

  Scenario: List plugin by multiple statuses
    Given a WP multisite install
    And a wp-content/plugins/network-only.php file:
      """
      <?php
      // Plugin Name: Example Plugin
      // Network: true
      """

    When I run `wp plugin activate akismet hello`
    Then STDOUT should not be empty

    When I run `wp plugin install wordpress-importer`
    Then STDOUT should not be empty

    When I run `wp plugin activate network-only`
    Then STDOUT should not be empty

    When I run `wp plugin list --status=active-network,inactive --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name               | status         | file                                      |
      | network-only       | active-network | network-only.php                          |
      | wordpress-importer | inactive       | wordpress-importer/wordpress-importer.php |

    When I run `wp plugin list --status=active,inactive --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name               | status   | file                                      |
      | akismet            | active   | akismet/akismet.php                       |
      | wordpress-importer | inactive | wordpress-importer/wordpress-importer.php |

  Scenario: Flag `--skip-update-check` skips update check when running `wp plugin list`
    Given a WP install

    When I run `wp plugin install wordpress-importer --version=0.2`
    Then STDOUT should contain:
      """
      Plugin installed successfully.
      """

    When I run `wp plugin list --fields=name,status,update --status=inactive`
    Then STDOUT should be a table containing rows:
      | name               | status   | update    |
      | wordpress-importer | inactive | available |

    When I run `wp transient delete update_plugins --network`
    Then STDOUT should be:
      """
      Success: Transient deleted.
      """

    When I run `wp plugin list --fields=name,status,update --status=inactive --skip-update-check`
    Then STDOUT should be a table containing rows:
      | name               | status   | update   |
      | wordpress-importer | inactive | none     |

  Scenario: Install a plugin when directory doesn't yet exist
    Given a WP install

    When I run `rm -rf wp-content/plugins`
    And I run `if test -d wp-content/plugins; then echo "fail"; fi`
    Then STDOUT should be empty

    When I run `wp plugin install wordpress-importer --activate`
    Then STDOUT should not be empty

    When I run `wp plugin list --status=active --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name               | status   | file                                      |
      | wordpress-importer | active   | wordpress-importer/wordpress-importer.php |

  Scenario: Plugin name with HTML entities
    Given a WP install

    When I run `wp plugin install debug-bar-list-dependencies`
    Then STDOUT should contain:
      """
      Installing Debug Bar List Script & Style Dependencies
      """

  Scenario: Enable and disable all plugins
    Given a WP install

    When I run `wp plugin activate --all`
    Then STDOUT should be:
      """
      Plugin 'akismet' activated.
      Plugin 'hello' activated.
      Success: Activated 2 of 2 plugins.
      """

    When I run `wp plugin activate --all`
    Then STDOUT should be:
      """
      Success: Plugins already activated.
      """

    When I run `wp plugin list --field=status`
    Then STDOUT should be:
      """
      active
      active
      must-use
      must-use
      """

    When I run `wp plugin deactivate --all`
    Then STDOUT should be:
      """
      Plugin 'akismet' deactivated.
      Plugin 'hello' deactivated.
      Success: Deactivated 2 of 2 plugins.
      """

    When I run `wp plugin deactivate --all`
    Then STDOUT should be:
      """
      Success: Plugins already deactivated.
      """

    When I run `wp plugin list --field=status`
    Then STDOUT should be:
      """
      inactive
      inactive
      must-use
      must-use
      """

  Scenario: Deactivate and uninstall a plugin, part one
    Given a WP install
    And these installed and active plugins:
      """
      wordpress-importer
      """

    When I run `wp plugin deactivate wordpress-importer --uninstall`
    Then STDOUT should be:
      """
      Plugin 'wordpress-importer' deactivated.
      Uninstalling 'wordpress-importer'...
      Uninstalled and deleted 'wordpress-importer' plugin.
      Success: Deactivated 1 of 1 plugins.
      """

    When I try `wp plugin get wordpress-importer`
    Then STDERR should be:
      """
      Error: The 'wordpress-importer' plugin could not be found.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Deactivate and uninstall a plugin, part two
    Given a WP install
    And these installed and active plugins:
      """
      wordpress-importer
      """

    When I run `wp plugin uninstall wordpress-importer --deactivate`
    Then STDOUT should be:
      """
      Deactivating 'wordpress-importer'...
      Plugin 'wordpress-importer' deactivated.
      Uninstalled and deleted 'wordpress-importer' plugin.
      Success: Uninstalled 1 of 1 plugins.
      """

    When I try `wp plugin get wordpress-importer`
    Then STDERR should be:
      """
      Error: The 'wordpress-importer' plugin could not be found.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Uninstall a plugin without deleting
    Given a WP install

    When I run `wp plugin install akismet --version=2.5.7 --force`
    Then STDOUT should not be empty

    When I run `wp plugin uninstall akismet --skip-delete`
    Then STDOUT should be:
      """
      Ran uninstall procedure for 'akismet' plugin without deleting.
      Success: Uninstalled 1 of 1 plugins.
      """

  Scenario: Two plugins, one directory
    Given a WP install
    And a wp-content/plugins/handbook/handbook.php file:
      """
      <?php
      /**
       * Plugin Name: Handbook
       * Description: Features for a handbook, complete with glossary and table of contents
       * Author: Nacin
       */
      """
    And a wp-content/plugins/handbook/functionality-for-pages.php file:
      """
      <?php
      /**
       * Plugin Name: Handbook Functionality for Pages
       * Description: Adds handbook-like table of contents to all Pages for a site. Covers Table of Contents and the "watch this page" widget
       * Author: Nacin
       */
      """

    When I run `wp plugin list --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name                             | status   | file                                 |
      | handbook/handbook                | inactive | handbook/handbook.php                |
      | handbook/functionality-for-pages | inactive | handbook/functionality-for-pages.php |

    When I run `wp plugin activate handbook/functionality-for-pages`
    Then STDOUT should not be empty

    When I run `wp plugin list --fields=name,status,file`
    Then STDOUT should be a table containing rows:
      | name                             | status   | file                                 |
      | handbook/handbook                | inactive | handbook/handbook.php                |
      | handbook/functionality-for-pages | active   | handbook/functionality-for-pages.php |

  Scenario: Install a plugin, then update to a specific version of that plugin
    Given a WP install

    When I run `wp plugin install akismet --version=2.5.7 --force`
    Then STDOUT should not be empty

    When I run `wp plugin update akismet --version=2.6.0`
    Then STDOUT should not be empty

    When I run `wp plugin list --fields=name,version,file`
    Then STDOUT should be a table containing rows:
      | name       | version   | file                |
      | akismet    | 2.6.0     | akismet/akismet.php |

  Scenario: Ignore empty slugs
    Given a WP install

    When I try `wp plugin install ''`
    Then STDERR should contain:
      """
      Warning: Ignoring ambiguous empty slug value.
      """
    And STDOUT should not contain:
      """
      Plugin installed successfully
      """
    And the return code should be 0

  @require-wp-4.7
  Scenario: Plugin hidden by "all_plugins" filter
    Given a WP install
    And these installed and active plugins:
      """
      akismet
      jetpack
      query-monitor
      """
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

    When I run `wp plugin list --fields=name`
    Then STDOUT should not contain:
      """
      query-monitor
      """

  Scenario: Show dropins plugin list
    Given a WP install
    And a wp-content/db-error.php file:
      """
      <?php
      """

    When I run `wp plugin list --status=active`
    Then STDOUT should not contain:
      """
      db-error.php
      """

    When I run `wp plugin list --status=dropin --fields=name,title,description,file`
    Then STDOUT should be a table containing rows:
      | name         | title | description                    | file         |
      | db-error.php |       | Custom database error message. | db-error.php |

  @require-wp-4.0
  Scenario: Validate installed plugin's version.
    Given a WP installation
    And I run `wp plugin uninstall --all`
    And I run `wp plugin install hello-dolly`
    And a wp-content/mu-plugins/test-plugin-update.php file:
      """
      <?php
      /**
       * Plugin Name: Test Plugin Update
       * Description: Fakes installed plugin's data to verify plugin version mismatch
       * Author: WP-CLI tests
       */

      add_filter( 'site_transient_update_plugins', function( $value ) {
          if ( ! is_object( $value ) ) {
              return $value;
          }

          unset( $value->response['hello-dolly/hello.php'] );
          $value->no_update['hello-dolly/hello.php']->new_version = '1.5';

          return $value;
      } );
      ?>
      """

    When I run `wp plugin list --name=hello-dolly  --field=version`
    And save STDOUT as {PLUGIN_VERSION}

    When I run `wp plugin list`
    Then STDOUT should be a table containing rows:
      | name               | status   | update                       | version          |
      | hello-dolly        | inactive | version higher than expected | {PLUGIN_VERSION} |

    When I try `wp plugin update --all`
    Then STDERR should be:
    """
    Warning: hello-dolly: version higher than expected.
    Error: No plugins updated.
    """

    When I try `wp plugin update hello-dolly`
    Then STDERR should be:
    """
    Warning: hello-dolly: version higher than expected.
    Error: No plugins updated.
    """

  Scenario: Only valid status filters are accepted when listing plugins
    Given a WP install

    When I run `wp plugin list`
    Then STDERR should be empty

    When I run `wp plugin list --status=active`
    Then STDERR should be empty

    When I try `wp plugin list --status=invalid-status`
    Then STDERR should be:
      """
      Error: Parameter errors:
       Invalid value specified for 'status' (Filter the output by plugin status.)
      """
