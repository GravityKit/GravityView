Feature: Scaffold theme unit tests

  Background:
    Given a WP install
    And I run `wp theme install p2`
    And I run `wp scaffold child-theme p2child --parent_theme=p2`

    When I run `wp theme path`
    Then save STDOUT as {THEME_DIR}

  @require-php-5.6 @less-than-php-7.2
  Scenario: Scaffold theme tests
    When I run `wp scaffold theme-tests p2child`
    Then STDOUT should not be empty
    And the {THEME_DIR}/p2child/tests directory should contain:
      """
      bootstrap.php
      test-sample.php
      """
    And the {THEME_DIR}/p2child/tests/bootstrap.php file should contain:
      """
      register_theme_directory( $theme_root );
      """
    And the {THEME_DIR}/p2child/tests/bootstrap.php file should contain:
      """
      * @package P2child
      """
    And the {THEME_DIR}/p2child/tests/test-sample.php file should contain:
      """
      * @package P2child
      """
    And the {THEME_DIR}/p2child/bin directory should contain:
      """
      install-wp-tests.sh
      """
    And the {THEME_DIR}/p2child/phpunit.xml.dist file should contain:
      """
      <exclude>./tests/test-sample.php</exclude>
      """
    And the {THEME_DIR}/p2child/.phpcs.xml.dist file should exist
    And the {THEME_DIR}/p2child/circle.yml file should not exist
    And the {THEME_DIR}/p2child/.circleci directory should not exist
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should not exist
    And the {THEME_DIR}/p2child/.gitlab-ci.yml file should not exist
    And the {THEME_DIR}/p2child/.travis.yml file should contain:
      """
      script:
        - |
          if [[ ! -z "$WP_VERSION" ]] ; then
            phpunit
            WP_MULTISITE=1 phpunit
          fi
        - |
          if [[ "$WP_TRAVISCI" == "phpcs" ]] ; then
            phpcs
          fi
      """
    And the {THEME_DIR}/p2child/.travis.yml file should contain:
      """
      matrix:
        include:
          - php: 7.4
            env: WP_VERSION=latest
          - php: 7.3
            env: WP_VERSION=latest
          - php: 7.2
            env: WP_VERSION=latest
          - php: 7.1
            env: WP_VERSION=latest
          - php: 7.0
            env: WP_VERSION=latest
          - php: 5.6
            env: WP_VERSION=latest
          - php: 5.6
            env: WP_VERSION=trunk
          - php: 5.6
            env: WP_TRAVISCI=phpcs
      """

    When I run `wp eval "if ( is_executable( '{THEME_DIR}/p2child/bin/install-wp-tests.sh' ) ) { echo 'executable'; } else { exit( 1 ); }"`
    Then STDOUT should be:
      """
      executable
      """

    # Warning: overwriting generated functions.php file, so functions.php file loaded only tests beyond here...
    Given a wp-content/themes/p2child/functions.php file:
      """
      <?php echo __FILE__ . " loaded.\n";
      """
    # This throws a warning for the password provided via command line.
    And I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "DROP DATABASE IF EXISTS wp_cli_test_scaffold"`

    And I try `WP_TESTS_DIR={RUN_DIR}/wordpress-tests-lib WP_CORE_DIR={RUN_DIR}/wordpress {THEME_DIR}/p2child/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest`
    Then the return code should be 0

    When I run `cd {THEME_DIR}/p2child; WP_TESTS_DIR={RUN_DIR}/wordpress-tests-lib phpunit`
    Then STDOUT should contain:
      """
      p2child/functions.php loaded.
      """
    And STDOUT should contain:
      """
      Running as single site
      """
    And STDOUT should contain:
      """
      No tests executed!
      """

    When I run `cd {THEME_DIR}/p2child; WP_MULTISITE=1 WP_TESTS_DIR={RUN_DIR}/wordpress-tests-lib phpunit`
    Then STDOUT should contain:
      """
      p2child/functions.php loaded.
      """
    And STDOUT should contain:
      """
      Running as multisite
      """
    And STDOUT should contain:
      """
      No tests executed!
      """

  Scenario: Scaffold theme tests invalid theme
    When I try `wp scaffold theme-tests p3child`
    Then STDERR should be:
      """
      Error: Invalid theme slug specified. The theme 'p3child' does not exist.
      """
    And the return code should be 1

  Scenario: Scaffold theme tests with Circle as the provider
    When I run `wp scaffold theme-tests p2child --ci=circle`
    Then STDOUT should not be empty
    And the {THEME_DIR}/p2child/.travis.yml file should not exist
    And the {THEME_DIR}/p2child/circle.yml file should not exist
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      version: 2
      """
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      php56-build
      """
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      php70-build
      """
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      php71-build
      """
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      php72-build
      """
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      php73-build
      """
    And the {THEME_DIR}/p2child/.circleci/config.yml file should contain:
      """
      php74-build
      """

  Scenario: Scaffold theme tests with Gitlab as the provider
    When I run `wp scaffold theme-tests p2child --ci=gitlab`
    Then STDOUT should not be empty
    And the {THEME_DIR}/p2child/.travis.yml file should not exist
    And the {THEME_DIR}/p2child/.gitlab-ci.yml file should contain:
      """
      MYSQL_DATABASE
      """

  Scenario: Scaffold theme tests with Bitbucket Pipelines as the provider
    When I run `wp scaffold theme-tests p2child --ci=bitbucket`
    Then STDOUT should not be empty
    And the {THEME_DIR}/p2child/.travis.yml file should not exist
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should contain:
      """
      pipelines:
        default:
      """
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:5.6
              name: "PHP 5.6"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:7.0
              name: "PHP 7.0"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:7.1
              name: "PHP 7.1"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:7.2
              name: "PHP 7.2"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {THEME_DIR}/p2child/bitbucket-pipelines.yml file should contain:
      """
      definitions:
        services:
          database:
            image: mysql:latest
            environment:
              MYSQL_DATABASE: 'wordpress_tests'
              MYSQL_ROOT_PASSWORD: 'root'
      """

  Scenario: Scaffold theme tests with invalid slug

    When I try `wp scaffold theme-tests .`
    Then STDERR should be:
      """
      Error: Invalid theme slug specified. The slug cannot be '.' or '..'.
      """
    And the return code should be 1

    When I try `wp scaffold theme-tests ../`
    Then STDERR should be:
      """
      Error: Invalid theme slug specified. The target directory '{RUN_DIR}/wp-content/themes/../' is not in '{RUN_DIR}/wp-content/themes'.
      """
    And the return code should be 1

  Scenario: Scaffold theme tests with invalid directory
    When I try `wp scaffold theme-tests p2 --dir=non-existent-dir`
    Then STDERR should be:
      """
      Error: Invalid theme directory specified. No such directory 'non-existent-dir'.
      """
    And the return code should be 1

    # Temporarily move.
    When I run `mv -f {THEME_DIR}/p2 {THEME_DIR}/hide-p2 && touch {THEME_DIR}/p2`
    Then the return code should be 0

    When I try `wp scaffold theme-tests p2`
    Then STDERR should be:
      """
      Error: Invalid theme slug specified. No such target directory '{THEME_DIR}/p2'.
      """
    And the return code should be 1

    # Restore.
    When I run `rm -f {THEME_DIR}/p2 && mv -f {THEME_DIR}/hide-p2 {THEME_DIR}/p2`
    Then the return code should be 0

  Scenario: Scaffold theme tests with a symbolic link
    # Temporarily move the whole theme dir and create a symbolic link to it.
    When I run `mv -f {THEME_DIR} {RUN_DIR}/alt-themes && ln -s {RUN_DIR}/alt-themes {THEME_DIR}`
    Then the return code should be 0

    When I run `wp scaffold theme-tests p2`
    Then STDOUT should not be empty
    And the {THEME_DIR}/p2/tests directory should contain:
      """
      bootstrap.php
      """

    # Restore.
    When I run `unlink {THEME_DIR} && mv -f {RUN_DIR}/alt-themes {THEME_DIR}`
    Then the return code should be 0
