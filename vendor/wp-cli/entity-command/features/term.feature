Feature: Manage WordPress terms

  Background:
    Given a WP install

  Scenario: Creating/listing a term
    When I run `wp term create post_tag 'Test term' --slug=test --description='This is a test term' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I try the previous command again
    Then STDERR should not be empty
    And the return code should be 1

    When I run `wp term list post_tag --format=json`
    Then STDOUT should be JSON containing:
      """
      [{
        "name": "Test term",
        "slug":"test",
        "description":"This is a test term",
        "parent":0,
        "count":0
      }]
      """

    When I run `wp term list post_tag --fields=name,slug --format=csv`
    Then STDOUT should be CSV containing:
      | name      | slug |
      | Test term | test |

    When I run `wp term create category 'Test category' --slug=test-category --description='This is a test category'`
    Then STDOUT should not be empty

    When I run `wp term list post_tag category --fields=name,slug --format=csv`
    Then STDOUT should be CSV containing:
      | name           | slug           |
      | Test term      | test           |
      | Test category  | test-category  |

    When I run `wp term get post_tag {TERM_ID}`
    Then STDOUT should be a table containing rows:
      | Field     | Value      |
      | term_id   | {TERM_ID}  |
      | name      | Test term  |

    When I run `wp term get post_tag {TERM_ID} --format=csv --fields=name,taxonomy`
    Then STDOUT should be CSV containing:
      | Field     | Value      |
      | name      | Test term  |
      | taxonomy  | post_tag   |

    When I try `wp term list nonexistent_taxonomy`
    Then STDERR should be:
      """
      Error: Taxonomy nonexistent_taxonomy doesn't exist.
      """
    And the return code should be 1

  Scenario: Creating/deleting a term
    When I run `wp term create post_tag 'Test delete term' --slug=test-delete --description='This is a test term to be deleted' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `wp term create post_tag 'Test delete term 2' --slug=test-two --description='This is a test term to be deleted' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID_TWO}

    When I run `wp term get post_tag {TERM_ID} --field=slug --format=json`
    Then STDOUT should be:
      """
      "test-delete"
      """

    When I run `wp term delete post_tag {TERM_ID}`
    Then STDOUT should be:
      """
      Deleted post_tag {TERM_ID}.
      Success: Deleted 1 of 1 terms.
      """
    And the return code should be 0

    When I try the previous command again
    Then STDOUT should be:
      """
      Success: Term already deleted.
      """
    And STDERR should be:
      """
      Warning: post_tag {TERM_ID} doesn't exist.
      """
    And the return code should be 0

    When I try `wp term delete post_tag {TERM_ID} {TERM_ID_TWO}`
    Then STDOUT should be:
      """
      Deleted post_tag {TERM_ID_TWO}.
      Success: Deleted 1 of 2 terms.
      """
    And STDERR should be:
      """
      Warning: post_tag {TERM_ID} doesn't exist.
      """
    And the return code should be 0

  Scenario: Term with a non-existent parent
    When I try `wp term create category Apple --parent=99 --porcelain`
    Then STDERR should be:
      """
      Error: Parent term does not exist.
      """
    And the return code should be 1

  Scenario: Filter terms by term_id
    When I run `wp term generate category --count=10`
    And I run `wp term create category "My Test Category" --porcelain`
    And save STDOUT as {TERM_ID}

    When I run `wp term list category --term_id={TERM_ID} --field=name`
    Then STDOUT should be:
      """
      My Test Category
      """

  Scenario: Fetch term url
    When I run `wp term create category "First Category" --porcelain`
    And save STDOUT as {TERM_ID}
    And I run `wp term create category "Second Category" --porcelain`
    And save STDOUT as {SECOND_TERM_ID}

    When I run `wp term url category {TERM_ID}`
    Then STDOUT should be:
      """
      http://example.com/?cat=2
      """

    When I run `wp term url category {TERM_ID} {SECOND_TERM_ID}`
    Then STDOUT should be:
      """
      http://example.com/?cat=2
      http://example.com/?cat=3
      """

    When I run `wp term url category {SECOND_TERM_ID} {TERM_ID}`
    Then STDOUT should be:
      """
      http://example.com/?cat=3
      http://example.com/?cat=2
      """

  Scenario: Make sure WordPress receives the slashed data it expects
    When I run `wp term create category 'My\Term' --description='My\Term\Description' --porcelain`
    Then save STDOUT as {TERM_ID}

    When I run `wp term get category {TERM_ID} --field=name`
    Then STDOUT should be:
      """
      My\Term
      """

    When I run `wp term get category {TERM_ID} --field=description`
    Then STDOUT should be:
      """
      My\Term\Description
      """

    When I run `wp term update category {TERM_ID} --name='My\New\Term' --description='var isEmailValid = /^\S+@\S+.\S+$/.test(email);'`
    Then STDOUT should not be empty

    When I run `wp term get category {TERM_ID} --field=name`
    Then STDOUT should be:
      """
      My\New\Term
      """

    When I run `wp term get category {TERM_ID} --field=description`
    Then STDOUT should be:
      """
      var isEmailValid = /^\S+@\S+.\S+$/.test(email);
      """

  Scenario: Deleting a term by slug or ID
    When I run `wp term create category Apple --description="A type of fruit"`
    Then STDOUT should be:
      """
      Success: Created category 2.
      """

    When I run `wp term create category Orange --description="A type of fruit"`
    Then STDOUT should be:
      """
      Success: Created category 3.
      """

    When I run `wp term create category Mango --description="A type of fruit"`
    Then STDOUT should be:
      """
      Success: Created category 4.
      """

    When I run `wp term get category 2 --field=slug --format=json`
    Then STDOUT should be:
      """
      "apple"
      """

    When I run `wp term delete category apple --by=slug`
    Then STDOUT should be:
      """
      Deleted category 2.
      Success: Deleted 1 of 1 terms.
      """

    When I run `wp term delete category 3 --by=id`
    Then STDOUT should be:
      """
      Deleted category 3.
      Success: Deleted 1 of 1 terms.
      """

    When I run `wp term delete category 4`
    Then STDOUT should be:
      """
      Deleted category 4.
      Success: Deleted 1 of 1 terms.
      """

  @require-wp-4.7
  Scenario: Fetch term by slug or ID
    When I run `wp term create category Apple --description="A type of fruit"`
    Then STDOUT should be:
      """
      Success: Created category 2.
      """

    When I run `wp term get category 2 --by=id --format=json --fields=term_id,name,slug,count`
    Then STDOUT should be:
      """
      {"term_id":2,"name":"Apple","slug":"apple","count":0}
      """

    When I run `wp term get category apple --by=slug --format=json --fields=term_id,name,slug,count`
    Then STDOUT should be:
      """
      {"term_id":2,"name":"Apple","slug":"apple","count":0}
      """

  Scenario: Update term by slug or ID
    When I run `wp term create category Apple --description="A type of fruit"`
    Then STDOUT should be:
      """
      Success: Created category 2.
      """

    When I run `wp term update category apple --by=slug --name=PineApple`
    Then STDOUT should be:
      """
      Success: Term updated.
      """

    When I run `wp term update category 2 --by=id --description="This is testing description"`
    Then STDOUT should be:
      """
      Success: Term updated.
      """
