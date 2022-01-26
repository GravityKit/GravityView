Feature: Update WordPress core

  Scenario: Update from a ZIP file
    Given a WP install
    And I try `wp theme install twentytwenty --activate`

    When I run `wp core download --version=3.9 --force`
    Then STDOUT should not be empty

    When I run `wp eval 'echo $GLOBALS["wp_version"];'`
    Then STDOUT should be:
      """
      3.9
      """

    When I run `wget http://wordpress.org/wordpress-4.0.zip --quiet`
    And I run `wp core update wordpress-4.0.zip`
    Then STDOUT should be:
      """
      Starting update...
      Unpacking the update...
      Cleaning up files...
      No files found that need cleaning up.
      Success: WordPress updated successfully.
      """

    When I run `wp eval 'echo $GLOBALS["wp_version"];'`
    Then STDOUT should be:
      """
      4.0
      """

  # PHP 7.1 needs WP 3.9 (due to wp_check_php_mysql_versions(), see trac changeset [27257]),
  # and travis doesn't install mysql extension by default for PHP 7.0.
  @less-than-php-7
  Scenario: Update to the latest minor release
    Given a WP install
    And I try `wp theme install twentytwenty --activate`

    When I run `wp core download --version=3.7.9 --force`
    Then STDOUT should not be empty

    # WP core throws notice for PHP 8+.
    When I try `wp core update --minor`
    Then STDOUT should contain:
      """
      Updating to version {WP_VERSION-3.7-latest}
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """

    When I run `wp core update --minor`
    Then STDOUT should be:
      """
      Success: WordPress is at the latest minor release.
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      {WP_VERSION-3.7-latest}
      """

  Scenario: Update to the latest minor release (PHP 7.1 compatible with WP >= 3.9)
    Given a WP install
    And I try `wp theme install twentytwenty --activate`

    When I run `wp core download --version=3.9.9 --force`
    Then STDOUT should not be empty

    # This version of WP throws a PHP notice
    When I try `wp core update --minor`
    Then STDOUT should contain:
      """
      Updating to version {WP_VERSION-3.9-latest}
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
    And STDERR should contain:
      """
      Undefined variable
      """
    And the return code should be 0

    When I run `wp core update --minor`
    Then STDOUT should be:
      """
      Success: WordPress is at the latest minor release.
      """

    When I run `wp core version`
    Then STDOUT should be:
      """
      {WP_VERSION-3.9-latest}
      """

  Scenario: Core update from cache
    Given a WP install
    And I try `wp theme install twentytwenty --activate`
    And an empty cache

    When I run `wp core update --version=3.9.1 --force`
    Then STDOUT should not contain:
      """
      Using cached file
      """
    And STDOUT should contain:
      """
      Downloading
      """

    When I run `wp core update --version=4.0 --force`
    Then STDOUT should not be empty

    When I run `wp core update --version=3.9.1 --force`
    Then STDOUT should contain:
      """
      Using cached file '{SUITE_CACHE_DIR}/core/wordpress-3.9.1-en_US.zip'...
      """
    And STDOUT should not contain:
      """
      Downloading
      """

  @require-php-5.6
  Scenario: Don't run update when up-to-date
    Given a WP install
    And I run `wp core update`

    When I run `wp core update`
    Then STDOUT should contain:
      """
      WordPress is up to date
      """
    And STDOUT should not contain:
      """
      Updating
      """

    When I run `wp core update --force`
    Then STDOUT should contain:
      """
      Updating
      """

  Scenario: Ensure cached partial upgrades aren't used in full upgrade
    Given a WP install
    And I try `wp theme install twentytwenty --activate`
    And an empty cache
    And a wp-content/mu-plugins/upgrade-override.php file:
      """
      <?php
      add_filter( 'pre_site_transient_update_core', function(){
        return (object) array(
          'updates' => array(
              (object) array(
                'response' => 'autoupdate',
                'download' => 'https://downloads.wordpress.org/release/wordpress-4.2.4.zip',
                'locale' => 'en_US',
                'packages' => (object) array(
                  'full' => 'https://downloads.wordpress.org/release/wordpress-4.2.4.zip',
                  'no_content' => 'https://downloads.wordpress.org/release/wordpress-4.2.4-no-content.zip',
                  'new_bundled' => 'https://downloads.wordpress.org/release/wordpress-4.2.4-new-bundled.zip',
                  'partial' => 'https://downloads.wordpress.org/release/wordpress-4.2.4-partial-1.zip',
                  'rollback' => 'https://downloads.wordpress.org/release/wordpress-4.2.4-rollback-1.zip',
                ),
                'current' => '4.2.4',
                'version' => '4.2.4',
                'php_version' => '5.2.4',
                'mysql_version' => '5.0',
                'new_bundled' => '4.1',
                'partial_version' => '4.2.1',
                'support_email' => 'updatehelp42@wordpress.org',
                'new_files' => '',
             ),
          ),
          'version_checked' => '4.2.4', // Needed to avoid PHP notice in `wp_version_check()`.
        );
      });
      """

    When I run `wp core download --version=4.2.1 --force`
    And I run `wp core update`
    Then STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
    And the {SUITE_CACHE_DIR}/core directory should contain:
      """
      wordpress-4.2.1-en_US.tar.gz
      wordpress-4.2.4-partial-1-en_US.zip
      """

    When I run `wp core download --version=4.1.1 --force`
    And I run `wp core update`
    Then STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """

    # Allow for warnings to be produced.
    When I try `wp core verify-checksums`
    Then STDOUT should be:
      """
      Success: WordPress installation verifies against checksums.
      """
    And the {SUITE_CACHE_DIR}/core directory should contain:
      """
      wordpress-4.1.1-en_US.tar.gz
      wordpress-4.2.1-en_US.tar.gz
      wordpress-4.2.4-no-content-en_US.zip
      wordpress-4.2.4-partial-1-en_US.zip
      """

  @less-than-php-7.3
  Scenario: Make sure files are cleaned up
    Given a WP install
    And I try `wp theme install twentytwenty --activate`
    When I run `wp core update --version=4.4 --force`
    Then the wp-includes/rest-api.php file should exist
    Then the wp-includes/class-wp-comment.php file should exist
    And STDOUT should not contain:
      """
      File removed: wp-content
      """

    When I run `wp core update --version=4.3.2 --force`
    Then the wp-includes/rest-api.php file should not exist
    Then the wp-includes/class-wp-comment.php file should not exist
    Then STDOUT should contain:
      """
      File removed: wp-includes/class-walker-comment.php
      File removed: wp-includes/class-wp-network.php
      File removed: wp-includes/embed-template.php
      File removed: wp-includes/class-wp-comment.php
      File removed: wp-includes/class-wp-http-response.php
      File removed: wp-includes/class-walker-category-dropdown.php
      File removed: wp-includes/rest-api.php
      """
    And STDOUT should not contain:
      """
      File removed: wp-content
      """

    When I run `wp option add str_opt 'bar'`
    Then STDOUT should not be empty
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number

  Scenario: Make sure files are cleaned up with mixed case
    Given a WP install
    And I try `wp theme install twentytwenty --activate`

    When I run `wp core update --version=5.8 --force`
    Then the wp-includes/Requests/Transport/cURL.php file should exist
    Then the wp-includes/Requests/Exception/Transport/cURL.php file should exist
    Then the wp-includes/Requests/Exception/HTTP/502.php file should exist
    Then the wp-includes/Requests/IRI.php file should exist
    Then the wp-includes/Requests/Transport/Curl.php file should not exist
    Then the wp-includes/Requests/Exception/Transport/Curl.php file should not exist
    Then the wp-includes/Requests/Exception/Http/Status502.php file should not exist
    Then the wp-includes/Requests/Iri.php file should not exist

    When I run `wp core update --version=5.9-beta1 --force`
    Then the wp-includes/Requests/Transport/cURL.php file should not exist
    Then the wp-includes/Requests/Exception/Transport/cURL.php file should not exist
    Then the wp-includes/Requests/Exception/HTTP/502.php file should not exist
    Then the wp-includes/Requests/IRI.php file should not exist
    Then the wp-includes/Requests/Transport/Curl.php file should exist
    Then the wp-includes/Requests/Exception/Transport/Curl.php file should exist
    Then the wp-includes/Requests/Exception/Http/Status502.php file should exist
    Then the wp-includes/Requests/Iri.php file should exist
    Then STDOUT should contain:
      """
      File removed: wp-includes/Requests/Transport/fsockopen.php
      File removed: wp-includes/Requests/Transport/cURL.php
      File removed: wp-includes/Requests/Hooker.php
      File removed: wp-includes/Requests/IPv6.php
      File removed: wp-includes/Requests/Exception/Transport/cURL.php
      File removed: wp-includes/Requests/Exception/HTTP.php
      File removed: wp-includes/Requests/Exception/HTTP/502.php
      File removed: wp-includes/Requests/Exception/HTTP/Unknown.php
      File removed: wp-includes/Requests/Exception/HTTP/412.php
      File removed: wp-includes/Requests/Exception/HTTP/408.php
      File removed: wp-includes/Requests/Exception/HTTP/431.php
      File removed: wp-includes/Requests/Exception/HTTP/501.php
      File removed: wp-includes/Requests/Exception/HTTP/500.php
      File removed: wp-includes/Requests/Exception/HTTP/407.php
      File removed: wp-includes/Requests/Exception/HTTP/416.php
      File removed: wp-includes/Requests/Exception/HTTP/428.php
      File removed: wp-includes/Requests/Exception/HTTP/406.php
      File removed: wp-includes/Requests/Exception/HTTP/504.php
      File removed: wp-includes/Requests/Exception/HTTP/411.php
      File removed: wp-includes/Requests/Exception/HTTP/414.php
      File removed: wp-includes/Requests/Exception/HTTP/511.php
      File removed: wp-includes/Requests/Exception/HTTP/410.php
      File removed: wp-includes/Requests/Exception/HTTP/403.php
      File removed: wp-includes/Requests/Exception/HTTP/400.php
      File removed: wp-includes/Requests/Exception/HTTP/505.php
      File removed: wp-includes/Requests/Exception/HTTP/413.php
      File removed: wp-includes/Requests/Exception/HTTP/404.php
      File removed: wp-includes/Requests/Exception/HTTP/306.php
      File removed: wp-includes/Requests/Exception/HTTP/304.php
      File removed: wp-includes/Requests/Exception/HTTP/405.php
      File removed: wp-includes/Requests/Exception/HTTP/429.php
      File removed: wp-includes/Requests/Exception/HTTP/417.php
      File removed: wp-includes/Requests/Exception/HTTP/409.php
      File removed: wp-includes/Requests/Exception/HTTP/402.php
      File removed: wp-includes/Requests/Exception/HTTP/418.php
      File removed: wp-includes/Requests/Exception/HTTP/305.php
      File removed: wp-includes/Requests/Exception/HTTP/415.php
      File removed: wp-includes/Requests/Exception/HTTP/401.php
      File removed: wp-includes/Requests/Exception/HTTP/503.php
      File removed: wp-includes/Requests/IRI.php
      File removed: wp-includes/Requests/IDNAEncoder.php
      File removed: wp-includes/Requests/SSL.php
      File removed: wp-includes/Requests/Proxy/HTTP.php
      """

    When I run `wp option add str_opt 'bar'`
    Then STDOUT should not be empty
    When I run `wp post create --post_title='Test post' --porcelain`
    Then STDOUT should be a number

  @less-than-php-7.3
  Scenario: Minor update on an unlocalized WordPress release
    Given a WP install
    And I try `wp theme install twentytwenty --activate`
    And an empty cache

    # If current WP_VERSION is nightly, trunk or old then from checksums might not exist, so STDERR may or may not be empty.
    When I try `wp core download --version=4.0 --locale=es_ES --force`
    Then STDOUT should contain:
      """
      Success: WordPress downloaded.
      """
    And the return code should be 0

    # No checksums available for this WP version/locale
    Given I run `wp option set WPLANG es_ES`
    When I try `wp core update --minor`
    Then STDOUT should contain:
      """
      Updating to version {WP_VERSION-4.0-latest} (en_US)...
      """
    And STDOUT should contain:
      """
      https://downloads.wordpress.org/release/wordpress-{WP_VERSION-4.0-latest}-partial-0.zip
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """
    And STDERR should be:
      """
      Warning: Checksums not available for WordPress {WP_VERSION-4.0-latest}/es_ES. Please cleanup files manually.
      """
    And the return code should be 0

  @require-php-5.6
  Scenario Outline: Use `--version=(nightly|trunk)` to update to the latest nightly version
    Given a WP install

    When I run `wp core update --version=<version>`
    Then STDOUT should contain:
      """
      Updating to version nightly (en_US)...
      Downloading update from https://wordpress.org/nightly-builds/wordpress-latest.zip...
      """
    And STDOUT should contain:
      """
      Success: WordPress updated successfully.
      """

    Examples:
    | version    |
    | trunk      |
    | nightly    |

  @require-php-5.6
  Scenario: Installing latest nightly build should skip cache
    Given a WP install

    # May produce warnings if checksums cannot be retrieved.
    When I try `wp core upgrade --force http://wordpress.org/nightly-builds/wordpress-latest.zip`
    Then STDOUT should contain:
      """
      Success:
      """
    And STDOUT should not contain:
      """
      Using cached
      """

    # May produce warnings if checksums cannot be retrieved.
    When I try `wp core upgrade --force http://wordpress.org/nightly-builds/wordpress-latest.zip`
    Then STDOUT should contain:
      """
      Success:
      """
    And STDOUT should not contain:
      """
      Using cached
      """
