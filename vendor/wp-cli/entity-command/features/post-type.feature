Feature: Manage WordPress post types

  Background:
    Given a WP install

  Scenario: Listing post types
    When I run `wp post-type list --format=csv`
    Then STDOUT should be CSV containing:
      | name | label | description | hierarchical | public | capability_type |
      | post | Posts |             |              | 1      | post            |
      | page | Pages |             | 1            | 1      | page            |

  @require-wp-5.0
  Scenario: Listing post types with count
    When I run `wp post-type list --fields=name,count --format=csv`
    Then STDOUT should be CSV containing:
      | name | count |
      | post | 1     |
      | page | 2     |

  Scenario: Get a post type
    When I try `wp post-type get invalid-post-type`
    Then STDERR should be:
      """
      Error: Post type invalid-post-type doesn't exist.
      """
    And the return code should be 1

    When I run `wp post-type get page`
    Then STDOUT should be a table containing rows:
      | Field       | Value     |
      | name        | page      |
      | label       | Pages     |
    And STDOUT should contain:
      """
      supports
      """
    And STDOUT should contain:
      """
      "title":true
      """

  @require-wp-5.0
  Scenario: Get a post type with count
    When I try `wp post-type get page --fields=name,count`
    Then STDOUT should be a table containing rows:
      | Field       | Value     |
      | name        | page      |
      | count       | 2         |
