Feature: Manage term custom fields

  @require-wp-4.4
  Scenario: Migrate an existing term by slug
    Given a WP install

    When I run `wp term create category apple`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category apple`
    Then STDOUT should not be empty

    When I run `wp term migrate apple --by=slug --from=category --to=post_tag`
    Then STDOUT should be:
      """
      Term 'apple' assigned to post 4.
      Term 'apple' migrated.
      Old instance of term 'apple' removed from its original taxonomy.
      Success: Migrated the term 'apple' from taxonomy 'category' to taxonomy 'post_tag' for 1 post.
      """

  @require-wp-4.4
  Scenario: Migrate an existing term by ID
    Given a WP install

    When I run `wp term create category apple --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category {TERM_ID}`
    Then STDOUT should not be empty

    When I run `wp term migrate {TERM_ID} --by=slug --from=category --to=post_tag`
    Then STDOUT should be:
      """
      Term '{TERM_ID}' assigned to post 4.
      Term '{TERM_ID}' migrated.
      Old instance of term '{TERM_ID}' removed from its original taxonomy.
      Success: Migrated the term '{TERM_ID}' from taxonomy 'category' to taxonomy 'post_tag' for 1 post.
      """
  
  @require-wp-4.4
  Scenario: Migrate a term in multiple posts
    Given a WP install

    When I run `wp term create category orange`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category orange`
    Then STDOUT should not be empty

    When I run `wp post create --post_title='Test post 2' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term set {POST_ID} category orange`
    Then STDOUT should not be empty

    When I run `wp term migrate orange --by=slug --from=category --to=post_tag`
    Then STDOUT should be:
      """
      Term 'orange' assigned to post 4.
      Term 'orange' assigned to post 5.
      Term 'orange' migrated.
      Old instance of term 'orange' removed from its original taxonomy.
      Success: Migrated the term 'orange' from taxonomy 'category' to taxonomy 'post_tag' for 2 posts.
      """

  @require-wp-4.4
  Scenario: Try to migrate a term that does not exist
    Given a WP install

    When I try `wp term migrate peach --by=slug --from=category --to=post_tag`
    Then STDERR should be:
      """
      Error: Taxonomy term 'peach' for taxonomy 'category' doesn't exist.
      """
