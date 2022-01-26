Feature: List the status of plugins

  @require-wp-4.0
  Scenario: Status should include drop-ins
    Given a WP install
    And a wp-content/db-error.php file:
      """
      <?php
      """

    When I run `wp plugin status`
    Then STDOUT should contain:
      """
      D db-error.php
      """
    And STDOUT should contain:
      """
      D = Drop-In
      """
    And STDERR should be empty
