Feature: Install WP-CLI packages

  Background:
    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

  Scenario: Install a package with an http package index url in package composer.json
    Given an empty directory
    And a composer.json file:
      """
      {
        "repositories": {
          "test" : {
            "type": "path",
            "url": "./dummy-package/"
          },
          "wp-cli": {
            "type": "composer",
            "url": "http://wp-cli.org/package-index/"
          }
        }
      }
      """
    And a dummy-package/composer.json file:
	  """
	  {
	    "name": "wp-cli/restful",
	    "description": "This is a dummy package we will install instead of actually installing the real package. This prevents the test from hanging indefinitely for some reason, even though it passes. The 'name' must match a real package as it is checked against the package index."
	  }
	  """
    When I run `WP_CLI_PACKAGES_DIR=. wp package install wp-cli/restful`
    Then STDOUT should contain:
	  """
	  Updating package index repository url...
	  """
    And STDOUT should contain:
	  """
	  Success: Package installed
	  """
    And the composer.json file should contain:
      """
      "url": "https://wp-cli.org/package-index/"
      """
    And the composer.json file should not contain:
      """
      "url": "http://wp-cli.org/package-index/"
      """

  @require-php-5.6
  Scenario: Install a package with 'wp-cli/wp-cli' as a dependency
    Given a WP install

    When I run `wp package install wp-cli-test/test-command:v0.2.0`
    Then STDOUT should contain:
      """
      Success: Package installed
      """
    And STDOUT should not contain:
      """
      requires wp-cli/wp-cli
      """

    When I run `wp test-command`
    Then STDOUT should contain:
      """
      Version C.
      """

  @require-php-5.6
  Scenario: Install a package with a dependency
    Given an empty directory

    When I run `wp package install yoast/wp-cli-faker`
    And STDOUT should contain:
      """
      Success: Package installed
      """
    And the {PACKAGE_PATH}/vendor/yoast directory should contain:
      """
      wp-cli-faker
      """
    And the {PACKAGE_PATH}/vendor/fzaninotto directory should contain:
      """
      faker
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                |
      | yoast/wp-cli-faker  |
    And STDOUT should not contain:
      """
      fzaninotto/faker
      """

    When I run `wp package uninstall yoast/wp-cli-faker`
    Then STDOUT should contain:
      """
      Removing require statement
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """
    And the {PACKAGE_PATH}/vendor directory should not contain:
      """
      yoast
      """
    And the {PACKAGE_PATH}/vendor directory should not contain:
      """
      fzaninotto
      """

    When I run `wp package list`
    Then STDOUT should not contain:
      """
      trendwerk/faker
      """

  @github-api
  Scenario: Install a package from a Git URL
    Given an empty directory

    When I try `wp package install git@github.com:wp-cli-test/repository-name.git`
    Then the return code should be 0
    And STDERR should contain:
      """
      Warning: Package name mismatch...Updating from git name 'wp-cli-test/repository-name' to composer.json name 'wp-cli-test/package-name'.
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the {PACKAGE_PATH}composer.json file should contain:
      """
      "wp-cli-test/package-name": "dev-master"
      """

    When I try `wp package install git@github.com:wp-cli.git`
    Then STDERR should be:
      """
      Error: Couldn't parse package name from expected path '<name>/<package>'.
      """

    When I run `wp package install git@github.com:wp-cli/google-sitemap-generator-cli.git`
    Then STDOUT should contain:
      """
      Installing package wp-cli/google-sitemap-generator-cli (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering git@github.com:wp-cli/google-sitemap-generator-cli.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                                |
      | wp-cli/google-sitemap-generator-cli |

    When I run `wp google-sitemap`
    Then STDOUT should contain:
      """
      usage: wp google-sitemap rebuild
      """

    When I run `wp package uninstall wp-cli/google-sitemap-generator-cli`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    Then STDOUT should contain:
      """
      Removing repository details from
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      "wp-cli/google-sitemap-generator-cli": "dev-master"
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      "url": "git@github.com:wp-cli/google-sitemap-generator-cli.git"
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli/google-sitemap-generator-cli
      """

  @github-api
  Scenario: Install a package from a Git URL with mixed-case git name but lowercase composer.json name
    Given an empty directory

    When I try `wp package install https://github.com/CapitalWPCLI/examplecommand.git`
    Then the return code should be 0
    And STDERR should contain:
      """
      Warning: Package name mismatch...Updating from git name 'CapitalWPCLI/examplecommand' to composer.json name 'capitalwpcli/examplecommand'.
      """
    And STDOUT should contain:
      """
      Installing package capitalwpcli/examplecommand (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/CapitalWPCLI/examplecommand.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the {PACKAGE_PATH}composer.json file should contain:
      """
      "capitalwpcli/examplecommand"
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      "CapitalWPCLI/examplecommand"
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                        |
      | capitalwpcli/examplecommand |

    When I run `wp hello-world`
    Then STDOUT should contain:
      """
      Success: Hello world.
      """

  @github-api
  Scenario: Install a package from a Git URL with mixed-case git name and the same mixed-case composer.json name
    Given an empty directory

    When I run `wp package install https://github.com/gitlost/TestMixedCaseCommand.git`
    Then STDERR should be empty
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the contents of the {PACKAGE_PATH}composer.json file should match /\"gitlost\/(?:TestMixedCaseCommand|testmixedcasecommand)\"/

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                         |
      | gitlost/TestMixedCaseCommand |

    When I run `wp TestMixedCaseCommand`
    Then STDOUT should contain:
      """
      Success: Test Mixed Case Command Name
      """

  @github-api @shortened
  Scenario: Install a package from Git using a shortened package identifier
    Given an empty directory

    When I run `wp package install wp-cli-test/github-test-command`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/github-test-command (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/wp-cli-test/github-test-command.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name,version`
    Then STDOUT should be a table containing rows:
      | name                            | version    |
      | wp-cli-test/github-test-command | dev-master |

    When I run `wp test-command`
    Then STDOUT should contain:
      """
      Success: Version E.
      """

    When I run `wp package uninstall wp-cli-test/github-test-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli-test/github-test-command
      """

  @github-api @shortened
  Scenario: Install a package from Git using a shortened package identifier with a version requirement
    Given an empty directory

    When I try `wp package install wp-cli-test/github-test-command:^0`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/github-test-command (^0)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/wp-cli-test/github-test-command.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name,version`
    Then STDOUT should be a table containing rows:
      | name                            | version |
      | wp-cli-test/github-test-command | v0.2.0  |

    When I run `wp test-command`
    Then STDOUT should contain:
      """
      Success: Version C.
      """

    When I run `wp package uninstall wp-cli-test/github-test-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli-test/github-test-command
      """

  @github-api @shortened
  Scenario: Install a package from Git using a shortened package identifier with a specific version
    Given an empty directory

    # Need to specify actual tag.
    When I try `wp package install wp-cli-test/github-test-command:0.1.0`
    Then STDERR should contain:
      """
      Warning: Couldn't download composer.json file from 'https://raw.githubusercontent.com/wp-cli-test/github-test-command/0.1.0/composer.json' (HTTP code 404).
      """

    When I run `wp package install wp-cli-test/github-test-command:v0.1.0`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/github-test-command (v0.1.0)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/wp-cli-test/github-test-command.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name,version`
    Then STDOUT should be a table containing rows:
      | name                            | version |
      | wp-cli-test/github-test-command | v0.1.0  |

    When I run `wp test-command`
    Then STDOUT should contain:
      """
      Success: Version A.
      """

    When I run `wp package uninstall wp-cli-test/github-test-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli-test/github-test-command
      """

  @github-api @shortened
  Scenario: Install a package from Git using a shortened package identifier and a specific commit hash
    Given an empty directory

    When I run `wp package install wp-cli-test/github-test-command:dev-master#bcfac95e2193e9f5f8fbd3004fab9d902a5e4de3`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/github-test-command (dev-master#bcfac95e2193e9f5f8fbd3004fab9d902a5e4de3)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/wp-cli-test/github-test-command.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name,version`
    Then STDOUT should be a table containing rows:
      | name                            | version    |
      | wp-cli-test/github-test-command | dev-master |

    When I run `wp test-command`
    Then STDOUT should contain:
      """
      Success: Version B.
      """

    When I run `wp package uninstall wp-cli-test/github-test-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli-test/github-test-command
      """

  @github-api @shortened
  Scenario: Install a package from Git using a shortened package identifier and a branch
    Given an empty directory

    When I run `wp package install wp-cli-test/github-test-command:dev-custom-branch`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/github-test-command (dev-custom-branch)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/wp-cli-test/github-test-command.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name,version`
    Then STDOUT should be a table containing rows:
      | name                            | version           |
      | wp-cli-test/github-test-command | dev-custom-branch |

    When I run `wp test-command`
    Then STDOUT should contain:
      """
      Success: Version D.
      """

    When I run `wp package uninstall wp-cli-test/github-test-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli-test/github-test-command
      """

  @github-api
  Scenario: Install a package from the wp-cli package index with a mixed-case name
    Given an empty directory

    # Install and uninstall with case-sensitive name
    When I run `wp package install GeekPress/wp-rocket-cli`
    Then STDERR should be empty
    And STDOUT should match /Installing package (?:GeekPress|geekpress)\/wp-rocket-cli \(dev-master\)/
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the contents of the {PACKAGE_PATH}composer.json file should match /("?:GeekPress|geekpress)\/wp-rocket-cli"/

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                    |
      | GeekPress/wp-rocket-cli |

    When I run `wp help rocket`
    Then STDOUT should contain:
      """
      wp rocket
      """

    When I run `wp package uninstall GeekPress/wp-rocket-cli`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      rocket
      """

    # Install with lowercase name (for BC - no warning) and uninstall with lowercase name (for BC and convenience)
    When I run `wp package install geekpress/wp-rocket-cli`
    Then STDERR should be empty
    And STDOUT should match /Installing package (?:GeekPress|geekpress)\/wp-rocket-cli \(dev-master\)/
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the contents of the {PACKAGE_PATH}composer.json file should match /("?:GeekPress|geekpress)\/wp-rocket-cli"/

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                    |
      | GeekPress/wp-rocket-cli |

    When I run `wp help rocket`
    Then STDOUT should contain:
      """
      wp rocket
      """

    When I run `wp package uninstall geekpress/wp-rocket-cli`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      rocket
      """

  @github-api
  Scenario: Install a package with a composer.json that differs between versions
    Given an empty directory

    When I run `wp package install wp-cli-test/version-composer-json-different:v1.0.0`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/version-composer-json-different (v1.0.0)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the {PACKAGE_PATH}/vendor/wp-cli-test/version-composer-json-different/composer.json file should exist
    And the {PACKAGE_PATH}/vendor/wp-cli-test/version-composer-json-different/composer.json file should contain:
      """
      1.0.0
      """
    And the {PACKAGE_PATH}/vendor/wp-cli-test/version-composer-json-different/composer.json file should not contain:
      """
      1.0.1
      """
    And the {PACKAGE_PATH}/vendor/wp-cli/profile-command directory should not exist

    When I run `wp package install wp-cli-test/version-composer-json-different:v1.0.1`
    Then STDOUT should contain:
      """
      Installing package wp-cli-test/version-composer-json-different (v1.0.1)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the {PACKAGE_PATH}/vendor/wp-cli-test/version-composer-json-different/composer.json file should exist
    And the {PACKAGE_PATH}/vendor/wp-cli-test/version-composer-json-different/composer.json file should contain:
      """
      1.0.1
      """
    And the {PACKAGE_PATH}/vendor/wp-cli-test/version-composer-json-different/composer.json file should not contain:
      """
      1.0.0
      """
    And the {PACKAGE_PATH}/vendor/wp-cli/profile-command directory should exist

  Scenario: Install a package from a local zip
    Given an empty directory
    And I run `wget -q -O google-sitemap-generator-cli.zip https://github.com/wp-cli/google-sitemap-generator-cli/archive/master.zip`

    When I run `wp package install google-sitemap-generator-cli.zip`
    Then STDOUT should contain:
      """
      Installing package wp-cli/google-sitemap-generator-cli (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering {PACKAGE_PATH}local/wp-cli-google-sitemap-generator-cli as a path repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                                |
      | wp-cli/google-sitemap-generator-cli |

    When I run `wp google-sitemap`
    Then STDOUT should contain:
      """
      usage: wp google-sitemap rebuild
      """

    When I run `wp package uninstall wp-cli/google-sitemap-generator-cli`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli/google-sitemap-generator-cli
      """

  @github-api
  Scenario: Install a package from Git using a shortened mixed-case package identifier but lowercase composer.json name
    Given an empty directory

    When I try `wp package install CapitalWPCLI/examplecommand`
    Then the return code should be 0
    And STDERR should contain:
      """
      Warning: Package name mismatch...Updating from git name 'CapitalWPCLI/examplecommand' to composer.json name 'capitalwpcli/examplecommand'.
      """
    And STDOUT should contain:
      """
      Installing package capitalwpcli/examplecommand (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering https://github.com/CapitalWPCLI/examplecommand.git as a VCS repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """
    And the {PACKAGE_PATH}composer.json file should contain:
      """
      "capitalwpcli/examplecommand"
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      "CapitalWPCLI/examplecommand"
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                        |
      | capitalwpcli/examplecommand |

    When I run `wp hello-world`
    Then STDOUT should contain:
      """
      Success: Hello world.
      """

    When I run `wp package uninstall capitalwpcli/examplecommand`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """
    And the {PACKAGE_PATH}composer.json file should not contain:
      """
      capital
      """

  @github-api
  Scenario: Install a package from a remote ZIP
    Given an empty directory

    When I try `wp package install https://github.com/wp-cli/google-sitemap-generator.zip`
    Then STDERR should be:
      """
      Error: Couldn't download package from 'https://github.com/wp-cli/google-sitemap-generator.zip' (HTTP code 404).
      """

    When I run `wp package install https://github.com/wp-cli/google-sitemap-generator-cli/archive/master.zip`
    Then STDOUT should contain:
      """
      Installing package wp-cli/google-sitemap-generator-cli (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering {PACKAGE_PATH}local/wp-cli-google-sitemap-generator-cli as a path repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                                |
      | wp-cli/google-sitemap-generator-cli |

    When I run `wp google-sitemap`
    Then STDOUT should contain:
      """
      usage: wp google-sitemap rebuild
      """

    When I run `wp package uninstall wp-cli/google-sitemap-generator-cli`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli/google-sitemap-generator-cli
      """

  @gitlab-api
  Scenario: Install a package from a GitLab URL
    Given an empty directory

    When I try `wp package install https://gitlab.com/gitlab-examples/php.git`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: Invalid package: no name in composer.json file 'https://gitlab.com/gitlab-examples/php/-/raw/master/composer.json'.
      """

  Scenario: Install a package at an existing path
    Given an empty directory
    And a path-command/command.php file:
      """
      <?php
      WP_CLI::add_command( 'community-command', function(){
        WP_CLI::success( "success!" );
      }, array( 'when' => 'before_wp_load' ) );
      """
    And a path-command/composer.json file:
      """
      {
        "name": "wp-cli/community-command",
        "description": "A demo community command.",
        "license": "MIT",
        "minimum-stability": "dev",
        "require": {
        },
        "autoload": {
          "files": [ "command.php" ]
        },
        "require-dev": {
          "behat/behat": "~2.5"
        }
      }
      """

    When I run `pwd`
    Then save STDOUT as {CURRENT_PATH}

    When I run `wp package install path-command`
    Then STDOUT should contain:
      """
      Installing package wp-cli/community-command (dev-master)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering {CURRENT_PATH}/path-command as a path repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                            |
      | wp-cli/community-command        |

    When I run `wp community-command`
    Then STDOUT should be:
      """
      Success: success!
      """

    When I run `wp package uninstall wp-cli/community-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """
    And the path-command directory should exist

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli/community-command
      """

  Scenario: Install a package at an existing path with a version constraint
    Given an empty directory
    And a path-command/command.php file:
      """
      <?php
      WP_CLI::add_command( 'community-command', function(){
        WP_CLI::success( "success!" );
      }, array( 'when' => 'before_wp_load' ) );
      """
    And a path-command/composer.json file:
      """
      {
        "name": "wp-cli/community-command",
        "description": "A demo community command.",
        "license": "MIT",
        "minimum-stability": "dev",
        "version": "0.2.0-beta",
        "require": {
        },
        "autoload": {
          "files": [ "command.php" ]
        },
        "require-dev": {
          "behat/behat": "~2.5"
        }
      }
      """

    When I run `pwd`
    Then save STDOUT as {CURRENT_PATH}

    When I run `wp package install path-command`
    Then STDOUT should contain:
      """
      Installing package wp-cli/community-command (0.2.0-beta)
      """
    # This path is sometimes changed on Macs to prefix with /private
    And STDOUT should contain:
      """
      {PACKAGE_PATH}composer.json to require the package...
      """
    And STDOUT should contain:
      """
      Registering {CURRENT_PATH}/path-command as a path repository...
      Using Composer to install the package...
      """
    And STDOUT should contain:
      """
      Success: Package installed.
      """

    When I run `wp package list --fields=name`
    Then STDOUT should be a table containing rows:
      | name                            |
      | wp-cli/community-command        |

    When I run `wp community-command`
    Then STDOUT should be:
      """
      Success: success!
      """

    When I run `wp package uninstall wp-cli/community-command`
    Then STDOUT should contain:
      """
      Removing require statement from
      """
    And STDOUT should contain:
      """
      Success: Uninstalled package.
      """
    And the path-command directory should exist

    When I run `wp package list --fields=name`
    Then STDOUT should not contain:
      """
      wp-cli/community-command
      """

  Scenario: Try to install bad packages
    Given an empty directory
    And a package-dir/composer.json file:
      """
      {
      }
      """
    And a package-dir-bad-composer/composer.json file:
      """
      {
      """
    And a package-dir/zero.zip file:
      """
      """

    When I try `wp package install https://github.com/non-existent-git-user-asdfasdf/non-existent-git-repo-asdfasdf.git`
    Then the return code should be 1
    And STDERR should contain:
      """
      Warning: Couldn't download composer.json file from 'https://raw.githubusercontent.com/non-existent-git-user-asdfasdf/non-existent-git-repo-asdfasdf/master/composer.json' (HTTP code 404). Presuming package name is 'non-existent-git-user-asdfasdf/non-existent-git-repo-asdfasdf'.
      """

    When I try `wp package install https://github.com/wp-cli-tests/private-repository.git`
    Then STDERR should contain:
      """
      Warning: Couldn't download composer.json file from 'https://raw.githubusercontent.com/wp-cli-tests/private-repository/master/composer.json' (HTTP code 404). Presuming package name is 'wp-cli-tests/private-repository'.
      """

    When I try `wp package install non-existent-git-user-asdfasdf/non-existent-git-repo-asdfasdf`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Invalid package: shortened identifier 'non-existent-git-user-asdfasdf/non-existent-git-repo-asdfasdf' not found.
      """
    And STDOUT should be empty

    When I try `wp package install https://example.com/non-existent-zip-asdfasdf.zip`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: Couldn't download package from 'https://example.com/non-existent-zip-asdfasdf.zip' (HTTP code 404).
      """
    And STDOUT should be empty

    When I try `wp package install package-dir-bad-composer`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: Invalid package: failed to parse composer.json file
      """
    # Split string up to get around Mac OS X inconsistencies with RUN_DIR
    And STDERR should contain:
      """
      /package-dir-bad-composer/composer.json' as json.
      """
    And STDOUT should be empty

    When I try `wp package install package-dir`
    Then the return code should be 1
    And STDERR should contain:
      """
      Error: Invalid package: no name in composer.json file
      """
    # Split string up to get around Mac OS X inconsistencies with RUN_DIR
    And STDERR should contain:
      """
      /package-dir/composer.json'.
      """
    And STDOUT should be empty

    When I try `wp package install package-dir/zero.zip`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: ZipArchive failed to unzip 'package-dir/zero.zip': Not a zip archive (19).
      """
    And STDOUT should be empty
