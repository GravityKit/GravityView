Feature: Manage oEmbed cache.

  Background:
    Given a WP install

  Scenario: Clear oEmbed cache for an empty post
    When I run `wp post create --post_title="Foo Bar" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I try `wp embed cache clear {POST_ID}`
    Then STDERR should be:
      """
      Error: No cache to clear!
      """

  Scenario: Clear oEmbed cache for a post
    When I run `wp post-meta add 1 _oembed_foo 'bar'`
    Then STDOUT should not be empty

    When I run `wp post-meta get 1 _oembed_foo`
    Then STDOUT should be:
      """
      bar
      """

    When I run `wp embed cache clear 1`
    Then STDOUT should be:
      """
      Success: Cleared oEmbed cache.
      """

  Scenario: Trigger and clear oEmbed cache for a post
    When I run `wp post create --post_title=Foo --post_type=post --post_status=publish --post_content="[embed]https://www.youtube.com/watch?v=dQw4w9WgXcQ[/embed]" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed cache trigger {POST_ID}`
    Then STDOUT should be:
      """
      Success: Caching triggered!
      """

    When I run `wp embed cache clear {POST_ID}`
    Then STDOUT should be:
      """
      Success: Cleared oEmbed cache.
      """

  Scenario: Trigger oEmbed cache for a non-existent post
    When I try `wp embed cache trigger 123456`
    Then STDERR should contain:
      """
      Post id '123456' not found.
      """
    And the return code should be 0

  Scenario: Trigger oEmbed cache for a hidden post
    When I run `wp post create --post_title="Foo Bar" --post_type=revision --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I try `wp embed cache trigger {POST_ID}`
    Then STDERR should contain:
      """
      Cannot cache oEmbed results for 'revision' post type
      """
    And the return code should be 0

  @require-wp-4.9
  Scenario: Find oEmbed cache post ID for a non-existent key
    When I try `wp embed cache find foo`
    Then STDERR should be:
      """
      Error: No cache post ID found!
      """
    And the return code should be 1

  @require-wp-4.9
  Scenario: Find oEmbed cache post ID for an existing key
    # Add a non-post embed, default attributes.
    When I run `wp eval 'echo $GLOBALS["wp_embed"]->run_shortcode( "[embed]https://www.youtube.com/watch?v=dQw4w9WgXcQ[/embed]" );'`
    Then STDOUT should contain:
      """
      dQw4w9WgXcQ
      """

    When I run `wp embed cache find 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'`
    Then STDOUT should be a number

    # Add a non-post embed with width attribute.
    When I run `wp eval 'echo $GLOBALS["wp_embed"]->run_shortcode( "[embed width=400]https://www.youtube.com/watch?v=yPYZpwSpKmA[/embed]" );'`
    Then STDOUT should contain:
      """
      yPYZpwSpKmA
      """

    # Fail if width not given.
    When I try `wp embed cache find 'https://www.youtube.com/watch?v=yPYZpwSpKmA'`
    Then STDERR should be:
      """
      Error: No cache post ID found!
      """
    And the return code should be 1

    # Succeed if correct width given.
    When I run `wp embed cache find 'https://www.youtube.com/watch?v=yPYZpwSpKmA' --width=400`
    Then STDOUT should be a number

    # Fail if incorrect width given.
    When I try `wp embed cache find 'https://www.youtube.com/watch?v=yPYZpwSpKmA' --width=500`
    Then STDERR should be:
      """
      Error: No cache post ID found!
      """
    And the return code should be 1

    # Add a non-post embed with discover=1 attribute.
    When I run `wp eval 'echo $GLOBALS["wp_embed"]->run_shortcode( "[embed discover=1]https://www.youtube.com/watch?v=yBwD4iYcWC4[/embed]" );'`
    Then STDOUT should contain:
      """
      yBwD4iYcWC4
      """

    # Succeed if no options given.
    When I run `wp embed cache find 'https://www.youtube.com/watch?v=yBwD4iYcWC4'`
    Then STDOUT should be a number

    # Fail if incorrect discover given.
    When I try `wp embed cache find 'https://www.youtube.com/watch?v=yBwD4iYcWC4' --no-discover`
    Then STDERR should be:
      """
      Error: No cache post ID found!
      """
    And the return code should be 1

    # Succeed if correct discover given.
    When I run `wp embed cache find 'https://www.youtube.com/watch?v=yBwD4iYcWC4' --discover`
    Then STDOUT should be a number

    # Add a non-post embed with width and discover attributes.
    When I run `wp eval 'echo $GLOBALS["wp_embed"]->run_shortcode( "[embed width=450 discover=0]https://www.youtube.com/watch?v=eYuUAGXN0KM[/embed]" );'`
    Then STDOUT should contain:
      """
      eYuUAGXN0KM
      """

    # Fail if no options given.
    When I try `wp embed cache find 'https://www.youtube.com/watch?v=eYuUAGXN0KM'`
    Then STDERR should be:
      """
      Error: No cache post ID found!
      """
    And the return code should be 1

    # Succeed if correct width given.
    When I run `wp embed cache find 'https://www.youtube.com/watch?v=eYuUAGXN0KM' --width=450`
    Then STDOUT should be a number

    # Succeed if correct width and discover given.
    When I run `wp embed cache find 'https://www.youtube.com/watch?v=eYuUAGXN0KM' --width=450 --no-discover`
    Then STDOUT should be a number

    # Fail if correct width and incorrect discover given.
    When I try `wp embed cache find 'https://www.youtube.com/watch?v=eYuUAGXN0KM' --width=450 --discover`
    Then STDERR should be:
      """
      Error: No cache post ID found!
      """
    And the return code should be 1

    # Add using embed fetch. Temporarily disabled as requires embed fetch changes.
    #When I run `wp embed fetch https://example.org/embed?1234`
    #Then STDOUT should be:
      #"""
     #<a href="https://example.org/embed?1234">https://example.org/embed?1234</a>
      #"""

    #When I run `wp embed cache find https://example.org/embed?1234`
    #Then STDOUT should be a number

    # Dummy data with default width/height.
    When I run `wp eval 'echo md5( "foo" . serialize( wp_embed_defaults() ) );'`
    Then STDOUT should not be empty
    And save STDOUT as {CACHE_KEY}

    When I run `wp post create --post_title=Foo --post_name={CACHE_KEY} --post_type=oembed_cache --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed cache find foo`
    Then STDOUT should be:
      """
     {POST_ID}
      """

    # Dummy data with given width/height. Specify width/height as strings as that's what shortcode attributes will be passed as.
    When I run `wp eval 'echo md5( "foo" . serialize( array( "width" => "600", "height" => "400" ) ) );'`
    Then STDOUT should not be empty
    And save STDOUT as {CACHE_KEY}

    When I run `wp post create --post_title=Foo --post_name={CACHE_KEY} --post_type=oembed_cache --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed cache find foo --width=600 --height=400`
    Then STDOUT should be:
      """
     {POST_ID}
      """
