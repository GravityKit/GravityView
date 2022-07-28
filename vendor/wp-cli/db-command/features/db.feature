Feature: Perform database operations

  Scenario: DB CRUD
    Given an empty directory
    And WP files
    And wp-config.php
    And I run `wp config set WP_DEBUG true --type=constant --raw`
    And a session_no file:
      """
      n
      """
    And a session_yes file:
      """
      y
      """

    When I try `wp option get home`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      (which means your username and password is okay)
      """

    When I run `wp db create`
    Then STDOUT should be:
      """
      Success: Database created.
      """

    When I try the previous command again
    Then the return code should be 1

    When I run `wp db drop --yes`
    Then STDOUT should be:
      """
      Success: Database dropped.
      """

    When I try the previous command again
    Then the return code should be 1

    When I run `wp db drop < session_no`
    # Check for contains only, as the string contains a trailing space.
    Then STDOUT should contain:
      """
      Are you sure you want to drop the 'wp_cli_test' database? [y/n]
      """

    When I run `wp db reset < session_yes`
    Then STDOUT should be:
      """
      Are you sure you want to reset the 'wp_cli_test' database? [y/n] Success: Database reset.
      """

  Scenario: DB CRUD with passed-in dbuser/dbpass
    Given an empty directory
    And WP files
    And wp-config.php
    And I run `wp config set WP_DEBUG true --type=constant --raw`

    When I try `wp option get home`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      (which means your username and password is okay)
      """

    When I run `wp db create --dbuser=wp_cli_test`
    Then STDOUT should be:
      """
      Success: Database created.
      """

    When I try `wp db create --dbuser=no_such_user`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    When I run `wp db drop --yes --dbpass=password1`
    Then STDOUT should be:
      """
      Success: Database dropped.
      """

    When I try `wp db drop --yes --dbpass=no_such_pass`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    When I run `wp db reset --yes --dbuser=wp_cli_test --dbpass=password1`
    Then STDOUT should be:
      """
      Success: Database reset.
      """

    When I try `wp db reset --yes --dbuser=no_such_user`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

  Scenario: Clean up a WordPress install without dropping its database entirely but tables with prefix.
    Given a WP install

    When I run `wp db query "create table custom_table as select * from wp_users;"`
    Then STDOUT should be empty
    And the return code should be 0

    When I run `wp db clean --yes --dbuser=wp_cli_test --dbpass=password1`
    Then STDOUT should be:
      """
      Success: Tables dropped.
      """

    When I run `wp core install --title="WP-CLI Test" --url=example.com --admin_user=admin --admin_password=admin --admin_email=admin@example.com`
    Then STDOUT should not be empty

    When I try `wp db clean --yes --dbuser=no_such_user`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    When I run `wp db tables custom_table --all-tables`
    Then STDOUT should be:
      """
      custom_table
      """
    And the return code should be 0

  Scenario: DB Operations
    Given a WP install

    When I run `wp db optimize`
    Then STDOUT should not be empty

    When I run `wp db repair`
    Then STDOUT should not be empty

  Scenario: DB Operations with passed-in options
    Given a WP install

    When I run `wp db optimize --dbuser=wp_cli_test`
    Then STDOUT should not be empty

    When I try `wp db optimize --dbuser=no_such_user`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    When I try `wp db optimize --verbose`
    Then the return code should be 0
    And STDOUT should not be empty

    When I run `wp db repair --dbpass=password1`
    Then STDOUT should not be empty

    When I try `wp db repair --dbpass=no_such_pass`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    # Verbose option prints to STDERR.
    When I try `wp db repair --verbose`
    Then the return code should be 0
    And STDERR should contain:
      """
      Connecting
      """
    And STDOUT should not be empty

  Scenario: DB Query
    Given a WP install

    When I run `wp db query 'SELECT COUNT(*) as total FROM wp_posts'`
    Then STDOUT should contain:
      """
      total
      """

    Given a debug.sql file:
      """
      SELECT COUNT(*) as total FROM wp_posts
      """
    When I run `wp db query < debug.sql`
    Then STDOUT should contain:
      """
      total
      """

    When I run `wp db query 'SELECT * FROM wp_options WHERE option_name="home"' --skip-column-names`
    Then STDOUT should not contain:
      """
      option_name
      """
    And STDOUT should contain:
      """
      home
      """

  Scenario: DB export/import
    Given a WP install

    When I run `wp post list --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp db export /tmp/wp-cli-behat.sql`
    Then STDOUT should contain:
      """
      Success: Exported
      """

    When I run `wp db export wp-cli-behat.sql --porcelain`
    Then STDOUT should be:
      """
      wp-cli-behat.sql
      """

    When I try `wp db export - --porcelain`
    Then STDERR should be:
      """
      Error: Porcelain is not allowed when output mode is STDOUT.
      """

    When I run `wp db reset --yes`
    Then STDOUT should contain:
      """
      Success: Database reset.
      """

    When I try `wp post list --format=count`
    Then STDERR should not be empty

    When I run `wp db import /tmp/wp-cli-behat.sql`
    Then STDOUT should contain:
      """
      Success: Imported
      """

    When I run `wp post list --format=count`
    Then STDOUT should contain:
      """
      1
      """

  Scenario: DB export no charset
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --dbcharset=""`
    Then STDOUT should not be empty

    When I run `cat wp-config.php`
    Then STDOUT should contain:
      """
      define( 'DB_CHARSET', '' );
      """

    When I run `wp db create`
    Then STDOUT should not be empty

    When I run `wp db export /tmp/wp-cli-behat.sql`
    Then STDOUT should contain:
      """
      Success: Exported
      """

  Scenario: Persist DB charset and collation
    Given an empty directory
    And WP files

    When I run `wp core config {CORE_CONFIG_SETTINGS} --dbcharset=latin1 --dbcollate=latin1_spanish_ci`
    Then STDOUT should not be empty

    When I run `wp db create`
    Then STDOUT should not be empty

    When I run `wp core install --title="WP-CLI Test" --url=example.com --admin_user=admin --admin_password=admin --admin_email=admin@example.com`
    Then STDOUT should not be empty

    When I run `wp db query 'SHOW variables LIKE "character_set_database";'`
    Then STDOUT should contain:
      """
      latin1
      """

    When I run `wp db query 'SHOW variables LIKE "collation_database";'`
    Then STDOUT should contain:
      """
      latin1_spanish_ci
      """

    When I run `wp db reset --yes`
    Then STDOUT should not be empty

    When I run `wp core install --title="WP-CLI Test" --url=example.com --admin_user=admin --admin_password=admin --admin_email=admin@example.com`
    Then STDOUT should not be empty

    When I run `wp db query 'SHOW variables LIKE "character_set_database";'`
    Then STDOUT should contain:
      """
      latin1
      """

    When I run `wp db query 'SHOW variables LIKE "collation_database";'`
    Then STDOUT should contain:
      """
      latin1_spanish_ci
      """
