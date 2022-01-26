Feature: Export content.

  Scenario: Basic export
    Given a WP install

    When I run `wp export`
    Then STDOUT should contain:
      """
      All done with export.
      """

  Scenario: Export argument validator
    Given a WP install

    When I try `wp export --post_type=wp-cli-party`
    Then STDERR should contain:
      """
      Warning: The post type wp-cli-party does not exist.
      """
    And the return code should be 1

    When I try `wp export --author=invalid-author`
    Then STDERR should contain:
      """
      Warning: Could not find a matching author for invalid-author
      """
    And the return code should be 1

    When I try `wp export --start_date=invalid-date`
    Then STDERR should contain:
      """
      Warning: The start_date invalid-date is invalid.
      """
    And the return code should be 1

    When I try `wp export --end_date=invalid-date`
    Then STDERR should contain:
      """
      Warning: The end_date invalid-date is invalid.
      """
    And the return code should be 1

  Scenario: Export with post_type and post_status argument
    Given a WP install

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp site empty --yes`
    And I run `wp post generate --post_type=page --post_status=draft --count=10`
    And I run `wp post list --post_type=page --post_status=draft --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp export --post_type=page --post_status=draft`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page --post_status=draft --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page --post_status=draft --format=count`
    Then STDOUT should be:
      """
      10
      """

  Scenario: Export a comma-separated list of post types
    Given a WP install

    When I run `wp plugin install wordpress-importer --activate`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp site empty --yes`
    And I run `wp post generate --post_type=page --count=10`
    And I run `wp post generate --post_type=post --count=10`
    And I run `wp post generate --post_type=attachment --count=10`
    And I run `wp post list --post_type=page,post,attachment --format=count`
    Then STDOUT should be:
      """
      30
      """

    When I run `wp export --post_type=page,post`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page,post --format=count`
    Then STDOUT should be:
      """
      20
      """

    When I run `wp post list --post_type=page --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      10
      """

  Scenario: Export only one post
    Given a WP install

    When I run `wp plugin install wordpress-importer --activate`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp post generate --count=10`
    And I run `wp post list --format=count`
    Then STDOUT should be:
      """
      11
      """

    When I run `wp post create --post_title='Post with attachment' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ATTACHMENT_POST_ID}

    When I run `wp post create --post_type=attachment --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ATTACHMENT_ID}

    When I run `wp post update {ATTACHMENT_ID} --post_parent={ATTACHMENT_POST_ID} --porcelain`
    Then STDOUT should contain:
      """
      Success: Updated post {ATTACHMENT_ID}
      """

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp comment generate --count=2 --post_id={POST_ID}`
    And I run `wp comment list --format=count`
    Then STDOUT should contain:
      """
      3
      """

    When I run `wp export --post__in={POST_ID}`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    And the {EXPORT_FILE} file should not contain:
      """
      <wp:post_id>{ATTACHMENT_ID}</wp:post_id>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <wp:post_type>attachment</wp:post_type>
      """

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp comment list --format=count`
    Then STDOUT should be:
      """
      2
      """

  Scenario: Export multiple posts, separated by spaces
    Given a WP install

    When I run `wp plugin install wordpress-importer --activate`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post create --post_title='Test post 2' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID_TWO}

    When I run `wp export --post__in="{POST_ID} {POST_ID_TWO}"`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      2
      """

  Scenario: Export posts within a given date range
    Given a WP install

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp site empty --yes`
    And I run `wp post generate --post_type=post --post_date=2013-08-01 --count=10`
    And I run `wp post generate --post_type=post --post_date=2013-08-02 --count=10`
    And I run `wp post generate --post_type=post --post_date=2013-08-03 --count=10`
    And I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      30
      """

    When I run `wp export --post_type=post --start_date=2013-08-02 --end_date=2013-08-02`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      10
      """

  Scenario: Export posts from a given category
    Given a WP install
    And I run `wp site empty --yes`
    And I run `wp plugin install wordpress-importer --activate`

    When I run `wp term create category Apple --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {APPLE_TERM_ID}

    When I run `wp term create category Pear --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PEAR_TERM_ID}

    When I run `wp post create --post_type=post --post_title='Apple Post' --post_category={APPLE_TERM_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {APPLE_POST_ID}

    When I run `wp post create --post_type=post --post_title='Pear Post' --post_category={PEAR_TERM_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PEAR_POST_ID}

    When I run `wp export --post_type=post --category=apple`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}
    Then the {EXPORT_FILE} file should contain:
      """
      <category domain="category" nicename="apple"><![CDATA[Apple]]></category>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <![CDATA[Apple Post]]>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <category domain="category" nicename="pear"><![CDATA[Pear]]></category>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <![CDATA[Pear Post]]>
      """

    When I run `wp export --post_type=post --category={PEAR_TERM_ID}`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}
    Then the {EXPORT_FILE} file should contain:
      """
      <category domain="category" nicename="pear"><![CDATA[Pear]]></category>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <![CDATA[Pear Post]]>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <category domain="category" nicename="apple"><![CDATA[Apple]]></category>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <![CDATA[Apple Post]]>
      """

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post`
    Then STDOUT should contain:
      """
      Pear Post
      """
    And STDOUT should not contain:
      """
      Apple Post
      """

  Scenario: Export posts from a given author
    Given a WP install
    And I run `wp site empty --yes`
    And I run `wp plugin install wordpress-importer --activate`

    When I run `wp user create john john.doe@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {JOHN_USER_ID}

    When I run `wp user create jane jane.doe@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {JANE_USER_ID}

    When I run `wp post create --post_type=post --post_title='Post by John' --post_author={JOHN_USER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {JOHN_POST_ID}

    When I run `wp post create --post_type=post --post_title='Post by Jane' --post_author={JANE_USER_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {JANE_POST_ID}

    When I run `wp export --post_type=post --author={JANE_USER_ID}`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}
    Then the {EXPORT_FILE} file should contain:
      """
      jane.doe@example.com
      """
    And the {EXPORT_FILE} file should contain:
      """
      Post by Jane
      """
    And the {EXPORT_FILE} file should not contain:
      """
      john.doe@example.com
      """
    And the {EXPORT_FILE} file should not contain:
      """
      Post by John
      """

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp user delete {JOHN_USER_ID} {JANE_USER_ID} --yes`
    Then STDOUT should contain:
      """
      Success: Removed user {JOHN_USER_ID} from https://example.com.
      """
    And STDOUT should contain:
      """
      Success: Removed user {JANE_USER_ID} from https://example.com.
      """

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=create`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post`
    Then STDOUT should contain:
      """
      Post by Jane
      """
    And STDOUT should not contain:
      """
      Post by John
      """

    When I run `wp user list`
    Then STDOUT should contain:
      """
      jane.doe@example.com
      """
    And STDOUT should not contain:
      """
      john.doe@example.com
      """

  Scenario: Export posts should include user information
    Given a WP install
    And I run `wp plugin install wordpress-importer --activate`
    And I run `wp user create user user@user.com --role=editor --display_name="Test User"`
    And I run `wp post generate --post_type=post --count=10 --post_author=user`

    When I run `wp export`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}
    Then the {EXPORT_FILE} file should contain:
      """
      <wp:author_display_name><![CDATA[Test User]]></wp:author_display_name>
      """

    When I run `wp site empty --yes`
    And I run `wp user list --field=user_login | xargs -n 1 wp user delete --yes`
    Then STDOUT should not be empty

    When I run `wp import {EXPORT_FILE} --authors=create`
    Then STDOUT should not be empty

    When I run `wp user get user --field=display_name`
    Then STDOUT should be:
      """
      Test User
      """

  Scenario: Export posts from a given starting post ID
    Given a WP install

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp site empty --yes`
    And I run `wp post generate --post_type=post --count=10`
    And I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp export --start_id=6`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      5
      """

  Scenario: Exclude a specific post type from export
    Given a WP install
    And I run `wp site empty --yes`
    And I run `wp post create --post_title="Some page" --post_type=page`
    And I run `wp post generate --post_type=post --count=10`
    And I run `wp plugin install wordpress-importer --activate`

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      11
      """

    When I run `wp export --post_type__not_in=post`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post generate --post_type=post --count=10`
    And I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      11
      """

    When I run `wp export --post_type__not_in=post,page`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      0
      """

  Scenario: Export posts using --max_num_posts
    Given a WP install
    And I run `wp site empty --yes`
    And a count-instances.php file:
      """
      <?php
      echo 'count=' . preg_match_all( '#<wp:post_type>' . $args[0] . '<\/wp:post_type>#', file_get_contents( 'php://stdin' ), $matches );
      """

    When I run `wp post generate --post_type=post --count=10`
    And I run `wp export --post_type=post --max_num_posts=1 --stdout | wp --skip-wordpress eval-file count-instances.php post`
    Then STDOUT should contain:
      """
      count=1
      """

    When I run `wp post generate --post_type=post --count=10`
    And I run `wp post generate --post_type=attachment --count=10`
    And I run `wp export --max_num_posts=1 --stdout | wp --skip-wordpress eval-file count-instances.php "(post|attachment)"`
    Then STDOUT should contain:
      """
      count=1
      """

    When I run `wp export --max_num_posts=5 --stdout | wp --skip-wordpress eval-file count-instances.php "(post|attachment)"`
    Then STDOUT should contain:
      """
      count=5
      """

  Scenario: Export a site with a custom filename format
    Given a WP install

    When I run `wp export --filename_format='foo-bar.{date}.{n}.xml'`
    Then STDOUT should contain:
      """
      foo-bar.
      """
    And STDOUT should contain:
      """
      000.xml
      """

  Scenario: Export a site and skip the comments
    Given a WP install
    And I run `wp comment generate --post_id=1 --count=2`
    And I run `wp plugin install wordpress-importer --activate`

    When I run `wp comment list --format=count`
    Then STDOUT should contain:
      """
      3
      """

    When I run `wp export --skip_comments`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --format=count`
    Then STDOUT should contain:
      """
      0
      """

    When I run `wp comment list --format=count`
    Then STDOUT should contain:
      """
      0
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --format=count`
    Then STDOUT should contain:
      """
      1
      """

    When I run `wp comment list --format=count`
    Then STDOUT should contain:
      """
      0
      """

  Scenario: Export splitting the dump
    Given a WP install

    When I run `wp export --max_file_size=0.0001`
    Then STDOUT should contain:
      """
      001.xml
      """
    And STDERR should be empty

  Scenario: Export without splitting the dump
    Given a WP install
    # Make export file > 15MB so will split by default. Need to split into 4 * 4MB to stay below 10% of default redo log size of 48MB, otherwise get MySQL error.
    And I run `wp db query "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (1, '_dummy', REPEAT( 'A', 4 * 1024 * 1024 ) );"`
    And I run `wp db query "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (1, '_dummy', REPEAT( 'A', 4 * 1024 * 1024 ) );"`
    And I run `wp db query "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (1, '_dummy', REPEAT( 'A', 4 * 1024 * 1024 ) );"`
    And I run `wp db query "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (1, '_dummy', REPEAT( 'A', 4 * 1024 * 1024 ) );"`

    When I run `wp export`
    Then STDOUT should contain:
      """
      000.xml
      """
    And STDOUT should contain:
      """
      001.xml
      """
    And STDERR should be empty

    When I run `wp export --max_file_size=0`
    Then STDOUT should contain:
      """
      000.xml
      """
    And STDOUT should contain:
      """
      001.xml
      """
    And STDERR should be empty

    When I run `wp export --max_file_size=-1`
    Then STDOUT should contain:
      """
      000.xml
      """
    And STDOUT should not contain:
      """
      001.xml
      """
    And STDERR should be empty

  Scenario: Export a site to stdout
    Given a WP install
    And I run `wp comment generate --post_id=1 --count=1`
    And I run `wp plugin install wordpress-importer --activate`

    When I run `wp export --stdout > export.xml`
    Then STDOUT should be empty
    And the return code should be 0

    When I run `cat export.xml`
    Then STDOUT should not contain:
      """
      Writing to file
      """
    And STDOUT should contain:
      """
      <generator>
      """

    When I run `wp site empty --yes`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp comment list --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp import export.xml --authors=skip`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp comment list --format=count`
    Then STDOUT should be:
      """
      2
      """

  Scenario: Error when --stdout and --dir are both provided
    Given a WP install

    When I try `wp export --stdout --dir=foo`
    Then STDERR should be:
      """
      Error: --stdout and --dir cannot be used together.
      """
    And the return code should be 1

  Scenario: Export individual post with attachments
    Given a WP install
    And I run `wp plugin install wordpress-importer --activate`
    And I run `wp site empty --yes`

    When I run `wp post generate --count=10`
    And I run `wp post list --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp post create --post_title='Post with attachment to export' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {EXPORT_ATTACHMENT_POST_ID}

    When I run `wp media import 'http://wp-cli.org/behat-data/codeispoetry.png' --post_id={EXPORT_ATTACHMENT_POST_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {EXPORT_ATTACHMENT_ID}

    When I run `wp post create --post_title='Post with attachment to ignore' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {IGNORE_ATTACHMENT_POST_ID}

    When I run `wp media import 'http://wp-cli.org/behat-data/white-150-square.jpg' --post_id={IGNORE_ATTACHMENT_POST_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {IGNORE_ATTACHMENT_ID}

    When I run `wp post list --post_type=post --format=count`
    Then STDOUT should be:
      """
      12
      """

    When I run `wp post list --post_type=attachment --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp export --post__in={EXPORT_ATTACHMENT_POST_ID} --with_attachments`
    Then save STDOUT 'Writing to file %s' as {EXPORT_FILE}
    And the {EXPORT_FILE} file should contain:
      """
      <wp:post_id>{EXPORT_ATTACHMENT_POST_ID}</wp:post_id>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:post_type>attachment</wp:post_type>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:post_id>{EXPORT_ATTACHMENT_ID}</wp:post_id>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:meta_key>_wp_attachment_metadata</wp:meta_key>
      """
    And the {EXPORT_FILE} file should contain:
      """
      codeispoetry.png";s:5:"sizes"
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:meta_key>_wp_attached_file</wp:meta_key>
      """
    And the {EXPORT_FILE} file should contain:
      """
      codeispoetry.png]]></wp:meta_value>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <wp:meta_key>_edit_lock</wp:meta_key>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <wp:post_id>{IGNORE_ATTACHMENT_POST_ID}</wp:post_id>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      <wp:post_id>{IGNORE_ATTACHMENT_ID}</wp:post_id>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      white-150-square.jpg]]></wp:meta_value>
      """
    And the {EXPORT_FILE} file should not contain:
      """
      white-150-square.jpg";s:5:"sizes"
      """

  Scenario: Export categories, tags and terms
    Given a WP install
    And a wp-content/mu-plugins/register-region-taxonomy.php file:
      """
      <?php
      function wp_cli_region_taxonomy() {
          register_taxonomy( 'region', 'post', [
              'label'        => 'Region',
              'rewrite'      => [ 'slug' => 'region' ],
              'hierarchical' => true,
          ] );
      }
      add_action( 'init', 'wp_cli_region_taxonomy' );
      """
    And I run `wp plugin install wordpress-importer --activate`
    And I run `wp site empty --yes`

    When I run `wp term create category News --description="A news article" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {EXPORT_CATEGORY_ID}

    When I run `wp term create post_tag Tech --description="Technology-related" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {EXPORT_TAG_ID}

    When I run `wp term create region Europe --description="Concerns Europe" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {EXPORT_TERM_ID}

    When I run `wp post create --post_title='Breaking News' --post_category={EXPORT_CATEGORY_ID} --tags_input={EXPORT_TAG_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {EXPORT_POST_ID}

    When I run `wp post term add {EXPORT_POST_ID} region {EXPORT_TERM_ID}`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp export --post__in={EXPORT_POST_ID}`
    Then save STDOUT 'Writing to file %s' as {EXPORT_FILE}
    And the {EXPORT_FILE} file should contain:
      """
      <wp:post_id>{EXPORT_POST_ID}</wp:post_id>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:category>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:term_id>{EXPORT_CATEGORY_ID}</wp:term_id>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:cat_name><![CDATA[News]]></wp:cat_name>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:tag>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:term_id>{EXPORT_TAG_ID}</wp:term_id>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:tag_name><![CDATA[Tech]]></wp:tag_name>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:term>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:term_id>{EXPORT_TERM_ID}</wp:term_id>
      """
    And the {EXPORT_FILE} file should contain:
      """
      <wp:term_name><![CDATA[Europe]]></wp:term_name>
      """

    When I run `wp site empty --yes`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp post list`
    Then STDOUT should not contain:
      """
      Breaking News
      """

    When I run `wp term list category`
    Then STDOUT should not contain:
      """
      News
      """

    When I run `wp term list post_tag`
    Then STDOUT should not contain:
      """
      Tech
      """

    When I run `wp term list region`
    Then STDOUT should not contain:
      """
      Europe
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp post list`
    Then STDOUT should contain:
      """
      Breaking News
      """

    When I run `wp term list category`
    Then STDOUT should contain:
      """
      News
      """

    When I run `wp term list post_tag`
    Then STDOUT should contain:
      """
      Tech
      """

    When I run `wp term list region`
    Then STDOUT should contain:
      """
      Europe
      """
