Feature: Manage WP Cron events

  Background:
    Given a WP install

  Scenario: --due-now with supplied events should only run those
    # WP throws a notice here for older versions of core.
    When I try `wp cron event run --all`
    Then STDOUT should contain:
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

    When I run `wp cron event schedule wp_cli_test_event_2 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_2'
      """

    When I run `wp cron event run wp_cli_test_event_1 --due-now`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_1'
      """
    And STDOUT should contain:
      """
      Executed a total of 1 cron event
      """

  @require-wp-4.9.0
  Scenario: Unschedule cron event
    When I run `wp cron event schedule wp_cli_test_event_1 now hourly`
    And I try `wp cron event unschedule wp_cli_test_event_1`
    Then STDOUT should contain:
      """
      Success: Unscheduled 1 event for hook 'wp_cli_test_event_1'.
      """

    When I run `wp cron event schedule wp_cli_test_event_2 now hourly`
    And I run `wp cron event schedule wp_cli_test_event_2 '+1 hour' hourly`
    And I try `wp cron event unschedule wp_cli_test_event_2`
    Then STDOUT should contain:
      """
      Success: Unscheduled 2 events for hook 'wp_cli_test_event_2'.
      """

    When I try `wp cron event unschedule wp_cli_test_event`
    Then STDERR should be:
      """
      Error: No events found for hook 'wp_cli_test_event'.
      """

  @less-than-wp-4.9.0
  Scenario: Unschedule cron event for WP < 4.9.0, wp_unschedule_hook was not included
    When I try `wp cron event unschedule wp_cli_test_event_1`
    Then STDERR should be:
      """
      Error: Unscheduling events is only supported from WordPress 4.9.0 onwards.
      """
