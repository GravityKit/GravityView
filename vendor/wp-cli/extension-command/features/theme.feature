Feature: Manage WordPress themes

  Scenario: Installing and deleting theme
    Given a WP install

    When I run `wp theme install p2`
    Then STDOUT should not be empty

    When I run `wp theme status p2`
    Then STDOUT should contain:
      """
      Theme p2 details:
          Name: P2
      """

    When I run `wp theme path p2`
    Then STDOUT should contain:
      """
      /themes/p2/style.css
      """

    When I run `wp option get stylesheet`
    Then save STDOUT as {PREVIOUS_THEME}

    When I run `wp theme activate p2`
    Then STDOUT should be:
      """
      Success: Switched to 'P2' theme.
      """

    When I try `wp theme delete p2`
    Then STDERR should be:
      """
      Warning: Can't delete the currently active theme: p2
      Error: No themes deleted.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp theme activate {PREVIOUS_THEME}`
    Then STDOUT should not be empty

    When I run `wp theme delete p2`
    Then STDOUT should not be empty

    When I try the previous command again
    Then STDERR should be:
      """
      Warning: The 'p2' theme could not be found.
      """
    And STDOUT should be:
      """
      Success: Theme already deleted.
      """
    And the return code should be 0

    When I run `wp theme list`
    Then STDOUT should not be empty

  Scenario: Checking theme status without theme parameter
    Given a WP install

    When I run `wp theme install classic --activate`
    And I run `wp theme list --field=name --status=inactive | xargs wp theme delete`
    And I run `wp theme status`
    Then STDOUT should be:
      """
      1 installed theme:
        A classic 1.6

      Legend: A = Active
      """

  Scenario: Install a theme, activate, then force install an older version of the theme
    Given a WP install

    When I run `wp theme install p2 --version=1.4.2`
    Then STDOUT should not be empty

    When I run `wp theme list`
    Then STDOUT should be a table containing rows:
      | name  | status   | update    | version   |
      | p2    | inactive | available | 1.4.2     |

    When I run `wp theme activate p2`
    Then STDOUT should not be empty

    When I run `wp theme install p2 --version=1.4.1 --force`
    Then STDOUT should not be empty

    When I run `wp theme list`
    Then STDOUT should be a table containing rows:
      | name  | status   | update    | version   |
      | p2    | active   | available | 1.4.1     |

    When I try `wp theme update`
    Then STDERR should be:
      """
      Error: Please specify one or more themes, or use --all.
      """
    And the return code should be 1

    When I run `wp theme update --all --format=summary | grep 'updated successfully from'`
    Then STDOUT should contain:
      """
      P2 updated successfully from version 1.4.1 to version
      """

    When I run `wp theme install p2 --version=1.4.1 --force`
    Then STDOUT should not be empty

    When I run `wp theme update --all`
    Then STDOUT should contain:
      """
      Success: Updated 1 of 1 themes.
      """

  Scenario: Exclude theme from bulk updates.
    Given a WP install

    When I run `wp theme install p2 --version=1.4.1 --force`
    Then STDOUT should contain:
      """"
      Downloading install
      """"
    And STDOUT should contain:
      """"
      package from https://downloads.wordpress.org/theme/p2.1.4.1.zip...
      """"

    When I run `wp theme status p2`
    Then STDOUT should contain:
      """"
      Update available
      """"

    When I run `wp theme update --all --exclude=p2 | grep 'Skipped'`
    Then STDOUT should contain:
      """
      p2
      """

    When I run `wp theme status p2`
    Then STDOUT should contain:
      """"
      Update available
      """"

  Scenario: Get the path of an installed theme
    Given a WP install

    When I run `wp theme install p2`
    Then STDOUT should not be empty

    When I run `wp theme path p2 --dir`
    Then STDOUT should contain:
       """
       wp-content/themes/p2
       """

  Scenario: Activate an already active theme
    Given a WP install

    When I run `wp theme install p2`
    Then STDOUT should not be empty

    When I run `wp theme activate p2`
    Then STDOUT should be:
      """
      Success: Switched to 'P2' theme.
      """

    When I try `wp theme activate p2`
    Then STDERR should be:
      """
      Warning: The 'P2' theme is already active.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Install a theme when the theme directory doesn't yet exist
    Given a WP install

    When I run `rm -rf wp-content/themes`
    And I run `if test -d wp-content/themes; then echo "fail"; fi`
    Then STDOUT should be empty

    When I run `wp theme install p2 --activate`
    Then STDOUT should not be empty

    When I run `wp theme list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name  | status   |
      | p2    | active   |

  Scenario: Attempt to activate or fetch a broken theme
    Given a WP install

    When I run `mkdir -pv wp-content/themes/myth`
    Then the wp-content/themes/myth directory should exist

    When I try `wp theme activate myth`
    Then STDERR should contain:
      """
      Error: Stylesheet is missing.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme get myth`
    Then STDERR should contain:
      """
      Error: Stylesheet is missing.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme status myth`
    Then STDERR should be:
      """
      Error: Stylesheet is missing.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp theme install myth --force`
    Then STDOUT should contain:
      """
      Theme updated successfully.
      """

  Scenario: Enabling and disabling a theme
  	Given a WP multisite install
    And I run `wp theme install stargazer`
    And I run `wp theme install buntu`

    When I try `wp option get allowedthemes`
    Then the return code should be 1
    # STDERR may or may not be empty, depending on WP-CLI version.
    And STDOUT should be empty

    When I run `wp theme enable buntu`
    Then STDOUT should contain:
       """
       Success: Enabled the 'Buntu' theme.
       """

    When I run `wp option get allowedthemes`
    Then STDOUT should contain:
       """
       'buntu' => true
       """

    When I run `wp theme disable buntu`
    Then STDOUT should contain:
       """
       Success: Disabled the 'Buntu' theme.
       """

    When I run `wp option get allowedthemes`
    Then STDOUT should not contain:
       """
       'buntu' => true
       """

    When I run `wp theme enable buntu --activate`
    Then STDOUT should contain:
       """
       Success: Enabled the 'Buntu' theme.
       Success: Switched to 'Buntu' theme.
       """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp network-meta get 1 allowedthemes`
    Then STDOUT should not contain:
       """
       'buntu' => true
       """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp theme enable buntu --network`
    Then STDOUT should contain:
       """
       Success: Network enabled the 'Buntu' theme.
       """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp network-meta get 1 allowedthemes`
    Then STDOUT should contain:
       """
       'buntu' => true
       """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp theme disable buntu --network`
    Then STDOUT should contain:
       """
       Success: Network disabled the 'Buntu' theme.
       """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp network-meta get 1 allowedthemes`
    Then STDOUT should not contain:
       """
       'buntu' => true
       """

  Scenario: Enabling and disabling a theme without multisite
  	Given a WP install

    When I try `wp theme enable p2`
    Then STDERR should contain:
      """
      Error: This is not a multisite install
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme disable p2`
    Then STDERR should contain:
      """
      Error: This is not a multisite install
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Install a theme, then update to a specific version of that theme
    Given a WP install

    When I run `wp theme install p2 --version=1.4.1`
    Then STDOUT should not be empty

    When I run `wp theme update p2 --version=1.4.2`
    Then STDOUT should not be empty

    When I run `wp theme list --fields=name,version`
    Then STDOUT should be a table containing rows:
      | name       | version   |
      | p2         | 1.4.2     |

  Scenario: Install and attempt to activate a child theme without its parent
    Given a WP install
    And I run `wp theme install buntu`
    And I run `rm -rf wp-content/themes/stargazer`

    When I try `wp theme activate buntu`
    Then STDERR should contain:
      """
      Error: The parent theme is missing. Please install the "stargazer" parent theme.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: List an active theme with its parent
    Given a WP install
    And I run `wp theme install stargazer`
    And I run `wp theme install --activate buntu`

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp theme list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name          | status   |
      | buntu         | active   |
      | stargazer     | parent   |

  Scenario: When updating a theme --format should be the same when using --dry-run
    Given a WP install

    When I run `wp theme install --force twentytwelve --version=1.0`
    Then STDOUT should not be empty

    When I run `wp theme list --name=twentytwelve --field=update_version`
    And save STDOUT as {UPDATE_VERSION}

    When I run `wp theme update twentytwelve --format=summary --dry-run`
    Then STDOUT should contain:
      """
      Available theme updates:
      Twenty Twelve update from version 1.0 to version {UPDATE_VERSION}
      """

    When I run `wp theme update twentytwelve --format=json --dry-run`
    Then STDOUT should be JSON containing:
      """
      [{"name":"twentytwelve","status":"inactive","version":"1.0","update_version":"{UPDATE_VERSION}"}]
      """

    When I run `wp theme update twentytwelve --format=csv --dry-run`
    Then STDOUT should contain:
      """
      name,status,version,update_version
      twentytwelve,inactive,1.0,{UPDATE_VERSION}
      """

  Scenario: When updating a theme --dry-run cannot be used when specifying a specific version.
    Given a WP install

    When I try `wp theme update --all --version=whatever --dry-run`
    Then STDERR should be:
      """
      Error: --dry-run cannot be used together with --version.
      """
    And the return code should be 1

  Scenario: Check json and csv formats when updating a theme
    Given a WP install

    When I run `wp theme install --force twentytwelve --version=1.0`
    Then STDOUT should not be empty

    When I run `wp theme list --name=twentytwelve --field=update_version`
    And save STDOUT as {UPDATE_VERSION}

    When I run `wp theme update twentytwelve --format=json`
    Then STDOUT should contain:
      """
      [{"name":"twentytwelve","old_version":"1.0","new_version":"{UPDATE_VERSION}","status":"Updated"}]
      """

    When I run `wp theme install --force twentytwelve --version=1.0`
    Then STDOUT should not be empty

    When I run `wp theme update twentytwelve --format=csv`
    Then STDOUT should contain:
      """
      name,old_version,new_version,status
      twentytwelve,1.0,{UPDATE_VERSION},Updated
      """

  Scenario: Automatically install parent theme for a child theme
    Given a WP install

    When I try `wp theme status stargazer`
    Then STDERR should contain:
      """
      Error: The 'stargazer' theme could not be found.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp theme install buntu`
    Then STDOUT should contain:
      """
      This theme requires a parent theme. Checking if it is installed
      """

    When I run `wp theme status stargazer`
    Then STDOUT should contain:
      """
      Theme stargazer details:
      """
    And STDERR should be empty

  Scenario: Not giving a slug on update should throw an error unless --all given
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}
    And an empty {THEME_DIR} directory

    # No themes installed. Don't give an error if --all given for BC.
    When I run `wp theme update --all`
    Then STDOUT should be:
      """
      Success: No themes installed.
      """

    When I run `wp theme update --version=0.6 --all`
    Then STDOUT should be:
      """
      Success: No themes installed.
      """

    # One theme installed.
    Given I run `wp theme install p2 --version=1.4.2`

    When I try `wp theme update`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more themes, or use --all.
      """
    And STDOUT should be empty

    When I run `wp theme update --all`
    Then STDOUT should contain:
      """
      Success: Updated
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Theme already updated.
      """

    # Note: if given version then re-installs.
    When I run `wp theme update --version=1.4.2 --all`
    Then STDOUT should contain:
      """
      Success: Installed 1 of 1 themes.
      """

    When I run the previous command again
    Then STDOUT should contain:
      """
      Success: Installed 1 of 1 themes.
      """

    # Two themes installed.
    Given I run `wp theme install --force twentytwelve --version=1.0`

    When I run `wp theme update --all`
    Then STDOUT should contain:
      """
      Success: Updated
      """

    When I run the previous command again
    # BUG: Message should be in plural.
    Then STDOUT should be:
      """
      Success: Theme already updated.
      """

    # Using version with all rarely makes sense and should probably error and do nothing.
    When I try `wp theme update --version=1.4.2 --all`
    Then the return code should be 1
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 themes.
      """
    And STDERR should be:
      """
      Error: Can't find the requested theme's version 1.4.2 in the WordPress.org theme repository (HTTP code 404).
      """

  Scenario: Get status field in theme detail
    Given a WP install

    When I run `wp theme install p2`
    Then STDOUT should not be empty

    When I run `wp theme get p2`
    Then STDOUT should be a table containing rows:
    | Field   | Value     |
    | status  | inactive  |

    When I run `wp theme get p2 --field=status`
    Then STDOUT should be:
       """
       inactive
       """

    When I run `wp theme activate p2`
    Then STDOUT should not be empty

    When I run `wp theme get p2 --field=status`
    Then STDOUT should be:
       """
       active
       """

  Scenario: Theme activation fails when slug does not match exactly
    Given a WP install

    When I run `wp theme install p2`
    Then the return code should be 0

    When I try `wp theme activate P2`
    Then STDERR should contain:
      """
      Error: The 'P2' theme could not be found. Did you mean 'p2'?
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme activate p3`
    Then STDERR should contain:
      """
      Error: The 'p3' theme could not be found. Did you mean 'p2'?
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme activate pb2`
    Then STDERR should contain:
      """
      Error: The 'pb2' theme could not be found. Did you mean 'p2'?
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme activate completelyoff`
    Then STDERR should contain:
      """
      Error: The 'completelyoff' theme could not be found.
      """
    And STDERR should not contain:
      """
      Did you mean
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Only valid status filters are accepted when listing themes
    Given a WP install

    When I run `wp theme list`
    Then STDERR should be empty

    When I run `wp theme list --status=active`
    Then STDERR should be empty

    When I try `wp theme list --status=invalid-status`
    Then STDERR should be:
      """
      Error: Parameter errors:
       Invalid value specified for 'status' (Filter the output by theme status.)
      """
