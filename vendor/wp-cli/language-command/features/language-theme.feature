Feature: Manage translation files for a WordPress install

  @require-wp-4.0
  Scenario: Theme translation CRUD
    Given a WP install
    And an empty cache

    When I try `wp theme install twentyten`
    Then STDOUT should not be empty

    When I run `wp theme is-installed twentyten`
    Then the return code should be 0

    When I run `wp language theme list twentyten --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status        |
      | cs_CZ     | Czech                   | uninstalled   |
      | de_DE     | German                  | uninstalled   |
      | en_US     | English (United States) | active        |
      | en_GB     | English (UK)            | uninstalled   |

    When I try `wp language theme is-installed twentyten en_GB`
    Then the return code should be 1
    And STDERR should be empty

    When I try `wp language theme is-installed twentyten de_DE`
    Then the return code should be 1
    And STDERR should be empty

    When I run `wp language theme install twentyten en_GB`
    Then the wp-content/languages/themes/twentyten-en_GB.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I run `wp language theme install twentyten cs_CZ de_DE`
    Then the wp-content/languages/themes/twentyten-cs_CZ.po file should exist
    And the wp-content/languages/themes/twentyten-de_DE.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 2 of 2 languages.
      """
    And STDERR should be empty

    When I try `wp language theme is-installed twentyten en_GB`
    Then the return code should be 0

    When I try `wp language theme is-installed twentyten de_DE`
    Then the return code should be 0

    When I run `ls {SUITE_CACHE_DIR}/translation | grep theme-twentyten-`
    Then STDOUT should contain:
      """
      de_DE
      """
    And STDOUT should contain:
      """
      en_GB
      """

    When I try `wp language theme install twentyten en_GB`
    Then STDERR should be empty
    And STDOUT should be:
      """
      Language 'en_GB' already installed.
      Success: Installed 0 of 1 languages (1 skipped).
      """
    And the return code should be 0

    When I run `wp language theme list twentyten --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status      |
      | cs_CZ     | Czech                   | installed   |
      | de_DE     | German                  | installed   |
      | en_GB     | English (UK)            | installed   |
      | en_US     | English (United States) | active      |

    When I run `wp language theme list twentyten --fields=language,english_name,update`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | update   |
      | cs_CZ     | Czech                   | none     |
      | de_DE     | German                  | none     |
      | en_GB     | English (UK)            | none     |
      | en_US     | English (United States) | none     |

    When I run `wp language theme update --all`
    Then STDOUT should contain:
      """
      Success: Translations are up to date.
      """
    And the wp-content/languages/themes directory should exist

    When I try `wp language core install en_GB --activate`
    Then STDOUT should contain:
      """
      Success: Language activated.
      """

    When I run `wp language theme list twentyten --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status        |
      | cs_CZ     | Czech                   | installed     |
      | de_DE     | German                  | installed     |
      | en_US     | English (United States) | installed     |
      | en_GB     | English (UK)            | active        |

    When I run `wp language theme uninstall twentyten cs_CZ de_DE`
    Then the wp-content/languages/themes/twentyten-cs_CZ.po file should not exist
    And the wp-content/languages/themes/twentyten-cs_CZ.mo file should not exist
    And the wp-content/languages/themes/twentyten-de_DE.po file should not exist
    And the wp-content/languages/themes/twentyten-de_DE.mo file should not exist
      """
      Success: Language uninstalled.
      Success: Language uninstalled.
      """

    When I try `wp language theme uninstall twentyten de_DE`
    Then STDERR should be:
      """
      Error: Language not installed.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp language theme install twentyten invalid_lang`
    Then STDERR should be:
      """
      Warning: Language 'invalid_lang' not available.
      """
    And STDOUT should be:
      """
      Language 'invalid_lang' not installed.
      Success: Installed 0 of 1 languages (1 skipped).
      """
    And the return code should be 0

  @require-wp-4.0
  Scenario: Don't allow active language to be uninstalled
    Given a WP install

    When I run `wp language core install en_GB --activate`
    Then STDOUT should not be empty

    When I run `wp language theme install twentyten en_GB`
    Then the wp-content/languages/themes/twentyten-en_GB.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I try `wp language theme uninstall twentyten en_GB`
    Then STDERR should be:
      """
      Warning: The 'en_GB' language is active.
      """
    And STDOUT should be empty
    And the return code should be 0

  @require-wp-4.0
  Scenario: Not providing theme slugs should throw an error unless --all given
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}

    When I try `wp language theme list`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more themes, or use --all.
      """
    And STDOUT should be empty

    When I try `wp language theme update`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more themes, or use --all.
      """
    And STDOUT should be empty

    Given an empty {THEME_DIR} directory
    When I run `wp language theme list --all`
    Then STDOUT should be:
      """
      Success: No themes installed.
      """

    When I run `wp language theme update --all`
    Then STDOUT should be:
      """
      Success: No themes installed.
      """

  @require-wp-4.0
  Scenario: Ensure correct language is installed for theme version
    Given a WP install
    And an empty cache
    And I run `wp theme install twentyseventeen --version=1.0 --force`

    When I run `wp language theme install twentyseventeen de_DE`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/theme/twentyseventeen/1.0/de_DE.zip
      """

    When I run `wp theme install twentyseventeen --version=1.6 --force`
    And I run `wp language theme update twentyseventeen`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/theme/twentyseventeen/1.6/de_DE.zip
      """

  @require-wp-4.0
  Scenario: Install translations for all installed themes
    Given a WP install
    And I run `wp theme path`
    And save STDOUT as {THEME_DIR}
    And an empty {THEME_DIR} directory
    And I run `wp theme install twentyseventeen --version=1.0 --force`
    And I run `wp theme install twentysixteen --version=1.0 --force`

    When I try `wp language theme install de_DE`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify a theme, or use --all.
      """
    And STDOUT should be empty

    When I run `wp language theme install --all de_DE --format=csv`
    Then the return code should be 0
    And STDOUT should be:
      """
      name,locale,status
      twentyseventeen,de_DE,installed
      twentysixteen,de_DE,installed
      """
    And STDERR should be empty

    When I run `wp language theme install --all de_DE --format=summary`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Success: Installed 0 of 2 languages (2 skipped).
      """
    And STDERR should be empty

    When I run `wp language theme install --all de_DE invalid_lang --format=csv`
    Then the return code should be 0
    And STDOUT should contain:
      """
      name,locale,status
      twentyseventeen,de_DE,"already installed"
      twentyseventeen,invalid_lang,"not available"
      twentysixteen,de_DE,"already installed"
      twentysixteen,invalid_lang,"not available"
      """
    And STDERR should be empty
