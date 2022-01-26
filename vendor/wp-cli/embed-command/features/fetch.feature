Feature: Manage oEmbed cache.

  Background:
    Given a WP install

  @require-wp-4.0
  Scenario: Get HTML embed code for a given URL
    # Known provider not requiring discovery.
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --width=500`
    Then STDOUT should contain:
      """
      https://www.youtube.com/
      """
    And STDOUT should contain:
      """
      dQw4w9WgXcQ
      """

    # Unknown provider (taken from https://oembed.com) requiring discovery but returning iframe so not sanitized.
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `wp embed fetch http://LearningApps.org/259`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      LearningApps.org/
     """
    And STDOUT should contain:
      """
      <iframe
     """

    # How unknown provider checked depends on WP version and post_id so recheck with post_id.
    When I run `wp post create --post_title="Foo Bar" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}
    When I try `wp embed fetch http://LearningApps.org/259 --post-id={POST_ID}`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      LearningApps.org/
     """
    And STDOUT should contain:
      """
      <iframe
     """

    # Unknown provider requiring discovery but not returning iframe so would be sanitized for WP >= 4.4 without 'skip-sanitization' option.
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `wp embed fetch https://asciinema.org/a/140798 --skip-sanitization`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      asciinema.org/
      """
    And STDOUT should contain:
      """
      <a
      """
    And STDOUT should not contain:
      """
      <iframe
     """

  # WP 4.9 always returns clickable link even for sanitized oEmbed responses.
  @require-wp-4.9
  Scenario: Get HTML embed code for a given URL that requires discovery and is sanitized
    When I run `wp embed fetch https://asciinema.org/a/140798`
    Then STDOUT should contain:
      """
      asciinema.org/
      """
    And STDOUT should contain:
      """
      <a
      """

  # `wp_filter_oembed_result` filter introduced WP 4.4 which sanitizes oEmbed responses that don't include an iframe.
  @less-than-wp-4.9 @require-wp-4.4
  Scenario: Get HTML embed code for a given URL that requires discovery and is sanitized
    When I try `wp embed fetch https://asciinema.org/a/140798`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: There was an error fetching the oEmbed data.
      """
    And STDOUT should be empty

  # No sanitization prior to WP 4.4.
  @less-than-wp-4.4 @require-wp-4.0
  Scenario: Get HTML embed code for a given URL that requires discovery and is sanitized
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `wp embed fetch https://asciinema.org/a/140798`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      asciinema.org/
      """

  @require-wp-4.0
  Scenario: Get raw oEmbed data for a given URL
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --raw`
    And save STDOUT as {DEFAULT_STDOUT}
    Then STDOUT should contain:
      """
      "type":"video"
      """

    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --raw --raw-format=json`
    And save STDOUT as {DEFAULT_STDOUT}
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    # Raw requests are not sanitized.
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `wp embed fetch https://asciinema.org/a/140798 --raw`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      asciinema.org
      """

  @require-wp-4.0
  Scenario: Fail then succeed when given unknown discoverable provider for a raw request, depending on discover option
    When I try `wp embed fetch http://LearningApps.org/259 --raw --no-discover`
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discovery so use "contain" to ignore these.
    Then STDERR should contain:
      """
      Error: No oEmbed provider found for given URL. Maybe try discovery?
      """

    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `wp embed fetch http://LearningApps.org/259 --raw`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      LearningApps.org
     """

  @require-wp-4.0
  Scenario: Bails when no oEmbed provider is found for a raw request
    When I try `wp embed fetch https://foo.example.com --raw`
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discovery so use "contain" to ignore these.
    Then STDERR should contain:
      """
      Error: No oEmbed provider found for given URL.
      """

  @require-wp-4.0
  Scenario: Bails when no oEmbed provider is found for a raw request and discovery is off
    When I try `wp embed fetch https://foo.example.com --raw --discover=0`
    Then STDERR should be:
      """
      Error: No oEmbed provider found for given URL. Maybe try discovery?
      """

  # WP 4.9 always returns clickable link.
  @require-wp-4.9
  Scenario: Makes unknown URLs clickable
    When I run `wp embed fetch https://foo.example.com`
    Then STDOUT should contain:
      """
      <a href="https://foo.example.com">https://foo.example.com</a>
      """

  # WP prior to 4.9 does not return clickable link.
  @less-than-wp-4.9 @require-wp-4.0
  Scenario: Doesn't make unknown URLs clickable
    When I try `wp embed fetch https://foo.example.com`
    Then the return code should be 1
    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "contain" to cater for these.
    And STDERR should contain:
      """
      Error: There was an error fetching the oEmbed data.
      """
    And STDOUT should be empty

  @require-wp-4.0
  Scenario: Caches oEmbed response data for a given post
    # Note need post author for 'unfiltered_html' check to work for WP < 4.4.
    When I run `wp post create --post_title="Foo Bar" --post_author=1 --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    # Old versions of WP_oEmbed can trigger PHP "Only variables should be passed by reference" notices on discover so use "try" to cater for these.
    When I try `wp embed fetch https://foo.example.com --post-id={POST_ID}`
    Then the return code should be 0
    And STDERR should not contain:
      """
      Error:
      """
    And STDOUT should contain:
      """
      <a href="https://foo.example.com">https://foo.example.com</a>
      """

    When I run `wp embed cache clear {POST_ID}`
    Then STDOUT should be:
      """
      Success: Cleared oEmbed cache.
      """

  @require-wp-4.0
  Scenario: Return data as XML when requested
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --raw-format=xml --raw`
    Then STDOUT should contain:
      """
      <type>video</type>
      """

  # Depends on `oembed_remote_get_args` filter introduced WP 4.0 https://core.trac.wordpress.org/ticket/23442
  @require-wp-4.0
  Scenario: Get embed code for a URL with limited response size
    # Need post_id for caching to work for WP < 4.9, and also post_author for caching to work for WP < 4.4 (due to 'unfiltered_html' check).
    When I run `wp post create --post_title="Foo Bar" --post_author=1 --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --post-id={POST_ID}`
    And save STDOUT as {DEFAULT_STDOUT}
    Then STDOUT should contain:
      """
      <iframe
      """

    # Response limit too small but cached so ignored.
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --post-id={POST_ID} --limit-response-size=10`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    # Response limit too small and skip cache (and as html failed the cache will not be updated)
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --post-id={POST_ID} --limit-response-size=10 --skip-cache`
    Then STDOUT should not contain:
      """
      {DEFAULT_STDOUT}
      """
    And STDOUT should be:
      """
      <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ">https://www.youtube.com/watch?v=dQw4w9WgXcQ</a>
      """

    # Response limit big enough and don't skip cache but as previous failed result not cached it doesn't matter
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --post-id={POST_ID} --limit-response-size=50000`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    # Response limit big enough and skip cache
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --post-id={POST_ID} --limit-response-size=50000 --skip-cache`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

  # Same as above but without the post_id. WP >= 4.9 only
  @require-wp-4.9
  Scenario: Get embed code for a URL with limited response size and post-less cache
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ`
    And save STDOUT as {DEFAULT_STDOUT}
    Then STDOUT should contain:
      """
      <iframe
      """

    # Response limit too small but cached so ignored.
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --limit-response-size=10`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    # Response limit too small and skip cache (and as html failed the cache will not be updated)
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --limit-response-size=10 --skip-cache`
    Then STDOUT should not contain:
      """
      {DEFAULT_STDOUT}
      """
    And STDOUT should be:
      """
      <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ">https://www.youtube.com/watch?v=dQw4w9WgXcQ</a>
      """

    # Response limit big enough and don't skip cache but as previous failed result not cached it doesn't matter
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --limit-response-size=50000`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

    # Response limit big enough and skip cache
    When I run `wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --limit-response-size=50000 --skip-cache`
    Then STDOUT should be:
      """
      {DEFAULT_STDOUT}
      """

  # Depends on `wp_filter_pre_oembed_result` filter introduced WP 4.5.3 https://core.trac.wordpress.org/ticket/36767
  @require-wp-4.5.3
  Scenario: Fetch locally provided URL
    When I run `wp embed fetch http://example.com/?p=1`
    Then STDOUT should contain:
      """
      Hello world!
      """

    When I run `wp embed fetch http://example.com/?p=1 --raw`
    Then STDOUT should contain:
      """
      Hello world!
      """

  # `wp_embed_handler_youtube` handler introduced WP 4.0.
  @require-wp-4.0
  Scenario: Invoke built-in YouTube handler
    When I run `wp post create --post_title="Foo Bar" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed fetch http://www.youtube.com/embed/dQw4w9WgXcQ --post-id={POST_ID}`
    Then STDOUT should contain:
      """
      youtube
      """
    And STDOUT should contain:
      """
      <iframe
      """

  @require-wp-4.0
  Scenario: Invoke built-in audio handler
    When I run `wp post create --post_title="Foo Bar" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed fetch http://www.example.com/never-gonna-give-you-up.mp3 --post-id={POST_ID}`
    Then STDOUT should contain:
      """
      example.com
      """
    And STDOUT should contain:
      """
      [audio
      """

    When I run `wp embed fetch http://www.example.com/never-gonna-give-you-up.mp3 --post-id={POST_ID} --do-shortcode`
    Then STDOUT should contain:
      """
      example.com
      """
    And STDOUT should contain:
      """
      <audio
      """

  @require-wp-4.0
  Scenario: Invoke built-in video handler
    When I run `wp post create --post_title="Foo Bar" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed fetch http://www.example.com/never-gonna-give-you-up.mp4 --post-id={POST_ID}`
    Then STDOUT should contain:
      """
      example.com
      """
    And STDOUT should contain:
      """
      [video
      """

    When I run `wp embed fetch http://www.example.com/never-gonna-give-you-up.mp4 --post-id={POST_ID} --do-shortcode`
    Then STDOUT should contain:
      """
      example.com
      """
    And STDOUT should contain:
      """
      <video
      """

  # `wp_embed_handler_googlevideo` handler deprecated WP 4.6.
  @less-than-wp-4.6 @require-wp-4.0
  Scenario: Invoke built-in Google Video handler
    When I run `wp post create --post_title="Foo Bar" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `wp embed fetch http://video.google.com/videoplay?docid=123456789 --post-id={POST_ID}`
    Then STDOUT should contain:
      """
      video.google.com
      """
    And STDOUT should contain:
      """
      <embed
      """

  @require-wp-4.0
  Scenario: Incompatible options
    When I try `wp embed fetch https://www.example.com/watch?v=dQw4w9WgXcQ --no-discover --limit-response-size=50000`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'limit-response-size' option can only be used with discovery.
      """
    And STDOUT should be empty

    When I try `wp embed fetch https://www.example.com/watch?v=dQw4w9WgXcQ --raw-format=json`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'raw-format' option can only be used with the 'raw' option.
      """
    And STDOUT should be empty
