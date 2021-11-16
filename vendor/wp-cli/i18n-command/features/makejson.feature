Feature: Split PO files into JSON files.

  Background:
    Given a WP install

  Scenario: Bail for invalid source file or directory
    When I try `wp i18n make-json foo`
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

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should exist

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

    When I run `wp i18n make-json foo-plugin result`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the result/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should exist

  Scenario: Sets some meta data
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

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "translation-revision-date":
      """
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "generator":"WP-CLI
      """
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "source":"foo-plugin.js"
      """

  Scenario: Always uses messages as text domain
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

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "domain":"messages"
      """
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "messages":{
      """

  Scenario: Sets correct plural form
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
      "Plural-Forms: nplurals=3; plural=(n != 2);\n"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Foo Plugin"
      """

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "plural-forms":"nplurals=3; plural=(n != 2);"
      """

  Scenario: Sets default plural form if missing
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

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "plural-forms":"nplurals=2; plural=(n != 1);"
      """

  Scenario: Splits PO file into multiple JSON files
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

      #: a.js:10
      msgid "A"
      msgstr "A"

      #: b.js:10
      msgid "B"
      msgstr "B"

      #: a.js:10
      #: b.js:10
      msgid "C"
      msgstr "C"

      #: foo-plugin.php:10
      msgid "D"
      msgstr "D"
      """

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 2 files.
      """
    And the return code should be 0

    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should exist
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should contain:
      """
      "A"
      """
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should contain:
      """
      "C"
      """
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should not contain:
      """
      "B"
      """
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should not contain:
      """
      "D"
      """

    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should exist
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should contain:
      """
      "B"
      """
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should contain:
      """
      "C"
      """
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should not contain:
      """
      "A"
      """
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should not contain:
      """
      "D"
      """

    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      "D"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should not contain:
      """
      "A"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should not contain:
      """
      "B"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should not contain:
      """
      "C"
      """

  Scenario: Does not remove strings from original PO file
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

      #: a.js:10
      msgid "A"
      msgstr "A"

      #: b.js:10
      msgid "B"
      msgstr "B"

      #: a.js:10
      #: b.js:10
      msgid "C"
      msgstr "C"

      #: foo-plugin.php:10
      msgid "D"
      msgstr "D"
      """

    When I run `wp i18n make-json foo-plugin --no-purge`
    Then STDOUT should contain:
      """
      Success: Created 2 files.
      """
    And the return code should be 0

    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should exist
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should contain:
      """
      "A"
      """
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should contain:
      """
      "C"
      """
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should not contain:
      """
      "B"
      """
    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should not contain:
      """
      "D"
      """

    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should exist
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should contain:
      """
      "B"
      """
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should contain:
      """
      "C"
      """
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should not contain:
      """
      "A"
      """
    And the foo-plugin/foo-plugin-de_DE-656ad21ad877025a82411b49aa0f8b88.json file should not contain:
      """
      "D"
      """

    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      "D"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      "A"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      "B"
      """
    And the foo-plugin/foo-plugin-de_DE.po file should contain:
      """
      "C"
      """

  Scenario: Does generate or update MO files
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

      #: a.js:10
      msgid "A"
      msgstr "A"

      #: b.js:10
      msgid "B"
      msgstr "B"

      #: a.js:10
      #: b.js:10
      msgid "C"
      msgstr "C"

      #: foo-plugin.php:10
      msgid "D"
      msgstr "D"
      """

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 2 files.
      """
    And the return code should be 0

    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should exist
    And the foo-plugin/foo-plugin-de_DE.mo file should exist

  Scenario: Does not generate or update MO files
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

      #: a.js:10
      msgid "A"
      msgstr "A"

      #: b.js:10
      msgid "B"
      msgstr "B"

      #: a.js:10
      #: b.js:10
      msgid "C"
      msgstr "C"

      #: foo-plugin.php:10
      msgid "D"
      msgstr "D"
      """

    When I run `wp i18n make-json foo-plugin --no-update-mo-files`
    Then STDOUT should contain:
      """
      Success: Created 2 files.
      """
    And the return code should be 0

    And the foo-plugin/foo-plugin-de_DE-95f0a310f289230d56c3a4949c17963e.json file should exist
    And the foo-plugin/foo-plugin-de_DE.mo file should not exist

  Scenario: Correctly saves strings with context
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
      msgctxt "Plugin Name"
      msgid "Foo Plugin (EN)"
      msgstr "Foo Plugin (DE)"
      """

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "domain":"messages"
      """
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "Plugin Name\u0004Foo Plugin (EN)":["Foo Plugin (DE)"]
      """

  Scenario: Should create pretty-printed JSON files
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

    When I run `wp i18n make-json foo-plugin --pretty-print`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE-56746e49c6485323d16a717754b7447e.json file should contain:
      """
          "domain": "messages",
          "locale_data": {
              "messages": {
                  "": {
                      "domain": "messages",
                      "lang": "de_DE",
                      "plural-forms": "nplurals=2; plural=(n != 1);"
                  },
                  "Foo Plugin": [
                      "Foo Plugin"
                  ]
              }
          }
      """

  Scenario: Should not error for invalid languages
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin-invalid.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: invalid\n"
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

    When I run `wp i18n make-json foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-invalid-56746e49c6485323d16a717754b7447e.json file should contain:
      """
      "lang":"invalid"
      """
