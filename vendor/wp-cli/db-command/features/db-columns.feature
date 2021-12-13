Feature: Display information about a given table.

  # This requires conditional tags to target different DB versions, as bigint(20) is only bigint on MySQL 8.
  @require-wp-4.2 @broken
  Scenario: Display information about the wp_posts table
    Given a WP install

    When I run `wp db columns wp_posts --format=table`
    Then STDOUT should be a table containing rows:
      |         Field         |        Type         | Null | Key |       Default       |     Extra      |
      | ID                    | bigint(20) unsigned | NO   | PRI |                     | auto_increment |
      | post_author           | bigint(20) unsigned | NO   | MUL | 0                   |                |
      | post_date             | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
      | post_date_gmt         | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
      | post_content          | longtext            | NO   |     |                     |                |
      | post_title            | text                | NO   |     |                     |                |
      | post_excerpt          | text                | NO   |     |                     |                |
      | post_status           | varchar(20)         | NO   |     | publish             |                |
      | comment_status        | varchar(20)         | NO   |     | open                |                |
      | ping_status           | varchar(20)         | NO   |     | open                |                |
      | post_password         | varchar(255)        | NO   |     |                     |                |
      | post_name             | varchar(200)        | NO   | MUL |                     |                |
      | to_ping               | text                | NO   |     |                     |                |
      | pinged                | text                | NO   |     |                     |                |
      | post_modified         | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
      | post_modified_gmt     | datetime            | NO   |     | 0000-00-00 00:00:00 |                |
      | post_content_filtered | longtext            | NO   |     |                     |                |
      | post_parent           | bigint(20) unsigned | NO   | MUL | 0                   |                |
      | guid                  | varchar(255)        | NO   |     |                     |                |
      | menu_order            | int(11)             | NO   |     | 0                   |                |
      | post_type             | varchar(20)         | NO   | MUL | post                |                |
      | post_mime_type        | varchar(100)        | NO   |     |                     |                |
      | comment_count         | bigint(20)          | NO   |     | 0                   |                |

  Scenario: Display information about non-existing table
    Given a WP install

    When I try `wp db columns wp_foobar`
    Then STDERR should contain:
      """
      Couldn't find any tables matching: wp_foobar
      """

  Scenario: Display information about a non default WordPress table
    Given a WP install
    And I run `wp db query "CREATE TABLE not_wp ( date DATE NOT NULL, awesome_stuff TEXT, PRIMARY KEY (date) );;"`

    When I try `wp db columns not_wp`
    Then STDOUT should be a table containing rows:
      | Field         | Type       | Null | Key | Default | Extra |
      | date          | date       | NO   | PRI |         |       |
      | awesome_stuff | text       | YES  |     |         |       |
