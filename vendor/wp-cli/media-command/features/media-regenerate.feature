Feature: Regenerate WordPress attachments

  Background:
    Given a WP install
    And I try `wp theme install twentynineteen --activate`

  Scenario: Regenerate all images while none exists
    When I try `wp media regenerate --yes`
    Then STDERR should contain:
      """
      No images found.
      """
    And the return code should be 0

  @require-wp-5.3
  Scenario: Regenerate all images default behavior
    Given download:
      | path                             | url                                               |
      | {CACHE_DIR}/large-image.jpg      | http://wp-cli.org/behat-data/large-image.jpg      |
      | {CACHE_DIR}/canola.jpg           | http://wp-cli.org/behat-data/canola.jpg           |
      | {CACHE_DIR}/white-150-square.jpg | http://wp-cli.org/behat-data/white-150-square.jpg |
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported large attachment" --porcelain`
    Then save STDOUT as {LARGE_ATTACHMENT_ID}
    And the wp-content/uploads/large-image.jpg file should exist
    And the wp-content/uploads/large-image-scaled.jpg file should exist
    And the wp-content/uploads/large-image-150x150.jpg file should exist
    And the wp-content/uploads/large-image-300x225.jpg file should exist
    And the wp-content/uploads/large-image-1024x768.jpg file should exist
    And the wp-content/uploads/large-image-2048x1536.jpg file should exist

    When I run `wp media import {CACHE_DIR}/canola.jpg --title="My imported medium attachment" --porcelain`
    Then save STDOUT as {MEDIUM_ATTACHMENT_ID}
    And the wp-content/uploads/canola.jpg file should exist
    And the wp-content/uploads/canola-150x150.jpg file should exist
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-1024x768.jpg file should not exist

    When I run `wp media import {CACHE_DIR}/white-150-square.jpg --title="My imported small attachment" --porcelain`
    Then save STDOUT as {SMALL_ATTACHMENT_ID}
    And the wp-content/uploads/white-150-square.jpg file should exist
    And the wp-content/uploads/white-150-square-150x150.jpg file should exist
    And the wp-content/uploads/white-150-square-300x300.jpg file should not exist
    And the wp-content/uploads/white-150-square-1024x1024.jpg file should not exist

    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 3 images to regenerate.
      """
    And STDOUT should contain:
      """
      /3 Regenerated thumbnails for "My imported large attachment" (ID {LARGE_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      /3 Regenerated thumbnails for "My imported medium attachment" (ID {MEDIUM_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      /3 Regenerated thumbnails for "My imported small attachment" (ID {SMALL_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      Success: Regenerated 3 of 3 images.
      """
    And the wp-content/uploads/large-image.jpg file should exist
    And the wp-content/uploads/large-image-scaled.jpg file should exist
    And the wp-content/uploads/large-image-150x150.jpg file should exist
    And the wp-content/uploads/large-image-300x225.jpg file should exist
    And the wp-content/uploads/large-image-1024x768.jpg file should exist
    And the wp-content/uploads/large-image-2048x1536.jpg file should exist
    And the wp-content/uploads/canola.jpg file should exist
    And the wp-content/uploads/canola-150x150.jpg file should exist
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-1024x768.jpg file should not exist
    And the wp-content/uploads/white-150-square.jpg file should exist
    And the wp-content/uploads/white-150-square-150x150.jpg file should exist
    And the wp-content/uploads/white-150-square-300x300.jpg file should not exist
    And the wp-content/uploads/white-150-square-1024x1024.jpg file should not exist

  # Behavior changed with WordPress 5.3+, so we're adding separate tests for previous versions.
  # Changes that impact this:
  # https://core.trac.wordpress.org/ticket/43524
  # https://core.trac.wordpress.org/ticket/47873
  @less-than-wp-5.3 @broken
  Scenario: Regenerate all images default behavior (pre-WP-5.3)
    Given download:
      | path                             | url                                               |
      | {CACHE_DIR}/large-image.jpg      | http://wp-cli.org/behat-data/large-image.jpg      |
      | {CACHE_DIR}/canola.jpg           | http://wp-cli.org/behat-data/canola.jpg           |
      | {CACHE_DIR}/white-150-square.jpg | http://wp-cli.org/behat-data/white-150-square.jpg |
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported large attachment" --porcelain`
    Then save STDOUT as {LARGE_ATTACHMENT_ID}
    And the wp-content/uploads/large-image.jpg file should exist
    And the wp-content/uploads/large-image-150x150.jpg file should exist
    And the wp-content/uploads/large-image-300x225.jpg file should exist
    And the wp-content/uploads/large-image-1024x768.jpg file should exist

    When I run `wp media import {CACHE_DIR}/canola.jpg --title="My imported medium attachment" --porcelain`
    Then save STDOUT as {MEDIUM_ATTACHMENT_ID}
    And the wp-content/uploads/canola.jpg file should exist
    And the wp-content/uploads/canola-150x150.jpg file should exist
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-1024x768.jpg file should not exist

    When I run `wp media import {CACHE_DIR}/white-150-square.jpg --title="My imported small attachment" --porcelain`
    Then save STDOUT as {SMALL_ATTACHMENT_ID}
    And the wp-content/uploads/white-150-square.jpg file should exist
    And the wp-content/uploads/white-150-square-150x150.jpg file should not exist
    And the wp-content/uploads/white-150-square-300x300.jpg file should not exist
    And the wp-content/uploads/white-150-square-1024x1024.jpg file should not exist

    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 3 images to regenerate.
      """
    And STDOUT should contain:
      """
      /3 Regenerated thumbnails for "My imported large attachment" (ID {LARGE_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      /3 Regenerated thumbnails for "My imported medium attachment" (ID {MEDIUM_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      /3 Regenerated thumbnails for "My imported small attachment" (ID {SMALL_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      Success: Regenerated 3 of 3 images.
      """
    And the wp-content/uploads/large-image.jpg file should exist
    And the wp-content/uploads/large-image-150x150.jpg file should exist
    And the wp-content/uploads/large-image-300x225.jpg file should exist
    And the wp-content/uploads/large-image-1024x768.jpg file should exist
    And the wp-content/uploads/canola.jpg file should exist
    And the wp-content/uploads/canola-150x150.jpg file should exist
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-1024x768.jpg file should not exist
    And the wp-content/uploads/white-150-square.jpg file should exist
    And the wp-content/uploads/white-150-square-150x150.jpg file should not exist
    And the wp-content/uploads/white-150-square-300x300.jpg file should not exist
    And the wp-content/uploads/white-150-square-1024x1024.jpg file should not exist

  Scenario: Delete existing thumbnails when media is regenerated
    Given download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 200, 200, true );
      });
      """
    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/large-image-125x125.jpg file should not exist
    And the wp-content/uploads/large-image-200x200.jpg file should exist

  Scenario: Skip deletion of existing thumbnails when media is regenerated
    Given download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 200, 200, true );
      });
      """
    When I run `wp media regenerate --skip-delete --yes`
    Then STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/large-image-125x125.jpg file should exist
    And the wp-content/uploads/large-image-200x200.jpg file should exist

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Delete existing thumbnails when media including PDF is regenerated
    Given download:
      | path                              | url                                                   |
      | {CACHE_DIR}/large-image.jpg       | http://wp-cli.org/behat-data/large-image.jpg          |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf    |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
        add_filter( 'fallback_intermediate_image_sizes', function( $fallback_sizes ){
          $fallback_sizes[] = 'test1';
          return $fallback_sizes;
        });
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    When I run `wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then save STDOUT as {PDF_ATTACHMENT_ID}
    And the wp-content/uploads/minimal-us-letter-pdf-125x125.jpg file should exist

    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 200, 200, true );
        add_filter( 'fallback_intermediate_image_sizes', function( $fallback_sizes ){
          $fallback_sizes[] = 'test1';
          return $fallback_sizes;
        });
      });
      """
    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Success: Regenerated 2 of 2 images.
      """
    And the wp-content/uploads/large-image-125x125.jpg file should not exist
    And the wp-content/uploads/large-image-200x200.jpg file should exist
    And the wp-content/uploads/minimal-us-letter-pdf-125x125.jpg file should not exist
    And the wp-content/uploads/minimal-us-letter-pdf-200x200.jpg file should exist

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Skip deletion of existing thumbnails when media including PDF is regenerated
    Given download:
      | path                              | url                                                   |
      | {CACHE_DIR}/large-image.jpg       | http://wp-cli.org/behat-data/large-image.jpg          |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf    |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
        add_filter( 'fallback_intermediate_image_sizes', function( $fallback_sizes ){
          $fallback_sizes[] = 'test1';
          return $fallback_sizes;
        });
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    When I run `wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then save STDOUT as {PDF_ATTACHMENT_ID}
    And the wp-content/uploads/minimal-us-letter-pdf-125x125.jpg file should exist

    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 200, 200, true );
        add_filter( 'fallback_intermediate_image_sizes', function( $fallback_sizes ){
          $fallback_sizes[] = 'test1';
          return $fallback_sizes;
        });
      });
      """
    When I run `wp media regenerate --skip-delete --yes`
    Then STDOUT should contain:
      """
      Success: Regenerated 2 of 2 images.
      """
    And the wp-content/uploads/large-image-125x125.jpg file should exist
    And the wp-content/uploads/large-image-200x200.jpg file should exist
    And the wp-content/uploads/minimal-us-letter-pdf-125x125.jpg file should exist
    And the wp-content/uploads/minimal-us-letter-pdf-1-200x200.jpg file should exist

  Scenario: Provide helpful error messages when media can't be regenerated
    Given download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    When I run `rm wp-content/uploads/large-image.jpg`
    Then STDOUT should be empty

    When I try `wp media regenerate --yes`
    Then STDERR should be:
      """
      Warning: Can't find "My imported attachment" (ID {ATTACHMENT_ID}).
      Error: No images regenerated (1 failed).
      """
    And the return code should be 1

  Scenario: Only regenerate images which are missing sizes
    Given download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
      });
      // Handle WP < 4.4 when there was no dash before numbers (changeset 35276).
      add_filter( 'wp_handle_upload', function ( $info, $upload_type = null ) {
        if ( ( $new_file = str_replace( 'image1.jpg', 'image-1.jpg', $info['file'] ) ) !== $info['file'] ) {
            rename( $info['file'], $new_file );
            $info['file'] = $new_file;
            $info['url'] = str_replace( 'image1.jpg', 'image-1.jpg', $info['url'] );
        }
        return $info;
      } );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My second imported attachment" --porcelain`
    Then save STDOUT as {SECOND_ATTACHMENT_ID}

    When I run `rm wp-content/uploads/large-image-125x125.jpg`
    Then the wp-content/uploads/large-image-125x125.jpg file should not exist

    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 2 images to regenerate.
      """
    And STDOUT should contain:
      """
      /2 No thumbnail regeneration needed for "My second imported attachment"
      """
    And STDOUT should contain:
      """
      /2 Regenerated thumbnails for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 2 of 2 images
      """

    # If run again, nothing should happen.
    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 2 images to regenerate.
      """
    And STDOUT should contain:
      """
      /2 No thumbnail regeneration needed for "My second imported attachment"
      """
    And STDOUT should contain:
      """
      /2 No thumbnail regeneration needed for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 2 of 2 images
      """

    # Change dimensions of "test1".
    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 200, 200, true );
      });
      """
    Then the wp-content/uploads/large-image-125x125.jpg file should exist
    And the wp-content/uploads/large-image-1-125x125.jpg file should exist
    And the wp-content/uploads/large-image-200x200.jpg file should not exist
    And the wp-content/uploads/large-image-1-200x200.jpg file should not exist

    # Now thumbnails for both should be regenerated (and the old ones left as --only-missing sets --skip-delete).
    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 2 images to regenerate.
      """
    And STDOUT should contain:
      """
      /2 Regenerated thumbnails for "My second imported attachment"
      """
    And STDOUT should contain:
      """
      /2 Regenerated thumbnails for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 2 of 2 images
      """
    Then the wp-content/uploads/large-image-125x125.jpg file should exist
    And the wp-content/uploads/large-image-1-125x125.jpg file should exist
    And the wp-content/uploads/large-image-200x200.jpg file should exist
    And the wp-content/uploads/large-image-1-200x200.jpg file should exist

    # Check metadata updated.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"large-image-200x200.jpg"'`
    Then STDOUT should contain:
      """
      "file":"large-image-200x200.jpg"
      """
    When I run `wp post meta get {SECOND_ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"large-image-1-200x200.jpg"'`
    Then STDOUT should contain:
      """
      "file":"large-image-1-200x200.jpg"
      """

  Scenario: Regenerate images which are missing globally-defined image sizes
    Given download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://wp-cli.org/behat-data/large-image.jpg     |
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/large-image.jpg --title="My imported attachment" --porcelain`
    Then save STDOUT as {ATTACHMENT_ID}
    And the wp-content/uploads/large-image-125x125.jpg file should not exist

    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 125, 125, true );
      });
      """

    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Regenerated thumbnails for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/large-image-125x125.jpg file should exist

    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 No thumbnail regeneration needed for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/large-image-125x125.jpg file should exist

  Scenario: Only regenerate images that are missing if the size should exist
    Given download:
      | path                   | url                                         |
      | {CACHE_DIR}/canola.jpg | http://wp-cli.org/behat-data/canola.jpg     |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 500, 500, true ); // canola.jpg is 640x480.
        add_image_size( 'test2', 400, 400, true );
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/canola.jpg --title="My imported attachment" --porcelain`
    Then the wp-content/uploads/canola-500x500.jpg file should not exist
    And the wp-content/uploads/canola-400x400.jpg file should exist

    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 No thumbnail regeneration needed for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-500x500.jpg file should not exist
    And the wp-content/uploads/canola-400x400.jpg file should exist

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Only regenerate PDF previews that are missing if the size should exist
    Given download:
      | path                              | url                                                   |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf    |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 1100, 1100, true ); // minimal-us-letter.pdf is 1088x1408 at 128 dpi.
        add_image_size( 'test2', 1000, 1000, true );
        add_filter( 'fallback_intermediate_image_sizes', function( $fallback_sizes ){
          $fallback_sizes[] = 'test1';
          $fallback_sizes[] = 'test2';
          return $fallback_sizes;
        });
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then the wp-content/uploads/minimal-us-letter-pdf-1100x1100.jpg file should not exist
    And the wp-content/uploads/minimal-us-letter-pdf-1000x1000.jpg file should exist

    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 No thumbnail regeneration needed for "My imported PDF attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/minimal-us-letter-pdf-1100x1100.jpg file should not exist
    And the wp-content/uploads/minimal-us-letter-pdf-1000x1000.jpg file should exist

  # WP < 4.2 produced thumbnails duplicating original, https://core.trac.wordpress.org/ticket/31296
  # WP 5.3 alpha contains a bug where duplicate resizes are being stored: https://core.trac.wordpress.org/ticket/32437
  @require-wp-4.2 @less-than-wp-5.3
  Scenario: Only regenerate images that are missing if it has thumbnails
    Given download:
      | path                             | url                                               |
      | {CACHE_DIR}/white-150-square.jpg | http://wp-cli.org/behat-data/white-150-square.jpg |
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/white-150-square.jpg --title="My imported attachment" --porcelain`
    Then the wp-content/uploads/white-150-square-150x150.jpg file should not exist

    When I run `wp media regenerate --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 No thumbnail regeneration needed for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/white-150-square-150x150.jpg file should not exist

  Scenario: Regenerate a specific image size
    Given download:
      | path                        | url                                          |
      | {CACHE_DIR}/canola.jpg      | http://wp-cli.org/behat-data/canola.jpg      |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'too_big', 4000, 4000, true );
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    # Import without "test1" image size.
    When I run `wp media import {CACHE_DIR}/canola.jpg --title="My imported attachment" --porcelain`
    And save STDOUT as {ATTACHMENT_ID}
    Then the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-400x400.jpg file should not exist

    # Add "test1" image size.
    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 400, 400, true );
        add_image_size( 'too_big', 4000, 4000, true );
      });
      """

    # Run for "medium" size only if missing - nothing should happen.
    When I run `wp media regenerate --image_size=medium --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 No "medium" thumbnail regeneration needed for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-400x400.jpg file should not exist

    # Remove "medium" image size file.
    When I run `rm wp-content/uploads/canola-300x225.jpg`
    Then the wp-content/uploads/canola-300x225.jpg file should not exist

    # Run for "test1" size only if missing - should be generated.
    When I run `wp media regenerate --image_size=test1 --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "test1" thumbnail for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-300x225.jpg file should not exist
    And the wp-content/uploads/canola-400x400.jpg file should exist

    # Check metadata consistent.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"canola-400x400.jpg"'`
    Then STDOUT should contain:
      """
      "file":"canola-400x400.jpg"
      """

    # Regenerate "medium" image size removed above - should be regenerated.
    When I run `wp media regenerate --image_size=medium --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "medium" thumbnail for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-300x225.jpg file should exist

    # Check metadata consistent.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"medium":{[^}]*"file":"canola-300x225.jpg"'`
    Then STDOUT should contain:
      """
      "file":"canola-300x225.jpg"
      """

    # Regenerate "medium" image size whether missing or not - should be regenerated.
    When I run `wp media regenerate --image_size=medium --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "medium" thumbnail for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-300x225.jpg file should exist

    # Change "test1" image size.
    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 350, 350, true );
        add_image_size( 'too_big', 4000, 4000, true );
      });
      """

    # Regenerate "test1" image size only if missing (which also sets --skip-delete) - should be regenerated and 400x400 should still exist.
    When I run `wp media regenerate --image_size=test1 --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "test1" thumbnail for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-350x350.jpg file should exist
    And the wp-content/uploads/canola-400x400.jpg file should exist

    # Check metadata updated.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"canola-350x350.jpg"'`
    Then STDOUT should contain:
      """
      "file":"canola-350x350.jpg"
      """

    # Change "test1" image size again.
    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 380, 380, true );
        add_image_size( 'too_big', 4000, 4000, true );
      });
      """

    # Regenerate "test1" image size only if missing and with explicit --skip-delete - should be regenerated and 350x350 and 400x400 should still exist.
    When I run `wp media regenerate --image_size=test1 --only-missing --skip-delete --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "test1" thumbnail for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/canola-300x225.jpg file should exist
    And the wp-content/uploads/canola-350x350.jpg file should exist
    And the wp-content/uploads/canola-380x380.jpg file should exist
    And the wp-content/uploads/canola-400x400.jpg file should exist

    # Check metadata updated.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"canola-380x380.jpg"'`
    Then STDOUT should contain:
      """
      "file":"canola-380x380.jpg"
      """

    # The "too_big" thumbnail is never created so nothing should happen.
    When I run `wp media regenerate --image_size=too_big --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 No "too_big" thumbnail regeneration needed for "My imported attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Regenerate a specific image size for a PDF attachment
    Given download:
      | path                              | url                                                   |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf    |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function(){
        add_image_size( 'test1', 400, 400, true );
        add_image_size( 'not_in_fallback', 300, 300, true );
        add_filter( 'fallback_intermediate_image_sizes', function( $fallback_sizes ){
          $fallback_sizes[] = 'test1';
          return $fallback_sizes;
        });
      });
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    And save STDOUT as {ATTACHMENT_ID}
    Then the wp-content/uploads/minimal-us-letter-pdf-116x150.jpg file should exist
    And the wp-content/uploads/minimal-us-letter-pdf-400x400.jpg file should exist

    # Remove "thumbnail" image size and run for "test1" size - nothing should happen.
    When I run `rm wp-content/uploads/minimal-us-letter-pdf-116x150.jpg`
    And I run `wp media regenerate --image_size=test1 --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 No "test1" thumbnail regeneration needed for "My imported PDF attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/minimal-us-letter-pdf-1-116x150.jpg file should not exist

    # Remove "test1" image size and run for "test1" size only if missing - should be regenerated.
    When I run `rm wp-content/uploads/minimal-us-letter-pdf-400x400.jpg`
    And I run `wp media regenerate --image_size=test1 --only-missing --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "test1" thumbnail for "My imported PDF attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/minimal-us-letter-pdf-1-116x150.jpg file should not exist
    And the wp-content/uploads/minimal-us-letter-pdf-1-400x400.jpg file should exist

    # Check metadata updated.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"minimal-us-letter-pdf-1-400x400.jpg"'`
    Then STDOUT should contain:
      """
      "file":"minimal-us-letter-pdf-1-400x400.jpg"
      """

    # Regenerate "test1" image size whether missing or not - should be regenerated.
    # But skip deleting the existing thumbnail so its version increments.
    When I run `wp media regenerate --image_size=test1 --skip-delete --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 Regenerated "test1" thumbnail for "My imported PDF attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/minimal-us-letter-pdf-2-116x150.jpg file should not exist
    And the wp-content/uploads/minimal-us-letter-pdf-1-400x400.jpg file should exist
    And the wp-content/uploads/minimal-us-letter-pdf-2-400x400.jpg file should exist

    # Check metadata updated with incremented version.
    When I run `wp post meta get {ATTACHMENT_ID} _wp_attachment_metadata --format=json | grep -o '"test1":{[^}]*"file":"minimal-us-letter-pdf-2-400x400.jpg"'`
    Then STDOUT should contain:
      """
      "file":"minimal-us-letter-pdf-2-400x400.jpg"
      """

    # The "not_in_fallback" thumbnail is never created for PDFs so nothing should happen.
    When I run `wp media regenerate --image_size=not_in_fallback --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate
      """
    And STDOUT should contain:
      """
      1/1 No "not_in_fallback" thumbnail regeneration needed for "My imported PDF attachment"
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """

  Scenario: Provide error message when non-existent image size requested for regeneration
    When I try `wp media regenerate --image_size=test1`
    Then STDERR should be:
      """
      Error: Unknown image size "test1".
      """
    And the return code should be 1

  Scenario: Regenerating SVGs should be marked as skipped and not produce PHP notices
    Given an svg.svg file:
      """
      <?xml version="1.0" encoding="utf-8"?>
      <svg xmlns="http://www.w3.org/2000/svg"/>
      """
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function () {
        add_filter( 'upload_mimes', function ( $mimes ) { $mimes['svg'] = 'image/svg+xml'; return $mimes; } );
      } );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {RUN_DIR}/svg.svg --title="My imported SVG attachment" --porcelain`
    And save STDOUT as {ATTACHMENT_ID}
    Then the wp-content/uploads/svg.svg file should exist
    And STDERR should be empty

    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 0 of 1 images (1 skipped).
      """
    And STDERR should be empty

    # Behavior should be the same if --only-missing.
    When I run `wp media regenerate --yes --only-missing`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 0 of 1 images (1 skipped).
      """
    And STDERR should be empty

  @require-wp-4.7.3
  Scenario: Regenerating PDFs when thumbnails disabled should be marked as skipped and not produce PHP notices
    Given download:
      | path                              | url                                                |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf |
      | {CACHE_DIR}/canola.jpg            | http://wp-cli.org/behat-data/canola.jpg            |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      // Disable PDF thumbnails.
      add_filter( 'wp_image_editors', function ( $image_editors ) {
          if ( false !== ( $idx = array_search( 'WP_Image_Editor_Imagick', $image_editors, true ) ) ) {
            unset( $image_editors[ $idx ] );
            $image_editors = array_values( $image_editors );
          }
          return $image_editors;
      } );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then save STDOUT as {PDF_ATTACHMENT_ID}
    And the wp-content/uploads/minimal-us-letter-pdf.jpg file should not exist

    When I run `wp media import {CACHE_DIR}/canola.jpg --title="My imported JPG attachment" --porcelain`
    Then save STDOUT as {JPG_ATTACHMENT_ID}
    Then the wp-content/uploads/canola-300x225.jpg file should exist

    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 2 images to regenerate.
      """
    And STDOUT should contain:
      """
      /2 Skipped thumbnail regeneration for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      /2 Regenerated thumbnails for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 2 images (1 skipped).
      """
    And STDERR should be empty

    # Behavior should be the same if --only-missing.
    When I run `wp media regenerate --yes --only-missing`
    Then STDOUT should contain:
      """
      Found 2 images to regenerate.
      """
    And STDOUT should contain:
      """
      /2 Skipped thumbnail regeneration for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID})
      """
    And STDOUT should contain:
      """
      /2 No thumbnail regeneration needed for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 2 images (1 skipped).
      """
    And STDERR should be empty

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Regenerating PDFs when thumbnails enabled on import but disabled on regeneration
    Given download:
      | path                              | url                                                |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      // Disable PDF thumbnails.
      add_filter( 'wp_image_editors', function ( $image_editors ) {
          if ( ! getenv( 'WP_CLI_TEST_MEDIA_REGENERATE_PDF' ) && false !== ( $idx = array_search( 'WP_Image_Editor_Imagick', $image_editors, true ) ) ) {
            unset( $image_editors[ $idx ] );
            $image_editors = array_values( $image_editors );
          }
          return $image_editors;
      } );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    # Enable PDF thumbnails on import.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=1 wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then save STDOUT as {PDF_ATTACHMENT_ID}
    And the wp-content/uploads/minimal-us-letter-pdf.jpg file should exist
    And the wp-content/uploads/minimal-us-letter-pdf-116x150.jpg file should exist

    # Disable PDF thumbnails on regeneration.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=0 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      /1 Skipped thumbnail regeneration for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 0 of 1 images (1 skipped).
      """
    And STDERR should be empty

    # Re-enable PDF thumbnails on regeneration.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=1 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      /1 Regenerated thumbnails for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And STDERR should be empty

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Regenerating PDFs when thumbnails disabled on import but enabled on regeneration
    Given download:
      | path                              | url                                                |
      | {CACHE_DIR}/minimal-us-letter.pdf | http://wp-cli.org/behat-data/minimal-us-letter.pdf |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      // Disable PDF thumbnails.
      add_filter( 'wp_image_editors', function ( $image_editors ) {
          if ( ! getenv( 'WP_CLI_TEST_MEDIA_REGENERATE_PDF' ) && false !== ( $idx = array_search( 'WP_Image_Editor_Imagick', $image_editors, true ) ) ) {
            unset( $image_editors[ $idx ] );
            $image_editors = array_values( $image_editors );
          }
          return $image_editors;
      } );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    # Disable PDF thumbnails on import.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=0 wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then save STDOUT as {PDF_ATTACHMENT_ID}
    And the wp-content/uploads/minimal-us-letter-pdf.jpg file should not exist
    And the wp-content/uploads/minimal-us-letter-pdf-116x150.jpg file should not exist

    # Enable PDF thumbnails on regeneration.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=1 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      /1 Regenerated thumbnails for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And STDERR should be empty

    # Re-disable PDF thumbnails on regeneration.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=0 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      /1 Skipped thumbnail regeneration for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID})
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 0 of 1 images (1 skipped).
      """
    And STDERR should be empty

  # Audio/video `_cover_hash` meta, used to determine if sub attachment, added in WP 3.9
  # Test on PHP 5.6 latest only, and iterate over various WP versions.
  @require-wp-latest @require-php-5.6 @less-than-php-7.0
  Scenario Outline: Regenerating audio with thumbnail
    # If version is trunk/latest then can get warning about checksums not being available, so STDERR may or may not be empty
    Given I try `wp core download --version=<version> --force`
    Then the return code should be 0
    And I run `wp core update-db`
    And download:
      | path                                     | url                                                       |
      | {CACHE_DIR}/audio-with-400x300-cover.mp3 | http://wp-cli.org/behat-data/audio-with-400x300-cover.mp3 |
      | {CACHE_DIR}/audio-with-no-cover.mp3      | http://wp-cli.org/behat-data/audio-with-no-cover.mp3      |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php add_post_type_support( 'attachment:audio', 'thumbnail' );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/audio-with-400x300-cover.mp3 --title="My imported audio with cover attachment" --porcelain`
    Then save STDOUT as {COVER_ATTACHMENT_ID}
    And the wp-content/uploads/audio-with-400x300-cover.mp3 file should exist
    And the wp-content/uploads/audio-with-400x300-cover-mp3-image.png file should exist
    And the wp-content/uploads/audio-with-400x300-cover-mp3-image-150x150.png file should exist
    And the wp-content/uploads/audio-with-400x300-cover-mp3-image-300x225.png file should exist
    When I run `wp post meta get {COVER_ATTACHMENT_ID} _thumbnail_id`
    Then save STDOUT as {COVER_SUB_ATTACHMENT_ID}

    When I run `wp media import {CACHE_DIR}/audio-with-no-cover.mp3 --title="My imported audio with no cover attachment" --porcelain`
    Then save STDOUT as {NO_COVER_ATTACHMENT_ID}
    And the wp-content/uploads/audio-with-no-cover.mp3 file should exist
    And the wp-content/uploads/audio-with-no-cover-mp3-image.png file should not exist
    And the wp-content/uploads/audio-with-no-cover-mp3-image-150x150.png file should not exist
    And the wp-content/uploads/audio-with-no-cover-mp3-image-300x225.png file should not exist
    When I try `wp post meta get {NO_COVER_ATTACHMENT_ID} _thumbnail_id`
    Then the return code should be 1

    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Regenerated thumbnails for cover attachment (ID {COVER_SUB_ATTACHMENT_ID}).
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And STDERR should be empty

    Examples:
      | version |
      | latest  |
      | trunk   |
      | 4.2     |
      | 3.9     |

  # Video cover support requires ID3 library 1.9.9, updated WP 4.3 https://core.trac.wordpress.org/ticket/32806
  # Currently throwing notice on PHP 7.4+: https://core.trac.wordpress.org/ticket/49945
  @require-wp-4.3 @less-than-php-7.4 @less-than-wp-5.5
  Scenario: Regenerating video with thumbnail
    Given download:
      | path                                        | url                                                          |
      | {CACHE_DIR}/video-400x300-with-cover.mp4    | http://wp-cli.org/behat-data/video-400x300-with-cover.mp4    |
      | {CACHE_DIR}/video-400x300-with-no-cover.mp4 | http://wp-cli.org/behat-data/video-400x300-with-no-cover.mp4 |
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/video-400x300-with-cover.mp4 --title="My imported video with cover attachment" --porcelain`
    Then save STDOUT as {COVER_ATTACHMENT_ID}
    And the wp-content/uploads/video-400x300-with-cover.mp4 file should exist
    And the wp-content/uploads/video-400x300-with-cover-mp4-image.png file should exist
    And the wp-content/uploads/video-400x300-with-cover-mp4-image-150x150.png file should exist
    And the wp-content/uploads/video-400x300-with-cover-mp4-image-300x225.png file should exist
    When I run `wp post meta get {COVER_ATTACHMENT_ID} _thumbnail_id`
    Then save STDOUT as {COVER_SUB_ATTACHMENT_ID}

    When I run `wp media import {CACHE_DIR}/video-400x300-with-no-cover.mp4 --title="My imported video with no cover attachment" --porcelain`
    Then save STDOUT as {NO_COVER_ATTACHMENT_ID}
    And the wp-content/uploads/video-400x300-with-no-cover.mp4 file should exist
    And the wp-content/uploads/video-400x300-with-no-cover-mp4-image.png file should not exist
    And the wp-content/uploads/video-400x300-with-no-cover-mp4-image-150x150.png file should not exist
    And the wp-content/uploads/video-400x300-with-no-cover-mp4-image-300x225.png file should not exist
    When I try `wp post meta get {NO_COVER_ATTACHMENT_ID} _thumbnail_id`
    Then the return code should be 1

    When I run `wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Regenerated thumbnails for cover attachment (ID {COVER_SUB_ATTACHMENT_ID}).
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And STDERR should be empty

  @require-extension-imagick @broken
  Scenario: Regenerate image uploaded with no sizes metadata
    Given download:
      | path                             | url                                               |
      | {CACHE_DIR}/white-160-square.bmp | http://wp-cli.org/behat-data/white-160-square.bmp |
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      // Ensure BMPs are allowed.
      add_action( 'after_setup_theme', function () {
        add_filter( 'upload_mimes', function ( $mimes ) { $mimes['bmp'] = 'image/bmp'; return $mimes; } );
      } );
      // Disable Imagick.
      add_filter( 'wp_image_editors', function ( $image_editors ) {
          if ( ! getenv( 'WP_CLI_TEST_MEDIA_REGENERATE_IMAGICK' ) && false !== ( $idx = array_search( 'WP_Image_Editor_Imagick', $image_editors, true ) ) ) {
            unset( $image_editors[ $idx ] );
            $image_editors = array_values( $image_editors );
          }
          return $image_editors;
      } );
      // Enable BMP as displayable image (for WP < 4.0).
      add_filter( 'file_is_displayable_image', function ( $result, $path ) {
          return $result ? $result : false !== strpos( $path, '.bmp' );
      }, 10, 2 );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/white-160-square.bmp --title="My imported BMP attachment" --porcelain`
    Then save STDOUT as {BMP_ATTACHMENT_ID}
    And the wp-content/uploads/white-160-square-150x150.bmp file should not exist

    # Regenerate with Imagick disabled.
    When I try `WP_CLI_TEST_MEDIA_REGENERATE_IMAGICK=0 wp media regenerate --yes`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Skipped thumbnail regeneration for "My imported BMP attachment" (ID {BMP_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      Success: Regenerated 0 of 1 images (1 skipped).
      """
    And STDERR should not contain:
      """
      Warning: No editor could be selected.
      """
    And the wp-content/uploads/white-160-square-150x150.bmp file should not exist

    # Regenerate with Imagick enabled.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_IMAGICK=1 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Regenerated thumbnails for "My imported BMP attachment" (ID {BMP_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      Success: Regenerated 1 of 1 images.
      """
    And the wp-content/uploads/white-160-square-150x150.bmp file should exist

    # Now disable BMP support.
    Given a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      // Ensure BMPs are allowed.
      add_action( 'after_setup_theme', function () {
        add_filter( 'upload_mimes', function ( $mimes ) { $mimes['bmp'] = 'image/bmp'; return $mimes; } );
      } );
      // Disable Imagick.
      add_filter( 'wp_image_editors', function ( $image_editors ) {
          if ( ! getenv( 'WP_CLI_TEST_MEDIA_REGENERATE_IMAGICK' ) && false !== ( $idx = array_search( 'WP_Image_Editor_Imagick', $image_editors, true ) ) ) {
            unset( $image_editors[ $idx ] );
            $image_editors = array_values( $image_editors );
          }
          return $image_editors;
      } );
      // Disable BMP as displayable image.
      add_filter( 'file_is_displayable_image', function ( $result, $path ) {
          return $result ? false === strpos( $path, '.bmp' ) : $result;
      }, 10, 2 );
      """

    # Try with no image editor available and get warning about no editor.
    When I try `WP_CLI_TEST_MEDIA_REGENERATE_IMAGICK=0 wp media regenerate --yes`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Skipped thumbnail regeneration for "My imported BMP attachment" (ID {BMP_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      Success: Regenerated 0 of 1 images (1 skipped).
      """
    And STDERR should be:
      """
      Warning: No editor could be selected. (ID {BMP_ATTACHMENT_ID})
      """
    # Note in this case regenerate is not destructive.
    And the wp-content/uploads/white-160-square-150x150.bmp file should exist

    # Try with image editor available and get warning about no metadata.
    When I try `WP_CLI_TEST_MEDIA_REGENERATE_IMAGICK=1 wp media regenerate --yes`
    Then the return code should be 1
    And STDOUT should contain:
      """
      Found 1 image to regenerate.
      """
    And STDOUT should contain:
      """
      1/1 Couldn't regenerate thumbnails for "My imported BMP attachment" (ID {BMP_ATTACHMENT_ID}).
      """
    And STDERR should be:
      """
      Warning: No metadata. (ID {BMP_ATTACHMENT_ID})
      Error: No images regenerated (1 failed).
      """
    # Note in this case regenerate is destructive.
    And the wp-content/uploads/white-160-square-150x150.bmp file should not exist

  @require-wp-4.7.3 @require-extension-imagick
  Scenario: Regenerating melange with batch results: regenerated (and not needing regeneration), skipped, failed
    Given download:
      | path                                     | url                                                       |
      | {CACHE_DIR}/canola.jpg                   | http://wp-cli.org/behat-data/canola.jpg                   |
      | {CACHE_DIR}/minimal-us-letter.pdf        | http://wp-cli.org/behat-data/minimal-us-letter.pdf        |
      | {CACHE_DIR}/video-400x300-with-cover.mp4 | http://wp-cli.org/behat-data/video-400x300-with-cover.mp4 |
    And an svg.svg file:
      """
      <?xml version="1.0" encoding="utf-8"?>
      <svg xmlns="http://www.w3.org/2000/svg"/>
      """
    And a wp-content/mu-plugins/media-settings.php file:
      """
      <?php
      add_action( 'after_setup_theme', function () {
        add_filter( 'upload_mimes', function ( $mimes ) { $mimes['svg'] = 'image/svg+xml'; return $mimes; } );
      } );
      // Disable PDF thumbnails.
      add_filter( 'wp_image_editors', function ( $image_editors ) {
          if ( ! getenv( 'WP_CLI_TEST_MEDIA_REGENERATE_PDF' ) && false !== ( $idx = array_search( 'WP_Image_Editor_Imagick', $image_editors, true ) ) ) {
            unset( $image_editors[ $idx ] );
            $image_editors = array_values( $image_editors );
          }
          return $image_editors;
      } );
      """
    And I run `wp option update uploads_use_yearmonth_folders 0`

    When I run `wp media import {CACHE_DIR}/canola.jpg --title="My imported JPG attachment" --porcelain`
    Then save STDOUT as {JPG_ATTACHMENT_ID}
    And the wp-content/uploads/canola-150x150.jpg file should exist

    When I run `wp media import {RUN_DIR}/svg.svg --title="My imported SVG attachment" --porcelain`
    And save STDOUT as {SVG_ATTACHMENT_ID}
    Then the wp-content/uploads/svg.svg file should exist

    # Disable PDF thumbnails on import.
    When I run `wp media import {CACHE_DIR}/minimal-us-letter.pdf --title="My imported PDF attachment" --porcelain`
    Then save STDOUT as {PDF_ATTACHMENT_ID}
    And the wp-content/uploads/minimal-us-letter-pdf-116x150.jpg file should not exist

    When I run `wp media import {CACHE_DIR}/video-400x300-with-cover.mp4 --title="My imported video attachment" --porcelain`
    Then save STDOUT as {VIDEO_ATTACHMENT_ID}
    And the wp-content/uploads/video-400x300-with-cover-mp4-image-150x150.png file should exist
    When I run `wp post meta get {VIDEO_ATTACHMENT_ID} _thumbnail_id`
    Then save STDOUT as {VIDEO_SUB_ATTACHMENT_ID}

    # Regenerate with PDF thumbnails enabled.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=1 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 4 images to regenerate.
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {SVG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for cover attachment (ID {VIDEO_SUB_ATTACHMENT_ID}).
      """
    And STDOUT should not contain:
      """
      Warning
      """
    And STDOUT should contain:
      """
      Success: Regenerated 3 of 4 images (1 skipped).
      """
    And STDERR should be empty

    # Regenerate with PDF thumbnails disabled after being enabled.
    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=0 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 4 images to regenerate.
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {SVG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for cover attachment (ID {VIDEO_SUB_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      Success: Regenerated 2 of 4 images (2 skipped).
      """
    And STDERR should be empty

    # Regenerate canola only.
    Given I run `rm wp-content/uploads/canola-150x150.jpg`

    When I run `WP_CLI_TEST_MEDIA_REGENERATE_PDF=0 wp media regenerate --yes --only-missing`
    Then STDOUT should contain:
      """
      Found 4 images to regenerate.
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {SVG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 No thumbnail regeneration needed for cover attachment (ID {VIDEO_SUB_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      Success: Regenerated 2 of 4 images (2 skipped).
      """
    And STDERR should be empty

    # Make canola.jpg fail.
    Given a wp-content/uploads/canola.jpg file:
      """
      """

    When I try `WP_CLI_TEST_MEDIA_REGENERATE_PDF=1 wp media regenerate --yes`
    Then STDOUT should contain:
      """
      Found 4 images to regenerate.
      """
    And STDOUT should contain:
      """
      /4 Couldn't regenerate thumbnails for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {SVG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Regenerated thumbnails for cover attachment (ID {VIDEO_SUB_ATTACHMENT_ID}).
      """
    And STDERR should contain:
      """
      Warning:
      """
    And STDERR should contain:
      """
      (ID {JPG_ATTACHMENT_ID})
      """
    And STDERR should contain:
      """
      Error: Only regenerated 2 of 4 images (1 failed, 1 skipped).
      """
    And STDERR should not contain:
      """
      Warning: No editor could be selected.
      """
    And the return code should be 1

    # Make minimal pdf fail.
    Given a wp-content/uploads/minimal-us-letter.pdf file:
      """
      %PDF-1.1
      %¥±ë

      %%EOF
      """
    And I run `rm wp-content/uploads/minimal-us-letter-pdf-116x150.jpg`

    When I try `WP_CLI_TEST_MEDIA_REGENERATE_PDF=1 wp media regenerate --yes --only-missing`
    Then STDOUT should contain:
      """
      Found 4 images to regenerate.
      """
    And STDOUT should contain:
      """
      /4 Couldn't regenerate thumbnails for "My imported JPG attachment" (ID {JPG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Skipped thumbnail regeneration for "My imported SVG attachment" (ID {SVG_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 Couldn't regenerate thumbnails for "My imported PDF attachment" (ID {PDF_ATTACHMENT_ID}).
      """
    And STDOUT should contain:
      """
      /4 No thumbnail regeneration needed for cover attachment (ID {VIDEO_SUB_ATTACHMENT_ID}).
      """
    And STDERR should contain:
      """
      Warning:
      """
    And STDERR should contain:
      """
      (ID {JPG_ATTACHMENT_ID})
      """
    And STDERR should contain:
      """
      (ID {PDF_ATTACHMENT_ID})
      """
    And STDERR should contain:
      """
      Error: Only regenerated 1 of 4 images (2 failed, 1 skipped).
      """
    And STDERR should not contain:
      """
      Warning: No editor could be selected.
      """
    And the return code should be 1
