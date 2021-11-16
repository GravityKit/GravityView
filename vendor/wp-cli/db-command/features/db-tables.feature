Feature: List database tables

  Scenario: List database tables on a single WordPress install
    Given a WP install

    When I run `wp db tables`
    Then STDOUT should contain:
      """
      wp_commentmeta
      wp_comments
      wp_links
      wp_options
      wp_postmeta
      wp_posts
      wp_term_relationships
      wp_term_taxonomy
      """
    # Leave out wp_termmeta for old WP compat.
    And STDOUT should contain:
      """
      wp_terms
      wp_usermeta
      wp_users
      """

    When I run `wp db tables --format=csv`
    Then STDOUT should contain:
      """
      ,wp_terms,wp_usermeta,wp_users
      """

    When I run `wp db tables 'wp_post*' --format=csv`
    Then STDOUT should be:
      """
      wp_postmeta,wp_posts
      """

  @require-wp-3.9
  Scenario: List database tables on a multisite WordPress install
    Given a WP multisite install

    When I run `wp db tables`
    # Leave out wp_blog_versions, it was never used and removed in WP 5.3+.
    # See https://core.trac.wordpress.org/ticket/19755

    # Leave out wp_blogmeta for old WP compat.
    And STDOUT should contain:
      """
      wp_blogs
      wp_commentmeta
      wp_comments
      wp_links
      wp_options
      wp_postmeta
      wp_posts
      wp_registration_log
      wp_signups
      wp_site
      wp_sitemeta
      wp_term_relationships
      wp_term_taxonomy
      """
    # Leave out wp_termmeta for old WP compat.
    And STDOUT should contain:
      """
      wp_terms
      wp_usermeta
      wp_users
      """

    When I run `wp site create --slug=foo`
    And I run `wp db tables --url=example.com/foo`
    Then STDOUT should contain:
      """
      wp_users
      """
    And STDOUT should contain:
      """
      wp_usermeta
      """
    And STDOUT should contain:
      """
      wp_2_posts
      """

    When I run `wp db tables --url=example.com/foo --scope=global`
    Then STDOUT should not contain:
      """
      wp_2_posts
      """

    When I run `wp db tables --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      wp_2_posts
      """
    And STDOUT should contain:
      """
      wp_posts
      """

    When I run `wp db tables --url=example.com/foo --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      wp_2_posts
      """
    And STDOUT should not contain:
      """
      wp_posts
      """

    When I run `wp db tables --url=example.com/foo --network`
    Then STDOUT should contain:
      """
      wp_2_posts
      """
    And STDOUT should contain:
      """
      wp_posts
      """

  Scenario: Listing a site's tables should only list that site's tables
    Given a WP multisite install

    When I run `wp site create --slug=foo --porcelain`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp db query "ALTER TABLE wp_blogs AUTO_INCREMENT=21"`
    Then the return code should be 0

    When I run `wp site create --slug=bar --porcelain`
    Then STDOUT should be:
      """
      21
      """

    When I run `wp db tables --url=example.com/foo --all-tables-with-prefix`
    Then STDOUT should contain:
      """
      wp_2_posts
      """
    And STDOUT should not contain:
      """
      wp_21_posts
      """

  Scenario: List database tables with wildcards on a single WordPress install with custom table prefix
    Given a WP install
    And "$table_prefix = 'wp_';" replaced with "$table_prefix = 'as_wp_';" in the wp-config.php file
    And I try `wp core install --url=example.com --title=example --admin_user=wpcli --admin_email=wpcli@example.com`
    Then STDOUT should contain:
      """
      Success:
      """
    And the return code should be 0

    When I run `wp db tables '*_posts'`
    Then STDOUT should be:
      """
      as_wp_posts
      """

    When I run `wp db tables '*_posts' --network`
    Then STDOUT should be:
      """
      as_wp_posts
      """

    When I run `wp db tables '*_posts' --scope=blog`
    Then STDOUT should be:
      """
      as_wp_posts
      """

    When I try `wp db tables '*_posts' --scope=global`
    Then STDERR should not be empty
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp db tables '*_users' --scope=global`
    Then STDOUT should be:
      """
      as_wp_users
      """

  Scenario: List database tables with wildcards on a multisite WordPress install with custom table prefix
    Given a WP multisite install
    And "$table_prefix = 'wp_';" replaced with "$table_prefix = 'as_wp_';" in the wp-config.php file
    # Use try to cater for wp-db errors in old WPs.
    And I try `wp core multisite-install --url=example.com --title=example --admin_user=wpcli --admin_email=wpcli@example.com`
    Then STDOUT should contain:
      """
      Success:
      """
    And the return code should be 0
    And I run `wp site create --slug=foo`

    When I run `wp db tables '*_posts'`
    Then STDOUT should be:
      """
      as_wp_posts
      """

    When I run `wp db tables '*_posts' --url=example.com/foo`
    Then STDOUT should be:
      """
      as_wp_2_posts
      """

    When I run `wp db tables '*_posts' --network`
    Then STDOUT should be:
      """
      as_wp_2_posts
      as_wp_posts
      """

    When I run `wp db tables '*_posts' --scope=blog`
    Then STDOUT should be:
      """
      as_wp_posts
      """

    When I run `wp db tables '*_posts' --scope=blog --network`
    Then STDOUT should be:
      """
      as_wp_2_posts
      as_wp_posts
      """

    When I try `wp db tables '*_posts' --scope=global`
    Then STDERR should not be empty
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp db tables '*_posts' --scope=global --network`
    Then STDERR should not be empty
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp db tables '*_users' --scope=global`
    Then STDOUT should be:
      """
      as_wp_users
      """

    When I run `wp db tables '*_users' --scope=global --network`
    Then STDOUT should be:
      """
      as_wp_users
      """
