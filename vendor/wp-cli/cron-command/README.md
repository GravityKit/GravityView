wp-cli/cron-command
===================

Tests, runs, and deletes WP-Cron events; manages WP-Cron schedules.

[![Testing](https://github.com/wp-cli/cron-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/cron-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp cron

Tests, runs, and deletes WP-Cron events; manages WP-Cron schedules.

~~~
wp cron
~~~

**EXAMPLES**

    # Test WP Cron spawning system
    $ wp cron test
    Success: WP-Cron spawning is working as expected.



### wp cron test

Tests the WP Cron spawning system and reports back its status.

~~~
wp cron test 
~~~

This command tests the spawning system by performing the following steps:

* Checks to see if the `DISABLE_WP_CRON` constant is set; errors if true
because WP-Cron is disabled.
* Checks to see if the `ALTERNATE_WP_CRON` constant is set; warns if true.
* Attempts to spawn WP-Cron over HTTP; warns if non 200 response code is
returned.

**EXAMPLES**

    # Cron test runs successfully.
    $ wp cron test
    Success: WP-Cron spawning is working as expected.



### wp cron event

Schedules, runs, and deletes WP-Cron events.

~~~
wp cron event
~~~

**EXAMPLES**

    # Schedule a new cron event
    $ wp cron event schedule cron_test
    Success: Scheduled event with hook 'cron_test' for 2016-05-31 10:19:16 GMT.

    # Run all cron events due right now
    $ wp cron event run --due-now
    Success: Executed a total of 2 cron events.

    # Delete all scheduled cron events for the given hook
    $ wp cron event delete cron_test
    Success: Deleted 2 instances of the cron event 'cron_test'.

    # List scheduled cron events in JSON
    $ wp cron event list --fields=hook,next_run --format=json
    [{"hook":"wp_version_check","next_run":"2016-05-31 10:15:13"},{"hook":"wp_update_plugins","next_run":"2016-05-31 10:15:13"},{"hook":"wp_update_themes","next_run":"2016-05-31 10:15:14"}]





### wp cron event delete

Deletes all scheduled cron events for the given hook.

~~~
wp cron event delete <hook>
~~~

**OPTIONS**

	<hook>
		The hook name.

**EXAMPLES**

    # Delete all scheduled cron events for the given hook
    $ wp cron event delete cron_test
    Success: Deleted 2 instances of the cron event 'cron_test'.



### wp cron event list

Lists scheduled cron events.

~~~
wp cron event list [--fields=<fields>] [--<field>=<value>] [--field=<field>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--<field>=<value>]
		Filter by one or more fields.

	[--field=<field>]
		Prints the value of a single field for each event.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each cron event:
* hook
* next_run_gmt
* next_run_relative
* recurrence

These fields are optionally available:
* time
* sig
* args
* schedule
* interval
* next_run

**EXAMPLES**

    # List scheduled cron events
    $ wp cron event list
    +-------------------+---------------------+---------------------+------------+
    | hook              | next_run_gmt        | next_run_relative   | recurrence |
    +-------------------+---------------------+---------------------+------------+
    | wp_version_check  | 2016-05-31 22:15:13 | 11 hours 57 minutes | 12 hours   |
    | wp_update_plugins | 2016-05-31 22:15:13 | 11 hours 57 minutes | 12 hours   |
    | wp_update_themes  | 2016-05-31 22:15:14 | 11 hours 57 minutes | 12 hours   |
    +-------------------+---------------------+---------------------+------------+

    # List scheduled cron events in JSON
    $ wp cron event list --fields=hook,next_run --format=json
    [{"hook":"wp_version_check","next_run":"2016-05-31 10:15:13"},{"hook":"wp_update_plugins","next_run":"2016-05-31 10:15:13"},{"hook":"wp_update_themes","next_run":"2016-05-31 10:15:14"}]



### wp cron event run

Runs the next scheduled cron event for the given hook.

~~~
wp cron event run [<hook>...] [--due-now] [--all]
~~~

**OPTIONS**

	[<hook>...]
		One or more hooks to run.

	[--due-now]
		Run all hooks due right now.

	[--all]
		Run all hooks.

**EXAMPLES**

    # Run all cron events due right now
    $ wp cron event run --due-now
    Success: Executed a total of 2 cron events.



### wp cron event schedule

Schedules a new cron event.

~~~
wp cron event schedule <hook> [<next-run>] [<recurrence>] [--<field>=<value>]
~~~

**OPTIONS**

	<hook>
		The hook name.

	[<next-run>]
		A Unix timestamp or an English textual datetime description compatible with `strtotime()`. Defaults to now.

	[<recurrence>]
		How often the event should recur. See `wp cron schedule list` for available schedule names. Defaults to no recurrence.

	[--<field>=<value>]
		Arguments to pass to the hook for the event. <field> should be a numeric key, not a string.

**EXAMPLES**

    # Schedule a new cron event
    $ wp cron event schedule cron_test
    Success: Scheduled event with hook 'cron_test' for 2016-05-31 10:19:16 GMT.

    # Schedule new cron event with hourly recurrence
    $ wp cron event schedule cron_test now hourly
    Success: Scheduled event with hook 'cron_test' for 2016-05-31 10:20:32 GMT.

    # Schedule new cron event and pass arguments
    $ wp cron event schedule cron_test '+1 hour' --0=first-argument --1=second-argument
    Success: Scheduled event with hook 'cron_test' for 2016-05-31 11:21:35 GMT.



### wp cron schedule

Gets WP-Cron schedules.

~~~
wp cron schedule
~~~

**EXAMPLES**

    # List available cron schedules
    $ wp cron schedule list
    +------------+-------------+----------+
    | name       | display     | interval |
    +------------+-------------+----------+
    | hourly     | Once Hourly | 3600     |
    | twicedaily | Twice Daily | 43200    |
    | daily      | Once Daily  | 86400    |
    +------------+-------------+----------+





### wp cron schedule list

List available cron schedules.

~~~
wp cron schedule list [--fields=<fields>] [--field=<field>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--field=<field>]
		Prints the value of a single field for each schedule.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each cron schedule:

* name
* display
* interval

There are no additional fields.

**EXAMPLES**

    # List available cron schedules
    $ wp cron schedule list
    +------------+-------------+----------+
    | name       | display     | interval |
    +------------+-------------+----------+
    | hourly     | Once Hourly | 3600     |
    | twicedaily | Twice Daily | 43200    |
    | daily      | Once Daily  | 86400    |
    +------------+-------------+----------+

    # List id of available cron schedule
    $ wp cron schedule list --fields=name --format=ids
    hourly twicedaily daily



### wp cron event unschedule

Unschedules all cron events for a given hook.

~~~
wp cron event unschedule <hook>
~~~

**OPTIONS**

	<hook>
		Name of the hook for which all events should be unscheduled.

**EXAMPLES**

    # Unschedule a cron event on given hook.
    $ wp cron event unschedule cron_test
    Success: Unscheduled 2 events with hook 'cron_test'.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/cron-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/cron-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/cron-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/cron-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
