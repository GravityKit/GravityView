Feature: Search through the database

  Scenario: Search on a single site install
    Given a WP install
    And I run `wp db query "CREATE TABLE wp_not ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) );"`
    And I run `wp db query "INSERT INTO wp_not (awesome_stuff) VALUES ('example.com'), ('e_ample.c%m'), ('example.comm'), ('example.com example.com');"`
    And I run `wp db query "CREATE TABLE pw_options ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) );"`
    And I run `wp db query "INSERT INTO pw_options (awesome_stuff) VALUES ('example.com'), ('e_ample.c%m'), ('example.comm'), ('example.com example.com');"`

    When I run `wp db query "SELECT CONCAT( id, ':', awesome_stuff) FROM wp_not ORDER BY id;" --skip-column-names`
    Then STDOUT should be:
      """
      1:example.com
      2:e_ample.c%m
      3:example.comm
      4:example.com example.com
      """
    When I run `wp db query "SELECT CONCAT( id, ':', awesome_stuff) FROM pw_options ORDER BY id;" --skip-column-names`
    Then STDOUT should be:
      """
      1:example.com
      2:e_ample.c%m
      3:example.comm
      4:example.com example.com
      """

    When I run `wp db search example.com`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com wp_options`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com wp_options wp_not --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:example.com
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDOUT should not contain:
      """
      e_ample.c%m
      """

    When I run `wp db search example.com wp_options wp_not --before_context=0 --after_context=0 --all-tables`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      4:example.com [...] example.com
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDOUT should not contain:
      """
      e_ample.c%m
      """
    And STDERR should be empty

    When I run `wp db search EXAMPLE.COM --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:example.com
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search Example.Com --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:example.com
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search nothing_matches`
    Then STDOUT should be empty
    And STDERR should be empty

    When I run `wp db prefix`
    Then STDOUT should be:
      """
      wp_
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables-with-prefix --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      3:example.com
      """
    And STDOUT should not contain:
      """
      e_ample.c%m
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      3:example.com
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      3:example.com
      """
    And STDOUT should not contain:
      """
      e_ample.c%m
      """
    And STDERR should be empty

    When I run `wp db search e_ample.c%m`
    Then STDOUT should be empty
    And STDERR should be empty

    When I run `wp db search e_ample.c%m --all-tables --before_context=0 --after_context=0`
    Then STDOUT should not contain:
      """
      wp_options
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      2:e_ample.c%m
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      2:e_ample.c%m
      """
    And STDOUT should not contain:
      """
      example.com
      """
    And STDERR should be empty

    When I run `wp db search example.comm --all-tables --before_context=0 --after_context=0`
    Then STDOUT should not contain:
      """
      wp_options
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      3:example.comm
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      3:example.comm
      """
    And STDOUT should not contain:
      """
      e_ample.c%m
      """
    And STDOUT should not contain:
      """
      1:example.com
      """
    And STDERR should be empty

    When I try `wp db search example.com no_such_table`
    Then STDERR should be:
      """
      Error: Couldn't find any tables matching: no_such_table
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp db query "CREATE TABLE no_key ( awesome_stuff TEXT );"`
    And I run `wp db query "CREATE TABLE no_text ( id int(11) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (id) );"`

    When I try `wp db search example.com no_key --all-tables`
    Then STDOUT should be empty
    And STDERR should be:
      """
      Warning: No primary key for table 'no_key'. No row ids will be outputted.
      """
    And the return code should be 0

    When I try `wp db search example.com no_text --all-tables`
    Then STDOUT should be empty
    And STDERR should be:
      """
      Warning: No text columns for table 'no_text' - skipped.
      """
    And the return code should be 0

  @require-wp-4.0
  Scenario: Search on a multisite install
    Given a WP multisite install
    And I run `wp site create --slug=foo`
    And I run `wp db query "CREATE TABLE wp_not ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) );"`
    And I run `wp db query "INSERT INTO wp_not (awesome_stuff) VALUES ('example.com'), ('e_ample.c%m');"`
    And I run `wp db query "CREATE TABLE wp_2_not ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) );"`
    And I run `wp db query "INSERT INTO wp_2_not (awesome_stuff) VALUES ('example.com'), ('e_ample.c%m');"`
    And I run `wp db query "CREATE TABLE pw_options ( id int(11) unsigned NOT NULL AUTO_INCREMENT, awesome_stuff TEXT, PRIMARY KEY (id) );"`
    And I run `wp db query "INSERT INTO pw_options (awesome_stuff) VALUES ('example.com'), ('e_ample.c%m');"`

    When I run `wp db query "SELECT CONCAT( id, ':', awesome_stuff) FROM wp_not ORDER BY id;" --skip-column-names`
    Then STDOUT should be:
      """
      1:example.com
      2:e_ample.c%m
      """
    When I run `wp db query "SELECT CONCAT( id, ':', awesome_stuff) FROM wp_2_not ORDER BY id;" --skip-column-names`
    Then STDOUT should be:
      """
      1:example.com
      2:e_ample.c%m
      """
    When I run `wp db query "SELECT CONCAT( id, ':', awesome_stuff) FROM pw_options ORDER BY id;" --skip-column-names`
    Then STDOUT should be:
      """
      1:example.com
      2:e_ample.c%m
      """

    When I run `wp db search example.com`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      wp_2_options
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      wp_2_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com wp_options`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      wp_2_options
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      wp_2_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --url=example.com/foo`
    Then STDOUT should not contain:
      """
      wp_options
      """
    And STDOUT should contain:
      """
      wp_2_options:option_value
      1:https://example.com/foo
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      wp_2_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --network`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should contain:
      """
      wp_2_options:option_value
      1:https://example.com/foo
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      wp_2_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --no-network`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      wp_2_options
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      wp_2_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should contain:
      """
      wp_2_options:option_value
      1:https://example.com/foo
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_2_not:awesome_stuff
      1:example.com
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --no-all-tables-with-prefix`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      wp_2_options
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should not contain:
      """
      wp_2_not
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables-with-prefix --url=example.com/foo`
    Then STDOUT should not contain:
      """
      wp_options
      """
    And STDOUT should contain:
      """
      wp_2_options:option_value
      1:https://example.com/foo
      """
    And STDOUT should not contain:
      """
      wp_not
      """
    And STDOUT should contain:
      """
      wp_2_not:awesome_stuff
      1:example.com
      """
    And STDOUT should not contain:
      """
      pw_options
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should contain:
      """
      wp_2_options:option_value
      1:https://example.com/foo
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_2_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      1:example.com
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables --url=example.com/foo`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should contain:
      """
      wp_2_options:option_value
      1:https://example.com/foo
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      wp_2_not:awesome_stuff
      1:example.com
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      1:example.com
      """
    And STDERR should be empty

    When I run `wp db search e_ample.c%m`
    Then STDOUT should be empty
    And STDERR should be empty

    When I run `wp db search e_ample.c%m --all-tables`
    Then STDOUT should not contain:
      """
      wp_options
      """
    And STDOUT should not contain:
      """
      wp_2_options
      """
    And STDOUT should contain:
      """
      wp_not:awesome_stuff
      2:e_ample.c%m
      """
    And STDOUT should contain:
      """
      wp_2_not:awesome_stuff
      2:e_ample.c%m
      """
    And STDOUT should contain:
      """
      pw_options:awesome_stuff
      2:e_ample.c%m
      """
    And STDERR should be empty

  Scenario: Long result strings are truncated
    Given a WP install
    And I run `wp option update searchtest '11111111searchstring11111111'`

    When I run `wp db search searchstring --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      :searchstring
      """
    And STDOUT should not contain:
      """
      searchstring1
      """

    When I run `wp db search searchstring --before_context=3 --after_context=3`
    Then STDOUT should contain:
      """
      :111searchstring111
      """
    And STDOUT should not contain:
      """
      searchstring1111
      """

    When I run `wp db search searchstring --before_context=2 --after_context=1`
    Then STDOUT should contain:
      """
      :11searchstring1
      """
    And STDOUT should not contain:
      """
      searchstring11
      """

    When I run `wp db search searchstring`
    Then STDOUT should contain:
      """
      :11111111searchstring11111111
      """

  Scenario: Search with multibyte strings
    Given a WP install
    And I run `wp option update multibytetest 'あいうえおかきくけこさしすせとたちつてと'`
    # Note ö is o with combining umlaut.
    And I run `wp option update plaintst_combining 'lllllムnöppppp'`

    When I run `wp db search "かきくけこ" --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      :かきくけこ
      """
    And STDOUT should not contain:
      """
      かきくけこさ
      """

    When I run `wp db search "かきくけこ" --before_context=3 --after_context=3`
    Then STDOUT should contain:
      """
      :うえおかきくけこさしす
      """


    When I run `wp db search "かきくけこ" --before_context=2 --after_context=1`
    Then STDOUT should contain:
      """
      :えおかきくけこさ
      """
    And STDOUT should not contain:
      """
      えおかきくけこさし
      """

    When I run `wp db search "かきくけこ"`
    Then STDOUT should contain:
      """
      :あいうえおかきくけこさしすせとたちつてと
      """

    When I run `wp db search 'ppppp' --before_context=3 --after_context=4`
    Then STDOUT should contain:
      """
      :ムnöppppp
      """

    When I run `wp db search 'ppppp' --before_context=1 --after_context=1`
    Then STDOUT should contain:
      """
      :öppppp
      """

    When I run `wp db search 'ムn' --before_context=2 --after_context=1`
    Then STDOUT should contain:
      """
      :llムnö
      """
    And STDOUT should not contain:
      """
      :llムnöp
      """

    When I run `wp db search 'ムn' --before_context=2 --after_context=2`
    Then STDOUT should contain:
      """
      :llムnöp
      """
    And STDOUT should not contain:
      """
      :llムnöpp
      """

  Scenario: Search with regular expressions
    Given a WP install
    And I run `wp option update regextst '12345é789あhttps://regextst.com1234567890123456789éhttps://regextst.com12345678901234567890regextst.com34567890t.com67890'`
    # Note ö is o with combining umlaut.
    And I run `wp option update regextst_combining 'lllllムnöppppp'`

    When I run `wp db search 'https?:\/\/example.c.m' --regex`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      """
    And STDOUT should not contain:
      """
      [...]
      """

    When I run `wp db search 'unfindable' --regex`
    Then STDOUT should be empty

    When I try `wp db search 'unfindable' --regex --regex-flags='abcd'`
    Then STDERR should contain:
      """
      unfindable
      """
    And STDERR should contain:
      """
      abcd
      """
    And the return code should be 1

    When I try `wp db search 'unfindable' --regex --regex-delimiter='1'`
    Then STDERR should be:
      """
      Error: The regex '1unfindable1' fails.
      """
    And the return code should be 1

    When I try `wp db search 'regex error)' --regex`
    Then STDERR should be:
      """
      Error: The regex pattern 'regex error)' with default delimiter 'chr(1)' and no flags fails.
      """
    And the return code should be 1

    When I try `wp db search 'regex error)' --regex --regex-flags=u`
    Then STDERR should be:
      """
      Error: The regex pattern 'regex error)' with default delimiter 'chr(1)' and flags 'u' fails.
      """
    And the return code should be 1

    When I try `wp db search 'regex error)' --regex --regex-delimiter=/`
    Then STDERR should be:
      """
      Error: The regex '/regex error)/' fails.
      """
    And the return code should be 1

    When I try `wp db search 'regex error)' --regex --regex-delimiter=/ --regex-flags=u`
    Then STDERR should be:
      """
      Error: The regex '/regex error)/u' fails.
      """
    And the return code should be 1

    When I run `wp db search '[0-9é]+?https:' --regex --regex-flags=u --before_context=0 --after_context=0`
    Then STDOUT should contain:
      """
      :1234567890123456789éhttps:
      """
    And STDOUT should not contain:
      """
      /
      """
    And STDOUT should not contain:
      """
      [...]
      """

    When I run `wp db search 'htt(p(s):)\/\/' --regex --before_context=1 --after_context=3`
    Then STDOUT should contain:
      """
      :あhttps://reg [...] éhttps://reg
      """
    And STDOUT should not contain:
      """
      rege
      """

    When I run `wp db search 'https://' --regex --regex-delimiter=# --before_context=9 --after_context=11`
    Then STDOUT should contain:
      """
      :2345é789あhttps://regextst.co [...] 23456789éhttps://regextst.co
      """
    And STDOUT should not contain:
      """
      regextst.com
      """

    When I run `wp db search 'httPs://' --regex --regex-delimiter=# --before_context=3 --after_context=0`
    Then STDOUT should be empty

    When I run `wp db search 'httPs://' --regex --regex-flags=i --regex-delimiter=# --before_context=3 --after_context=0`
    Then STDOUT should contain:
      """
      :89あhttps:// [...] 89éhttps://
      """
    And STDOUT should not contain:
      """
      https://r
      """

    When I run `wp db search 'ppppp' --regex --before_context=3 --after_context=4`
    Then STDOUT should contain:
      """
      :ムnöppppp
      """

    When I run `wp db search 'ppppp' --regex --before_context=1 --after_context=1`
    Then STDOUT should contain:
      """
      :öppppp
      """

    When I run `wp db search 'ムn' --before_context=2 --after_context=1`
    Then STDOUT should contain:
      """
      :llムnö
      """
    And STDOUT should not contain:
      """
      :llムnöp
      """

    When I run `wp db search 'ムn' --regex --before_context=2 --after_context=2`
    Then STDOUT should contain:
      """
      :llムnöp
      """
    And STDOUT should not contain:
      """
      :llムnöpp
      """

    When I run `wp db search 't\.c' --regex --before_context=1 --after_context=1`
    Then STDOUT should contain:
      """
      :st.co [...] st.co [...] st.co [...] 0t.co
      """
    And STDOUT should not contain:
      """
      st.com
      """

    When I run `wp db search 'https://' --regex`
    Then the return code should be 0

  @require-wp-4.7
  Scenario: Search with output options
    Given a WP install

    When I run `wp db search example.com`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      wp_options:option_value
      2:https://example.com
      """

    When I run `wp db search example.com --table_column_once`
    Then STDOUT should contain:
      """
      wp_options:option_value
      1:https://example.com
      2:https://example.com
      """

    When I run `wp db search example.com --one_line`
    Then STDOUT should contain:
      """
      wp_options:option_value:1:https://example.com
      wp_options:option_value:2:https://example.com
      """

    When I run `wp db search example.com --table_column_once --one_line`
    Then STDOUT should contain:
      """
      wp_options:option_value:1:https://example.com
      wp_options:option_value:2:https://example.com
      """

    When I run `wp db search example.com --all-tables --before_context=0 --after_context=0 --matches_only`
    Then STDOUT should not contain:
      """
      :
      """
    And STDERR should be empty

    When I run `wp db search example.com --all-tables --before_context=0 --after_context=0 --stats`
    Then STDOUT should contain:
      """
      Success: Found
      """
    And STDOUT should contain:
      """
      1 table skipped: wp_term_relationships.
      """
    And STDERR should be empty

  Scenario: Search with custom colors
    Given a WP install

    When I run `SHELL_PIPE=0 wp db search example.com`
    Then STDOUT should strictly contain:
      """
      [32;1mwp_options:option_value[0m
      [33;1m1[0m:https://[43m[30mexample.com[0m
      """

    When I run `SHELL_PIPE=0 wp db search example.com --table_column_color=%r --id_color=%g --match_color=%b`
    Then STDOUT should strictly contain:
      """
      [31mwp_options:option_value[0m
      [32m1[0m:https://[34mexample.com[0m
      """

    When I run `SHELL_PIPE=0 wp db search example.com --table_column_color=%r`
    Then STDOUT should strictly contain:
      """
      [31mwp_options:option_value[0m
      [33;1m1[0m:https://[43m[30mexample.com[0m
      """

    When I run `SHELL_PIPE=0 wp db search example.com --id_color=%g`
    Then STDOUT should strictly contain:
      """
      [32;1mwp_options:option_value[0m
      [32m1[0m:https://[43m[30mexample.com[0m
      """

    When I run `SHELL_PIPE=0 wp db search example.com --match_color=%b`
    Then STDOUT should strictly contain:
      """
      [32;1mwp_options:option_value[0m
      [33;1m1[0m:https://[34mexample.com[0m
      """

    When I run `SHELL_PIPE=0 wp db search example.com --before_context=0 --after_context=0`
    Then STDOUT should strictly contain:
      """
      [32;1mwp_options:option_value[0m
      [33;1m1[0m:example.com
      """

    When I try `wp db search example.com --match_color=%x`
    Then STDERR should be:
      """
      Warning: Unrecognized percent color code '%x' for 'match_color'.
      """
    And STDOUT should contain:
      """
      example.com
      """
    And STDOUT should strictly not contain:
      """
      
      """
    And the return code should be 0

  Scenario: Search should cater for field/table names that use reserved words or unusual characters
    Given a WP install
    And a esc_sql_ident.sql file:
      """
      CREATE TABLE `TABLE` (`KEY` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `VALUES` TEXT, `back``tick` TEXT, `single'double"quote` TEXT, PRIMARY KEY (`KEY`) );
      INSERT INTO `TABLE` (`VALUES`, `back``tick`, `single'double"quote`) VALUES ('v"v`v\'v\\v_v1', 'v"v`v\'v\\v_v1', 'v"v`v\'v\\v_v1' );
      INSERT INTO `TABLE` (`VALUES`, `back``tick`, `single'double"quote`) VALUES ('v"v`v\'v\\v_v2', 'v"v`v\'v\\v_v2', 'v"v`v\'v\\v_v2' );
      """

    When I run `wp db query "SOURCE esc_sql_ident.sql;"`
    Then STDERR should be empty

    When I run `wp db search 'v_v' TABLE --all-tables`
    Then STDOUT should be:
      """
      TABLE:VALUES
      1:v"v`v'v\v_v1
      TABLE:VALUES
      2:v"v`v'v\v_v2
      TABLE:back`tick
      1:v"v`v'v\v_v1
      TABLE:back`tick
      2:v"v`v'v\v_v2
      TABLE:single'double"quote
      1:v"v`v'v\v_v1
      TABLE:single'double"quote
      2:v"v`v'v\v_v2
      """
    And STDERR should be empty

  Scenario: Search with matches within context
    Given a WP install
    And I run `wp option update matches_in_context '1234_XYXYX_2345678_XYXYX_2345678901_XYXYX_2345'`

    When I run `wp db search XYXYX --before_context=10 --after_context=10 --stats`
    Then STDOUT should contain:
      """
      Success: Found 3 matches
      """
    And STDOUT should contain:
      """
      :1234_XYXYX_2345678_XYXYX_234567890 [...] 345678901_XYXYX_2345
      """
    And STDERR should be empty

    When I run `wp db search XYXYX --before_context=10 --after_context=10 --regex --stats`
    Then STDOUT should contain:
      """
      Success: Found 3 matches
      """
    And STDOUT should contain:
      """
      :1234_XYXYX_2345678_XYXYX_234567890 [...] 345678901_XYXYX_2345
      """
    And STDERR should be empty

  @broken
  Scenario: Search with large data
    Given a WP install
    # Note "_utf8 X'CC88'" is combining umlaut. Doing it this way as non-ASCII stuff gets stripped due to (eventually) been put thru `escapeshellarg()` with a default C locale.
    # Also restricted by default MySQL values for the version-dependent size of the innodb redo log file (max 10% one transaction) and `max_allowed_packet` size (16MB).
    And I run `wp db query "INSERT INTO wp_options (option_name, option_value) VALUES ('opt_large', CONCAT(REPEAT('a', 1024 * 1024 * 8 - 9), 'o', _utf8 X'CC88', 'XYXYX'));"`

    When I run `wp db search XYXYX --before_context=1 --stats`
    Then STDOUT should contain:
      """
      Success: Found 1 match
      """
    And STDOUT should contain:
      """
      :öXYXYX
      """
    And STDOUT should not contain:
      """
      :aöXYXYX
      """
    And STDERR should be empty

    When I run `wp db search XYXYX --regex --before_context=1 --stats`
    Then STDOUT should contain:
      """
      Success: Found 1 match
      """
    And STDOUT should contain:
      """
      :öXYXYX
      """
    And STDOUT should not contain:
      """
      :aöXYXYX
      """
    And STDERR should be empty
