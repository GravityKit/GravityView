Feature: Manage WordPress theme mods list

  Background:
    Given a WP install
    And I run `wp theme mod set key_a value_a`
    And I run `wp theme mod set key_b value_b`

  Scenario: Getting theme mods
    When I run `wp theme mod list`
    Then STDOUT should be a table containing rows:
      | key  | value   |
    
    When I run `wp theme mod list --field=key`
    Then STDOUT should be:
      """
      key_a
      key_b
      """

    When I run `wp theme mod list --field=value`
    Then STDOUT should be:
      """
      value_a
      value_b
      """

    When I run `wp theme mod list --format=json`
    Then STDOUT should be:
      """
      [{"key":"key_a","value":"value_a"},{"key":"key_b","value":"value_b"}]
      """

    When I run `wp theme mod list --format=csv`
    Then STDOUT should be:
      """
      key,value
      key_a,value_a
      key_b,value_b
      """

    When I run `wp theme mod list --format=yaml`
    Then STDOUT should be:
      """
      ---
      - 
        key: key_a
        value: value_a
      - 
        key: key_b
        value: value_b
      """
