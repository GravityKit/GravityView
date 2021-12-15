Feature: Do global search/replace

  Scenario: Basic search/replace
    Given a WP install

    When I run `wp search-replace foo bar`
    Then STDOUT should contain:
      """
      guid
      """

    When I run `wp search-replace foo bar --skip-tables=wp_posts`
    Then STDOUT should not contain:
      """
      wp_posts
      """

    When I run `wp search-replace foo bar --skip-tables=wp_post\*`
    Then STDOUT should not contain:
      """
      wp_posts
      """
    And STDOUT should not contain:
      """
      wp_postmeta
      """
    And STDOUT should contain:
      """
      wp_users
      """

    When I run `wp search-replace foo bar --skip-columns=guid`
    Then STDOUT should not contain:
      """
      guid
      """

    When I run `wp search-replace foo bar --include-columns=post_content`
    Then STDOUT should be a table containing rows:
    | Table    | Column       | Replacements | Type |
    | wp_posts | post_content | 0            | SQL  |


  Scenario: Multisite search/replace
    Given a WP multisite install
    And I run `wp site create --slug="foo" --title="foo" --email="foo@example.com"`
    And I run `wp search-replace foo bar --network`
    Then STDOUT should be a table containing rows:
      | Table        | Column       | Replacements | Type |
      | wp_2_options | option_value | 4            | PHP  |
      | wp_blogs     | path         | 1            | SQL  |

  Scenario: Don't run on unregistered tables by default
    Given a WP install
    And I run `wp db query "CREATE TABLE wp_awesome ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"`

    When I run `wp search-replace foo bar`
    Then STDOUT should not contain:
      """
      wp_awesome
      """

    When I run `wp search-replace foo bar --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      wp_awesome
      """

  Scenario: Run on unregistered, unprefixed tables with --all-tables flag
    Given a WP install
    And I run `wp db query "CREATE TABLE awesome_table ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;"`

    When I run `wp search-replace foo bar`
    Then STDOUT should not contain:
      """
      awesome_table
      """

    When I run `wp search-replace foo bar --all-tables`
    Then STDOUT should contain:
      """
      awesome_table
      """

  Scenario: Run on all tables matching string with wildcard
    Given a WP install

    When I run `wp option set bar foo`
    And I run `wp option get bar`
    Then STDOUT should be:
      """
      foo
      """

    When I run `wp post create --post_title=bar --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post meta add {POST_ID} foo bar`
    Then STDOUT should not be empty

    When I run `wp search-replace bar burrito wp_post\?`
    And STDOUT should be a table containing rows:
      | Table         | Column      | Replacements | Type |
      | wp_posts      | post_title  | 1            | SQL  |
    And STDOUT should not contain:
      """
      wp_options
      """

    When I run `wp post get {POST_ID} --field=title`
    Then STDOUT should be:
      """
      burrito
      """

    When I run `wp post meta get {POST_ID} foo`
    Then STDOUT should be:
      """
      bar
      """

    When I run `wp option get bar`
    Then STDOUT should be:
      """
      foo
      """

    When I try `wp search-replace foo burrito wp_opt\*on`
    Then STDERR should be:
      """
      Error: Couldn't find any tables matching: wp_opt*on
      """
    And the return code should be 1

    When I run `wp search-replace foo burrito wp_opt\* wp_postme\*`
    Then STDOUT should be a table containing rows:
      | Table         | Column       | Replacements | Type |
      | wp_options    | option_value | 1            | PHP  |
      | wp_postmeta   | meta_key     | 1            | SQL  |
    And STDOUT should not contain:
      """
      wp_posts
      """

    When I run `wp option get bar`
    Then STDOUT should be:
      """
      burrito
      """

    When I run `wp post meta get {POST_ID} burrito`
    Then STDOUT should be:
      """
      bar
      """

  Scenario: Quiet search/replace
    Given a WP install

    When I run `wp search-replace foo bar --quiet`
    Then STDOUT should be empty

  Scenario: Verbose search/replace
    Given a WP install
    And I run `wp post create --post_title='Replace this text' --porcelain`
    And save STDOUT as {POSTID}

    When I run `wp search-replace 'Replace' 'Replaced' --verbose`
    Then STDOUT should contain:
      """
      Checking: wp_posts.post_title
      1 rows affected
      """

    When I run `wp search-replace 'Replace' 'Replaced' --verbose --precise`
    Then STDOUT should contain:
      """
      Checking: wp_posts.post_title
      1 rows affected
      """

  Scenario: Regex search/replace
    Given a WP install
    When I run `wp search-replace '(Hello)\s(world)' '$2, $1' --regex`
    Then STDOUT should contain:
      """
      wp_posts
      """
    When I run `wp post list --fields=post_title`
    Then STDOUT should contain:
      """
      world, Hello
      """

  Scenario: Regex search/replace with a incorrect `--regex-flags`
    Given a WP install
    When I try `wp search-replace '(Hello)\s(world)' '$2, $1' --regex --regex-flags='kppr'`
    Then STDERR should be:
      """
      Error: The regex pattern '(Hello)\s(world)' with default delimiter 'chr(1)' and flags 'kppr' fails.
      preg_match(): Unknown modifier 'k'.
      """
    And the return code should be 1

  Scenario: Search and replace within theme mods
    Given a WP install
    And a setup-theme-mod.php file:
      """
      <?php
      set_theme_mod( 'header_image_data', (object) array( 'url' => 'http://subdomain.example.com/foo.jpg' ) );
      """
    And I run `wp eval-file setup-theme-mod.php`

    When I run `wp theme mod get header_image_data`
    Then STDOUT should be a table containing rows:
      | key               | value                                              |
      | header_image_data | {"url":"http:\/\/subdomain.example.com\/foo.jpg"}  |

    When I run `wp search-replace subdomain.example.com example.com --no-recurse-objects`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 0            | PHP        |

    When I run `wp search-replace subdomain.example.com example.com`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 1            | PHP        |

    When I run `wp theme mod get header_image_data`
    Then STDOUT should be a table containing rows:
      | key               | value                                           |
      | header_image_data | {"url":"http:\/\/example.com\/foo.jpg"}  |

  Scenario: Search and replace with quoted strings
    Given a WP install

    When I run `wp post create --post_content='<a href="http://apple.com">Apple</a>' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --field=content`
    Then STDOUT should be:
      """
      <a href="http://apple.com">Apple</a>
      """

    When I run `wp search-replace '<a href="http://apple.com">Apple</a>' '<a href="http://google.com">Google</a>' --dry-run`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_posts   | post_content | 1            | SQL        |

    When I run `wp search-replace '<a href="http://apple.com">Apple</a>' '<a href="http://google.com">Google</a>'`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_posts   | post_content | 1            | SQL        |

    When I run `wp search-replace '<a href="http://google.com">Google</a>' '<a href="http://apple.com">Apple</a>' --dry-run`
    Then STDOUT should contain:
      """
      1 replacement to be made.
      """

    When I run `wp post get {POST_ID} --field=content`
    Then STDOUT should be:
      """
      <a href="http://google.com">Google</a>
      """

  Scenario: Search and replace with the same terms
    Given a WP install

    When I try `wp search-replace foo foo`
    Then STDERR should be:
      """
      Warning: Replacement value 'foo' is identical to search value 'foo'. Skipping operation.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Search and replace a table that has a multi-column primary key
    Given a WP install
    And I run `wp db query "CREATE TABLE wp_multicol ( "id" bigint(20) NOT NULL AUTO_INCREMENT,"name" varchar(60) NOT NULL,"value" text NOT NULL,PRIMARY KEY ("id","name"),UNIQUE KEY "name" ("name") ) ENGINE=InnoDB DEFAULT CHARSET=utf8 "`
    And I run `wp db query "INSERT INTO wp_multicol VALUES (1, 'foo',  'bar')"`
    And I run `wp db query "INSERT INTO wp_multicol VALUES (2, 'bar',  'foo')"`

    When I run `wp search-replace bar replaced wp_multicol --all-tables`
    Then STDOUT should be a table containing rows:
      | Table       | Column | Replacements | Type |
      | wp_multicol | name   | 1            | SQL  |
      | wp_multicol | value  | 1            | SQL  |

  # Skip on 5.0 for now due to difficulties introduced by https://core.trac.wordpress.org/changeset/42981
  @less-than-wp-5.0
  Scenario Outline: Large guid search/replace where replacement contains search (or not)
    Given a WP install
    And I run `wp option get siteurl`
    And save STDOUT as {SITEURL}
    And I run `wp site empty --yes`
    And I run `wp post generate --count=20`

    When I run `wp search-replace <flags> {SITEURL} <replacement>`
    Then STDOUT should be a table containing rows:
      | Table    | Column | Replacements | Type |
      | wp_posts | guid   | 20           | SQL  |

    Examples:
      | replacement          | flags     |
      | {SITEURL}/subdir     |           |
      | http://newdomain.com |           |
      | http://newdomain.com | --dry-run |

  Scenario Outline: Choose replacement method (PHP or MySQL/MariaDB) given proper flags or data.
    Given a WP install
    And I run `wp option get siteurl`
    And save STDOUT as {SITEURL}
    When I run `wp search-replace <flags> {SITEURL} http://wordpress.org`

    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 2            | <serial>   |
      | wp_posts   | post_title   | 0            | <noserial> |

    Examples:
      | flags     | serial | noserial |
      |           | PHP    | SQL      |
      | --precise | PHP    | PHP      |

  Scenario Outline: Ensure search and replace uses PHP (precise) mode when serialized data is found
    Given a WP install
    And I run `wp post create --post_content='<input>' --porcelain`
    And save STDOUT as {CONTROLPOST}
    And I run `wp search-replace --precise foo bar`
    And I run `wp post get {CONTROLPOST} --field=content`
    And save STDOUT as {CONTROL}
    And I run `wp post create --post_content='<input>' --porcelain`
    And save STDOUT as {TESTPOST}
    And I run `wp search-replace foo bar`

    When I run `wp post get {TESTPOST} --field=content`
    Then STDOUT should be:
      """
      {CONTROL}
      """

    Examples:
      | input                                 |
      | a:1:{s:3:"bar";s:3:"foo";}            |
      | O:8:"stdClass":1:{s:1:"a";s:3:"foo";} |

  Scenario: Search replace with a regex flag
    Given a WP install

    When I run `wp search-replace 'EXAMPLE.com' 'BAXAMPLE.com' wp_options --regex`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 0            | PHP        |

    When I run `wp option get home`
    Then STDOUT should be:
      """
      http://example.com
      """

    When I run `wp search-replace 'EXAMPLE.com' 'BAXAMPLE.com' wp_options --regex --regex-flags=i`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 5            | PHP        |

    When I run `wp option get home`
    Then STDOUT should be:
      """
      http://BAXAMPLE.com
      """

  Scenario: Search replace with a regex delimiter
    Given a WP install

    When I run `wp search-replace 'HTTP://EXAMPLE.COM' 'http://example.jp/' wp_options --regex --regex-flags=i --regex-delimiter='#'`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 2            | PHP        |

    When I run `wp option get home`
    Then STDOUT should be:
      """
      http://example.jp
      """

    When I run `wp search-replace 'http://example.jp/' 'http://example.com/' wp_options --regex-delimiter='/'`
    Then STDOUT should be a table containing rows:
      | Table      | Column       | Replacements | Type       |
      | wp_options | option_value | 2            | PHP        |

    When I run `wp option get home`
    Then STDOUT should be:
      """
      http://example.com
      """

    When I try `wp search-replace 'HTTP://EXAMPLE.COM' 'http://example.jp/' wp_options --regex --regex-flags=i --regex-delimiter='1'`
    Then STDERR should be:
      """
      Error: The regex '1HTTP://EXAMPLE.COM1i' fails.
      preg_match(): Delimiter must not be alphanumeric or backslash.
      """
    And the return code should be 1

    When I try `wp search-replace 'regex error)' '' --regex`
    Then STDERR should contain:
      """
      Error: The regex pattern 'regex error)' with default delimiter 'chr(1)' and no flags fails.
      """
    And STDERR should contain:
      """
      preg_match(): Compilation failed:
      """
    And STDERR should contain:
      """
      at offset 11
      """
    And the return code should be 1

    When I try `wp search-replace 'regex error)' '' --regex --regex-flags=u`
    Then STDERR should contain:
      """
      Error: The regex pattern 'regex error)' with default delimiter 'chr(1)' and flags 'u' fails.
      """
    And STDERR should contain:
      """
      preg_match(): Compilation failed:
      """
    And STDERR should contain:
      """
      at offset 11
      """
    And the return code should be 1

    When I try `wp search-replace 'regex error)' '' --regex --regex-delimiter=/`
    Then STDERR should contain:
      """
      Error: The regex '/regex error)/' fails.
      """
    And STDERR should contain:
      """
      preg_match(): Compilation failed:
      """
    And STDERR should contain:
      """
      at offset 11
      """
    And the return code should be 1

    When I try `wp search-replace 'regex error)' '' --regex --regex-delimiter=/ --regex-flags=u`
    Then STDERR should contain:
      """
      Error: The regex '/regex error)/u' fails.
      """
    And STDERR should contain:
      """
      preg_match(): Compilation failed:
      """
    And STDERR should contain:
      """
      at offset 11
      """
    And the return code should be 1

  Scenario: Formatting as count-only
    Given a WP install
    And I run `wp option set foo 'ALPHA.example.com'`

    # --quite should suppress --format=count
    When I run `wp search-replace 'ALPHA.example.com' 'BETA.example.com' --quiet --format=count`
    Then STDOUT should be empty

    # --format=count should suppress --verbose
    When I run `wp search-replace 'BETA.example.com' 'ALPHA.example.com' --format=count --verbose`
    Then STDOUT should be:
      """
      1
      """

    # The normal command
    When I run `wp search-replace 'ALPHA.example.com' 'BETA.example.com' --format=count`
    Then STDOUT should be:
      """
      1
      """

    # Lets just make sure that zero works, too.
    When I run `wp search-replace 'DELTA.example.com' 'ALPHA.example.com' --format=count`
    Then STDOUT should be:
      """
      0
      """

  Scenario: Search / replace should cater for field/table names that use reserved words or unusual characters
    Given a WP install
    And a esc_sql_ident.sql file:
      """
      CREATE TABLE `TABLE` (`KEY` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `VALUES` TEXT, `back``tick` TEXT, `single'double"quote` TEXT, PRIMARY KEY (`KEY`) );
      INSERT INTO `TABLE` (`VALUES`, `back``tick`, `single'double"quote`) VALUES ('v"vvvv_v1', 'v"vvvv_v1', 'v"vvvv_v1' );
      INSERT INTO `TABLE` (`VALUES`, `back``tick`, `single'double"quote`) VALUES ('v"vvvv_v2', 'v"vvvv_v2', 'v"vvvv_v2' );
      """

    When I run `wp db query "SOURCE esc_sql_ident.sql;"`
    Then STDERR should be empty

    When I run `wp search-replace 'v"vvvv_v' 'w"wwww_w' TABLE --format=count --all-tables`
    Then STDOUT should be:
      """
      6
      """
    And STDERR should be empty

    # Regex uses wpdb::update() which can't handle backticks in field names so avoid `back``tick` column.
    When I run `wp search-replace 'w"wwww_w' 'v"vvvv_v' TABLE --regex --include-columns='VALUES,single'\''double"quote' --format=count --all-tables`
    Then STDOUT should be:
      """
      4
      """
    And STDERR should be empty

  @suppress_report__only_changes
  Scenario: Suppress report or only report changes
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

    When I run `wp search-replace baz baz1`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements.
      """
    And STDOUT should be a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_commentmeta | meta_key     | 0            | SQL  |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | SQL  |
    | wp_posts       | post_title   | 1            | SQL  |
    | wp_users       | display_name | 0            | SQL  |
    And STDERR should be empty

    When I run `wp search-replace baz1 baz2 --report`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements.
      """
    And STDOUT should be a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_commentmeta | meta_key     | 0            | SQL  |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | SQL  |
    | wp_posts       | post_title   | 1            | SQL  |
    | wp_users       | display_name | 0            | SQL  |
    And STDERR should be empty

    When I run `wp search-replace baz2 baz3 --no-report`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements.
      """
    And STDOUT should not contain:
      """
      Table	Column	Replacements	Type
      """
    And STDOUT should not contain:
      """
      wp_commentmeta	meta_key	0	SQL
      """
    And STDOUT should not contain:
      """
      wp_options	option_value	1	PHP
      """
    And STDERR should be empty

    When I run `wp search-replace baz3 baz4 --no-report-changed-only`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements.
      """
    And STDOUT should be a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_commentmeta | meta_key     | 0            | SQL  |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | SQL  |
    | wp_posts       | post_title   | 1            | SQL  |
    | wp_users       | display_name | 0            | SQL  |
    And STDERR should be empty

    When I run `wp search-replace baz4 baz5 --report-changed-only`
    Then STDOUT should contain:
      """
      Success: Made 3 replacements.
      """
    And STDOUT should end with a table containing rows:
    | Table          | Column       | Replacements | Type |
    | wp_options     | option_value | 1            | PHP  |
    | wp_postmeta    | meta_value   | 1            | SQL  |
    | wp_posts       | post_title   | 1            | SQL  |
    And STDOUT should not contain:
      """
      wp_commentmeta	meta_key	0	SQL
      """
    And STDOUT should not contain:
      """
      wp_users	display_name	0	SQL
      """
    And STDERR should be empty

    When I run `wp search-replace nobaz1 baz6 --report-changed-only`
    Then STDOUT should contain:
      """
      Success: Made 0 replacements.
      """
    And STDOUT should not contain:
      """
      Table	Column	Replacements	Type
      """
    And STDERR should be empty

  @no_table__no_primary_key
  Scenario: Deal with non-existent table and table with no primary keys
    Given a WP install

    When I try `wp search-replace foo bar no_such_table --all-tables`
    Then STDOUT should be empty
    And STDERR should be:
      """
      Error: Couldn't find any tables matching: no_such_table
      """
    And the return code should be 1

    When I run `wp db query "CREATE TABLE no_key ( awesome_stuff TEXT );"`
    And I run `wp search-replace foo bar no_key --all-tables`
    Then STDOUT should contain:
      """
      Success: Made 0 replacements.
      """
    And STDOUT should end with a table containing rows:
    | Table  | Column | Replacements | Type |
    | no_key |        | skipped      |      |
    And STDERR should be empty

    And I run `wp search-replace foo bar no_key --report-changed-only --all-tables`
    Then STDOUT should contain:
      """
      Success: Made 0 replacements.
      """
    And STDOUT should not contain:
      """
      | Table  | Column | Replacements | Type |
      | no_key |        | skipped      |      |
      """
    And STDERR should be empty

    When I try `wp search-replace foo bar no_key --no-report --all-tables`
    Then STDOUT should contain:
      """
      Success: Made 0 replacements.
      """
    And STDOUT should not contain:
      """
      Table	Column	Replacements	Type
      """
    And STDERR should be:
      """
      Warning: No primary keys for table 'no_key'.
      """
    And the return code should be 0

  Scenario: Search / replace is case sensitive
    Given a WP install
    When I run `wp post create --post_title='Case Sensitive' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp search-replace sensitive insensitive`
    Then STDOUT should contain:
      """
      Success: Made 0 replacements.
      """
    And STDERR should be empty

    When I run `wp search-replace sensitive insensitive --dry-run`
    Then STDOUT should contain:
      """
      Success: 0 replacements to be made.
      """
    And STDERR should be empty

    When I run `wp search-replace Sensitive insensitive --dry-run`
    Then STDOUT should contain:
      """
      Success: 1 replacement to be made.
      """
    And STDERR should be empty

    When I run `wp search-replace Sensitive insensitive`
    Then STDOUT should contain:
      """
      Success: Made 1 replacement.
      """
    And STDERR should be empty

  Scenario: Logging with simple replace
    Given a WP install

    When I run `wp post create --post_title='Title_baz__baz_' --post_content='Content_baz_12345678901234567890_baz_12345678901234567890' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp search-replace '_baz_' '_' wp_posts --dry-run --log --before_context=10 --after_context=10`
    Then STDOUT should contain:
      """
      Success: 2 replacements to be made.
      """
    And STDOUT should end with a table containing rows:
    | Table    | Column       | Replacements | Type |
    | wp_posts | post_content | 1            | SQL  |
    | wp_posts | post_title   | 1            | SQL  |

    And STDOUT should contain:
      """
      wp_posts.post_content:{POST_ID}
      < Content_baz_1234567890 [...] 1234567890_baz_1234567890
      > Content_1234567890 [...] 1234567890_1234567890
      """
    And STDOUT should contain:
      """
      wp_posts.post_title:{POST_ID}
      < Title_baz__baz_
      > Title__
      """
    And STDERR should be empty

    When I run `wp search-replace '_baz_' '' wp_posts --dry-run --log=replace.log`
    Then STDOUT should contain:
      """
      Success: 2 replacements to be made.
      """
    And STDOUT should not contain:
      """
      < Content
      """
    And the replace.log file should contain:
      """
      wp_posts.post_content:{POST_ID}
      < Content_baz_12345678901234567890_baz_12345678901234567890
      > Content1234567890123456789012345678901234567890
      """
    And the replace.log file should contain:
      """
      wp_posts.post_title:{POST_ID}
      < Title_baz__baz_
      > Title
      """
    And STDERR should be empty

    # kana with diacritic and decomposed "a" + umlaut.
    When I run `wp search-replace '_baz_' '_バäz_' wp_posts --log=- --before_context=10 --after_context=20`
    Then STDOUT should contain:
      """
      Success: Made 2 replacements.
      """
    And STDOUT should contain:
      """
      wp_posts.post_content:{POST_ID}
      < Content_baz_12345678901234567890 [...] 1234567890_baz_12345678901234567890
      > Content_バäz_12345678901234567890 [...] 1234567890_バäz_12345678901234567890
      """
    And STDERR should be empty

    # Testing UTF-8 context
    When I run `wp search-replace 'z_' 'zzzz_' wp_posts --log --before_context=2 --after_context=1`
    Then STDOUT should contain:
      """
      Success: Made 2 replacements.
      """
    And STDOUT should contain:
      """
      wp_posts.post_content:{POST_ID}
      < バäz_1 [...] バäz_1
      > バäzzzz_1 [...] バäzzzz_1
      """
    And STDERR should be empty

    When I run `wp option set foobar '_bar1_ _bar1_12345678901234567890123456789012345678901234567890_bar1_ _bar1_1234567890123456789012345678901234567890'`

    When I run `wp search-replace '_bar1_' '_baz1_' wp_options --log`
    Then STDOUT should contain:
      """
      < _bar1_ _bar1_1234567890123456789012345678901234567890 [...] 1234567890123456789012345678901234567890_bar1_ _bar1_1234567890123456789012345678901234567890
      > _baz1_ _baz1_1234567890123456789012345678901234567890 [...] 1234567890123456789012345678901234567890_baz1_ _baz1_1234567890123456789012345678901234567890
      """
    And STDERR should be empty

    When I run `wp option get foobar`
    Then STDOUT should be:
      """
      _baz1_ _baz1_12345678901234567890123456789012345678901234567890_baz1_ _baz1_1234567890123456789012345678901234567890
      """

    When I run `wp search-replace '_baz1_' '_bar1_' wp_options --log --before_context=10 --after_context=10`
    Then STDOUT should contain:
      """
      < _baz1_ _baz1_1234567890 [...] 1234567890_baz1_ _baz1_1234567890
      > _bar1_ _bar1_1234567890 [...] 1234567890_bar1_ _bar1_1234567890
      """
    And STDERR should be empty

    When I run `wp option set foobar2 '12345678901234567890_bar2_1234567890_bar2_ _bar2_ _bar2_'`

    When I run `wp search-replace '_bar2_' '_baz2baz2_' wp_options --log --before_context=10 --after_context=10`
    Then STDOUT should contain:
      """
      < 1234567890_bar2_1234567890 [...] 1234567890_bar2_ _bar2_ _bar2_
      > 1234567890_baz2baz2_1234567890 [...] 1234567890_baz2baz2_ _baz2baz2_ _baz2baz2_
      """
    And STDERR should be empty

    When I run `wp option get foobar2`
    Then STDOUT should be:
      """
      12345678901234567890_baz2baz2_1234567890_baz2baz2_ _baz2baz2_ _baz2baz2_
      """

    When I run `wp search-replace '_baz2baz2_' '_barz2_' wp_options --log  --before_context=10 --after_context=4`
    Then STDOUT should contain:
      """
      < 1234567890_baz2baz2_1234 [...] 1234567890_baz2baz2_ _baz2baz2_ _baz2baz2_
      > 1234567890_barz2_1234 [...] 1234567890_barz2_ _barz2_ _barz2_
      """
    And STDERR should be empty

    When I run `wp option set foobar3 '_bar3 _bar3 _bar3 _bar3'`

    When I run `wp search-replace '_bar3' 'baz3' wp_options --log`
    Then STDOUT should contain:
      """
      < _bar3 _bar3 _bar3 _bar3
      > baz3 baz3 baz3 baz3
      """
    And STDERR should be empty

    When I run `wp option get foobar3`
    Then STDOUT should be:
      """
      baz3 baz3 baz3 baz3
      """

    When I run `wp search-replace 'baz3' 'baz\3' wp_options --dry-run --log`
    Then STDOUT should contain:
      """
      < baz3 baz3 baz3 baz3
      > baz\3 baz\3 baz\3 baz\3
      """
    And STDERR should be empty

  Scenario: Logging with regex replace
    Given a WP install

    When I run `wp post create --post_title='Title_baz__boz_' --post_content='Content_baz_1234567890_bez_1234567890_biz_1234567890_boz_1234567890_buz_' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp search-replace '_b[aeiou]z_' '_bz_' wp_posts --regex --dry-run --log  --before_context=11 --after_context=11`
    Then STDOUT should contain:
      """
      Success: 2 replacements to be made.
      """
    And STDOUT should end with a table containing rows:
    | Table    | Column       | Replacements | Type |
    | wp_posts | post_content | 1            | PHP  |
    | wp_posts | post_title   | 1            | PHP  |

    And STDOUT should contain:
      """
      wp_posts.post_content:{POST_ID}
      < Content_baz_1234567890_bez_1234567890_biz_1234567890_boz_1234567890_buz_
      > Content_bz_1234567890_bz_1234567890_bz_1234567890_bz_1234567890_bz_
      """
    And STDOUT should contain:
      """
      wp_posts.post_title:{POST_ID}
      < Title_baz__boz_
      > Title_bz__bz_
      """
    And STDERR should be empty

    When I run `wp search-replace '_b([aeiou])z_' '_$1b\\1z_\0' wp_posts --regex --log --before_context=11 --after_context=11`
    Then STDOUT should contain:
      """
      Success: Made 2 replacements.
      """

    And STDOUT should contain:
      """
      wp_posts.post_content:{POST_ID}
      < Content_baz_1234567890_bez_1234567890_biz_1234567890_boz_1234567890_buz_
      > Content_ab\1z__baz_1234567890_eb\1z__bez_1234567890_ib\1z__biz_1234567890_ob\1z__boz_1234567890_ub\1z__buz_
      """
    And STDOUT should contain:
      """
      wp_posts.post_title:{POST_ID}
      < Title_baz__boz_
      > Title_ab\1z__baz__ob\1z__boz_
      """
    And STDERR should be empty

    When I run `wp post get {POST_ID} --field=title`
    Then STDOUT should be:
      """
      Title_ab\1z__baz__ob\1z__boz_
      """

    When I run `wp post get {POST_ID} --field=content`
    Then STDOUT should be:
      """
      Content_ab\1z__baz_1234567890_eb\1z__bez_1234567890_ib\1z__biz_1234567890_ob\1z__boz_1234567890_ub\1z__buz_
      """

  Scenario: Logging with prefixes and custom colors
    Given a WP install

    When I run `WP_CLI_SEARCH_REPLACE_LOG_PREFIXES='- ,+ ' wp search-replace Just Yet --dry-run --log`
    Then STDOUT should contain:
      """
      - Just another WordPress site
      + Yet another WordPress site
      """
    And STDERR should be empty

    When I run `WP_CLI_SEARCH_REPLACE_LOG_PREFIXES=',' wp search-replace Just Yet --dry-run --log`
    Then STDOUT should not contain:
      """
      < Just
      """
    And STDOUT should contain:
      """
      Just
      """
    And STDOUT should not contain:
      """
      > Yet
      """
    And STDOUT should contain:
      """
      Yet
      """
    And STDERR should be empty

    When I run `SHELL_PIPE=0 wp search-replace WordPress WP --dry-run --log`
    Then STDOUT should strictly contain:
      """
      [34;1mwp_options.option_value:
      """
    And STDOUT should strictly contain:
      """
      [31;1m< [0mJust another [31;1mWordPress[0m site
      [32;1m> [0mJust another [32;1mWP[0m site
      """
    And STDERR should be empty

    When I run `SHELL_PIPE=0 WP_CLI_SEARCH_REPLACE_LOG_COLORS='%b,%r,%g' wp search-replace WordPress WP --dry-run --log`
    Then STDOUT should strictly contain:
      """
      [34mwp_options.option_value:
      """
    And STDOUT should strictly contain:
      """
      [31m< [0mJust another [31mWordPress[0m site
      [32m> [0mJust another [32mWP[0m site
      """
    And STDERR should be empty

    When I run `SHELL_PIPE=0 WP_CLI_SEARCH_REPLACE_LOG_COLORS='%b,%r,%g' wp search-replace WordPress WP --dry-run --log=replace.log`
    Then STDOUT should not contain:
      """
      wp_options.option_value
      """
    And the replace.log file should strictly contain:
      """
      [34mwp_options.option_value:
      """
    And the replace.log file should strictly contain:
      """
      [31m< [0mJust another [31mWordPress[0m site
      [32m> [0mJust another [32mWP[0m site
      """
    And STDERR should be empty

    When I run `SHELL_PIPE=0 wp search-replace WordPress WP --dry-run --log=replace.log`
    Then STDOUT should not contain:
      """
      wp_options.option_value
      """
    And the replace.log file should contain:
      """
      wp_options.option_value:
      """
    And the replace.log file should contain:
      """
      < Just another WordPress site
      > Just another WP site
      """
    And STDERR should be empty

    When I run `SHELL_PIPE=0 WP_CLI_SEARCH_REPLACE_LOG_COLORS=',,' wp search-replace WordPress WP --dry-run --log`
    Then STDOUT should contain:
      """
      wp_options.option_value:
      """
    And STDOUT should contain:
      """
      < Just another WordPress site
      > Just another WP site
      """
    And STDERR should be empty

  # Regression test for https://github.com/wp-cli/search-replace-command/issues/58
  Scenario: The parameters --regex and --all-tables-with-prefix produce valid SQL
    Given a WP install
    And a test_db.sql file:
      """
      CREATE TABLE `wp_123_test` (
        `name` varchar(50),
        `value` varchar(5000),
        `created_at` datetime NOT NULL,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`name`)
      ) ENGINE=InnoDB;
      INSERT INTO `wp_123_test` VALUES ('test_val','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('123.','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('quote\'quote','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('0','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('18446744073709551616','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('-18446744073709551615','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');
      INSERT INTO `wp_123_test` VALUES ('123456789012345678801234567890','wp_123_test_value_X','2016-11-15 14:41:33','2016-11-15 21:41:33');

      CREATE TABLE `wp_123_test2` (`bigint_unsigned_key` BIGINT UNSIGNED NOT NULL, `value` VARCHAR(255), PRIMARY KEY (`bigint_unsigned_key`) );
      INSERT INTO `wp_123_test2` VALUES ('18446744073709551615','wp_123_test2_value_X');

      CREATE TABLE `wp_123_test3` (`bigint_signed_key` BIGINT SIGNED NOT NULL, `value` VARCHAR(255), PRIMARY KEY (`bigint_signed_key`) );
      INSERT INTO `wp_123_test3` VALUES ('-9223372036854775808','wp_123_test3_value_X');
      """
    And I run `wp db query "SOURCE test_db.sql;"`

    When I run `wp search-replace --dry-run --regex 'mytestdomain.com\/' 'mytestdomain2.com/' --all-tables-with-prefix --skip-columns=guid,domain`
    Then STDOUT should contain:
      """
      Success: 0 replacements to be made.
      """

    When I run `wp search-replace --dry-run --regex 'wp_123_test_value_X' 'wp_123_test_value_Y' --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      Success: 8 replacements to be made.
      """

    When I run `wp search-replace --dry-run --regex 'wp_123_test2_value_X' 'wp_123_test2_value_Y' --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      Success: 1 replacement to be made.
      """

    When I run `wp search-replace --dry-run --regex 'wp_123_test3_value_X' 'wp_123_test3_value_Y' --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      Success: 1 replacement to be made.
      """

  # Regression test for https://github.com/wp-cli/search-replace-command/issues/68
  Scenario: Incomplete classes are handled gracefully during (un)serialization

    Given a WP install
    And I run `wp option add cereal_isation 'a:1:{i:0;O:10:"CornFlakes":0:{}}'`

    When I try `wp search-replace CornFlakes Smacks`
    Then STDERR should contain:
      """
      Warning: Skipping an uninitialized class "CornFlakes", replacements might not be complete.
      """
    And STDOUT should contain:
      """
      Success: Made 0 replacements.
      """

    When I run `wp option get cereal_isation`
    Then STDOUT should contain:
      """
      a:1:{i:0;O:10:"CornFlakes":0:{}}
      """

  Scenario: Regex search/replace with `--regex-limit=1` option
    Given a WP install
    And I run `wp post create --post_content="I have a pen, I have an apple. Pen, pine-apple, apple-pen."`

    When I run `wp search-replace --regex "ap{2}le" "orange" --regex-limit=1 --log`
    Then STDOUT should contain:
      """
      I have a pen, I have an orange. Pen, pine-apple, apple-pen.
      """

  Scenario: Regex search/replace with `--regex-limit=2` option
    Given a WP install
    And I run `wp post create --post_content="I have a pen, I have an apple. Pen, pine-apple, apple-pen."`

    When I run `wp search-replace --regex "ap{2}le" "orange" --regex-limit=2 --log`
    Then STDOUT should contain:
      """
      I have a pen, I have an orange. Pen, pine-orange, apple-pen.
      """

  Scenario: Regex search/replace with incorrect or default `--regex-limit`
    Given a WP install
    When I try `wp search-replace '(Hello)\s(world)' '$2, $1' --regex --regex-limit=asdf`
    Then STDERR should be:
      """
      Error: `--regex-limit` expects a non-zero positive integer or -1.
      """
    When I try `wp search-replace '(Hello)\s(world)' '$2, $1' --regex --regex-limit=0`
    Then STDERR should be:
      """
      Error: `--regex-limit` expects a non-zero positive integer or -1.
      """
    When I try `wp search-replace '(Hello)\s(world)' '$2, $1' --regex --regex-limit=-2`
    Then STDERR should be:
      """
      Error: `--regex-limit` expects a non-zero positive integer or -1.
      """
    When I run `wp search-replace '(Hello)\s(world)' '$2, $1' --regex --regex-limit=-1`
    Then STDOUT should contain:
      """
      Success:
      """

  Scenario: Chunking works without skipping lines
    Given a WP install
    And a create_sql_file.sh file:
      """
      #!/bin/bash
      echo "CREATE TABLE \`wp_123_test\` (\`key\` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT, \`text\` TEXT, PRIMARY KEY (\`key\`) );" > test_db.sql
      echo "INSERT INTO \`wp_123_test\` (\`text\`) VALUES" >> test_db.sql
      index=1
      while [[ $index -le 199 ]];
      do
        echo "('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc')," >> test_db.sql
        index=`expr $index + 1`
      done
        echo "('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc'),('abc');" >> test_db.sql
      """
    And I run `bash create_sql_file.sh`
    And I run `wp db query "SOURCE test_db.sql;"`

    When I run `wp search-replace --dry-run 'abc' 'def' --all-tables-with-prefix --skip-columns=guid,domain --precise`
    Then STDOUT should contain:
      """
      Success: 2000 replacements to be made.
      """

    When I run `wp search-replace 'abc' 'def' --all-tables-with-prefix --skip-columns=guid,domain --precise`
    Then STDOUT should contain:
      """
      Success: Made 2000 replacements.
      """

    When I run `wp search-replace --dry-run 'abc' 'def' --all-tables-with-prefix --skip-columns=guid,domain --precise`
    Then STDOUT should contain:
      """
      Success: 0 replacements to be made.
      """

    When I run `wp search-replace 'abc' 'def' --all-tables-with-prefix --skip-columns=guid,domain --precise`
    Then STDOUT should contain:
      """
      Success: Made 0 replacements.
      """
