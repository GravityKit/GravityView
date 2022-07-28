Feature: Generate a POT file of a WordPress project

  Background:
    Given a WP install

  Scenario: Bail for invalid source directories
    When I try `wp i18n make-pot foo bar/baz.pot`
    Then STDERR should contain:
      """
      Error: Not a valid source directory!
      """
    And the return code should be 1

  Scenario: Generates a POT file by default
    When I run `wp scaffold plugin hello-world`
    Then the wp-content/plugins/hello-world directory should exist
    And the wp-content/plugins/hello-world/hello-world.php file should exist

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should exist

  Scenario: Does include file headers.
    When I run `wp scaffold plugin hello-world --plugin_name="Hello World" --plugin_author="John Doe" --plugin_author_uri="https://example.com" --plugin_uri="https://foo.example.com"`
    Then the wp-content/plugins/hello-world directory should exist
    And the wp-content/plugins/hello-world/hello-world.php file should exist

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Hello World"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "John Doe"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "https://example.com"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "https://foo.example.com"
      """

  Scenario: Does not include empty file headers.
    When I run `wp scaffold plugin hello-world --plugin_description=""`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should not contain:
      """
      Description of the plugin
      """

  Scenario: Adds copyright comments
    When I run `wp scaffold plugin hello-world`

    When I run `date +"%Y"`
    Then STDOUT should not be empty
    And save STDOUT as {YEAR}

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      # Copyright (C) {YEAR} YOUR NAME HERE
      # This file is distributed under the same license as the Hello World plugin.
      """

  Scenario: Use the same license as the plugin
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );

      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot`
    Then the foo-plugin.pot file should contain:
      """
      # This file is distributed under the GPL-2.0+.
      """

  Scenario: Sets Project-Id-Version
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "Project-Id-Version: Hello World 0.1.0\n"
      """

  Scenario: Sets Report-Msgid-Bugs-To
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/hello-world\n"
      """

  Scenario: Sets custom Report-Msgid-Bugs-To
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot --headers='{"Report-Msgid-Bugs-To":"https://github.com/hello-world/hello-world/"}'`
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "Report-Msgid-Bugs-To: https://github.com/hello-world/hello-world/\n"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should not contain:
      """
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/hello-world\n"
      """

  Scenario: Sets custom header
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot --headers='{"X-Poedit-Basepath":".."}'`
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "X-Poedit-Basepath: ..\n"
      """

  Scenario: Sets a placeholder PO-Revision-Date header
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
      """

  Scenario: Sets the last translator and the language team
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      """

  Scenario: Sets the generator header
    When I run `wp scaffold plugin hello-world`

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot`
    Then the contents of the wp-content/plugins/hello-world/languages/hello-world.pot file should match /X-Generator:\s/

  Scenario: Ignores any other text domain
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );

       __( 'Foo', 'bar' );

       __( 'bar' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --domain=bar`
    Then the foo-plugin.pot file should contain:
      """
      msgid "Foo"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Hello World"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "bar"
      """

  Scenario: Bails when no plugin files are found
    Given an empty foo-plugin directory
    When I try `wp i18n make-pot foo-plugin foo-plugin.pot --debug`
    Then STDERR should contain:
      """
      No valid theme stylesheet or plugin file found, treating as a regular project.
      """

  Scenario: Bails when no main plugin file is found
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      """
    When I try `wp i18n make-pot foo-plugin foo-plugin.pot --debug`
    Then STDERR should contain:
      """
      No valid theme stylesheet or plugin file found, treating as a regular project.
      """

  Scenario: Adds relative paths to source file as comments.
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( 'Hello World', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot`
    Then the foo-plugin.pot file should contain:
      """
      #: foo-plugin.php:15
      """
    And the foo-plugin.pot file should contain:
      """
      #: foo-plugin.js:1
      """

  Scenario: Uses the current folder as destination path when none is set.
    When I run `wp scaffold plugin hello-world`
    Then the wp-content/plugins/hello-world directory should exist

    When I run `wp i18n make-pot wp-content/plugins/hello-world`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should exist

  Scenario: Uses Domain Path as destination path when none is set.
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
      """

    When I run `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the foo-plugin/languages/foo-plugin.pot file should exist

  Scenario: Uses Text Domain header when no domain is set.
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: bar-plugin
       * Domain Path: /languages
       */

       __( 'Foo Text', 'foo-plugin' );

       __( 'Bar Text', 'bar-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the foo-plugin.pot file should exist
    And the foo-plugin.pot file should contain:
      """
      msgid "Bar Text"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Foo Text"
      """

  Scenario: Extract all supported functions
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      __( '__', 'foo-plugin' );
      esc_attr__( 'esc_attr__', 'foo-plugin' );
      esc_html__( 'esc_html__', 'foo-plugin' );
      esc_xml__( 'esc_xml__', 'foo-plugin' );
      _e( '_e', 'foo-plugin' );
      esc_attr_e( 'esc_attr_e', 'foo-plugin' );
      esc_html_e( 'esc_html_e', 'foo-plugin' );
      esc_xml_e( 'esc_xml_e', 'foo-plugin' );
      _x( '_x', '_x_context', 'foo-plugin' );
      _ex( '_ex', '_ex_context', 'foo-plugin' );
      esc_attr_x( 'esc_attr_x', 'esc_attr_x_context', 'foo-plugin' );
      esc_html_x( 'esc_html_x', 'esc_html_x_context', 'foo-plugin' );
      esc_xml_x( 'esc_xml_x', 'esc_xml_x_context', 'foo-plugin' );
      _n( '_n_single', '_n_plural', $number, 'foo-plugin' );
      _nx( '_nx_single', '_nx_plural', $number, '_nx_context', 'foo-plugin' );
      _n_noop( '_n_noop_single', '_n_noop_plural', 'foo-plugin' );
      _nx_noop( '_nx_noop_single', '_nx_noop_plural', '_nx_noop_context', 'foo-plugin' );

      // Compat.
      _( '_', 'foo-plugin' );

      // Deprecated.
      _c( '_c', 'foo-plugin' );
      _nc( '_nc_single', '_nc_plural', $number, 'foo-plugin' );
      __ngettext( '__ngettext_single', '__ngettext_plural', $number, 'foo-plugin' );
      __ngettext_noop( '__ngettext_noop_single', '__ngettext_noop_plural', 'foo-plugin' );

      __unsupported_func( '__unsupported_func', 'foo-plugin' );
      __( 'wrong-domain', 'wrong-domain' );
      """

    When I run `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_attr__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_html__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_xml__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_e"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_attr_e"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_html_e"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_xml_e"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "_x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_ex"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "_ex_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_attr_x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "esc_attr_x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_html_x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "esc_html_x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "esc_xml_x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "esc_xml_x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_n_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_n_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_nx_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_nx_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "_nx_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_n_noop_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_n_noop_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_nx_noop_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_nx_noop_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "_nx_noop_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_c"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_nc_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_nc_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "__ngettext_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "__ngettext_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "__ngettext_noop_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "__ngettext_noop_plural"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "__unsupported_func"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "wrong-domain"
      """

  Scenario: Extract translator comments
    Given I run `echo "\t"`
    And save STDOUT as {TAB}
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      /* translators: Translators 1! */
      _e( 'hello world', 'foo-plugin' );

      /* Translators: Translators 2! */
      $foo = __( 'foo', 'foo-plugin' );

      /* translators: localized date and time format, see https://secure.php.net/date */
      __( 'F j, Y g:i a', 'foo-plugin' );

      // translators: let your ears fly!
      __( 'on', 'foo-plugin' );

      /*
       * Translators: If there are characters in your language that are not supported
       * by Lato, translate this to 'off'. Do not translate into your own language.
       */
       __( 'off', 'foo-plugin' );

      /* translators: this should get extracted. */ $foo = __( 'baba', 'foo-plugin' );

      /* translators: boo */ /* translators: this should get extracted too. */ /* some other comment */ $bar = g( __( 'bubu', 'foo-plugin' ) );

      {TAB}/*
      {TAB} * translators: this comment block is indented with a tab and should get extracted too.
      {TAB} */
      {TAB}__( 'yolo', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Plugin name"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: Translators 1!
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. Translators: Translators 2!
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "F j, Y g:i a"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: localized date and time format, see https://secure.php.net/date
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: let your ears fly!
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. Translators: If there are characters in your language that are not supported by Lato, translate this to 'off'. Do not translate into your own language.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this should get extracted.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this should get extracted too.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this comment block is indented with a tab and should get extracted too.
      """

  Scenario: Remove duplicate translator comments
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      /* translators: This is a duplicate comment! */
      __( 'Hello World', 'foo-plugin' );

      /* translators: This is a duplicate comment! */
      __( 'Hello World', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: This is a duplicate comment!
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      #. translators: This is a duplicate comment!
      #. translators: This is a duplicate comment!
      """

  Scenario: Generates a POT file for a child theme with no other files
    When I run `wp scaffold child-theme foobar --parent_theme=twentyseventeen --theme_name="Foo Bar" --author="Jane Doe" --author_uri="https://example.com" --theme_uri="https://foobar.example.com"`
    Then the wp-content/themes/foobar directory should exist
    And the wp-content/themes/foobar/style.css file should exist

    When I run `wp i18n make-pot wp-content/themes/foobar wp-content/themes/foobar/languages/foobar.pot`
    Then STDOUT should be:
      """
      Theme stylesheet detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/themes/foobar/languages/foobar.pot file should exist
    And the wp-content/themes/foobar/languages/foobar.pot file should contain:
      """
      msgid "Foo Bar"
      """
    And the wp-content/themes/foobar/languages/foobar.pot file should contain:
      """
      msgid "Jane Doe"
      """
    And the wp-content/themes/foobar/languages/foobar.pot file should contain:
      """
      msgid "https://example.com"
      """
    And the wp-content/themes/foobar/languages/foobar.pot file should contain:
      """
      msgid "https://foobar.example.com"
      """

  Scenario: Prints a warning when two identical strings have different translator comments.
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      /* translators: Translators 1! */
      __( 'Hello World', 'foo-plugin' );

      /* Translators: Translators 2! */
      __( 'Hello World', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Warning: The string "Hello World" has 2 different translator comments. (foo-plugin.php:7)
      translators: Translators 1!
      Translators: Translators 2!
      """

  Scenario: Does not print a warning when two identical strings have the same translator comment
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      /* translators: This is a duplicate comment! */
      __( 'Hello World', 'foo-plugin' );

      /* translators: This is a duplicate comment! */
      __( 'Hello World', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should not contain:
      """
      Warning: The string "Hello World" has 2 different translator comments.
      """

  Scenario: Does not print a warning for translator comments clashing with meta data
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       * Plugin URI: https://example.com
       * Author URI: https://example.com
       */

      /* translators: This is a comment */
      __( 'Plugin name', 'foo-plugin' );

      /* Translators: This is another comment! */
      __( 'https://example.com', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should not contain:
      """
      The string "Plugin name" has 2 different translator comments.
      """
    And STDERR should not contain:
      """
      The string "https://example.com" has 3 different translator comments.
      """

  Scenario: Prints a warning for strings without translatable content
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      sprintf( __( '"%s"', 'foo-plugin' ), $some_variable );

      """

    When I try `wp i18n make-pot foo-plugin --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Warning: Found string without translatable content. (foo-plugin.php:6)
      """

  Scenario: Prints a warning for a string with missing translator comment
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      sprintf( __( 'Hello, %s', 'foo-plugin' ), $some_variable );

      """

    When I try `wp i18n make-pot foo-plugin --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Warning: The string "Hello, %s" contains placeholders but has no "translators:" comment to clarify their meaning. (foo-plugin.php:6)
      """

  Scenario: Prints a warning for missing singular placeholder
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      sprintf(
        _n( 'One Comment', '%s Comments', $number, 'foo-plugin' ),
        $number
      );

      """

    When I try `wp i18n make-pot foo-plugin --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Missing singular placeholder, needed for some languages. See https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#plurals (foo-plugin.php:7)
      """

  Scenario: Prints a warning for mismatched placeholders
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      sprintf(
        _n( '%1$s Comment (%2$d)', '%2$s Comments (%1$s)', $number, 'foo-plugin' ),
        $number,
        $another_variable
      );

      """

    When I try `wp i18n make-pot foo-plugin --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Mismatched placeholders for singular and plural string. (foo-plugin.php:7)
      """

  Scenario: Prints a warning for multiple unordered placeholders
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      sprintf(
        __( 'Hello %s %s', 'foo-plugin' ),
        $a_variable,
        $another_variable
      );

      """

    When I try `wp i18n make-pot foo-plugin --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Multiple placeholders should be ordered. (foo-plugin.php:7)
      """

  Scenario: Prints no warnings when audit is being skipped.
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Plugin name
       */

      /* translators: Translators 1! */
      __( 'Hello World', 'foo-plugin' );

      /* Translators: Translators 2! */
      __( 'Hello World', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin --debug --skip-audit`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should not contain:
      """
      Warning: The string "Hello World" has 2 different translator comments.
      """

  Scenario: Skips excluded folders
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description: Plugin Description
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/vendor/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin foo-plugin.pot --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Extracted 4 strings
      """
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """

  Scenario: Skips additionally excluded folders
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/vendor/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/foo/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/bar/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
     And a foo-plugin/bar/ignored.js file:
      """
      __( 'I am being ignored', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --exclude=foo,bar`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """

  Scenario: Skips excluded subfolders
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/foo/bar/vendor/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """

  Scenario: Skips additionally excluded file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/notignored.php file:
      """
      <?php
       __( 'I am not being ignored', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --exclude=ignored.php`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """
    And the foo-plugin.pot file should contain:
      """
      I am not being ignored
      """

  Scenario: Does not exclude files and folders with partial matches
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/myvendor/notignored.php file:
      """
      <?php
       __( 'I am not being ignored', 'foo-plugin' );
      """
    And a foo-plugin/foos.php file:
      """
      <?php
       __( 'I am not being ignored either', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --exclude=foo`
    Then the foo-plugin.pot file should contain:
      """
      I am not being ignored
      """
    And the foo-plugin.pot file should contain:
      """
      I am not being ignored either
      """

  Scenario: Removes trailing and leading slashes of excluded paths
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/myvendor/foo.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --exclude="/myvendor/,/ignored.php"`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """

  Scenario: Excludes nested folders and files
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/some/sub/folder/foo.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/some/other/sub/folder/foo.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/bsome/sub/folder/foo.php file:
      """
      <?php
       __( 'I am not being ignored', 'foo-plugin' );
      """
    And a foo-plugin/some/sub/folder.php file:
      """
      <?php
       __( 'I am not being ignored either', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --exclude="some/sub/folder,other/sub/folder/foo.php"`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """
    And the foo-plugin.pot file should contain:
      """
      I am not being ignored
      """
    And the foo-plugin.pot file should contain:
      """
      I am not being ignored either
      """

  Scenario: Supports glob patterns for file exclusion
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/sub/foobar.php file:
      """
      <?php
       __( 'I am not being ignored', 'foo-plugin' );
      """
    And a foo-plugin/sub/foo-bar.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/sub/foo-baz.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --exclude="sub/foo-*.php"`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """
    And the foo-plugin.pot file should contain:
      """
      I am not being ignored
      """

  Scenario: Only extract strings from included paths
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/vendor/ignored.php file:
      """
      <?php
       __( 'I am being ignored', 'foo-plugin' );
      """
    And a foo-plugin/foo/file.php file:
      """
      <?php
       __( 'I am included', 'foo-plugin' );
      """
    And a foo-plugin/bar/file.php file:
      """
      <?php
       __( 'I am also included', 'foo-plugin' );
      """
    And a foo-plugin/baz/included.js file:
      """
      __( 'I am totally included', 'foo-plugin' );
      """
    And a foo-plugin/foobar/ignored.js file:
      """
      __( 'I should not be included either', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --include=foo,bar,baz/inc*.js`
    Then the foo-plugin.pot file should not contain:
      """
      I am being ignored
      """
    And the foo-plugin.pot file should contain:
      """
      I am included
      """
    And the foo-plugin.pot file should contain:
      """
      I am also included
      """
    And the foo-plugin.pot file should contain:
      """
      I am totally included
      """
    And the foo-plugin.pot file should not contain:
      """
      I should not be included either
      """

  Scenario: Inclusion takes precedence over exclusion
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/wp-admin/includes/continents-cities.php file:
      """
      <?php
       __( 'I am included', 'foo-plugin' );
      """
    And a foo-plugin/wp-content/file.php file:
      """
      <?php
       __( 'I am not included', 'foo-plugin' );
      """
    And a foo-plugin/wp-includes/file.php file:
      """
      <?php
       __( 'I am not included', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --include=wp-admin/includes/continents-cities.php`
    Then the foo-plugin.pot file should contain:
      """
      I am included
      """
    And the foo-plugin.pot file should not contain:
      """
      I am not included
      """

  Scenario: Includes minified JavaScript files if asked to
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/bar/minified.min.js file:
      """
      __( 'I am not being ignored', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot`
    Then the foo-plugin.pot file should not contain:
      """
      I am not being ignored
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --include=bar/*.min.js`
    Then the foo-plugin.pot file should contain:
      """
      I am not being ignored
      """

  Scenario: Omit source code references
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );
      """
    And a foo-plugin/file.php file:
      """
      <?php
       __( 'Foo', 'foo-plugin' );
      """
    And a foo-plugin/file.js file:
      """
      __( 'Bar', 'foo-plugin' );
      """
    And a foo-plugin/block.json file:
      """
      {
        "name": "my-plugin/notice",
        "title": "Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Shows warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "textdomain": "foo-plugin",
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css"
      }
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --no-location`
    Then the foo-plugin.pot file should not contain:
      """
      #:
      """

  Scenario: Merges translations with the ones from an existing POT file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr ""
      """

    When I run `wp scaffold plugin hello-world --plugin_name="Hello World" --plugin_author="John Doe" --plugin_author_uri="https://example.com" --plugin_uri="https://foo.example.com"`
    Then the wp-content/plugins/hello-world directory should exist
    And the wp-content/plugins/hello-world/hello-world.php file should exist

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot --merge=foo-plugin/foo-plugin.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Hello World"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      #: foo-plugin.js:15
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Foo Plugin"
      """

  Scenario: Merges translations with the ones from multiple existing POT files
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr ""
      """
    And a foo-plugin/bar-plugin.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      #: bar-plugin.js:15
      msgid "Bar Plugin"
      msgstr ""
      """

    When I run `wp scaffold plugin hello-world --plugin_name="Hello World" --plugin_author="John Doe" --plugin_author_uri="https://example.com" --plugin_uri="https://foo.example.com"`
    Then the wp-content/plugins/hello-world directory should exist
    And the wp-content/plugins/hello-world/hello-world.php file should exist

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot --merge=foo-plugin/foo-plugin.pot,foo-plugin/bar-plugin.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Hello World"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      #: foo-plugin.js:15
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      #: bar-plugin.js:15
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Bar Plugin"
      """

  Scenario: Merges translations with existing destination file
    When I run `wp scaffold plugin hello-world --plugin_name="Hello World" --plugin_author="John Doe" --plugin_author_uri="https://example.com" --plugin_uri="https://foo.example.com"`
    Then the wp-content/plugins/hello-world directory should exist
    And the wp-content/plugins/hello-world/hello-world.php file should exist

    Given a wp-content/plugins/hello-world/languages/hello-world.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      #: hello-world.js:15
      msgid "Hello World JS"
      msgstr ""
      """

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot --merge`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Hello World"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      #: hello-world.js:15
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Hello World JS"
      """

  Scenario: Uses newer file headers when merging translations
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: John Doe <johndoe@example.com>\n"
      "Language-Team: Language Team <foo@example.com>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr ""
      """

    When I run `wp scaffold plugin hello-world --plugin_name="Hello World" --plugin_author="John Doe" --plugin_author_uri="https://example.com" --plugin_uri="https://foo.example.com"`
    Then the wp-content/plugins/hello-world directory should exist
    And the wp-content/plugins/hello-world/hello-world.php file should exist

    When I run `date +"%Y"`
    Then save STDOUT as {YEAR}

    When I run `wp i18n make-pot wp-content/plugins/hello-world wp-content/plugins/hello-world/languages/hello-world.pot --merge=foo-plugin/foo-plugin.pot`
    Then the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should exist
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      # Copyright (C) {YEAR} John Doe
      # This file is distributed under the same license as the Hello World plugin.
      msgid ""
      msgstr ""
      "Project-Id-Version: Hello World 0.1.0\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/hello-world\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should not contain:
      """
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      "X-Domain: hello-world\n"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Hello World"
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      #: foo-plugin.js:15
      """
    And the wp-content/plugins/hello-world/languages/hello-world.pot file should contain:
      """
      msgid "Foo Plugin"
      """

  Scenario: Extracts functions from JavaScript file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      // Included to test if peast correctly parses regexes containing a quote.
      // See: https://github.com/wp-cli/i18n-command/issues/98
      n = n.replace(/"/g, '&quot;');
      n = n.replace(/"|'/g, '&quot;');

      __( '__', 'foo-plugin' );
      _x( '_x', '_x_context', 'foo-plugin' );
      _n( '_n_single', '_n_plural', number, 'foo-plugin' );
      _nx( '_nx_single', '_nx_plural', number, '_nx_context', 'foo-plugin' );

      __( 'wrong-domain', 'wrong-domain' );

      __( 'Hello world' ); // translators: Greeting

      // translators: Foo Bar Comment
      __( 'Foo Bar', 'foo-plugin' );

      // TrANslAtORs: Bar Baz Comment
      __( 'Bar Baz', 'foo-plugin' );

      // translators: Software name
      const string = __( 'WordPress', 'foo-plugin' );

      // translators: So much space

      __( 'Spacey text', 'foo-plugin' );

      /* translators: Long comment
      spanning multiple
      lines */
      const string = __( 'Short text', 'foo-plugin' );

      ReactDOM.render(
        <h1>{__( 'Hello JSX', 'foo-plugin' )}</h1>,
        document.getElementById('root')
      );

      wp.i18n.__( 'wp.i18n.__', 'foo-plugin' );
      wp.i18n._n( 'wp.i18n._n_single', 'wp.i18n._n_plural', number, 'foo-plugin' );

      const translate = wp.i18n;
      translate.__( 'translate.__', 'foo-plugin' );

      Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])( 'webpack.__', 'foo-plugin' );
      Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__[/* __ */ "a"])( 'webpack.mangle.__', 'foo-plugin' );

      Object(u.__)( 'minified.__', 'foo-plugin' );
      Object(j._x)( 'minified._x', 'minified._x_context', 'foo-plugin' );

      /* translators: babel */
      (0, __)( 'babel.__', 'foo-plugin' );
      (0, _i18n.__)( 'babel-i18n.__', 'foo-plugin' );
      (0, _i18n._x)( 'babel-i18n._x', 'babel-i18n._x_context', 'foo-plugin' );

      eval( "__( 'Hello Eval World', 'foo-plugin' );" );

      __( `This is a ${ bug }`, 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "_x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_n_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_n_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "_nx_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "_nx_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "_nx_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Hello JSX"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "wp.i18n.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "wp.i18n._n_single"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "wp.i18n._n_plural"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "translate.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "webpack.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "webpack.mangle.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "minified.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "minified._x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "minified._x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: babel
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "babel.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "babel-i18n.__"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "babel-i18n._x"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "babel-i18n._x_context"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Hello Eval World"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "wrong-domain"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "foo-plugin"
      """

  Scenario: Ignores dynamic import in JavaScript file.
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      // This should not trigger a compiler error
      import('./some-file.js').then(a => console.log(a))
      __( '__', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "__"
      """

  Scenario: Parse .js and .jsx files for javascript translations
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( 'js', 'foo-plugin' );
      """
    And a foo-plugin/foo-plugin.jsx file:
      """
      __( 'jsx', 'foo-plugin' );
      """
    And a foo-plugin/foo-plugin.whatever file:
      """
      __( 'whatever', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "js"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "jsx"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "whatever"
      """

  Scenario: Extract translator comments from JavaScript file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      /* translators: Translators 1! */
      __( 'hello world', 'foo-plugin' );

      /* Translators: Translators 2! */
      const foo = __( 'foo', 'foo-plugin' );

      /* translators: localized date and time format, see https://secure.php.net/date */
      __( 'F j, Y g:i a', 'foo-plugin' );

      // translators: let your ears fly!
      __( 'on', 'foo-plugin' );

      /*
       * Translators: If there are characters in your language that are not supported
       * by Lato, translate this to 'off'. Do not translate into your own language.
       */
      __( 'off', 'foo-plugin' );

      /* translators: this should get extracted. */ let bar = __( 'baba', 'foo-plugin' );

      /* translators: boo */ /* translators: this should get extracted too. */ /* some other comment */ let bar = g ( __( 'bubu', 'foo-plugin' ) );

      /* translators: this is before the multiline call. */
      var baz = __(
        /* translators: this is inside the multiline call. */
        'This is the original',
        'foo-plugin'
      );

      ReactDOM.render(
        /* translators: this is JSX */
        <h1>{__( 'Hello JSX', 'foo-plugin' )}</h1>,
        document.getElementById('root')
      );

      // translators: this is wp.i18n
      wp.i18n.__( 'Hello wp.i18n', 'foo-plugin' );

      message = Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["sprintf"])( // translators: this is Webpack
      Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__["__"])('Hello webpack.', 'foo-plugin'), mediaFile.name);
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: Translators 1!
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. Translators: Translators 2!
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "F j, Y g:i a"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: localized date and time format, see https://secure.php.net/date
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: let your ears fly!
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. Translators: If there are characters in your language that are not supported by Lato, translate this to 'off'. Do not translate into your own language.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this should get extracted.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: boo
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this should get extracted too.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this is before the multiline call.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this is inside the multiline call.
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this is JSX
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this is wp.i18n
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: this is Webpack
      """

  Scenario: Extract plural strings with expressions from JavaScript file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      _n( '%d var (_n)', '%d vars (_n)', x, 'foo-plugin' );
      _n( '%d prop (_n)', '%d props (_n)', x.y, 'foo-plugin' );
      _n( '%d function (_n)', '%d functions (_n)', Math.abs(x), 'foo-plugin' );
      _n( '%d operation (_n)', '%d operations (_n)', x + x, 'foo-plugin' );

      _nx( '%d var (_nx)', '%d vars (_nx)', x, 'context', 'foo-plugin' );
      _nx( '%d prop (_nx)', '%d props (_nx)', x.y, 'context', 'foo-plugin' );
      _nx( '%d function (_nx)', '%d functions (_nx)', Math.abs(x), 'context', 'foo-plugin' );
      _nx( '%d operation (_nx)', '%d operations (_nx)', x + x, 'context', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d var (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d vars (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d var (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d vars (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d prop (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d props (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d prop (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d props (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d function (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d functions (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d function (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d functions (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d operation (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d operations (_n)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "%d operation (_nx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid_plural "%d operations (_nx)"
      """

  Scenario: Ignores any other text domain in JavaScript file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( 'Hello World', 'foo-plugin' );

      __( 'Foo', 'bar' );

      __( 'bar' );

      ReactDOM.render(
        <h1>{__( 'Hello JSX', 'baz' )}</h1>,
        document.getElementById('root')
      );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --domain=bar`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Foo"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Hello World"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "bar"
      """
     And the foo-plugin.pot file should not contain:
      """
      msgid "Hello JSX"
      """

  Scenario: Extract strings in template literals in JavaScript file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      /* translators: %s viewport width as css, ie: 100% */
      __( `Import me (%spx)`, 'foo-plugin' );

      /* translators: %s viewport width as css, ie: 100% */
      __( `Do not ${x} import me (%spx)`, 'foo-plugin' );
      """
    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Import me (%spx)"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. translators: %s viewport width as css, ie: 100%
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "Do not ${x} import me (%spx)"
      """

  Scenario: Extract translator comments from JavaScript map file
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js.map file:
      """
      {"version":3,"sources":["webpack:some-path/foo-plugin.js"],"sourcesContent":["/* Translators: foo */\n const foo = __( 'foo', 'foo-plugin' );"]}
      """
    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "foo"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      #. Translators: foo
      """

  Scenario: Skips JavaScript file altogether
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( 'Hello World', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --skip-js`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Hello World"
      """

  Scenario: Skips PHP file altogether
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */

       __( 'Hello World from PHP', 'foo-plugin' );
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( 'Hello World from JavaScript', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --skip-php`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Hello World from PHP"
      """
   And the foo-plugin.pot file should contain:
      """
      msgid "Hello World from JavaScript"
      """

  Scenario: Skips  JavaScript file and PHP file altogether
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */

       __( 'Hello World from PHP', 'foo-plugin' );
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( 'Hello World from JavaScript', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --skip-js --skip-php`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Hello World from PHP"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Hello World from JavaScript"
      """

  Scenario: Extract all strings regardless of text domain
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );

       __( 'Foo', 'bar' );

       __( 'bar' );
      """
    And a foo-plugin/foo-plugin.js file:
      """
      __( '__', 'foo-plugin' );

      __( 'wrong-domain', 'wrong-domain' );

      __( 'Hello JS' );

      __( `This is a ${ bug }`, 'do-not-import-me' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --domain=bar --ignore-domain`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the foo-plugin.pot file should contain:
      """
      msgid "Hello World"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Foo"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "bar"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "__"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "wrong-domain"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Hello JS"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "do-not-import-me"
      """

  Scenario: Associates comments with the right source string
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

      """
    And a foo-plugin/foo-plugin.js file:
      """
      printf( /* translators: %s: test */ __( 'Hi %s', 'foo-plugin' ), foo ); __( 'hello', 'foo-plugin' );
      """

    When I run `wp i18n make-pot foo-plugin foo-plugin.pot --domain=bar --ignore-domain`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the foo-plugin.pot file should contain:
      """
      #. translators: %s: test
      #: foo-plugin.js:1
      msgid "Hi %s"
      msgstr ""
      """
    And the foo-plugin.pot file should contain:
      """
      #: foo-plugin.js:1
      msgid "hello"
      msgstr ""
      """
    And the foo-plugin.pot file should not contain:
      """
      #. translators: %s: test
      #: foo-plugin.js:1
      msgid "hello"
      msgstr ""
      """

  Scenario: Prints helpful debug messages for plugin
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */
       __( 'Hello World', 'foo-plugin' );
      """

    When I try `wp i18n make-pot foo-plugin --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Extracting all strings with text domain "foo-plugin"
      """
    And STDERR should contain:
      """
      Plugin file:
      """
    And STDERR should contain:
      """
      foo-plugin/foo-plugin.php
      """
    And STDERR should contain:
      """
      Destination:
      """
    And STDERR should contain:
      """
      foo-plugin/languages/foo-plugin.pot
      """
    And STDERR should contain:
      """
      Extracted 3 strings
      """

    When I try `wp i18n make-pot foo-plugin --merge --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Merging with existing POT file
      """

    When I try `wp i18n make-pot foo-plugin --merge=bar.pot --debug`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Invalid file provided to --merge
      """

  Scenario: Prints helpful debug messages for theme
    Given an empty foo-theme directory
    And a foo-theme/style.css file:
      """
      /*
      Theme Name:     Foo Theme
      Theme URI:      https://example.com
      Description:
      Author:
      Author URI:
      Version:        0.1.0
      License:        GPL-2.0+
      Text Domain:    foo-theme
      Domain Path:    /languages
      */
      """

    When I try `wp i18n make-pot foo-theme --debug`
    Then STDOUT should be:
      """
      Theme stylesheet detected.
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      Extracting all strings with text domain "foo-theme"
      """
    And STDERR should contain:
      """
      Theme stylesheet:
      """
    And STDERR should contain:
      """
      foo-theme/style.css
      """
    And STDERR should contain:
      """
      Destination:
      """
    And STDERR should contain:
      """
      foo-theme/languages/foo-theme.pot
      """
    And STDERR should contain:
      """
      Extracted 2 strings
      """

  Scenario: Detects theme in sub folder
    Given an empty foo-themes directory
    And an empty foo-themes/theme-a directory
    And a foo-themes/theme-a/style.css file:
      """
      /*
      Theme Name:     Theme A
      Theme URI:      https://example.com
      Description:
      Author:
      Author URI:
      Version:        0.1.0
      License:        GPL-2.0+
      Text Domain:    foo-theme
      Domain Path:    /languages
      */
      """

    When I try `wp i18n make-pot foo-themes`
    Then STDOUT should be:
      """
      Theme stylesheet detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty

  @less-than-php-7.3
  Scenario: Ignore strings that are part of the exception list
    Given an empty directory
    And a exception.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      msgid "Foo Bar"
      msgstr ""

      msgid "Bar Baz"
      msgstr ""

      msgid "Some other text"
      msgstr ""
      """
    And a foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );

       __( 'Foo Bar', 'foo-plugin' );

       __( 'Bar Baz', 'foo-plugin' );

       __( 'Some text', 'foo-plugin' );

       __( 'Some other text', 'foo-plugin' );
      """

    When I run `wp i18n make-pot . foo-plugin.pot --domain=foo-plugin --subtract=exception.pot`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the foo-plugin.pot file should contain:
      """
      msgid "Hello World"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Some text"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Foo Bar"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Bar Baz"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Some other text"
      """

  @less-than-php-7.3
  Scenario: Add references to files used in exception list
    Given an empty directory
    And a exception.pot file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"

      #: bar-plugin.php:2
      #: bar-baz-plugin.php:20
      msgid "Foo Bar"
      msgstr ""

      #: bar-plugin.php:5
      #: bar-bar.php:50
      msgid "Bar Baz"
      msgstr ""

      #: bar-plugin.php:17
      #: bar-baz-plugin.php:99
      #: foobar/plugin.php:39
      msgid "Some other text"
      msgstr ""
      """
    And a foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );

       __( 'Foo Bar', 'foo-plugin' );

       __( 'Bar Baz', 'foo-plugin' );

       __( 'Some text', 'foo-plugin' );

       __( 'Some other text', 'foo-plugin' );
      """

    When I run `wp i18n make-pot . foo-plugin.pot --domain=foo-plugin --subtract=exception.pot --subtract-and-merge`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And STDERR should be empty
    And the foo-plugin.pot file should contain:
      """
      msgid "Hello World"
      """
    And the foo-plugin.pot file should contain:
      """
      msgid "Some text"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Foo Bar"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Bar Baz"
      """
    And the foo-plugin.pot file should not contain:
      """
      msgid "Some other text"
      """
    And the exception.pot file should contain:
      """
      #: foo-plugin.php:17
      """
    And the exception.pot file should contain:
      """
      #: foo-plugin.php:19
      """
    And the exception.pot file should contain:
      """
      #: foo-plugin.php:23
      """

  Scenario: Extract strings for a generic project
    Given an empty example-project directory
    And a example-project/stuff.php file:
      """
      <?php

       _( 'Hello World' );

       _( 'Foo' );

       _( 'Bar' );
      """

    When I try `wp i18n make-pot example-project result.pot --ignore-domain --debug`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And STDERR should contain:
      """
      No valid theme stylesheet or plugin file found, treating as a regular project.
      """
    And the result.pot file should contain:
      """
      msgid "Hello World"
      """
    And the result.pot file should contain:
      """
      msgid "Foo"
      """
    And the result.pot file should contain:
      """
      msgid "Bar"
      """

  @blade
  Scenario: Extract strings from a Blade-PHP file in a theme (ignoring domains)
    Given an empty foo-theme directory
    And a foo-theme/style.css file:
      """
      /*
      Theme Name:     Foo Theme
      Theme URI:      https://example.com
      Description:
      Author:
      Author URI:
      Version:        0.1.0
      License:        GPL-2.0+
      Text Domain:    foo-theme
      */
      """
    And a foo-theme/stuff.blade.php file:
      """
	  @php
		__('Test');
	  @endphp
      @extends('layouts.app')

	  @php(__('Another test.', 'some-other-domain'))

      @section('content')
        @include('partials.page-header')

        @if (! have_posts())
          <x-alert type="warning">
            {!! __('Page not found.', 'foo-theme') !!}
          </x-alert>

          {!! get_search_form(false) !!}
        @endif
      @endsection
      """

    When I try `wp i18n make-pot foo-theme result.pot --ignore-domain --debug`
    Then STDOUT should be:
      """
      Theme stylesheet detected.
      Success: POT file successfully generated!
      """
    And the result.pot file should contain:
      """
      msgid "Test"
      """
    And the result.pot file should contain:
      """
      msgid "Page not found."
      """
    And the result.pot file should contain:
      """
      msgid "Another test."
      """

  @blade
  Scenario: Extract strings from a Blade-PHP file in a theme
    Given an empty foo-theme directory
    And a foo-theme/style.css file:
      """
      /*
      Theme Name:     Foo Theme
      Theme URI:      https://example.com
      Description:
      Author:
      Author URI:
      Version:        0.1.0
      License:        GPL-2.0+
      Text Domain:    foo-theme
      */
      """
    And a foo-theme/stuff.blade.php file:
      """
	  @php
		__('Test');
	  @endphp
      @extends('layouts.app')

	  @php(__('Another test.', 'some-other-domain'))

      @section('content')
        @include('partials.page-header')

        @if (! have_posts())
          <x-alert type="warning">
            {!! __('Page not found.', 'foo-theme') !!}
          </x-alert>

          {!! get_search_form(false) !!}
        @endif
      @endsection
      """

    When I try `wp i18n make-pot foo-theme result.pot --debug`
    Then STDOUT should be:
      """
      Theme stylesheet detected.
      Success: POT file successfully generated!
      """
    And the result.pot file should contain:
      """
      msgid "Page not found."
      """

  Scenario: Custom package name
    Given an empty example-project directory
    And a example-project/stuff.php file:
      """
      <?php

       __( 'Hello World' );

       __( 'Foo' );

       __( 'Bar' );
      """

    When I run `wp i18n make-pot example-project result.pot --ignore-domain --package-name="Acme 1.2.3"`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And the result.pot file should contain:
      """
      Project-Id-Version: Acme 1.2.3
      """

  Scenario: Customized file comment
    Given an empty example-project directory
    And a example-project/stuff.php file:
      """
      <?php

       __( 'Hello World' );

       __( 'Foo' );

       __( 'Bar' );
      """

    When I run `wp i18n make-pot example-project result.pot --ignore-domain --file-comment="Copyright (C) 2018 John Doe\nPowered by WP-CLI."`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And the result.pot file should contain:
       """
      # Copyright (C) 2018 John Doe
      # Powered by WP-CLI.
      """

  Scenario: Empty file comment
    Given an empty example-project directory
    And a example-project/stuff.php file:
      """
      <?php

       /**
       * Plugin Name: Foo Plugin
       * Plugin URI:  https://example.com
       * Description:
       * Version:     0.1.0
       * Author:
       * Author URI:
       * License:     GPL-2.0+
       * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
       * Text Domain: foo-plugin
       * Domain Path: /languages
       */

       __( 'Hello World', 'foo-plugin' );
      """

    When I run `wp i18n make-pot example-project result.pot --ignore-domain --file-comment=""`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the contents of the result.pot file should match /^msgid/

  Scenario: Extract strings from block.json files
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/block.json file:
      """
      {
        "name": "my-plugin/notice",
        "title": "Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Shows warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "textdomain": "foo-plugin",
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css",
        "variations": [
          {
            "title": "Notice Variation A",
            "description": "Just a variation",
            "keywords": [ "msgvariation", "anotherkeyword" ]
          }
        ]
      }
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block title"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Notice"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block description"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Shows warning, error or success notices  ..."
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block keyword"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "alert"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "message"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block style label"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Default"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block style label"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Other"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block variation title"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Notice Variation A"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block variation description"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Just a variation"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block variation keyword"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "msgvariation"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block variation keyword"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "anotherkeyword"
      """

  Scenario: Ignores block.json files with other text domain
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/block.json file:
      """
      {
        "name": "my-plugin/notice",
        "title": "Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Shows warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "textdomain": "my-plugin",
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css"
      }
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "Notice"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "Shows warning, error or success notices  ..."
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "alert"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "message"
      """

  Scenario: Extract strings from block.json files with no text domain specified
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/block.json file:
      """
      {
        "name": "my-plugin/notice",
        "title": "Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Shows warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css"
      }
      """

    When I try `wp i18n make-pot foo-plugin`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block title"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Notice"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block description"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Shows warning, error or success notices  ..."
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block keyword"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "alert"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "message"
      """

  Scenario: Extract strings from all block.json files when domain is ignored
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/block_one/block.json file:
      """
      {
        "name": "my-plugin/notice",
        "title": "Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Shows warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "textdomain": "my-plugin",
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css"
      }
      """
    And a foo-plugin/block_two/block.json file:
      """
      {
        "name": "my-plugin/block_two",
        "title": "Second Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Another way to show warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css"
      }
      """

    When I try `wp i18n make-pot foo-plugin --ignore-domain`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block title"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Notice"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block description"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Shows warning, error or success notices  ..."
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgctxt "block keyword"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "alert"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "message"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Second Notice"
      """
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Another way to show warning, error or success notices  ..."
      """

  Scenario: Skips block.json file altogether
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin.php file:
      """
      <?php
      /**
       * Plugin Name: Foo Plugin
       */
      """
    And a foo-plugin/block.json file:
      """
      {
        "name": "my-plugin/notice",
        "title": "Notice",
        "category": "common",
        "parent": [ "core/group" ],
        "icon": "star",
        "description": "Shows warning, error or success notices  ...",
        "keywords": [ "alert", "message" ],
        "attributes": {
          "message": {
            "type": "string",
            "source": "html",
            "selector": ".message"
          }
        },
        "styles": [
          { "name": "default", "label": "Default", "isDefault": true },
          { "name": "other", "label": "Other" }
        ],
        "editorScript": "build/editor.js",
        "script": "build/main.js",
        "editorStyle": "build/editor.css",
        "style": "build/style.css"
      }
      """

    When I try `wp i18n make-pot foo-plugin --skip-block-json`
    Then STDOUT should be:
      """
      Plugin file detected.
      Success: POT file successfully generated!
      """
    And the foo-plugin/foo-plugin.pot file should exist
    And the foo-plugin/foo-plugin.pot file should contain:
      """
      msgid "Foo Plugin"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgctxt "block title"
      """
    And the foo-plugin/foo-plugin.pot file should not contain:
      """
      msgid "Notice"
      """

  Scenario: Skips theme.json file if skip-theme-json flag provided
    Given an empty foo-theme directory
    And a foo-theme/theme.json file:
      """
      {
        "version": "1",
        "settings": {
          "color": {
            "palette": [
              { "slug": "black", "color": "#000000", "name": "Black" }
            ]
          }
        }
      }
      """

    When I try `wp i18n make-pot foo-theme --skip-theme-json`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And the foo-theme/foo-theme.pot file should exist
    But the foo-theme/foo-theme.pot file should not contain:
      """
      msgctxt "Color name"
      msgid "Black"
      """

  Scenario: Extract strings from the top-level section of theme.json files
    Given an empty foo-theme directory
    And a foo-theme/theme.json file:
      """
      {
        "version": "1",
        "title": "My style variation",
        "settings": {
          "color": {
            "duotone": [
                { "slug": "dark-grayscale", "name": "Dark grayscale", "colors": [] }
            ],
            "gradients": [
                { "slug": "purple-to-yellow", "name": "Purple to yellow" }
            ],
            "palette": [
              { "slug": "black", "color": "#000000", "name": "Black" },
              { "slug": "white", "color": "#000000", "name": "White" }
            ]
          },
          "typography": {
              "fontSizes": [
                  { "name": "Small", "slug": "small", "size": "13px" }
              ]
          }
        }
      }
      """

    When I try `wp i18n make-pot foo-theme`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And the foo-theme/foo-theme.pot file should exist
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Duotone name"
      msgid "Dark grayscale"
      """
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Gradient name"
      msgid "Purple to yellow"
      """
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Color name"
      msgid "White"
      """
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Color name"
      msgid "White"
      """
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Font size name"
      msgid "Small"
      """
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Style variation name"
      msgid "My style variation"
      """

  Scenario: Extract strings from the blocks section of theme.json files
    Given an empty foo-theme directory
    And a foo-theme/theme.json file:
      """
      {
        "version": "1",
        "settings": {
          "blocks": {
            "core/paragraph": {
              "color": {
                "palette": [
                  { "slug": "black", "color": "#000000", "name": "Black" }
                ]
              }
            }
          }
        }
      }
      """

    When I try `wp i18n make-pot foo-theme`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And the foo-theme/foo-theme.pot file should exist
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Color name"
      msgid "Black"
      """

  Scenario: Extract strings from style variations
    Given an empty foo-theme/styles directory
    And a foo-theme/styles/my-style.json file:
      """
      {
        "version": "1",
        "settings": {
          "blocks": {
            "core/paragraph": {
              "color": {
                "palette": [
                  { "slug": "black", "color": "#000000", "name": "Black" }
                ]
              }
            }
          }
        }
      }
      """
    And a foo-theme/incorrect/styles/my-style.json file:
      """
      {
        "version": "1",
        "settings": {
          "blocks": {
            "core/paragraph": {
              "color": {
                "palette": [
                  { "slug": "white", "color": "#ffffff", "name": "White" }
                ]
              }
            }
          }
        }
      }
      """

    When I try `wp i18n make-pot foo-theme`
    Then STDOUT should be:
      """
      Success: POT file successfully generated!
      """
    And the foo-theme/foo-theme.pot file should exist
    And the foo-theme/foo-theme.pot file should contain:
      """
      msgctxt "Color name"
      msgid "Black"
      """
    And the foo-theme/foo-theme.pot file should not contain:
      """
      msgid "White"
      """

  Scenario: Extract strings from the patterns directory
    Given an empty foo-theme/patterns directory
    And a foo-theme/patterns/my-pattern.php file:
      """
      <?php
      /**
       * Title: My pattern title.
       * Description: My pattern description.
       */
      """
    And a foo-theme/incorrect/patterns/other-pattern.php file:
      """
      <?php
      /**
       * Title: Other pattern title.
       * Description: Other pattern description.
       */
      """
    And a foo-theme/style.css file:
      """
      /*
      Theme Name: foo theme
      */
      """

    When I try `wp i18n make-pot foo-theme`
    Then STDOUT should be:
      """
      Theme stylesheet detected.
      Success: POT file successfully generated!
      """
    And the foo-theme/foo-theme.pot file should exist
    And the foo-theme/foo-theme.pot file should contain:
      """
      #: patterns/my-pattern.php
      msgctxt "Pattern title"
      msgid "My pattern title."
      msgstr ""

      #: patterns/my-pattern.php
      msgctxt "Pattern description"
      msgid "My pattern description."
      msgstr ""
      """
    And the foo-theme/foo-theme.pot file should not contain:
      """
      msgid "Other pattern title."
      """
    And the foo-theme/foo-theme.pot file should not contain:
      """
      msgid "Other pattern description."
      """
