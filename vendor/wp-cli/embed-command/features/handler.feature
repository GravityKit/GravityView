Feature: Manage embed handlers.

  Background:
    Given a WP install

  Scenario: List embed handlers
    When I run `wp embed handler list`
    And save STDOUT as {DEFAULT_STDOUT}
    Then STDOUT should contain:
      """
      id
      """
    And STDOUT should contain:
      """
      regex
      """
    And STDOUT should contain:
      """
      audio
      """
    And STDOUT should contain:
      """
      video
      """
    And STDOUT should contain:
      """
      #http
      """
    And STDOUT should not contain:
      """
      priority
      """
    And STDOUT should not contain:
      """
      9999
      """
    And STDOUT should not contain:
      """
      callback
      """
    And STDOUT should not contain:
      """
      wp_embed_handler
      """

    When I run `wp embed handler list --fields=id,regex`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    When I run `wp embed handler list --fields=priority,id`
    Then STDOUT should end with a table containing rows:
      | priority | id                |
      | 9999     | audio             |
      | 9999     | video             |

    Given an embed_register_handler.php file:
      """
      <?php WP_CLI::add_hook( 'after_wp_load', function() { wp_embed_register_handler( 'my_id', '/regex/', 'callback', 123 ); } );
      """

    When I run `wp --require=embed_register_handler.php embed handler list`
    Then STDOUT should be a table containing rows:
      | id    | regex   |
      | my_id | /regex/ |
    And STDOUT should contain:
      """
      audio
      """
    And STDOUT should contain:
      """
      video
      """

    When I run `wp --require=embed_register_handler.php embed handler list --format=csv --fields=regex,callback,priority`
    Then STDOUT should contain:
      """
      /regex/,callback,123
      """

    # Handlers are sorted by priority
    When I run `wp --require=embed_register_handler.php embed handler list --field=id`
    Then STDOUT should contain:
      """
      my_id
      audio
      video
      """
