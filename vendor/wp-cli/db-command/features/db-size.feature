# Assumes wp_cli_test has a database size of around 655,360 bytes.

Feature: Display database size

  Scenario: Display only database size for a WordPress install
    Given a WP install

    When I run `wp db size`
    Then STDOUT should contain:
      """
      wp_cli_test
      """

    And STDOUT should contain:
      """
      B
      """

  Scenario: Display only table sizes for a WordPress install
    Given a WP install

    When I run `wp db size --tables`
    Then STDOUT should contain:
      """
      wp_posts	81920 B
      """

    But STDOUT should not contain:
      """
      wp_cli_test
      """

  Scenario: Display only database size in a human readable format for a WordPress install
    Given a WP install

    When I run `wp db size --human-readable`
    Then STDOUT should contain:
      """
      wp_cli_test
      """

    And STDOUT should contain:
      """
      KB
      """

    When I try `wp db size --human-readable --size_format=b`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Cannot use --size_format and --human-readable arguments at the same time.
      """
    And STDOUT should be empty

  Scenario: Display only table sizes in a human readable format for a WordPress install
    Given a WP install

    When I run `wp db size --tables --human-readable`
    Then STDOUT should contain:
      """
      wp_posts
      """

    And STDOUT should contain:
      """
      KB
      """

    But STDOUT should not contain:
      """
      wp_cli_test
      """

    When I try `wp db size --tables --human-readable --size_format=b`
    Then the return code should not be 0
    And STDERR should contain:
      """
      Cannot use --size_format and --human-readable arguments at the same time.
      """
    And STDOUT should be empty

  Scenario: Display only database size in bytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=b`
    Then STDOUT should be a number

  Scenario: Display only database size in kilobytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=kb`
    Then STDOUT should be a number

  Scenario: Display only database size in megabytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=mb`
    Then STDOUT should be a number

  Scenario: Display only database size in gigabytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=gb`
    Then STDOUT should be a number

  Scenario: Display only database size in terabytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=tb`
    Then STDOUT should be a number

  Scenario: Display only database size in Kibibytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=KB`
    Then STDOUT should be a number

  Scenario: Display only database size in Mebibytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=MB`
    Then STDOUT should be a number

  Scenario: Display only database size in Gibibytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=GB`
    Then STDOUT should be a number

  Scenario: Display only database size in Tebibytes for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=TB`
    Then STDOUT should be a number

  Scenario: Display only database size in megabytes with specific precision for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=mb --decimals=0`
    Then STDOUT should not contain:
      """
      .
      """

    And STDOUT should not contain:
      """
      MB
      """

    When I run `wp db size --size_format=mb --decimals=1`
    Then STDOUT should contain:
      """
      .
      """

    And STDOUT should not contain:
      """
      MB
      """

  Scenario: Display database size in bytes with specific format for a WordPress install
    Given a WP install

    When I run `wp db size --size_format=b --format=csv`
    Then STDOUT should contain:
      """
      Name,Size
      wp_cli_test,"
      """

    But STDOUT should not be a number

    When I run `wp db size --size_format=b --format=json`
    Then STDOUT should contain:
      """
      [{"Name":"wp_cli_test","Size":"
      """

    But STDOUT should not be a number

  Scenario: Display all table sizes for a WordPress install
    Given a WP install

    When I run `wp db size --all-tables --size_format=kb`
    Then STDOUT should contain:
      """
      wp_posts
      """

    And STDOUT should contain:
      """
      KB
      """

    When I run `wp db size --all-tables-with-prefix --size_format=kb`
    Then STDOUT should contain:
      """
      wp_posts
      """

    And STDOUT should contain:
      """
      KB
      """
