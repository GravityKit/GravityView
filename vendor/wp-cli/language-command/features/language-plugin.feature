Feature: Manage translation files for a WordPress install

  @require-wp-4.0
  Scenario: Plugin translation CRUD
    Given a WP install
    And an empty cache

    When I run `wp plugin install hello-dolly`
    Then STDOUT should contain:
      """
      Plugin installed successfully.
      """
    And STDERR should be empty

    When I run `wp language plugin list hello-dolly --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status        |
      | cs_CZ     | Czech                   | uninstalled   |
      | de_DE     | German                  | uninstalled   |
      | en_US     | English (United States) | active        |
      | en_GB     | English (UK)            | uninstalled   |

    When I try `wp language plugin is-installed hello-dolly en_GB`
    Then the return code should be 1
    And STDERR should be empty

    When I try `wp language plugin is-installed hello-dolly de_DE`
    Then the return code should be 1
    And STDERR should be empty

    When I run `wp language plugin install hello-dolly en_GB`
    Then the wp-content/languages/plugins/hello-dolly-en_GB.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I run `wp language plugin install hello-dolly cs_CZ de_DE`
    Then the wp-content/languages/plugins/hello-dolly-cs_CZ.po file should exist
    And the wp-content/languages/plugins/hello-dolly-de_DE.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 2 of 2 languages.
      """
    And STDERR should be empty

    When I try `wp language plugin is-installed hello-dolly en_GB`
    Then the return code should be 0

    When I try `wp language plugin is-installed hello-dolly de_DE`
    Then the return code should be 0

    When I run `ls {SUITE_CACHE_DIR}/translation | grep plugin-hello-dolly-`
    Then STDOUT should contain:
      """
      de_DE
      """
    And STDOUT should contain:
      """
      en_GB
      """

    When I try `wp language plugin install hello-dolly en_GB`
    Then STDERR should be empty
    And STDOUT should be:
      """
      Language 'en_GB' already installed.
      Success: Installed 0 of 1 languages (1 skipped).
      """
    And the return code should be 0

    When I run `wp language plugin list hello-dolly --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | status      |
      | cs_CZ     | Czech                   | installed   |
      | de_DE     | German                  | installed   |
      | en_US     | English (United States) | active      |
      | en_GB     | English (UK)            | installed   |

    When I run `wp language plugin list hello-dolly --fields=language,english_name,update`
    Then STDOUT should be a table containing rows:
      | language  | english_name            | update   |
      | cs_CZ     | Czech                   | none     |
      | de_DE     | German                  | none     |
      | en_US     | English (United States) | none     |
      | en_GB     | English (UK)            | none     |

    When I run `wp language plugin update --all`
    Then STDOUT should contain:
      """
      Success: Translations are up to date.
      """
    And the wp-content/languages/plugins directory should exist

    When I try `wp language core install en_GB --activate`
    Then STDOUT should contain:
      """
      Success: Language activated.
      """

    When I run `wp language plugin list hello-dolly --field=language --status=active`
    Then STDOUT should be:
      """
      en_GB
      """

    When I run `wp language plugin list hello-dolly --fields=language,english_name,status`
    Then STDOUT should be a table containing rows:
      | language  | english_name     | status        |
      | de_DE     | German           | installed     |
      | en_GB     | English (UK)     | active        |
      | fr_FR     | French (France)  | uninstalled   |

    When I run `wp language plugin uninstall hello-dolly cs_CZ de_DE`
    Then the wp-content/languages/plugins/hello-dolly-cs_CZ.po file should not exist
    And the wp-content/languages/plugins/hello-dolly-cs_CZ.mo file should not exist
    And the wp-content/languages/plugins/hello-dolly-de_DE.po file should not exist
    And the wp-content/languages/plugins/hello-dolly-de_DE.mo file should not exist
    And STDOUT should be:
      """
      Success: Language uninstalled.
      Success: Language uninstalled.
      """

    When I try `wp language plugin uninstall hello-dolly fr_FR`
    Then STDERR should be:
      """
      Error: Language not installed.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp language plugin uninstall hello-dolly en_GB`
    Then STDERR should be:
      """
      Warning: The 'en_GB' language is active.
      """
    And STDOUT should be empty
    And the return code should be 0

    When I try `wp language plugin install hello-dolly invalid_lang`
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

    When I run `wp language plugin install hello-dolly en_GB`
    Then the wp-content/languages/plugins/hello-dolly-en_GB.po file should exist
    And STDOUT should contain:
      """
      Success: Installed 1 of 1 languages.
      """
    And STDERR should be empty

    When I try `wp language plugin uninstall hello-dolly en_GB`
    Then STDERR should be:
      """
      Warning: The 'en_GB' language is active.
      """
    And STDOUT should be empty
    And the return code should be 0

  @require-wp-4.0
  Scenario: Not providing plugin slugs should throw an error unless --all given
    Given a WP install
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}

    When I try `wp language plugin list`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more plugins, or use --all.
      """
    And STDOUT should be empty

    When I try `wp language plugin update`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify one or more plugins, or use --all.
      """
    And STDOUT should be empty

    Given an empty {PLUGIN_DIR} directory
    When I run `wp language plugin list --all`
    Then STDOUT should be:
      """
      Success: No plugins installed.
      """

    When I run `wp language plugin update --all`
    Then STDOUT should be:
      """
      Success: No plugins installed.
      """

  @require-wp-4.0
  Scenario: Ensure correct language is installed for plugin version
    Given a WP install
    And an empty cache
    And I run `wp plugin install akismet --version=3.2 --force`
    And I run `wp plugin install jetpack --version=6.0 --force`

    When I run `wp language plugin install akismet de_DE_formal`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/akismet/3.2/de_DE_formal.zip
      """
    And STDOUT should not contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/jetpack
      """
    And STDERR should be empty

    When I run `wp language plugin list --all --fields=plugin,language,update,status`
    Then STDOUT should be a table containing rows:
      | plugin   | language     | update | status      |
      | akismet  | de_DE_formal | none   | installed   |
      | jetpack  | de_DE        | none   | uninstalled |

    When I run `wp language plugin install jetpack de_DE`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/jetpack/6.0/de_DE.zip
      """
    And STDOUT should not contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/akismet
      """
    And STDERR should be empty

    When I run `wp language plugin list --all --fields=plugin,language,update,status`
    Then STDOUT should be a table containing rows:
      | plugin   | language     | update | status    |
      | akismet  | de_DE_formal | none   | installed |
      | jetpack  | de_DE        | none   | installed |

    When I run `wp plugin install akismet --version=4.0 --force`
    And I run `wp plugin install jetpack --version=6.4 --force`

    When I run `wp language plugin list --all --fields=plugin,language,update,status`
    Then STDOUT should be a table containing rows:
      | plugin   | language     | update    | status    |
      | akismet  | de_DE_formal | available | installed |
      | jetpack  | de_DE        | available | installed |

    When I run `wp language plugin update akismet`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/akismet/4.0/de_DE_formal.zip
      """
    And STDOUT should not contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/jetpack
      """
    And STDERR should be empty

    When I run `wp language plugin list --all --fields=plugin,language,update,status`
    Then STDOUT should be a table containing rows:
      | plugin   | language     | update    | status    |
      | akismet  | de_DE_formal | none      | installed |
      | jetpack  | de_DE        | available | installed |

    When I run `wp language plugin update jetpack`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/jetpack/6.4/de_DE.zip
      """
    And STDERR should be empty

    When I run `wp language plugin list --all --fields=plugin,language,update,status`
    Then STDOUT should be a table containing rows:
      | plugin   | language     | update | status    |
      | akismet  | de_DE_formal | none   | installed |
      | jetpack  | de_DE        | none   | installed |

  @require-wp-4.0
  Scenario: Ensure availability status is correct for each plugin
    Given a WP install
    And an empty cache
    And I run `wp plugin install akismet --version=3.2 --force`
    And I run `wp plugin install jetpack --version=6.0 --force`

    When I run `wp language plugin install akismet de_DE`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/akismet/3.2/de_DE.zip
      """

    When I run `wp language plugin install jetpack de_DE`
    And STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/jetpack/6.0/de_DE.zip
      """
    And STDERR should be empty

    When I run `wp plugin install akismet --version=4.0 --force`
    And I run `wp plugin install jetpack --version=6.4 --force`
    And I run `wp language plugin update jetpack`
    Then STDOUT should contain:
      """
      Downloading translation from https://downloads.wordpress.org/translation/plugin/jetpack/6.4/de_DE.zip
      """

    When I run `wp language plugin list --all --fields=plugin,language,update,status --status=installed`
    Then STDOUT should be a table containing rows:
      | plugin   | language | update    | status    |
      | akismet  | de_DE    | available | installed |
      | jetpack  | de_DE    | none      | installed |
    And STDERR should be empty


  @require-wp-4.0
  Scenario: Install translations for all installed plugins
    Given a WP install
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And an empty {PLUGIN_DIR} directory
    And I run `wp plugin install akismet --version=4.0 --force`
    And I run `wp plugin install jetpack --version=6.4 --force`

    When I try `wp language plugin install de_DE`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Please specify a plugin, or use --all.
      """
    And STDOUT should be empty

    When I run `wp language plugin install --all de_DE --format=csv`
    Then the return code should be 0
    And STDOUT should be:
      """
      name,locale,status
      akismet,de_DE,installed
      jetpack,de_DE,installed
      """
    And STDERR should be empty

    When I run `wp language plugin install --all de_DE --format=summary`
    Then the return code should be 0
    And STDOUT should contain:
      """
      Success: Installed 0 of 2 languages (2 skipped).
      """
    And STDERR should be empty

    When I run `wp language plugin install --all de_DE invalid_lang --format=csv`
    Then the return code should be 0
    And STDOUT should contain:
      """
      name,locale,status
      akismet,de_DE,"already installed"
      akismet,invalid_lang,"not available"
      jetpack,de_DE,"already installed"
      jetpack,invalid_lang,"not available"
      """
    And STDERR should be empty
