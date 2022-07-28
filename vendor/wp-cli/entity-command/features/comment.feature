Feature: Manage WordPress comments

  Background:
    Given a WP install

  Scenario: Creating/updating/deleting comments
    When I run `wp comment create --comment_post_ID=1 --comment_content='Hello' --comment_author='Billy' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {COMMENT_ID}

    When I run `wp comment exists {COMMENT_ID}`
    Then STDOUT should be:
      """
	  Success: Comment with ID {COMMENT_ID} exists.
      """

    When I run `wp comment update {COMMENT_ID} --comment_author='Johnny'`
    Then STDOUT should not be empty

    When I run `wp comment get {COMMENT_ID} --field=author`
    Then STDOUT should be:
      """
      Johnny
      """

    When I run `wp comment delete {COMMENT_ID}`
    Then STDOUT should be:
      """
	  Success: Trashed comment {COMMENT_ID}.
      """

    When I run `wp comment get {COMMENT_ID} --field=comment_approved`
    Then STDOUT should be:
      """
      trash
      """

    When I run `wp comment delete {COMMENT_ID} --force`
    Then STDOUT should be:
      """
      Success: Deleted comment {COMMENT_ID}.
      """

    When I try `wp comment get {COMMENT_ID}`
    Then STDERR should be:
      """
      Error: Invalid comment ID.
      """
    And the return code should be 1

    When I run `wp comment create --comment_post_ID=1`
    And I run `wp comment create --comment_post_ID=1`
    And I run `wp comment delete 3 4`
    Then STDOUT should be:
      """
      Success: Trashed comment 3.
      Success: Trashed comment 4.
      """

    When I run `wp comment delete 3`
    Then STDOUT should be:
      """
      Success: Deleted comment 3.
      """

    When I try `wp comment get 3`
    Then STDERR should be:
      """
      Error: Invalid comment ID.
      """
    And the return code should be 1

  Scenario: Get details about an existing comment
    When I run `wp comment get 1`
    Then STDOUT should be a table containing rows:
      | Field               | Value     |
      | comment_approved    | 1         |

    When I run `wp comment get 1 --fields=comment_approved --format=json`
    Then STDOUT should be JSON containing:
      """
      {"comment_approved":"1"}
      """

    When I run `wp comment list --fields=comment_approved`
    Then STDOUT should be a table containing rows:
      | comment_approved |
      | 1                |

    When I run `wp comment list --field=approved`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp comment list --format=ids`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp comment url 1`
    Then STDOUT should contain:
      """
      #comment-1
      """

  Scenario: List the URLs of comments
    When I run `wp comment create --comment_post_ID=1 --porcelain`
    Then save STDOUT as {COMMENT_ID}

    When I run `wp comment url 1 {COMMENT_ID}`
    Then STDOUT should be:
      """
      https://example.com/?p=1#comment-1
      https://example.com/?p=1#comment-{COMMENT_ID}
      """

    When I run `wp comment url {COMMENT_ID} 1`
    Then STDOUT should be:
      """
      https://example.com/?p=1#comment-{COMMENT_ID}
      https://example.com/?p=1#comment-1
      """

  Scenario: Count comments
    When I run `wp comment count 1`
    Then STDOUT should contain:
      """
      approved:        1
      """
    And STDOUT should contain:
      """
      moderated:       0
      """
    And STDOUT should contain:
      """
      total_comments:  1
      """

    When I run `wp comment count`
    Then STDOUT should contain:
      """
      approved:        1
      """
    And STDOUT should contain:
      """
      moderated:       0
      """
    And STDOUT should contain:
      """
      total_comments:  1
      """

  Scenario: Approving/unapproving comments
    Given I run `wp comment create --comment_post_ID=1 --comment_approved=0 --porcelain`
    And save STDOUT as {COMMENT_ID}

    # With site url set.
    When I run `wp comment approve {COMMENT_ID} --url=www.example.com`
    Then STDOUT should be:
      """
      Success: Approved comment {COMMENT_ID}.
      """

    When I try the previous command again
    Then STDERR should contain:
      """
      Error: Could not update comment status
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp comment get --field=comment_approved {COMMENT_ID}`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp comment unapprove {COMMENT_ID} --url=www.example.com`
    Then STDOUT should be:
      """
      Success: Unapproved comment {COMMENT_ID}.
      """

    When I run `wp comment get --field=comment_approved {COMMENT_ID}`
    Then STDOUT should be:
      """
      0
      """

    # Without site url set.
    When I try `wp comment approve {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Approved comment {COMMENT_ID}.
      """
    And STDERR should be:
      """
      Warning: Site url not set - defaulting to 'example.com'. Any notification emails sent to post author may appear to come from 'example.com'.
      """
    And the return code should be 0

    When I try `wp comment unapprove {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Unapproved comment {COMMENT_ID}.
      """
    And STDERR should be:
      """
      Warning: Site url not set - defaulting to 'example.com'. Any notification emails sent to post author may appear to come from 'example.com'.
      """
    And the return code should be 0

  Scenario: Approving/unapproving comments with multidigit comment ID
    Given I run `wp comment delete $(wp comment list --field=ID)`
    And I run `wp comment generate --count=10 --quiet`
    And I run `wp comment create --comment_post_ID=1 --porcelain`
    And save STDOUT as {COMMENT_ID}

    # With site url set.
    When I run `wp comment unapprove {COMMENT_ID} --url=www.example.com`
    Then STDOUT should be:
      """
      Success: Unapproved comment {COMMENT_ID}.
      """

    When I run `wp comment list --format=count --status=approve`
    Then STDOUT should be:
      """
      10
      """

    When I run `wp comment approve {COMMENT_ID} --url=www.example.com`
    Then STDOUT should be:
      """
      Success: Approved comment {COMMENT_ID}.
      """

    When I run `wp comment list --format=count --status=approve`
    Then STDOUT should be:
      """
      11
      """

    # Without site url set.
    When I try `wp comment unapprove {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Unapproved comment {COMMENT_ID}.
      """
    And STDERR should be:
      """
      Warning: Site url not set - defaulting to 'example.com'. Any notification emails sent to post author may appear to come from 'example.com'.
      """
    And the return code should be 0

    When I try `wp comment approve {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Approved comment {COMMENT_ID}.
      """
    And STDERR should be:
      """
      Warning: Site url not set - defaulting to 'example.com'. Any notification emails sent to post author may appear to come from 'example.com'.
      """
    And the return code should be 0

  Scenario: Spam/unspam comments with multidigit comment ID
    Given I run `wp comment delete $(wp comment list --field=ID)`
    And I run `wp comment generate --count=10 --quiet`
    And I run `wp comment create --comment_post_ID=1 --porcelain`
    And save STDOUT as {COMMENT_ID}

    # With site url set.
    When I run `wp comment spam {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Marked comment {COMMENT_ID} as spam.
      """

    When I run `wp comment list --format=count --status=spam`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp comment unspam {COMMENT_ID} --url=www.example.com`
    Then STDOUT should be:
      """
      Success: Unspammed comment {COMMENT_ID}.
      """

    When I run `wp comment list --format=count --status=spam`
    Then STDOUT should be:
      """
      0
      """

    # Without site url set.
    When I run `wp comment spam {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Marked comment {COMMENT_ID} as spam.
      """

    When I try `wp comment unspam {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Unspammed comment {COMMENT_ID}.
      """
    And STDERR should be:
      """
      Warning: Site url not set - defaulting to 'example.com'. Any notification emails sent to post author may appear to come from 'example.com'.
      """
    And the return code should be 0

  Scenario: Trash/untrash comments with multidigit comment ID
    Given I run `wp comment delete $(wp comment list --field=ID) --force`
    And I run `wp comment generate --count=10 --quiet`
    And I run `wp comment create --comment_post_ID=1 --porcelain`
    And save STDOUT as {COMMENT_ID}

    # With site url set.
    When I run `wp comment trash {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Trashed comment {COMMENT_ID}.
      """

    When I run `wp comment list --format=count --status=trash`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp comment untrash {COMMENT_ID} --url=www.example.com`
    Then STDOUT should be:
      """
      Success: Untrashed comment {COMMENT_ID}.
      """

    When I run `wp comment list --format=count --status=trash`
    Then STDOUT should be:
      """
      0
      """

    # Without site url set.
    When I run `wp comment trash {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Trashed comment {COMMENT_ID}.
      """

    When I try `wp comment untrash {COMMENT_ID}`
    Then STDOUT should be:
      """
      Success: Untrashed comment {COMMENT_ID}.
      """
    And STDERR should be:
      """
      Warning: Site url not set - defaulting to 'example.com'. Any notification emails sent to post author may appear to come from 'example.com'.
      """
    And the return code should be 0

  Scenario: Make sure WordPress receives the slashed data it expects
    When I run `wp comment create --comment_content='My\Comment' --porcelain`
    Then save STDOUT as {COMMENT_ID}

    When I run `wp comment get {COMMENT_ID} --field=comment_content`
    Then STDOUT should be:
      """
      My\Comment
      """

    When I run `wp comment update {COMMENT_ID} --comment_content='My\New\Comment'`
    Then STDOUT should not be empty

    When I run `wp comment get {COMMENT_ID} --field=comment_content`
    Then STDOUT should be:
      """
      My\New\Comment
      """

  @require-wp-4.4
  Scenario: Approving/unapproving/unspamming/untrashing (approved) comments with no comment post id should not produce PHP notices for WP >= 4.4
    Given I run `wp comment create --comment_approved=0 --porcelain`
    And save STDOUT as {COMMENT_ID}
    When I run `wp comment approve {COMMENT_ID} --url=www.example.com`
    And I run `wp comment unapprove {COMMENT_ID} --url=www.example.com`

    Given I run `wp comment approve {COMMENT_ID} --url=www.example.com`
    When I run `wp comment spam {COMMENT_ID} --url=www.example.com`
    And I run `wp comment unspam {COMMENT_ID} --url=www.example.com`
    And I run `wp comment trash {COMMENT_ID} --url=www.example.com`
    And I run `wp comment untrash {COMMENT_ID} --url=www.example.com`
