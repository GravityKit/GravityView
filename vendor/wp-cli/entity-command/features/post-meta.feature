Feature: Manage post custom fields

  Scenario: Postmeta CRUD
    Given a WP install

    When I run `wp post-meta add 1 foo 'bar'`
    Then STDOUT should not be empty

    When I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      bar
      """

    When I try `wp post meta get 999999 foo`
    Then STDERR should be:
      """
      Error: Could not find the post with ID 999999.
      """
    And the return code should be 1

    When I run `wp post-meta set 1 foo '[ "1", "2" ]' --format=json`
    Then STDOUT should not be empty

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Value passed for custom field 'foo' is unchanged.
      """

    When I run `wp post-meta get 1 foo --format=json`
    Then STDOUT should be:
      """
      ["1","2"]
      """

    When I run `echo 'via STDIN' | wp post-meta set 1 foo`
    And I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      via STDIN
      """

    When I run `wp post-meta delete 1 foo`
    Then STDOUT should not be empty

    When I try `wp post-meta get 1 foo`
    Then the return code should be 1

  Scenario: List post meta
    Given a WP install

    When I run `wp post meta add 1 apple banana`
    And I run `wp post meta add 1 apple banana`
    Then STDOUT should not be empty

    When I run `wp post meta set 1 banana '["apple", "apple"]' --format=json`
    Then STDOUT should not be empty

    When I run `wp post meta list 1`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value                              |
      | 1       | apple    | banana                                  |
      | 1       | apple    | banana                                  |
      | 1       | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |

    When I run `wp post meta list 1 --unserialize`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |
      | 1       | banana   | ["apple","apple"]  |

    When I run `wp post meta list 1 --orderby=id --order=desc`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value                              |
      | 1       | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |
      | 1       | apple    | banana                                  |
      | 1       | apple    | banana                                  |

    When I run `wp post meta list 1 --orderby=id --order=desc --unserialize`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | banana   | ["apple","apple"]  |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |

    When I run `wp post meta list 1 --orderby=meta_key --order=asc`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value                              |
      | 1       | apple    | banana                                  |
      | 1       | apple    | banana                                  |
      | 1       | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |

    When I run `wp post meta list 1 --orderby=meta_key --order=asc --unserialize`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |
      | 1       | banana   | ["apple","apple"]  |

    When I run `wp post meta list 1 --orderby=meta_key --order=desc`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value                              |
      | 1       | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |
      | 1       | apple    | banana                                  |
      | 1       | apple    | banana                                  |

    When I run `wp post meta list 1 --orderby=meta_key --order=desc --unserialize`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | banana   | ["apple","apple"]  |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |

    When I run `wp post meta list 1 --orderby=meta_value --order=asc`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value                              |
      | 1       | apple    | banana                                  |
      | 1       | apple    | banana                                  |
      | 1       | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |

    When I run `wp post meta list 1 --orderby=meta_value --order=asc --unserialize`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |
      | 1       | banana   | ["apple","apple"]  |

    When I run `wp post meta list 1 --orderby=meta_value --order=desc`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value                              |
      | 1       | banana   | a:2:{i:0;s:5:"apple";i:1;s:5:"apple";}  |
      | 1       | apple    | banana                                  |
      | 1       | apple    | banana                                  |

    When I run `wp post meta list 1 --orderby=meta_value --order=desc --unserialize`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | banana   | ["apple","apple"]  |
      | 1       | apple    | banana             |
      | 1       | apple    | banana             |

  Scenario: Delete all post meta
    Given a WP install

    When I run `wp post meta add 1 apple banana`
    And I run `wp post meta add 1 _foo banana`
    Then STDOUT should not be empty

    When I run `wp post meta list 1 --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I try `wp post meta delete 1`
    Then STDERR should be:
      """
      Error: Please specify a meta key, or use the --all flag.
      """
    And the return code should be 1

    When I run `wp post meta delete 1 --all`
    Then STDOUT should contain:
      """
      Deleted 'apple' custom field.
      Deleted '_foo' custom field.
      Success: Deleted all custom fields.
      """

    When I run `wp post meta list 1 --format=count`
    Then STDOUT should be:
      """
      0
      """

  Scenario: List post meta with a null value
    Given a WP install
    And a setup.php file:
      """
      <?php
      update_post_meta( 1, 'foo', NULL );
      """
    And I run `wp eval-file setup.php`

    When I run `wp post meta list 1`
    Then STDOUT should be a table containing rows:
      | post_id | meta_key | meta_value         |
      | 1       | foo      |                    |

  Scenario: Make sure WordPress receives the slashed data it expects in meta fields
    Given a WP install

    When I run `wp post-meta add 1 foo 'My\Meta'`
    Then STDOUT should not be empty

    When I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      My\Meta
      """

    When I run `wp post-meta update 1 foo 'My\New\Meta'`
    Then STDOUT should be:
      """
      Success: Updated custom field 'foo'.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Value passed for custom field 'foo' is unchanged.
      """

    When I run `wp post-meta get 1 foo`
    Then STDOUT should be:
      """
      My\New\Meta
      """

  @pluck
  Scenario: Nested values can be retrieved.
    Given a WP install
    And an input.json file:
      """
      {
        "foo": "bar"
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta pluck 1 meta-key foo`
    Then STDOUT should be:
      """
      bar
      """

  @pluck @pluck-deep
  Scenario: A nested value can be retrieved at any depth.
    Given a WP install
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
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta pluck 1 meta-key foo bar baz`
    Then STDOUT should be:
      """
      some value
      """

    When I run `wp post meta pluck 1 meta-key foo.com visitors`
    Then STDOUT should be:
      """
      999
      """

  @pluck @pluck-fail
  Scenario: Attempting to pluck a non-existent nested value fails.
    Given a WP install
    And I run `wp post meta set 1 meta-key '{ "key": "value" }' --format=json`

    When I run `wp post meta pluck 1 meta-key key`
    Then STDOUT should be:
      """
      value
      """

    When I try `wp post meta pluck 1 meta-key foo`
    Then STDOUT should be empty
    And the return code should be 1

  @pluck @pluck-fail
  Scenario: Attempting to pluck from a primitive value fails.
    Given a WP install
    And I run `wp post meta set 1 meta-key simple-value`

    When I try `wp post meta pluck 1 meta-key foo`
    Then STDOUT should be empty
    And the return code should be 1

  @pluck @pluck-numeric
  Scenario: A nested value can be retrieved from an integer key.
    Given a WP install
    And I run `wp post meta set 1 meta-key '[ "foo", "bar" ]' --format=json`

    When I run `wp post meta pluck 1 meta-key 0`
    Then STDOUT should be:
      """
      foo
      """

  @patch @patch-update @patch-arg
  Scenario: Nested values can be changed.
    Given a WP install
    And an input.json file:
      """
      {
        "foo": "bar"
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta patch update 1 meta-key foo baz`
    Then STDOUT should be:
      """
      Success: Updated custom field 'meta-key'.
      """

    When I run `wp post meta get 1 meta-key --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": "baz"
      }
      """

  @patch @patch-update @patch-stdin
  Scenario: Nested values can be set with a value from STDIN.
    Given a WP install
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
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta patch update 1 meta-key foo bar < patch`
    Then STDOUT should be:
      """
      Success: Updated custom field 'meta-key'.
      """

    When I run `wp post meta get 1 meta-key --format=json`
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
    Given a WP install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz"
        },
        "bar": "bad"
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I try `wp post meta patch update 1 meta-key foo not-a-key new-value`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      No data exists for key "not-a-key"
      """
    And the return code should be 1

  @patch @patch-delete
  Scenario: A key can be deleted from a nested value.
    Given a WP install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz",
          "abe": "lincoln"
        }
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I run `wp post meta patch delete 1 meta-key foo bar`
    Then STDOUT should be:
      """
      Success: Updated custom field 'meta-key'.
      """

    When I run `wp post meta get 1 meta-key --format=json`
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
    Given a WP install
    And an input.json file:
      """
      {
        "foo": {
          "bar": "baz"
        }
      }
      """
    And I run `wp post meta set 1 meta-key --format=json < input.json`

    When I try `wp post meta patch delete 1 meta-key foo not-a-key`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      No data exists for key "not-a-key"
      """
    And the return code should be 1

  @patch @patch-insert
  Scenario: A new key can be inserted into a nested value.
    Given a WP install
    And I run `wp post meta set 1 meta-key '{}' --format=json`

    When I run `wp post meta patch insert 1 meta-key foo bar`
    Then STDOUT should be:
      """
      Success: Updated custom field 'meta-key'.
      """

    When I run `wp post meta get 1 meta-key --format=json`
    Then STDOUT should be JSON containing:
      """
      {
        "foo": "bar"
      }
      """

  @patch @patch-fail @patch-insert @patch-insert-fail
  Scenario: A new key cannot be inserted into a non-nested value.
    Given a WP install
    And I run `wp post meta set 1 meta-key 'a simple value'`

    When I try `wp post meta patch insert 1 meta-key foo bar`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      Cannot create key "foo"
      """
    And the return code should be 1

    When I run `wp post meta get 1 meta-key`
    Then STDOUT should be:
      """
      a simple value
      """

  @patch @patch-numeric
  Scenario: A nested value can be updated using an integer key.
    Given a WP install
    And I run `wp post meta set 1 meta-key '[ "foo", "bar" ]' --format=json`

    When I run `wp post meta patch update 1 meta-key 0 new`
    Then STDOUT should be:
      """
      Success: Updated custom field 'meta-key'.
      """

    When I run `wp post meta get 1 meta-key --format=json`
    Then STDOUT should be JSON containing:
      """
      [ "new", "bar" ]
      """
