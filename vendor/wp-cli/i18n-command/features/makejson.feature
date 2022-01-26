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
  
  Scenario: Should translate with single map file
    Given an empty foo-plugin directory
    And an empty foo-plugin/build directory
    And an empty foo-plugin/languages directory
    And a foo-plugin/build/map.json file:
      """
      {
        "src/index.js": "build/index.js"
      }
      """
    And a foo-plugin/languages/foo-plugin-de_DE.po file:
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

      #: src/index.js:2
      msgid "Title"
      msgstr "Titel"
      """
    
    When I run `wp i18n make-json languages --use-map=build/map.json` from 'foo-plugin'
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/languages/foo-plugin-de_DE-dfbff627e6c248bcb3b61d7d06da9ca9.json file should contain:
      """
      "source":"build\/index.js"
      """
  
  Scenario: Should translate with custom map files, mapping one input to multiple outputs
    Given an empty foo-plugin directory
    And an empty foo-plugin/build directory
    And an empty foo-plugin/languages directory
    And a foo-plugin/build/map1.json file:
      """
      {
        "src/index.js": "build/index.js"
      }
      """
    And a foo-plugin/build/map2.json file:
      """
      {
        "src/index.js": "build/other.js"
      }
      """
    And a foo-plugin/languages/foo-plugin-de_DE.po file:
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

      #: src/index.js:2
      msgid "Title"
      msgstr "Titel"
      """
    
    When I run `wp i18n make-json languages '--use-map=["build/map1.json","build/map2.json"]'` from 'foo-plugin'
    Then STDOUT should contain:
      """
      Success: Created 2 files.
      """
    And the return code should be 0
    And the foo-plugin/languages/foo-plugin-de_DE-dfbff627e6c248bcb3b61d7d06da9ca9.json file should contain:
      """
      "source":"build\/index.js"
      """
    And the foo-plugin/languages/foo-plugin-de_DE-62776f0ea873de0638d56fc239bc486d.json file should contain:
      """
      "source":"build\/other.js"
      """
  
  Scenario: Should remove translations not mapped
    Given an empty foo-plugin directory
    And an empty foo-plugin/build directory
    And an empty foo-plugin/languages directory
    And a foo-plugin/build/map.json file:
      """
      {
        "src/index.js": "build/index.js"
      }
      """
    And a foo-plugin/languages/foo-plugin-de_DE.po file:
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

      #: src/other.js:5
      msgid "Text"
      msgstr "Text"
      """
    
    When I try `wp i18n make-json languages --use-map=build/map.json` from 'foo-plugin'
    Then STDOUT should contain:
      """
      Success: Created 0 files.
      """
    And the return code should be 0
  
  Scenario: Should ignore nonexistant files given as map
    Given an empty foo-plugin directory

    When I try `wp i18n make-json foo-plugin --use-map=build/map.json`
    Then STDERR should contain:
      """
      Map file build/map.json does not exist
      """
    And STDERR should contain:
      """
      No valid keys found. No file was created.
      """
  
  Scenario: Should ignore invalid files given as map
    Given an empty foo-plugin directory
    And a foo-plugin/invalid.json file:
      """
      true
      """

    When I try `wp i18n make-json foo-plugin --use-map=foo-plugin/invalid.json`
    Then STDERR should contain:
      """
      Map file foo-plugin/invalid.json invalid
      """
  
  Scenario: Should be able to use given objects as map
    Given an empty foo-plugin directory
    And an empty foo-plugin/languages directory
    And a foo-plugin/languages/foo-plugin-de_DE.po file:
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

      #: src/index.js:2
      msgid "Title"
      msgstr "Titel"
      """
    
    When I run `wp i18n make-json languages '--use-map={"src/index.js": "build/index.js"}'` from 'foo-plugin'
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/languages/foo-plugin-de_DE-dfbff627e6c248bcb3b61d7d06da9ca9.json file should contain:
      """
      "source":"build\/index.js"
      """
  
  Scenario: Should translate with custom map file and inline map, mapping one input to multiple outputs
    Given an empty foo-plugin directory
    And an empty foo-plugin/build directory
    And an empty foo-plugin/languages directory
    And a foo-plugin/build/map.json file:
      """
      {
        "src/index.js": "build/other.js"
      }
      """
    And a foo-plugin/languages/foo-plugin-de_DE.po file:
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

      #: src/index.js:2
      msgid "Title"
      msgstr "Titel"
      """
    
    When I run `wp i18n make-json languages '--use-map=[{"src/index.js": "build/index.js"},"build/map.json"]'` from 'foo-plugin'
    Then STDOUT should contain:
      """
      Success: Created 2 files.
      """
    And the return code should be 0
    And the foo-plugin/languages/foo-plugin-de_DE-dfbff627e6c248bcb3b61d7d06da9ca9.json file should contain:
      """
      "source":"build\/index.js"
      """
    And the foo-plugin/languages/foo-plugin-de_DE-62776f0ea873de0638d56fc239bc486d.json file should contain:
      """
      "source":"build\/other.js"
      """


