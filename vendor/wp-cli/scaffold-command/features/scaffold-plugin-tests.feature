Feature: Scaffold plugin unit tests

  Scenario: Scaffold plugin tests
    Given a WP install
    When I run `wp plugin path`
    Then save STDOUT as {PLUGIN_DIR}

    When I run `wp scaffold plugin hello-world --skip-tests`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/hello-world/.editorconfig file should exist
    And the {PLUGIN_DIR}/hello-world/hello-world.php file should exist
    And the {PLUGIN_DIR}/hello-world/readme.txt file should exist
    And the {PLUGIN_DIR}/hello-world/tests directory should not exist

    When I run `wp scaffold plugin-tests hello-world`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/hello-world/tests directory should contain:
      """
      bootstrap.php
      test-sample.php
      """
    And the {PLUGIN_DIR}/hello-world/tests/bootstrap.php file should contain:
      """
      require dirname( dirname( __FILE__ ) ) . '/hello-world.php';
      """
    And the {PLUGIN_DIR}/hello-world/tests/bootstrap.php file should contain:
      """
      * @package Hello_World
      """
    And the {PLUGIN_DIR}/hello-world/tests/test-sample.php file should contain:
      """
      * @package Hello_World
      """
    And the {PLUGIN_DIR}/hello-world/bin directory should contain:
      """
      install-wp-tests.sh
      """
    And the {PLUGIN_DIR}/hello-world/phpunit.xml.dist file should contain:
      """
      <exclude>./tests/test-sample.php</exclude>
      """
    And the {PLUGIN_DIR}/hello-world/.phpcs.xml.dist file should exist
    And the {PLUGIN_DIR}/hello-world/circle.yml file should not exist
    And the {PLUGIN_DIR}/hello-world/.circleci directory should not exist
    And the {PLUGIN_DIR}/hello-world/bitbucket-pipelines.yml file should not exist
    And the {PLUGIN_DIR}/hello-world/.gitlab-ci.yml file should not exist
    And the {PLUGIN_DIR}/hello-world/.travis.yml file should contain:
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
    And the {PLUGIN_DIR}/hello-world/.travis.yml file should contain:
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
            env: WP_VERSION=4.5
          - php: 5.6
            env: WP_VERSION=latest
          - php: 5.6
            env: WP_VERSION=trunk
          - php: 5.6
            env: WP_TRAVISCI=phpcs
      """

    When I run `wp eval "if ( is_executable( '{PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh' ) ) { echo 'executable'; } else { exit( 1 ); }"`
    Then STDOUT should be:
      """
      executable
      """

  Scenario: Scaffold plugin tests with Circle as the provider, part one
    Given a WP install
    And I run `wp scaffold plugin hello-world --ci=circle`

    When I run `wp plugin path hello-world --dir`
    Then save STDOUT as {PLUGIN_DIR}
    And the {PLUGIN_DIR}/.travis.yml file should not exist
    And the {PLUGIN_DIR}/circle.yml file should not exist
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      version: 2
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      php56-build
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      php70-build
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      php71-build
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      php72-build
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      php73-build
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      php74-build
      """

  Scenario: Scaffold plugin tests with Circle as the provider, part two
    Given a WP install
    And I run `wp scaffold plugin hello-world --skip-tests`

    When I run `wp plugin path hello-world --dir`
    Then save STDOUT as {PLUGIN_DIR}

    When I run `wp scaffold plugin-tests hello-world --ci=circle`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/.travis.yml file should not exist
    And the {PLUGIN_DIR}/circle.yml file should not exist
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
      version: 2
      """
    And the {PLUGIN_DIR}/.circleci/config.yml file should contain:
      """
                  rm -rf $WP_TESTS_DIR $WP_CORE_DIR
                  bash bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 4.5 $SKIP_DB_CREATE
                  phpunit
                  WP_MULTISITE=1 phpunit
                  SKIP_DB_CREATE=true
                  rm -rf $WP_TESTS_DIR $WP_CORE_DIR
                  bash bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest $SKIP_DB_CREATE
                  phpunit
                  WP_MULTISITE=1 phpunit
                  SKIP_DB_CREATE=true
                  rm -rf $WP_TESTS_DIR $WP_CORE_DIR
                  bash bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 trunk $SKIP_DB_CREATE
                  phpunit
                  WP_MULTISITE=1 phpunit
                  SKIP_DB_CREATE=true
      """

  Scenario: Scaffold plugin tests with Gitlab as the provider
    Given a WP install
    And I run `wp scaffold plugin hello-world --skip-tests`

    When I run `wp plugin path hello-world --dir`
    Then save STDOUT as {PLUGIN_DIR}

    When I run `wp scaffold plugin-tests hello-world --ci=gitlab`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/.travis.yml file should not exist
    And the {PLUGIN_DIR}/.gitlab-ci.yml file should contain:
      """
      MYSQL_DATABASE
      """

  Scenario: Scaffold plugin tests with Bitbucket Pipelines as the provider
    Given a WP install
    And I run `wp scaffold plugin hello-world --skip-tests`

    When I run `wp plugin path hello-world --dir`
    Then save STDOUT as {PLUGIN_DIR}

    When I run `wp scaffold plugin-tests hello-world --ci=bitbucket`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/.travis.yml file should not exist
    And the {PLUGIN_DIR}/bitbucket-pipelines.yml file should contain:
      """
      pipelines:
        default:
      """
    And the {PLUGIN_DIR}/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:5.6
              name: "PHP 5.6"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {PLUGIN_DIR}/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:7.0
              name: "PHP 7.0"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {PLUGIN_DIR}/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:7.1
              name: "PHP 7.1"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {PLUGIN_DIR}/bitbucket-pipelines.yml file should contain:
      """
          - step:
              image: php:7.2
              name: "PHP 7.2"
              script:
                # Install Dependencies
                - docker-php-ext-install mysqli
                - apt-get update && apt-get install -y subversion --no-install-recommends
      """
    And the {PLUGIN_DIR}/bitbucket-pipelines.yml file should contain:
      """
      definitions:
        services:
          database:
            image: mysql:latest
            environment:
              MYSQL_DATABASE: 'wordpress_tests'
              MYSQL_ROOT_PASSWORD: 'root'
      """

  Scenario: Scaffold plugin tests with invalid slug
    Given a WP install
    Then the {RUN_DIR}/wp-content/plugins/hello.php file should exist

    When I try `wp scaffold plugin-tests hello`
    Then STDERR should be:
      """
      Error: Invalid plugin slug specified. No such target directory '{RUN_DIR}/wp-content/plugins/hello'.
      """
    And the return code should be 1

    When I try `wp scaffold plugin-tests .`
    Then STDERR should be:
      """
      Error: Invalid plugin slug specified. The slug cannot be '.' or '..'.
      """
    And the return code should be 1

    When I try `wp scaffold plugin-tests ../`
    Then STDERR should be:
      """
      Error: Invalid plugin slug specified. The target directory '{RUN_DIR}/wp-content/plugins/../' is not in '{RUN_DIR}/wp-content/plugins'.
      """
    And the return code should be 1

  Scenario: Scaffold plugin tests with invalid directory
    Given a WP install
    And I run `wp scaffold plugin hello-world --skip-tests`

    When I run `wp plugin path hello-world --dir`
    Then save STDOUT as {PLUGIN_DIR}

    When I try `wp scaffold plugin-tests hello-world --dir=non-existent-dir`
    Then STDERR should be:
      """
      Error: Invalid plugin directory specified. No such directory 'non-existent-dir'.
      """
    And the return code should be 1

    When I run `rm -rf {PLUGIN_DIR} && touch {PLUGIN_DIR}`
    Then the return code should be 0
    When I try `wp scaffold plugin-tests hello-world`
    Then STDERR should be:
      """
      Error: Invalid plugin slug specified. No such target directory '{PLUGIN_DIR}'.
      """
    And the return code should be 1

  Scenario: Scaffold plugin tests with a symbolic link
    Given a WP install
    And I run `wp scaffold plugin hello-world --skip-tests`

    When I run `wp plugin path hello-world --dir`
    Then save STDOUT as {PLUGIN_DIR}

    When I run `mv {PLUGIN_DIR} {RUN_DIR} && ln -s {RUN_DIR}/hello-world {PLUGIN_DIR}`
    Then the return code should be 0

    When I run `wp scaffold plugin-tests hello-world`
    Then STDOUT should not be empty
    And the {PLUGIN_DIR}/tests directory should contain:
      """
      bootstrap.php
      """
