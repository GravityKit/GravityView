Feature: Import content.

  Scenario: Basic export then import
    Given a WP install
    And I run `wp site empty --yes`
    And I run `wp post generate --post_type=post --count=4`
    And I run `wp post generate --post_type=page --count=3`
    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      7
      """

    When I run `wp export`
    And save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=any --format=count`
    Then STDOUT should be:
      """
      7
      """

    When I run `wp import {EXPORT_FILE} --authors=skip --skip=image_resize`
    Then STDOUT should not be empty

  Scenario: Export and import a directory of files
    Given a WP install
    And I run `mkdir export-posts`
    And I run `mkdir export-pages`
    And I run `wp site empty --yes`

    When I run `wp post generate --count=50`
    When I run `wp post generate --post_type=page --count=50`
    And I run `wp post list --post_type=post,page --format=count`
    Then STDOUT should be:
      """
      100
      """

    When I run `wp export --dir=export-posts --post_type=post`
    When I run `wp export --dir=export-pages --post_type=page`
    Then STDOUT should not be empty

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post,page --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `find export-* -type f | wc -l`
    Then STDOUT should contain:
      """
      2
      """

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp import export-posts --authors=skip --skip=image_resize`
    And I run `wp import export-pages --authors=skip --skip=image_resize`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post,page --format=count`
    Then STDOUT should be:
      """
      100
      """

  Scenario: Export and import a directory of files with .wxr and .xml extensions.
    Given a WP install
    And I run `mkdir export`
    And I run `wp site empty --yes`
    When I run `wp post generate --count=1`
    When I run `wp post generate --post_type=page --count=1`

    When I run `wp post list --post_type=post,page --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp export --dir=export --post_type=post --filename_format={site}.wordpress.{date}.{n}.xml`
    Then STDOUT should not be empty
    When I run `wp export --dir=export --post_type=page --filename_format={site}.wordpress.{date}.{n}.wxr`
    Then STDOUT should not be empty

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=post,page --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `find export -type f | wc -l`
    Then STDOUT should contain:
      """
      2
      """

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should be empty

    When I run `wp import export --authors=skip --skip=image_resize`
    Then STDOUT should not be empty
    And STDERR should be empty

    When I run `wp post list --post_type=post,page --format=count`
    Then STDOUT should be:
      """
      2
      """

  @require-wp-4.0
  Scenario: Export and import page and referencing menu item
    Given a WP install
    And I run `wp site empty --yes`
    And I run `wp post generate --count=1`
    And I run `wp post generate --post_type=page --count=1`
    And I run `mkdir export`

    # NOTE: The Hello World page ID is 2.
    When I run `wp menu create "My Menu"`
    And I run `wp menu item add-post my-menu 2`
    And I run `wp menu item list my-menu --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp export --dir=export`
    Then STDOUT should not be empty

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp menu create "My Menu"`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp post list --post_type=nav_menu_item --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `find export -type f | wc -l`
    Then STDOUT should contain:
      """
      1
      """

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp import export --authors=skip --skip=image_resize`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_type=nav_menu_item --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp menu item list my-menu --fields=object --format=csv`
    Then STDOUT should contain:
      """
      page
      """

    When I run `wp menu item list my-menu --fields=object_id --format=csv`
    Then STDOUT should contain:
      """
      2
      """

  @require-wp-4.0
  Scenario: Export and import page and referencing menu item in separate files
    Given a WP install
    And I run `wp site empty --yes`
    And I run `wp post generate --count=1`
    And I run `wp post generate --post_type=page --count=1`
    And I run `mkdir export`

    # NOTE: The Hello World page ID is 2.
    When I run `wp menu create "My Menu"`
    And I run `wp menu item add-post my-menu 2`
    And I run `wp menu item list my-menu --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp export --dir=export --post_type=page --filename_format=0.page.xml`
    And I run `wp export --dir=export --post_type=nav_menu_item --filename_format=1.menu.xml`
    Then STDOUT should not be empty

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp menu create "My Menu"`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp post list --post_type=nav_menu_item --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `find export -type f | wc -l`
    Then STDOUT should contain:
      """
      2
      """

    When I run `wp plugin install wordpress-importer --activate`
    Then STDERR should not contain:
      """
      Warning:
      """

    When I run `wp import export --authors=skip --skip=image_resize`
    Then STDOUT should not be empty

    When I run `wp post list --post_type=page --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp post list --post_type=nav_menu_item --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp menu item list my-menu --fields=object --format=csv`
    Then STDOUT should contain:
      """
      page
      """

    When I run `wp menu item list my-menu --fields=object_id --format=csv`
    Then STDOUT should contain:
      """
      2
      """

  Scenario: Indicate current file when importing
    Given a WP install
    And I run `wp plugin install --activate wordpress-importer`

    When I run `wp export --filename_format=wordpress.{n}.xml`
    Then save STDOUT 'Writing to file %s' as {EXPORT_FILE}

    When I run `wp site empty --yes`
    Then STDOUT should not be empty

    When I run `wp import {EXPORT_FILE} --authors=skip`
    Then STDOUT should contain:
      """
      (in file wordpress.000.xml)
      """
