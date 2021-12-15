Feature: Check the database

  Scenario: Run db check to check the database
    Given a WP install

    When I run `wp db check`
    Then STDOUT should contain:
      """
      wp_cli_test.wp_users
      """
    And STDOUT should contain:
      """
      Success: Database checked.
      """

  Scenario: Run db check with MySQL defaults to check the database
    Given a WP install

    When I run `wp db check --defaults`
    Then STDOUT should contain:
      """
      wp_cli_test.wp_users
      """
    And STDOUT should contain:
      """
      Success: Database checked.
      """

  Scenario: Run db check with --no-defaults to check the database
    Given a WP install

    When I run `wp db check --no-defaults`
    Then STDOUT should contain:
      """
      wp_cli_test.wp_users
      """
    And STDOUT should contain:
      """
      Success: Database checked.
      """

  Scenario: Run db check with passed-in options
    Given a WP install

    When I run `wp db check --dbuser=wp_cli_test`
    Then STDOUT should contain:
      """
      Success: Database checked.
      """

    When I run `wp db check --dbpass=password1`
    Then STDOUT should contain:
      """
      Success: Database checked.
      """

    When I run `wp db check --dbuser=wp_cli_test --dbpass=password1`
    Then STDOUT should contain:
      """
      Success: Database checked.
      """

    When I try `wp db check --dbuser=no_such_user`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    When I try `wp db check --dbpass=no_such_pass`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    When I try `wp db check --dbuser=wp_cli_test --verbose`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Success: Database checked.
      """

    # '--user' is ignored.
    When I try `wp db check --user=no_such_user`
    Then STDOUT should contain:
      """
      Success: Database checked.
      """

    # '--password' works, but MySQL may (depending on version) print warning to STDERR
    When I try `wp db check --password=password1`
    Then the return code should be 0
    # Match STDERR containing "insecure" or empty STDERR.
    And STDERR should match /^(?:.+insecure.+|)$/
    And STDOUT should contain:
      """
      Success: Database checked.
      """

    # Bad '--password' works in that it causes access fail.
    When I try `wp db check --password=no_such_pass`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

    # '--dbpass' overrides '--password'.
    When I run `wp db check --dbpass=password1 --password=no_such_pass`
    Then STDOUT should contain:
      """
      Success: Database checked.
      """

    When I try `wp db check --dbpass=no_such_pass --password=password1`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Access denied
      """
    And STDOUT should be empty

  Scenario: MySQL defaults are available as appropriate with --defaults flag
    Given a WP install

    When I try `wp db check --defaults --debug`
    Then STDERR should contain:
      """
      Debug (db): Running shell command: /usr/bin/env mysqlcheck %s
      """

    When I try `wp db check --debug`
    Then STDERR should contain:
      """
      Debug (db): Running shell command: /usr/bin/env mysqlcheck --no-defaults %s
      """

    When I try `wp db check --no-defaults --debug`
    Then STDERR should contain:
      """
      Debug (db): Running shell command: /usr/bin/env mysqlcheck --no-defaults %s
      """
