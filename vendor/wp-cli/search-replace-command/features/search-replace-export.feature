Feature: Search / replace with file export

  Scenario: Search / replace export to STDOUT
    Given a WP install
    And I run `echo ' '`
    And save STDOUT as {SPACE}

    When I run `wp search-replace example.com example.net --export`
    Then STDOUT should contain:
      """
      DROP TABLE IF EXISTS `wp_commentmeta`;
      CREATE TABLE `wp_commentmeta`
      """
    And STDOUT should contain:
      """
      ('1', 'siteurl', 'https://example.net', 'yes'),
      """

    When I run `wp option get home`
    Then STDOUT should be:
      """
      https://example.com
      """

    When I run `wp search-replace example.com example.net --skip-tables=wp_options --export`
    Then STDOUT should not contain:
      """
      INSERT INTO `wp_options`
      """

    When I run `wp search-replace example.com example.net --skip-tables=wp_opt\?ons,wp_post\* --export`
    Then STDOUT should not contain:
      """
      wp_posts
      """
    And STDOUT should not contain:
      """
      wp_postmeta
      """
    And STDOUT should not contain:
      """
      wp_options
      """
    And STDOUT should contain:
      """
      wp_users
      """

    When I run `wp search-replace example.com example.net --skip-columns=option_value --export`
    Then STDOUT should contain:
      """
      INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES{SPACE}
    ('1', 'siteurl', 'https://example.com', 'yes'),
      """

    When I run `wp search-replace example.com example.net --skip-columns=option_value --export --export_insert_size=1`
    Then STDOUT should contain:
      """
      ('1', 'siteurl', 'https://example.com', 'yes');
    INSERT INTO `wp_options` (`option_id`, `option_name`, `option_value`, `autoload`) VALUES{SPACE}
      """

    When I run `wp search-replace foo bar --export | tail -n 1`
    Then STDOUT should not contain:
      """
      Success: Made
      """

    When I run `wp search-replace example.com example.net --export > wordpress.sql`
    And I run `wp db import wordpress.sql`
    Then STDOUT should not be empty

    When I run `wp option get home`
    Then STDOUT should be:
      """
      https://example.net
      """

  Scenario: Search / replace export to file
    Given a WP install
    And I run `wp post generate --count=100`
    And I run `wp option add example_url https://example.com`

    When I run `wp search-replace example.com example.net --export=wordpress.sql`
    Then STDOUT should contain:
      """
      Success: Made
      """
    # Skip exact number as it changes in trunk due to https://core.trac.wordpress.org/changeset/42981
    And STDOUT should contain:
      """
      replacements and exported to wordpress.sql
      """
    And STDOUT should be a table containing rows:
      | Table         | Column       | Replacements | Type |
      | wp_options    | option_value | 6            | PHP  |

    When I run `wp option get home`
    Then STDOUT should be:
      """
      https://example.com
      """

    When I run `wp site empty --yes`
    And I run `wp post list --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp db import wordpress.sql`
    Then STDOUT should not be empty

    When I run `wp option get home`
    Then STDOUT should be:
      """
      https://example.net
      """

    When I run `wp option get example_url`
    Then STDOUT should be:
      """
      https://example.net
      """

    When I run `wp post list --format=count`
    Then STDOUT should be:
      """
      101
      """

  Scenario: Search / replace export to file with verbosity
    Given a WP install

    When I run `wp search-replace example.com example.net --export=wordpress.sql --verbose`
    Then STDOUT should contain:
      """
      Checking: wp_posts
      """
    And STDOUT should contain:
      """
      Checking: wp_options
      """

  Scenario: Search / replace export with dry-run
    Given a WP install

    When I try `wp search-replace example.com example.net --export --dry-run`
    Then STDERR should be:
      """
      Error: You cannot supply --dry-run and --export at the same time.
      """

  Scenario: Search / replace shouldn't affect primary key
    Given a WP install
    And I run `wp post create --post_title=foo --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp option update {POST_ID} foo`
    And I run `wp option get {POST_ID}`
    Then STDOUT should be:
      """
      foo
      """

    When I run `wp search-replace {POST_ID} 99999999 --export=wordpress.sql`
    And I run `wp db import wordpress.sql`
    Then STDOUT should not be empty

    When I run `wp post get {POST_ID} --field=title`
    Then STDOUT should be:
      """
      foo
      """

    When I try `wp option get {POST_ID}`
    Then STDOUT should be empty

    When I run `wp option get 99999999`
    Then STDOUT should be:
      """
      foo
      """

  Scenario: Search / replace export invalid file
    Given a WP install

    When I try `wp search-replace example.com example.net --export=foo/bar.sql`
    Then STDERR should contain:
      """
      Error: Unable to open export file "foo/bar.sql" for writing:
      """

  Scenario: Search / replace specific table
    Given a WP install

    When I run `wp post create --post_title=foo --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp option update bar foo`
    Then STDOUT should not be empty

    When I run `wp search-replace foo burrito wp_posts --export=wordpress.sql --verbose`
    Then STDOUT should contain:
      """
      Checking: wp_posts
      """
    And STDOUT should contain:
      """
      Success: Made 1 replacement and exported to wordpress.sql.
      """

    When I run `wp db import wordpress.sql`
    Then STDOUT should not be empty

    When I run `wp post get {POST_ID} --field=title`
    Then STDOUT should be:
      """
      burrito
      """

    When I run `wp option get bar`
    Then STDOUT should be:
      """
      foo
      """

  Scenario: Search / replace export should cater for field/table names that use reserved words or unusual characters
    Given a WP install
    # Unlike search-replace.features version, don't use `back``tick` column name as WP_CLI\Iterators\Table::build_fields() can't handle it.
    And a esc_sql_ident.sql file:
      """
      CREATE TABLE `TABLE` (`KEY` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `VALUES` TEXT, `single'double"quote` TEXT, PRIMARY KEY (`KEY`) );
      INSERT INTO `TABLE` (`VALUES`, `single'double"quote`) VALUES ('v"vvvv_v1', 'v"vvvv_v1' );
      INSERT INTO `TABLE` (`VALUES`, `single'double"quote`) VALUES ('v"vvvv_v2', 'v"vvvv_v2' );
      """

    When I run `wp db query "SOURCE esc_sql_ident.sql;"`
    Then STDERR should be empty

    When I run `wp search-replace 'v"vvvv_v' 'w"wwww_w' TABLE --export --all-tables`
    Then STDOUT should contain:
      """
      INSERT INTO `TABLE` (`KEY`, `VALUES`, `single'double"quote`) VALUES
      """
    And STDOUT should contain:
      """
      ('1', 'w\"wwww_w1', 'w\"wwww_w1')
      """
    And STDOUT should contain:
      """
      ('2', 'w\"wwww_w2', 'w\"wwww_w2')
      """
    And STDERR should be empty

    When I run `wp search-replace 'v"vvvv_v2' 'w"wwww_w2' TABLE --export --regex --all-tables`
    Then STDOUT should contain:
      """
      INSERT INTO `TABLE` (`KEY`, `VALUES`, `single'double"quote`) VALUES
      """
    And STDOUT should contain:
      """
      ('1', 'v\"vvvv_v1', 'v\"vvvv_v1')
      """
    And STDOUT should contain:
      """
      ('2', 'w\"wwww_w2', 'w\"wwww_w2')
      """
    And STDERR should be empty

  Scenario: Suppress report or only report changes on export to file
    Given a WP install

    When I run `wp option set foo baz`
    And I run `wp option get foo`
    Then STDOUT should be:
      """
      baz
      """

    When I run `wp post create --post_title=baz --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post meta add {POST_ID} foo baz`
    Then STDOUT should not be empty

    When I run `wp search-replace baz bar --export=wordpress.sql`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements and exported to wordpress.sql.
      """
    And STDOUT should be a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_commentmeta | meta_id      | 0            | PHP  |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | PHP  |
    | wp_posts       | post_title   | 1            | PHP  |
    | wp_users       | display_name | 0            | PHP  |
    And STDERR should be empty

    When I run `wp search-replace baz bar --report --export=wordpress.sql`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements and exported to wordpress.sql.
      """
    And STDOUT should be a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_commentmeta | meta_id      | 0            | PHP  |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | PHP  |
    | wp_posts       | post_title   | 1            | PHP  |
    | wp_users       | display_name | 0            | PHP  |
    And STDERR should be empty

    When I run `wp search-replace baz bar --no-report --export=wordpress.sql`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements and exported to wordpress.sql.
      """
    And STDOUT should not contain:
      """
      Table	Column	Replacements	Type
      """
    And STDOUT should not contain:
      """
      wp_commentmeta	meta_id	0	PHP
      """
    And STDOUT should not contain:
      """
      wp_options	option_value	1	PHP
      """
    And STDERR should be empty

    When I run `wp search-replace baz bar --no-report-changed-only --export=wordpress.sql`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements and exported to wordpress.sql.
      """
    And STDOUT should be a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_commentmeta | meta_id      | 0            | PHP  |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | PHP  |
    | wp_posts       | post_title   | 1            | PHP  |
    | wp_users       | display_name | 0            | PHP  |
    And STDERR should be empty

    When I run `wp search-replace baz bar --report-changed-only --export=wordpress.sql`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements and exported to wordpress.sql.
      """
    And STDOUT should end with a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | PHP  |
    | wp_posts       | post_title   | 1            | PHP  |
    And STDOUT should not contain:
      """
      wp_commentmeta	meta_id	0	PHP
      """
    And STDOUT should not contain:
      """
      wp_users	display_name	0	PHP
      """
    And STDERR should be empty

  Scenario: Search / replace should remove placeholder escape on export
    Given a WP install
    And I run `wp post create --post_title=test-remove-placeholder-escape% --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp search-replace baz bar --export | grep test-remove-placeholder-escape`
    Then STDOUT should contain:
      """
      'test-remove-placeholder-escape%'
      """
    And STDOUT should not contain:
      """
      'test-remove-placeholder-escape{'
      """

  Scenario: NULLs exported as NULL and not null string
    Given a WP install
    And I run `wp db query "INSERT INTO wp_postmeta VALUES (9999, 9999, NULL, 'foo')"`

    When I run `wp search-replace bar replaced wp_postmeta --export`
    Then STDOUT should contain:
      """
     ('9999', '9999', NULL, 'foo')
      """
    And STDOUT should not contain:
      """
     ('9999', '9999', '', 'foo')
      """
