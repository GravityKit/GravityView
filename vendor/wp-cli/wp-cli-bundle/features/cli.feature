Feature: `wp cli` tasks

  Scenario: Ability to set a custom version when building
    Given an empty directory
    And save the {FRAMEWORK_ROOT}/VERSION file as {TRUE_VERSION}
    And a new Phar with version "1.2.3"

    When I run `{PHAR_PATH} cli version`
    Then STDOUT should be:
    """
    WP-CLI 1.2.3
    """
    And the {FRAMEWORK_ROOT}/VERSION file should be:
    """
    {TRUE_VERSION}
    """

  @github-api
  Scenario: Check for updates
    Given an empty directory
    And a new Phar with version "0.0.0"

    When I run `{PHAR_PATH} cli check-update`
    Then STDOUT should contain:
    """
    package_url
    """
    And STDERR should be empty

  @github-api
  Scenario: Do WP-CLI Update
    Given an empty directory
    And a new Phar with version "0.0.0"

    When I run `{PHAR_PATH} --info`
    Then STDOUT should contain:
      """
      WP-CLI version
      """
    And STDOUT should contain:
      """
      0.0.0
      """

    When I run `{PHAR_PATH} cli update --yes`
    Then STDOUT should contain:
      """
      md5 hash verified:
      """
    And STDOUT should contain:
    """
    Success:
    """
    And STDERR should be empty
    And the return code should be 0

    When I run `{PHAR_PATH} --info`
    Then STDOUT should contain:
      """
      WP-CLI version
      """
    And STDOUT should not contain:
      """
      0.0.0
      """

    When I run `{PHAR_PATH} cli update`
    Then STDOUT should be:
      """
      Success: WP-CLI is at the latest version.
      """

  @github-api
  Scenario: Patch update from 0.14.0 to 0.14.1
    Given an empty directory
    And a new Phar with version "0.14.0"

    When I run `{PHAR_PATH} --version`
    Then STDOUT should be:
      """
      WP-CLI 0.14.0
      """

    When I run `{PHAR_PATH} cli update --patch --yes`
    Then STDOUT should contain:
      """
      md5 hash verified: 3f5fa2fda8457a9a5dc9875f17a3716d
      """
    And STDOUT should contain:
      """
      Success: Updated WP-CLI to 0.14.1
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `{PHAR_PATH} --version`
    Then STDOUT should be:
      """
      WP-CLI 0.14.1
      """

  @github-api
  Scenario: Not a patch update from 0.14.0
    Given an empty directory
    And a new Phar with version "0.14.0"

    When I run `{PHAR_PATH} cli update --no-patch --yes`
    Then STDOUT should contain:
    """
    Success:
    """
    And STDOUT should not contain:
    """
    0.14.1
    """
    And STDERR should be empty
    And the return code should be 0

  @require-php-5.6
  Scenario: Install WP-CLI nightly
    Given an empty directory
    And a new Phar with version "0.14.0"

    When I run `{PHAR_PATH} cli update --nightly --yes`
    Then STDOUT should contain:
      """
      md5 hash verified:
      """
    And STDOUT should contain:
      """
      Success: Updated WP-CLI to the latest nightly release.
      """

    And STDERR should be empty
    And the return code should be 0

  @github-api
  Scenario: Install WP-CLI stable
    Given an empty directory
    And a new Phar with version "0.14.0"
    And a session file:
      """
      y
      """

    When I run `{PHAR_PATH} cli check-update --field=version | head -1`
    Then STDOUT should not be empty
    And save STDOUT as {UPDATE_VERSION}

    When I run `{PHAR_PATH} cli update --stable < session`
    Then STDOUT should contain:
      """
      You have version 0.14.0. Would you like to update to the latest stable release? [y/n]
      """
    And STDOUT should contain:
      """
      md5 hash verified:
      """
    And STDOUT should contain:
      """
      Success: Updated WP-CLI to the latest stable release.
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `{PHAR_PATH} cli check-update`
    Then STDOUT should be:
      """
      Success: WP-CLI is at the latest version.
      """

    When I run `{PHAR_PATH} cli version`
    Then STDOUT should be:
      """
      WP-CLI {UPDATE_VERSION}
      """
