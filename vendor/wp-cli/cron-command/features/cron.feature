Feature: Manage WP-Cron events and schedules

  Background:
    Given a WP install
    And I run `wp config set DISABLE_WP_CRON false --raw --type=constant --anchor='// ** MySQL settings - You can get this info from your web host ** //'`

  Scenario: Scheduling and then deleting an event
    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' --0=banana`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_1'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence,args`
    Then STDOUT should be CSV containing:
      | hook                | recurrence      | args       |
      | wp_cli_test_event_1 | Non-repeating   | ["banana"] |

    When I run `wp cron event list --fields=hook,next_run_relative | grep wp_cli_test_event_1`
    Then STDOUT should contain:
      """
      1 hour
      """

    When I run `wp cron event list --hook=wp_cli_test_event_1 --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp cron event list --hook=apple --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp cron event delete wp_cli_test_event_1`
    Then STDOUT should contain:
      """
      Success: Deleted the cron event 'wp_cli_test_event_1'
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_1
      """

  Scenario: Scheduling and then running an event
    When I run `wp cron event schedule wp_cli_test_event_3 '-1 minutes'`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_3'
      """

    When I run `wp cron event schedule wp_cli_test_event_4`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_4'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence`
    Then STDOUT should be CSV containing:
      | hook                | recurrence    |
      | wp_cli_test_event_3 | Non-repeating |

    When I run `wp cron event run wp_cli_test_event_3`
    Then STDOUT should not be empty

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_3
      """

  Scenario: Scheduling, running, and deleting duplicate events
    When I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=banana`
    When I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=bar`
    Then STDOUT should not be empty

    When I run `wp cron event list --format=csv --fields=hook,recurrence,args`
    Then STDOUT should be CSV containing:
      | hook                | recurrence    | args       |
      | wp_cli_test_event_5 | Non-repeating | ["banana"] |
      | wp_cli_test_event_5 | Non-repeating | ["bar"]    |

    When I run `wp cron event run wp_cli_test_event_5`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_5'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_5'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of 2 cron events.
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_5
      """

    When I try `wp cron event run wp_cli_test_event_5`
    Then STDERR should be:
      """
      Error: Invalid cron event 'wp_cli_test_event_5'
      """

    When I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=banana`
    When I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=bar`
    Then STDOUT should not be empty

    When I run `wp cron event list`
    Then STDOUT should contain:
      """
      wp_cli_test_event_5
      """

    When I run `wp cron event delete wp_cli_test_event_5`
    Then STDOUT should be:
      """
      Success: Deleted 2 instances of the cron event 'wp_cli_test_event_5'.
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_5
      """

    When I try `wp cron event delete wp_cli_test_event_5`
    Then STDERR should be:
      """
      Error: Invalid cron event 'wp_cli_test_event_5'.
      """

  Scenario: Scheduling and then running a re-occurring event
    When I run `wp cron event schedule wp_cli_test_event_4 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_4'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence`
    Then STDOUT should be CSV containing:
      | hook                | recurrence    |
      | wp_cli_test_event_4 | 1 hour        |

    When I run `wp cron event run wp_cli_test_event_4`
    Then STDOUT should not be empty

    When I run `wp cron event list`
    Then STDOUT should contain:
      """
      wp_cli_test_event_4
      """

  Scenario: Scheduling and then deleting a recurring event
    When I run `wp cron event schedule wp_cli_test_event_2 now daily`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_2'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence`
    Then STDOUT should be CSV containing:
      | hook                | recurrence |
      | wp_cli_test_event_2 | 1 day      |

    When I run `wp cron event delete wp_cli_test_event_2`
    Then STDOUT should contain:
      """
      Success: Deleted the cron event 'wp_cli_test_event_2'
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_2
      """

  Scenario: Listing cron schedules
    When I run `wp cron schedule list --format=csv --fields=name,interval`
    Then STDOUT should be CSV containing:
      | name   | interval |
      | hourly | 3600     |

  Scenario: Testing WP-Cron
    When I try `wp cron test`
    Then STDERR should not contain:
      """
      Error:
      """

  Scenario: Run multiple cron events
    When I try `wp cron event run`
    Then STDERR should be:
      """
      Error: Please specify one or more cron events, or use --due-now/--all.
      """

    When I run `wp cron event run wp_version_check wp_update_plugins`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_version_check'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_plugins'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of 2 cron events.
      """

    # WP throws a notice here for older versions of core.
    When I try `wp cron event run --all`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_version_check'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_plugins'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_themes'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of
      """

  Scenario: Run currently scheduled events
    # WP throws a notice here for older versions of core.
    When I try `wp cron event run --all`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_version_check'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_plugins'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_themes'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of
      """

    When I run `wp cron event run --due-now`
    Then STDOUT should contain:
      """
      Executed a total of 0 cron events
      """

    When I run `wp cron event schedule wp_cli_test_event_1 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_1'
      """

    When I run `wp cron event run --due-now`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_1'
      """
    And STDOUT should contain:
      """
      Executed a total of 1 cron event
      """

    When I run `wp cron event run --due-now`
    Then STDOUT should contain:
      """
      Executed a total of 0 cron events
      """

  Scenario: Don't trigger cron when ALTERNATE_WP_CRON is defined
    Given a alternate-wp-cron.php file:
      """
      <?php
      define( 'ALTERNATE_WP_CRON', true );
      """
    And a wp-cli.yml file:
      """
      require:
        - alternate-wp-cron.php
      """

    When I run `wp eval 'var_export( ALTERNATE_WP_CRON );'`
    Then STDOUT should be:
      """
      true
      """

    When I run `wp option get home`
    Then STDOUT should be:
      """
      http://example.com
      """

  Scenario: Listing duplicated cron events
    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 6 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event list --format=ids`
    Then STDOUT should contain:
      """
      wp_cli_test_event_1 wp_cli_test_event_1
      """

  Scenario: Scheduling an event with non-numerically indexed arguments
    When I try `wp cron event schedule wp_cli_test_args_event '+10 minutes' --foo=banana --bar=apple`
    Then STDOUT should not be empty
    And STDERR should be:
      """
      Warning: Numeric keys should be used for the hook arguments.
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence,args`
    Then STDOUT should be CSV containing:
      | hook                   | recurrence    | args                           |
      | wp_cli_test_args_event | Non-repeating | {"foo":"banana","bar":"apple"} |
