Feature: Update existing PO files from a POT file

  Background:
    Given an empty directory

  Scenario: Bail for invalid source file
    When I try `wp i18n update-po bar/baz.pot`
    Then STDERR should contain:
      """
      Error: Source file does not exist!
      """
    And the return code should be 1

  Scenario: Does nothing if there are no PO files
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

    When I run `wp i18n update-po foo-plugin/foo-plugin.pot`
    Then STDOUT should be:
      """
      Success: Updated 0 files.
      """
    And STDERR should be empty

  Scenario: Updates all PO files in the source directory by default
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

      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr ""

      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""

      #: foo-plugin.php:30
      msgid "You have %d new message"
      msgid_plural "You have %d new messages"
      """
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #. translators: Old Comment.
      #: foo-plugin.php:10
      msgid "Some string"
      msgstr "Some translated string"

      #: foo-plugin.php:60
      msgid "You have %d new message"
      msgid_plural "You have %d new messages"
      msgstr[0] "Sie haben %d neue Nachricht"
      msgstr[1] "Sie haben %d neue Nachrichten"
      """

    When I run `wp i18n update-po foo-plugin/foo-plugin.pot`
    Then STDOUT should be:
      """
      Success: Updated 1 file.
      """
    And STDERR should be empty
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr "Some translated string"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""
      """
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      #: foo-plugin.php:30
      msgid "You have %d new message"
      msgid_plural "You have %d new messages"
      msgstr[0] "Sie haben %d neue Nachricht"
      msgstr[1] "Sie haben %d neue Nachrichten"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should not contain:
      """
      #. translators: Old Comment.
      """
    And the foo-plugin/foo-plugin-de_DE.po file should not contain:
      """
      #: foo-plugin.php:10
      """

  Scenario: Updates the specified target PO file
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

      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr ""

      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""
      """
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #. translators: Old Comment.
      #: foo-plugin.php:10
      msgid "Some string"
      msgstr "Some translated string"
      """
    And a foo-plugin/foo-plugin-es_ES.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #. translators: Old Comment.
      #: foo-plugin.php:10
      msgid "Some string"
      msgstr "Some translated string"
      """

    When I run `wp i18n update-po foo-plugin/foo-plugin.pot foo-plugin/foo-plugin-de_DE.po`
    Then STDOUT should be:
      """
      Success: Updated 1 file.
      """
    And STDERR should be empty
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr "Some translated string"

      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""
      """
    And the foo-plugin/foo-plugin-es_ES.po file should contain:
      """
      #. translators: Old Comment.
      """
    And the foo-plugin/foo-plugin-es_ES.po file should contain:
      """
      #: foo-plugin.php:10
      """

  Scenario: Updates all PO files in the specified target directory
    Given an empty source directory
    And an empty target directory
    And a source/foo-plugin.pot file:
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

      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr ""

      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""
      """
    And a target/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #. translators: Old Comment.
      #: foo-plugin.php:10
      msgid "Some string"
      msgstr "Some translated string"
      """
    And a target/foo-plugin-es_ES.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #. translators: Old Comment.
      #: foo-plugin.php:10
      msgid "Some string"
      msgstr "Some translated string"
      """

    When I run `wp i18n update-po source/foo-plugin.pot target`
    Then STDOUT should be:
      """
      Success: Updated 2 files.
      """
    And STDERR should be empty
    And the target/foo-plugin-de_DE.po file should contain:
      """
      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr "Some translated string"

      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""
      """
    And the target/foo-plugin-es_ES.po file should contain:
      """
      #. translators: New Comment.
      #: foo-plugin.php:1
      msgid "Some string"
      msgstr "Some translated string"

      #: foo-plugin.php:15
      msgid "Another new string"
      msgstr ""
      """
