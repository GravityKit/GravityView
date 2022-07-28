Feature: Create Duplicate WordPress post from existing posts.

  Background:
	Given a WP install

  Scenario: Generate duplicate post.
	When I run `wp term create category "Test Category" --porcelain`
	Then save STDOUT as {TERM_ID}

	When I run `wp term create post_tag "Test Tag" --porcelain`
	Then save STDOUT as {TAG_ID}

	When I run `wp post create --post_title='Test Duplicate Post' --post_category={TERM_ID} --porcelain`
	And save STDOUT as {POST_ID}

	When I run `wp post term add {POST_ID} post_tag {TAG_ID} --by=id`
	Then STDOUT should contain:
      """
      Success: Added term.
      """

	When I run `wp post create --from-post={POST_ID} --porcelain`
	Then STDOUT should be a number
	And save STDOUT as {DUPLICATE_POST_ID}

	When I run `wp post get {DUPLICATE_POST_ID} --field=title`
	Then STDOUT should be:
	  """
	  Test Duplicate Post
	  """

	When I run `wp post term list {DUPLICATE_POST_ID} category --field=term_id`
	Then STDOUT should be:
      """
      {TERM_ID}
      """

	When I run `wp post term list {DUPLICATE_POST_ID} post_tag --field=term_id`
	Then STDOUT should be:
      """
      {TAG_ID}
      """

  @require-wp-4.4
  Scenario: Generate duplicate post with post metadata.
	When I run `wp post create --post_title='Test Post' --meta_input='{"key1":"value1","key2":"value2"}' --porcelain`
	Then save STDOUT as {POST_ID}

	When I run `wp post create --from-post={POST_ID} --porcelain`
	Then save STDOUT as {DUPLICATE_POST_ID}

	When I run `wp post meta list {DUPLICATE_POST_ID} --format=table`
	Then STDOUT should be a table containing rows:
	  | post_id             | meta_key | meta_value |
	  | {DUPLICATE_POST_ID} | key1     | value1     |
	  | {DUPLICATE_POST_ID} | key2     | value2     |


  Scenario: Generate duplicate page.
	When I run `wp post create --post_type="page" --post_title="Test Page" --post_content="Page Content" --porcelain`
	Then save STDOUT as {POST_ID}

	When I run `wp post create --from-post={POST_ID} --post_title="Duplicate Page" --porcelain`
	Then save STDOUT as {DUPLICATE_POST_ID}

	When I run `wp post list --post_type='page' --fields="title, content, type"`
	Then STDOUT should be a table containing rows:
	  | post_title     | post_content | post_type |
	  | Test Page      | Page Content | page      |
	  | Duplicate Page | Page Content | page      |

  Scenario: Change type of duplicate post.
	When I run `wp post create --post_title='Test Post' --porcelain`
	Then save STDOUT as {POST_ID}

	When I run `wp post create --from-post={POST_ID} --post_type=page --porcelain`
	Then save STDOUT as {DUPLICATE_POST_ID}

	When I run `wp post get {DUPLICATE_POST_ID} --fields=type`
	Then STDOUT should be a table containing rows:
	  | Field     | Value |
	  | post_type | page  |
