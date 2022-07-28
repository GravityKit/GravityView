Feature: Manage translation files for a WordPress install

  @require-wp-4.0
  Scenario: Core translation CRUD
    Given a WP install
    And an empty cache

    When I run `wp language core list --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status        |
      | ar        | Arabic                  | uninstalled   |
      | en_AU     | English (Australia)     | uninstalled   |
      | en_CA     | English (Canada)        | uninstalled   |
      | en_GB     | English (UK)            | uninstalled   |
      | en_US     | English (United States) | active        |
      | ja        | Japanese                | uninstalled   |

    When I try `wp language core is-installed en_GB`
    Then the return code should be 1
    And STDERR should be empty

    When I try `wp language core is-installed en_AU`
    Then the return code should be 1
    And STDERR should be empty

    When I run `wp language core install en_GB`
    And I run `wp language core install en_AU`
    Then the wp-content/languages/admin-en_GB.po file should exist
    And the wp-content/languages/en_GB.po file should exist
    And the wp-content/languages/admin-en_AU.po file should exist
    And the wp-content/languages/en_AU.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I try `wp language core is-installed en_GB`
    Then the return code should be 0

    When I try `wp language core is-installed en_AU`
    Then the return code should be 0

    When I run `wp language core install en_CA ja`
    Then the wp-content/languages/admin-en_CA.po file should exist
    And the wp-content/languages/en_CA.po file should exist
    And the wp-content/languages/admin-ja.po file should exist
    And the wp-content/languages/ja.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 2 of 2 languages.
      """
    And STDERR should be empty

    When I run `ls {SUITE_CACHE_DIR}/translation | grep core-default-`
    Then STDOUT should contain:
      """
      en_AU
      """
    And STDOUT should contain:
      """
      en_GB
      """

    When I try `wp language core install en_AU`
    Then STDOUT should be:
      """
      Language 'en_AU' already installed.
      Success: Installed 0 of 1 languages (1 skipped).
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `wp language core list --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name     | status        |
      | ar        | Arabic           | uninstalled   |
      | en_GB     | English (UK)     | installed     |

    When I run `wp site switch-language en_GB`
    Then STDOUT should be:
      """
      Success: Language activated.
      """

    When I run `wp language core list --fields=language,english_name,update`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | update   |
      | ar        | Arabic                  | none     |
      | en_AU     | English (Australia)     | none     |
      | en_CA     | English (Canada)        | none     |
      | en_US     | English (United States) | none     |
      | en_GB     | English (UK)            | none     |
      | ja        | Japanese                | none     |

    When I run `wp language core update`
    Then STDOUT should contain:
      """
      Success: Translations are up to date.
      """

    When I run `wp language core list --field=language --status=active`
    Then STDOUT should be:
      """
      en_GB
      """

    When I run `wp language core list --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name     | status        |
      | ar        | Arabic           | uninstalled   |
      | en_GB     | English (UK)     | active        |

    When I try `wp language core install en_AU --activate`
    Then STDERR should be empty
    And STDOUT should be:
      """
      Language 'en_AU' already installed.
      Success: Language activated.
      Success: Installed 0 of 1 languages (1 skipped).
      """
    And the return code should be 0

    When I try `wp language core install en_AU --activate`
    Then STDERR should contain:
      """
      Warning: Language 'en_AU' already active.
      """
    And STDOUT should contain:
      """
      Language 'en_AU' already installed.
      Success: Installed 0 of 1 languages (1 skipped).
      """
    And the return code should be 0

    When I try `wp language core install en_CA ja --activate`
    Then STDERR should be:
      """
      Error: Only a single language can be active.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp site switch-language en_US`
    Then STDOUT should be:
      """
      Success: Language activated.
      """

    When I run `wp language core list --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status        |
      | ar        | Arabic                  | uninstalled   |
      | en_US     | English (United States) | active        |
      | en_GB     | English (UK)            | installed     |

    When I try `wp site switch-language invalid_lang`
    Then STDERR should be:
      """
      Error: Language not installed.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp language core uninstall en_GB`
    Then the wp-content/languages/admin-en_GB.po file should not exist
    And the wp-content/languages/en_GB.po file should not exist
    And STDOUT should be:
      """
      Success: Language uninstalled.
      """

    When I run `wp language core uninstall en_CA ja`
     Then the wp-content/languages/admin-en_CA.po file should not exist
     And the wp-content/languages/en_CA.po file should not exist
     And the wp-content/languages/admin-ja.po file should not exist
     And the wp-content/languages/ja.po file should not exist
     And STDOUT should be:
       """
      Success: Language uninstalled.
      Success: Language uninstalled.
      """

    When I try `wp language core uninstall en_GB`
    Then STDERR should be:
      """
      Error: Language not installed.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp language core install en_GB --activate`
    Then the wp-content/languages/admin-en_GB.po file should exist
    And the wp-content/languages/en_GB.po file should exist
    And STDOUT should contain:
      """
      Success: Language activated.
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I try `wp language core install invalid_lang`
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

  @require-wp-latest @require-php-5.6 @less-than-php-7.0
  Scenario Outline: Core translation update
    Given an empty directory
    And WP files
    And a database
    And I run `wp core download --version=<original> --force`
    And wp-config.php
    And I run `wp core install --url='localhost:8001' --title='Test' --admin_user=wpcli --admin_email=admin@example.com --admin_password=1`

    When I run `wp language core list --fields=language,status,update`
    Then STDOUT should be a table containing rows:
      | language | status      | update    |
      | ar       | uninstalled | none      |
      | en_CA    | uninstalled | none      |
      | en_US    | active      | none      |
      | ja       | uninstalled | none      |

    When I run `wp language core install en_CA ja`
    Then the wp-content/languages/admin-en_CA.po file should exist
    And the wp-content/languages/en_CA.po file should exist
    And the wp-content/languages/admin-ja.po file should exist
    And the wp-content/languages/ja.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 2 of 2 languages.
      """
    And STDERR should be empty

    Given I try `wp core download --version=<update> --force`
    Then the return code should be 0
    And I run `wp core update-db`

    When I run `wp language core list --fields=language,status,update`
    Then STDOUT should be a table containing rows:
      | language | status      | update    |
      | ar       | uninstalled | none      |
      | en_CA    | installed   | available |
      | en_US    | active      | none      |
      | ja       | installed   | available |

    When I run `wp language core update --dry-run`
    Then STDOUT should contain:
      """
      Found 2 translation updates that would be processed
      """
    And STDOUT should contain:
      """
      Core
      """
    And STDOUT should contain:
      """
      WordPress
      """
    And STDOUT should contain:
      """
      <update>
      """
    And STDOUT should contain:
      """
      English (Canada)
      """
    And STDOUT should contain:
      """
      Japanese
      """

    Examples:
      | original | update |
      | 4.8      | 4.9    |
      | 4.0.1    | 4.2    |

  @require-wp-4.0
  Scenario: Don't allow active language to be uninstalled
    Given a WP install

    When I run `wp language core install en_GB --activate`
    Then STDOUT should not be empty

    When I try `wp language core uninstall en_GB`
    Then STDERR should be:
      """
      Warning: The 'en_GB' language is active.
      """
    And STDOUT should be empty
    And the return code should be 0

  @require-wp-4.0
  Scenario: Ensure correct language is installed for WP version
    Given a WP install
    And I run `wp theme activate twentytwenty`
    And an empty cache
    And I run `wp core download --version=4.5.3 --force`

    When I run `wp language core install nl_NL`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/core/4.5.3
      """

  @require-wp-4.0
  Scenario: Ensure upgrader output is in English
    Given a WP install
    And I run `wp theme activate twentytwenty`
    And an empty cache
    And I run `wp core download --version=5.4.1 --force`
    And a disable_sidebar_check.php file:
      """
      <?php
      WP_CLI::add_wp_hook( 'init', static function () {
        remove_action( 'after_switch_theme', '_wp_sidebars_changed' );
      } );
      """

    When I run `wp language core install de_DE --activate --require=disable_sidebar_check.php`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/core/5.4.1/de_DE.zip
      """

    When I run `wp language core install nl_NL`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/core/5.4.1/nl_NL.zip
      """
    And STDOUT should contain:
      """
      Installing the latest version
      """
    And STDOUT should not contain:
      """
      Lädt Übersetzung von https://downloads.wordpress.org/translation/core/5.4.1./nl_NL.zip
      """
    And STDOUT should not contain:
      """
      Die aktuelle Version wird installiert
      """

    @require-wp-4.0
    Scenario: Show correct active language after switching
      Given a WP install
      And an empty cache

      When I try `wp language core is-installed en_US`
      Then the return code should be 0

      When I run `wp language core install de_DE`
      Then the wp-content/languages/admin-de_DE.po file should exist
      And the wp-content/languages/de_DE.po file should exist
      And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
      And STDERR should be empty

      When I try `wp language core is-installed de_DE`
      Then the return code should be 0

      When I run `wp language core list --field=language --status=active`
      Then STDOUT should be:
      """
      en_US
      """

      When I run `wp site switch-language de_DE`
      Then STDOUT should be:
      """
      Success: Language activated.
      """

      When I run `wp language core list --field=language --status=active`
      Then STDOUT should be:
      """
      de_DE
      """

      When I run `wp site switch-language en_US`
      Then STDOUT should be:
      """
      Success: Language activated.
      """

      When I run `wp language core list --field=language --status=active`
      Then STDOUT should be:
      """
      en_US
      """

  @require-wp-4.0
  Scenario: Switch to formal language variant
    Given a WP install
    And an empty cache

    When I run `wp language core install de_DE_formal`
    Then the wp-content/languages/admin-de_DE_formal.po file should exist
    And the wp-content/languages/de_DE_formal.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I try `wp language core is-installed de_DE_formal`
    Then the return code should be 0

    When I run `wp language core list --field=language --status=active`
    Then STDOUT should be:
      """
      en_US
      """

    When I run `wp site switch-language de_DE_formal`
    Then STDOUT should be:
      """
      Success: Language activated.
      """

    When I run `wp language core list --field=language --status=active`
    Then STDOUT should be:
      """
      de_DE_formal
      """

    When I run `wp site switch-language en_US`
    Then STDOUT should be:
      """
      Success: Language activated.
      """

    When I run `wp language core list --field=language --status=active`
    Then STDOUT should be:
      """
      en_US
      """
