Feature: Display database table prefix

  Scenario: Display database table prefix for a single WordPress install
    Given a WP install

    When I run `wp db prefix`
    Then STDOUT should be:
      """
      wp_
      """
