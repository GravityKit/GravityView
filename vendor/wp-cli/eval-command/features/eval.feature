Feature: Evaluating PHP code and files.

  Scenario: Basics
    Given a WP install

    When I run `wp eval 'var_dump(defined("WP_CONTENT_DIR"));'`
    Then STDOUT should contain:
      """
      bool(true)
      """

    Given a script.php file:
      """
      <?php
      WP_CLI::line( implode( ' ', $args ) );
      """

    When I run `wp eval-file script.php foo bar`
    Then STDOUT should contain:
      """
      foo bar
      """

    Given a script.sh file:
      """
      #! /bin/bash
      <?php
      WP_CLI::line( implode( ' ', $args ) );
      """

    When I run `wp eval-file script.sh foo bar`
    Then STDOUT should contain:
      """
      foo bar
      """
    But STDOUT should not contain:
      """
      #!
      """

  Scenario: Eval without WordPress install
    Given an empty directory

    When I try `wp eval 'var_dump(defined("WP_CONTENT_DIR"));'`
    Then STDERR should contain:
      """
      Error: This does not seem to be a WordPress install
      """
    And the return code should be 1

    When I run `wp eval 'var_dump(defined("WP_CONTENT_DIR"));' --skip-wordpress`
    Then STDOUT should contain:
      """
      bool(false)
      """

  Scenario: Eval file without WordPress install
    Given an empty directory
    And a script.php file:
      """
      <?php
      var_dump(defined("WP_CONTENT_DIR"));
      """

    When I try `wp eval-file script.php`
    Then STDERR should contain:
      """
      Error: This does not seem to be a WordPress install
      """
    And the return code should be 1

    When I run `wp eval-file script.php --skip-wordpress`
    Then STDOUT should contain:
      """
      bool(false)
      """

  Scenario: Eval stdin with args
    Given an empty directory
    And a script.php file:
      """
      <?php
      WP_CLI::line( implode( ' ', $args ) );
      """

    When I run `cat script.php | wp eval-file - x y z --skip-wordpress`
    Then STDOUT should contain:
      """
      x y z
      """

  Scenario: Eval-file will use the correct __FILE__ constant value
    Given an empty directory
    And a script.php file:
      """
      <?php
      echo __FILE__;
      """

    When I run `wp eval-file script.php --skip-wordpress`
    Then STDOUT should contain:
      """
      /script.php
      """
    And STDOUT should not contain:
      """
      eval()'d code
      """

  Scenario: Eval-file will not replace __FILE__ when quoted
    Given an empty directory
    And a script.php file:
      """
      <?php
      echo '__FILE__';
      echo "__FILE__";
      echo '"__FILE__"';
      echo "'__FILE__'";

      echo ' foo __FILE__ bar ';
      echo " foo __FILE__ bar ";
      echo '" foo __FILE__ bar "';
      echo "' foo __FILE__ bar '";
      """

    When I run `wp eval-file script.php --skip-wordpress`
    Then STDOUT should contain:
      """
      __FILE__
      """
    And STDOUT should not contain:
      """
      /script.php
      """
    And STDOUT should not contain:
      """
      eval()'d code
      """

  Scenario: Eval-file can handle both quoted and unquoted __FILE__ correctly
    Given an empty directory
    And a script.php file:
      """
      <?php
      echo ' __FILE__ => ' . __FILE__;
      """

    When I run `wp eval-file script.php --skip-wordpress`
    Then STDOUT should contain:
      """
      __FILE__ =>
      """
    And STDOUT should contain:
      """
      /script.php
      """
    And STDOUT should not contain:
      """
      eval()'d code
      """

  Scenario: Eval-file will use the correct __FILE__ constant value
    Given an empty directory
    And a script.php file:
      """
      <?php
      echo __FILE__ . PHP_EOL;
      """
    And a dir_script.php file:
      """
      <?php
      echo __DIR__ . '/script.php' . PHP_EOL;
      """
    And I run `wp eval-file script.php --skip-wordpress`
    And save STDOUT as {FILE_OUTPUT}

    When I run `wp eval-file dir_script.php --skip-wordpress`
    Then STDOUT should be:
      """
      {FILE_OUTPUT}
      """
    And STDOUT should not contain:
      """
      eval()'d code
      """
