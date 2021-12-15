Feature: Manage WordPress posts

  Background:
    Given a WP install

  Scenario: Creating/updating/deleting posts
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post create --post_title='Test post' --post_type="test" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CUSTOM_POST_ID}

    When I run `wp post exists {CUSTOM_POST_ID}`
    Then STDOUT should be:
      """
      Success: Post with ID {CUSTOM_POST_ID} exists.
      """
    And the return code should be 0

    When I try `wp post exists 1000`
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp post update {POST_ID} --post_title='Updated post'`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post delete {POST_ID}`
    Then STDOUT should be:
      """
      Success: Trashed post {POST_ID}.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Deleted post {POST_ID}.
      """

    When I try `wp post delete {CUSTOM_POST_ID}`
    Then STDERR should be:
      """
      Warning: Posts of type 'test' do not support being sent to trash.
      Please use the --force flag to skip trash and delete them permanently.
      """

    When I run `wp post delete {CUSTOM_POST_ID} --force`
    Then STDOUT should be:
      """
      Success: Deleted post {CUSTOM_POST_ID}.
      """

    When I try the previous command again
    Then the return code should be 1

  Scenario: Setting post categories
    When I run `wp term create category "First Category" --porcelain`
    And save STDOUT as {TERM_ID}
    And I run `wp term create category "Second Category" --porcelain`
    And save STDOUT as {SECOND_TERM_ID}

    When I run `wp post create --post_title="Test category" --post_category="First Category" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      {TERM_ID}
      """

    When I run `wp post update {POST_ID} --post_category={SECOND_TERM_ID}`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      {SECOND_TERM_ID}
      """

    When I run `wp post update {POST_ID} --post_category='Uncategorized,{TERM_ID},Second Category'`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    And save STDOUT as {MULTI_CATEGORIES_STDOUT}
    Then STDOUT should contain:
      """
      {TERM_ID}
      """
    And STDOUT should contain:
      """
      {SECOND_TERM_ID}
      """
    And STDOUT should contain:
      """
      1
      """

    # Blank categories with non-blank ignored.
    When I run `wp post update {POST_ID} --post_category='Uncategorized, ,{TERM_ID},Second Category,'`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      {MULTI_CATEGORIES_STDOUT}
      """

    # Zero category same as default Uncategorized (1) category.
    When I try `wp post update {POST_ID} --post_category=0`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      1
      """

    # Blank category/categories same as default Uncategorized (1) category.
    When I try `wp post update {POST_ID} --post_category=,`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be:
      """
      1
      """

    # Null category same as no categories.
    When I try `wp post update {POST_ID} --post_category=' '`
    Then STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post term list {POST_ID} category --field=term_id`
    Then STDOUT should be empty

    # Non-existent category.
    When I try `wp post update {POST_ID} --post_category=test`
    Then STDERR should be:
      """
      Error: No such post category 'test'.
      """

    When I try `wp post create --post_title="Non-existent Category" --post_category={SECOND_TERM_ID},Test --porcelain`
    Then STDERR should be:
      """
      Error: No such post category 'Test'.
      """

    # Error on first non-existent category found.
    When I try `wp post create --post_title="More than one non-existent Category" --post_category={SECOND_TERM_ID},Test,Bad --porcelain`
    Then STDERR should be:
      """
      Error: No such post category 'Test'.
      """

  Scenario: Creating/getting/editing posts
    Given a content.html file:
      """
      This is some content.

      <script>
      alert('This should not be stripped.');
      </script>
      """
    And a create-post.sh file:
      """
      cat content.html | wp post create --post_title='Test post' --post_excerpt="A multiline
      excerpt" --porcelain -
      """

    When I run `bash create-post.sh`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post get --field=excerpt {POST_ID}`
    Then STDOUT should be:
      """
      A multiline
      excerpt
      """

    When I run `wp post get --field=content {POST_ID} | diff -Bu content.html -`
    Then STDOUT should be empty

    When I run `wp post get --format=table {POST_ID}`
    Then STDOUT should be a table containing rows:
      | Field      | Value     |
      | ID         | {POST_ID} |
      | post_title | Test post |
      | post_name  |           |
      | post_type  | post      |

    When I run `wp post get {POST_ID} --format=csv --fields=post_title,type | wc -l | tr -d ' '`
    Then STDOUT should be:
      """
      3
      """

    When I run `wp post get --format=json {POST_ID}`
    Then STDOUT should be JSON containing:
      """
      {
        "ID": {POST_ID},
        "post_title": "Test post"
      }
      """

    When I try `EDITOR='ex -i NONE -c q!' wp post edit {POST_ID}`
    Then STDERR should contain:
      """
      No change made to post content.
      """
    And the return code should be 0

    When I run `EDITOR='ex -i NONE -c %s/content/bunkum -c wq' wp post edit {POST_ID}`
    Then STDERR should be empty
    Then STDOUT should contain:
      """
      Updated post {POST_ID}.
      """

    When I run `wp post get --field=content {POST_ID}`
    Then STDOUT should contain:
      """
      This is some bunkum.
      """

    When I run `wp post url 1 {POST_ID}`
    Then STDOUT should be:
      """
      http://example.com/?p=1
      http://example.com/?p={POST_ID}
      """

  Scenario: Update a post from file or STDIN
    Given a content.html file:
      """
      Oh glorious CLI
      """
    And a content-2.html file:
      """
      Let it be the weekend
      """

    When I run `wp post create --post_title="Testing update via STDIN" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `cat content.html | wp post update {POST_ID} -`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID}
      """

    When I run `wp post get --field=post_content {POST_ID}`
    Then STDOUT should be:
      """
      Oh glorious CLI
      """

    When I run `wp post create --post_title="Testing update via STDIN. Again!" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID_TWO}

    When I run `wp post update {POST_ID} {POST_ID_TWO} content-2.html`
    Then STDOUT should contain:
      """
      Success: Updated post {POST_ID_TWO}
      """

    When I run `wp post get --field=post_content {POST_ID_TWO}`
    Then STDOUT should be:
      """
      Let it be the weekend
      """

    When I try `wp post update {POST_ID} invalid-file.html`
    Then STDERR should be:
      """
      Error: Unable to read content from 'invalid-file.html'.
      """
    And the return code should be 1

  Scenario: Creating/listing posts
    When I run `wp post create --post_title='Publish post' --post_content='Publish post content' --post_status='publish' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post create --post_title='Draft post' --post_content='Draft post content' --post_status='draft' --porcelain`
    Then STDOUT should be a number

    When I run `wp post list --post_type='post' --fields=post_title,post_name,post_status --format=csv`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Publish post | publish-post | publish      |
      | Draft post   |              | draft        |

    When I run `wp post list --post_type='post' --fields=title,name,status --format=csv`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Publish post | publish-post | publish      |
      | Draft post   |              | draft        |

    When I run `wp post list --post_type='post' --fields="title, name, status" --format=csv`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Publish post | publish-post | publish      |
      | Draft post   |              | draft        |

    When I run `wp post list --post__in={POST_ID} --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_type='page' --field=title`
    Then STDOUT should contain:
      """
      Sample Page
      """

    When I run `wp post list --post_type=any --fields=post_title,post_name,post_status --format=csv --orderby=post_title --order=ASC`
    Then STDOUT should be CSV containing:
      | post_title   | post_name    | post_status  |
      | Draft post   |              | draft        |
      | Hello world! | hello-world  | publish      |
      | Publish post | publish-post | publish      |
      | Sample Page  | sample-page  | publish      |

  Scenario: Update categories on a post
    When I run `wp term create category "Test Category" --porcelain`
    Then save STDOUT as {TERM_ID}

    When I run `wp post update 1 --post_category={TERM_ID}`
    And I run `wp post term list 1 category --format=json --fields=name`
    Then STDOUT should be:
      """
      [{"name":"Test Category"}]
      """

  Scenario: Make sure WordPress receives the slashed data it expects
    When I run `wp post create --post_title='My\Post' --porcelain`
    Then save STDOUT as {POST_ID}

    When I run `wp post get {POST_ID} --field=title`
    Then STDOUT should be:
      """
      My\Post
      """

    When I run `wp post update {POST_ID} --post_content='var isEmailValid = /^\S+@\S+.\S+$/.test(email);'`
    Then STDOUT should not be empty

    When I run `wp post get {POST_ID} --field=content`
    Then STDOUT should be:
      """
      var isEmailValid = /^\S+@\S+.\S+$/.test(email);
      """

  @require-wp-4.4
  Scenario: Creating/updating posts with meta keys
    When I run `wp post create --post_title='Test Post' --post_content='Test post content' --meta_input='{"key1":"value1","key2":"value2"}' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp post meta list {POST_ID} --format=table`
    Then STDOUT should be a table containing rows:
      | post_id   | meta_key | meta_value |
      | {POST_ID} | key1     | value1     |
      | {POST_ID} | key2     | value2     |

    When I run `wp post update {POST_ID} --meta_input='{"key2":"value2b","key3":"value3"}'`
    And I run `wp post meta list {POST_ID} --format=table`
    Then STDOUT should be a table containing rows:
      | post_id   | meta_key | meta_value |
      | {POST_ID} | key1     | value1     |
      | {POST_ID} | key2     | value2b    |
      | {POST_ID} | key3     | value3     |

  @less-than-wp-4.4
  Scenario: Creating/updating posts with meta keys for WP < 4.4 has no effect so should give warning
    When I try `wp post create --post_title='Test Post' --post_content='Test post content' --meta_input='{"key1":"value1","key2":"value2"}' --porcelain`
    Then the return code should be 0
    And STDOUT should be a number
    And save STDOUT as {POST_ID}
    And STDERR should be:
      """
      Warning: The 'meta_input' field was only introduced in WordPress 4.4 so will have no effect.
      """

    When I run `wp post meta list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I try `wp post update {POST_ID} --meta_input='{"key2":"value2b","key3":"value3"}'`
    Then the return code should be 0
    And STDERR should be:
      """
      Warning: The 'meta_input' field was only introduced in WordPress 4.4 so will have no effect.
      """
    And STDOUT should be:
      """
      Success: Updated post {POST_ID}.
      """

    When I run `wp post meta list {POST_ID} --format=count`
    Then STDOUT should be:
      """
      0
      """
