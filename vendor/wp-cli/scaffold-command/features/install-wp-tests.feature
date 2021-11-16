# Note: You need to execute the mysql command `GRANT ALL PRIVILEGES ON wp_cli_test_scaffold.* TO "wp_cli_test"@"localhost" IDENTIFIED BY "{DB_PASSWORD}";` for these tests to work locally.
Feature: Scaffold install-wp-tests.sh tests

  Scenario: Help should be displayed
    Given a WP install
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And I run `wp scaffold plugin hello-world`

    When I try `/usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh`
    Then STDOUT should contain:
      """
      usage:
      """
    And the return code should be 1

  @require-php-5.6 @less-than-php-8.0
  Scenario: Install latest version of WordPress
    Given a WP install
    And a affirmative-response file:
    """
    Y
    """
    And a negative-response file:
    """
    No
    """
    And a get-phpunit-phar-url.php file:
    """
    <?php
    $version = 4;
    if(PHP_VERSION_ID >= 50600) {
        $version = 5;
    }
    if(PHP_VERSION_ID >= 70000) {
        $version = 6;
    }
    if(PHP_VERSION_ID >= 70100) {
        $version = 7;
    }
    if(PHP_VERSION_ID >= 80000) {
        $version = 9;
    }
    echo "https://phar.phpunit.de/phpunit-{$version}.phar";
    """
    And I run `wp eval-file get-phpunit-phar-url.php --skip-wordpress`
    And save STDOUT as {PHPUNIT_PHAR_URL}
    And I run `wget -q -O phpunit {PHPUNIT_PHAR_URL}`
    And I run `chmod +x phpunit`
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And I run `wp scaffold plugin hello-world`
    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    And I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "DROP DATABASE IF EXISTS wp_cli_test_scaffold"`
    And I try `rm -fr /tmp/behat-wordpress-tests-lib`
    And I try `rm -fr /tmp/behat-wordpress`

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest`
    Then the return code should be 0
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      data
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      includes
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      wp-tests-config.php
      """
    And the /tmp/behat-wordpress directory should contain:
      """
      index.php
      license.txt
      readme.html
      wp-activate.php
      wp-admin
      wp-blog-header.php
      wp-comments-post.php
      wp-config-sample.php
      wp-content
      wp-cron.php
      wp-includes
      wp-links-opml.php
      wp-load.php
      wp-login.php
      wp-mail.php
      wp-settings.php
      wp-signup.php
      wp-trackback.php
      xmlrpc.php
      """
    And the {PLUGIN_DIR}/hello-world/phpunit.xml.dist file should exist
    And STDERR should contain:
      """
      install_test_suite
      """

    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    When I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "SHOW DATABASES"`
    And STDOUT should contain:
      """
      wp_cli_test_scaffold
      """

    When I run `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib ./phpunit -c {PLUGIN_DIR}/hello-world/phpunit.xml.dist`
    Then the return code should be 0

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest < affirmative-response`
    Then the return code should be 0
    And STDERR should contain:
      """
      Reinstalling
      """
    And STDOUT should contain:
      """
      Recreated the database (wp_cli_test_scaffold)
      """

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest < negative-response`
    Then the return code should be 0
    And STDERR should contain:
      """
      Reinstalling
      """
    And STDOUT should contain:
      """
      Leaving the existing database (wp_cli_test_scaffold) in place
      """

  @require-php-8.0
  Scenario: Install latest version of WordPress on PHP 8.0+
    Given a WP install
    And a affirmative-response file:
    """
    Y
    """
    And a negative-response file:
    """
    No
    """
    And a get-phpunit-phar-url.php file:
    """
    <?php
    $version = 4;
    if(PHP_VERSION_ID >= 50600) {
        $version = 5;
    }
    if(PHP_VERSION_ID >= 70000) {
        $version = 6;
    }
    if(PHP_VERSION_ID >= 70100) {
        $version = 7;
    }
    if(PHP_VERSION_ID >= 80000) {
        $version = 9;
    }
    echo "https://phar.phpunit.de/phpunit-{$version}.phar";
    """
    And I run `wp eval-file get-phpunit-phar-url.php --skip-wordpress`
    And save STDOUT as {PHPUNIT_PHAR_URL}
    And I run `wget -q -O phpunit {PHPUNIT_PHAR_URL}`
    And I run `chmod +x phpunit`
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And I run `wp scaffold plugin hello-world`
    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    And I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "DROP DATABASE IF EXISTS wp_cli_test_scaffold"`
    And I try `rm -fr /tmp/behat-wordpress-tests-lib`
    And I try `rm -fr /tmp/behat-wordpress`

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest`
    Then the return code should be 0
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      data
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      includes
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      wp-tests-config.php
      """
    And the /tmp/behat-wordpress directory should contain:
      """
      index.php
      license.txt
      readme.html
      wp-activate.php
      wp-admin
      wp-blog-header.php
      wp-comments-post.php
      wp-config-sample.php
      wp-content
      wp-cron.php
      wp-includes
      wp-links-opml.php
      wp-load.php
      wp-login.php
      wp-mail.php
      wp-settings.php
      wp-signup.php
      wp-trackback.php
      xmlrpc.php
      """
    And the {PLUGIN_DIR}/hello-world/phpunit.xml.dist file should exist
    And STDERR should contain:
      """
      install_test_suite
      """

    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    When I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "SHOW DATABASES"`
    And STDOUT should contain:
      """
      wp_cli_test_scaffold
      """

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib ./phpunit -c {PLUGIN_DIR}/hello-world/phpunit.xml.dist`
    Then the return code should be 1
    And STDOUT should contain:
      """
      The scaffolded tests cannot currently be run on PHP 8.0+. See https://github.com/wp-cli/scaffold-command/issues/285
      """

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest < affirmative-response`
    Then the return code should be 0
    And STDERR should contain:
      """
      Reinstalling
      """
    And STDOUT should contain:
      """
      Recreated the database (wp_cli_test_scaffold)
      """

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} latest < negative-response`
    Then the return code should be 0
    And STDERR should contain:
      """
      Reinstalling
      """
    And STDOUT should contain:
      """
      Leaving the existing database (wp_cli_test_scaffold) in place
      """

  @require-php-5.6 @less-than-php-8.0
  Scenario: Install WordPress from trunk
    Given a WP install
    And a get-phpunit-phar-url.php file:
    """
    <?php
    $version = 4;
    if(PHP_VERSION_ID >= 50600) {
        $version = 5;
    }
    if(PHP_VERSION_ID >= 70000) {
        $version = 6;
    }
    if(PHP_VERSION_ID >= 70100) {
        $version = 7;
    }
    if(PHP_VERSION_ID >= 80000) {
        $version = 9;
    }
    echo "https://phar.phpunit.de/phpunit-{$version}.phar";
    """
    And I run `wp eval-file get-phpunit-phar-url.php --skip-wordpress`
    And save STDOUT as {PHPUNIT_PHAR_URL}
    And I run `wget -q -O phpunit {PHPUNIT_PHAR_URL}`
    And I run `chmod +x phpunit`
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And I run `wp scaffold plugin hello-world`
    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    And I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "DROP DATABASE IF EXISTS wp_cli_test_scaffold"`
    And I try `rm -fr /tmp/behat-wordpress-tests-lib`
    And I try `rm -fr /tmp/behat-wordpress`

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} trunk`
    Then the return code should be 0
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      data
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      includes
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      wp-tests-config.php
      """
    And the /tmp/behat-wordpress directory should contain:
      """
      index.php
      """

    # WP 5.0+: js

    And the /tmp/behat-wordpress directory should contain:
      """
      license.txt
      readme.html
      """

    # WP 5.0+: styles

    And the /tmp/behat-wordpress directory should contain:
      """
      wp-activate.php
      wp-admin
      wp-blog-header.php
      wp-comments-post.php
      wp-config-sample.php
      wp-content
      wp-cron.php
      wp-includes
      wp-links-opml.php
      wp-load.php
      wp-login.php
      wp-mail.php
      wp-settings.php
      wp-signup.php
      wp-trackback.php
      xmlrpc.php
      """
    And the contents of the /tmp/behat-wordpress/wp-includes/version.php file should match /\-(alpha|beta[0-9]+|RC[0-9]+)\-/
    And the {PLUGIN_DIR}/hello-world/phpunit.xml.dist file should exist
    And STDERR should contain:
      """
      install_test_suite
      """

    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    When I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "SHOW DATABASES"`
    And STDOUT should contain:
      """
      wp_cli_test_scaffold
      """

    When I run `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib ./phpunit -c {PLUGIN_DIR}/hello-world/phpunit.xml.dist`
    Then the return code should be 0

  @require-php-8.0
  Scenario: Install WordPress from trunk for PHP 8.0+
    Given a WP install
    And a get-phpunit-phar-url.php file:
    """
    <?php
    $version = 4;
    if(PHP_VERSION_ID >= 50600) {
        $version = 5;
    }
    if(PHP_VERSION_ID >= 70000) {
        $version = 6;
    }
    if(PHP_VERSION_ID >= 70100) {
        $version = 7;
    }
    if(PHP_VERSION_ID >= 80000) {
        $version = 9;
    }
    echo "https://phar.phpunit.de/phpunit-{$version}.phar";
    """
    And I run `wp eval-file get-phpunit-phar-url.php --skip-wordpress`
    And save STDOUT as {PHPUNIT_PHAR_URL}
    And I run `wget -q -O phpunit {PHPUNIT_PHAR_URL}`
    And I run `chmod +x phpunit`
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And I run `wp scaffold plugin hello-world`
    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    And I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "DROP DATABASE IF EXISTS wp_cli_test_scaffold"`
    And I try `rm -fr /tmp/behat-wordpress-tests-lib`
    And I try `rm -fr /tmp/behat-wordpress`

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} trunk`
    Then the return code should be 0
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      data
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      includes
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      wp-tests-config.php
      """
    And the /tmp/behat-wordpress directory should contain:
      """
      index.php
      """

    # WP 5.0+: js

    And the /tmp/behat-wordpress directory should contain:
      """
      license.txt
      readme.html
      """

    # WP 5.0+: styles

    And the /tmp/behat-wordpress directory should contain:
      """
      wp-activate.php
      wp-admin
      wp-blog-header.php
      wp-comments-post.php
      wp-config-sample.php
      wp-content
      wp-cron.php
      wp-includes
      wp-links-opml.php
      wp-load.php
      wp-login.php
      wp-mail.php
      wp-settings.php
      wp-signup.php
      wp-trackback.php
      xmlrpc.php
      """
    And the contents of the /tmp/behat-wordpress/wp-includes/version.php file should match /\-(alpha|beta[0-9]+|RC[0-9]+)\-/
    And the {PLUGIN_DIR}/hello-world/phpunit.xml.dist file should exist
    And STDERR should contain:
      """
      install_test_suite
      """

    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    When I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "SHOW DATABASES"`
    And STDOUT should contain:
      """
      wp_cli_test_scaffold
      """

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib ./phpunit -c {PLUGIN_DIR}/hello-world/phpunit.xml.dist`
    Then the return code should be 1
    And STDOUT should contain:
      """
      The scaffolded tests cannot currently be run on PHP 8.0+. See https://github.com/wp-cli/scaffold-command/issues/285
      """

  Scenario: Install WordPress 3.7 and phpunit will not run
    Given a WP install
    And I run `wp plugin path`
    And save STDOUT as {PLUGIN_DIR}
    And I run `wp scaffold plugin hello-world`
    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    And I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "DROP DATABASE IF EXISTS wp_cli_test_scaffold"`
    And I try `rm -fr /tmp/behat-wordpress-tests-lib`
    And I try `rm -fr /tmp/behat-wordpress`

    When I try `WP_TESTS_DIR=/tmp/behat-wordpress-tests-lib WP_CORE_DIR=/tmp/behat-wordpress /usr/bin/env bash {PLUGIN_DIR}/hello-world/bin/install-wp-tests.sh wp_cli_test_scaffold {DB_USER} {DB_PASSWORD} {DB_HOST} 3.7`
    Then the return code should be 0
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      data
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      includes
      """
    And the /tmp/behat-wordpress-tests-lib directory should contain:
      """
      wp-tests-config.php
      """
    And the /tmp/behat-wordpress directory should contain:
      """
      index.php
      license.txt
      readme.html
      wp-activate.php
      wp-admin
      wp-blog-header.php
      wp-comments-post.php
      wp-config-sample.php
      wp-content
      wp-cron.php
      wp-includes
      wp-links-opml.php
      wp-load.php
      wp-login.php
      wp-mail.php
      wp-settings.php
      wp-signup.php
      wp-trackback.php
      xmlrpc.php
      """
    And the /tmp/behat-wordpress/wp-includes/version.php file should contain:
      """
      3.7
      """
    And STDERR should contain:
      """
      install_test_suite
      """

    # TODO: Hardcoded for GHA, needs to be made more flexible for local setups.
    # This throws a warning for the password provided via command line.
    When I try `mysql -u{DB_USER} -p{DB_PASSWORD} -h{MYSQL_HOST} -P{MYSQL_PORT} --protocol=tcp -e "SHOW DATABASES"`
    And STDOUT should contain:
      """
      wp_cli_test_scaffold
      """
