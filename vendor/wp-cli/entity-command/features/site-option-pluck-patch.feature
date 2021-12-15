Feature: Site option commands have pluck and patch.

  @pluck
  Scenario: Nested values can be retrieved.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": "bar"
      }
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I run `wp site option pluck option_name foo`
    Then STDOUT should be:
      """
      bar
      """

  @pluck @pluck-deep
  Scenario: A nested value can be retrieved at any depth.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": {
          "bar": {
            "baz": "some value"
          }
        },
        "foo.com": {
          "visitors": 999
        }
      }
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I run `wp site option pluck option_name foo bar baz`
    Then STDOUT should be:
      """
      some value
      """

    When I run `wp site option pluck option_name foo.com visitors`
    Then STDOUT should be:
      """
      999
      """

  @pluck @pluck-fail
  Scenario: Attempting to pluck a non-existent nested value fails.
    Given a WP multisite install
    And I run `wp site option update option_name '{ "key": "value" }' --format=json`

    When I run `wp site option pluck option_name key`
    Then STDOUT should be:
      """
      value
      """

    When I try `wp site option pluck option_name foo`
    Then STDOUT should be empty
    And the return code should be 1

  @pluck @pluck-fail
  Scenario: Attempting to pluck from a primitive value fails.
    Given a WP multisite install
    And I run `wp site option update option_name simple-value`

    When I try `wp site option pluck option_name foo`
    Then STDOUT should be empty
    And the return code should be 1

  @pluck @pluck-numeric
  Scenario: A nested value can be retrieved from an integer key.
    Given a WP multisite install
    And I run `wp site option update option_name '[ "foo", "bar" ]' --format=json`

    When I run `wp site option pluck option_name 0`
    Then STDOUT should be:
      """
      foo
      """

  @patch @patch-update @patch-arg
  Scenario: Nested values can be changed.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": "bar"
      }
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I run `wp site option patch update option_name foo baz`
    Then STDOUT should be:
      """
      Success: Updated 'option_name' site option.
      """

    When I run `wp site option get option_name --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": "baz"
      }
      """

  @patch @patch-update @patch-stdin
  Scenario: Nested values can be set with a value from STDIN.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz"
        },
        "bar": "bad"
      }
      """
    And a patch file:
      """
      new value
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I run `wp site option patch update option_name foo bar < patch`
    Then STDOUT should be:
      """
      Success: Updated 'option_name' site option.
      """

    When I run `wp site option get option_name --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": {
          "bar": "new value"
        },
        "bar": "bad"
      }
      """

  @patch @patch-update @patch-fail
  Scenario: Attempting to update a nested value fails if a parent's key does not exist.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz"
        },
        "bar": "bad"
      }
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I try `wp site option patch update option_name foo not-a-key new-value`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      No data exists for key "not-a-key"
      """
    And the return code should be 1

  @patch @patch-delete
  Scenario: A key can be deleted from a nested value.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz",
          "abe": "lincoln"
        }
      }
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I run `wp site option patch delete option_name foo bar`
    Then STDOUT should be:
      """
      Success: Updated 'option_name' site option.
      """

    When I run `wp site option get option_name --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": {
          "abe": "lincoln"
        }
      }
      """

  @patch @patch-fail @patch-delete @patch-delete-fail
  Scenario: A key cannot be deleted from a nested value from a non-existent key.
    Given a WP multisite install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz"
        }
      }
      """
    And I run `wp site option update option_name --format=json < input.json`

    When I try `wp site option patch delete option_name foo not-a-key`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      No data exists for key "not-a-key"
      """
    And the return code should be 1

  @patch @patch-insert
  Scenario: A new key can be inserted into a nested value.
    Given a WP multisite install
    And I run `wp site option update option_name '{}' --format=json`

    When I run `wp site option patch insert option_name foo bar`
    Then STDOUT should be:
      """
      Success: Updated 'option_name' site option.
      """

    When I run `wp site option get option_name --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": "bar"
      }
      """

  @patch @patch-fail @patch-insert @patch-insert-fail
  Scenario: A new key cannot be inserted into a non-nested value.
    Given a WP multisite install
    And I run `wp site option update option_name 'a simple value'`

    When I try `wp site option patch insert option_name foo bar`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Cannot create key "foo"
      """
    And the return code should be 1

    When I run `wp site option get option_name`
    Then STDOUT should be:
      """
      a simple value
      """

  @patch @patch-numeric
  Scenario: A nested value can be updated using an integer key.
    Given a WP multisite install
    And I run `wp site option update option_name '[ "foo", "bar" ]' --format=json`

    When I run `wp site option patch update option_name 0 new`
    Then STDOUT should be:
      """
      Success: Updated 'option_name' site option.
      """

    When I run `wp site option get option_name --format=json`
    Then STDOUT should be JSON containing:
      """
      [ "new", "bar" ]
      """
