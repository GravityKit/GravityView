Feature: Generate MO files from PO files

  Background:
    Given a WP install

  Scenario: Bail for invalid source directories
    When I try `wp i18n make-mo foo`
    Then STDERR should contain:
      """
      Error: Source file or directory does not exist!
      """
    And the return code should be 1

  Scenario: Uses source folder as destination by default
    Given an empty foo-plugin directory
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

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Foo Plugin"
      """

    When I run `wp i18n make-mo foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE.mo file should exist

  Scenario: Allows setting custom destination directory
    Given an empty foo-plugin directory
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

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Foo Plugin"
      """

    When I run `wp i18n make-mo foo-plugin result`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the result/foo-plugin-de_DE.mo file should exist

  Scenario: Does include headers
    Given an empty foo-plugin directory
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

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Foo Plugin"
      """

    When I run `wp i18n make-mo foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE.mo file should contain:
      """
      Language: de_DE
      """
    And the foo-plugin/foo-plugin-de_DE.mo file should contain:
      """
      X-Domain: foo-plugin
      """

  Scenario: Does include translations
    Given an empty foo-plugin directory
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

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Bar Plugin"
      """

    When I run `wp i18n make-mo foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE.mo file should contain:
      """
      Bar Plugin
      """
