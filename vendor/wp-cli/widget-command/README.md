wp-cli/widget-command
=====================

Adds, moves, and removes widgets; lists sidebars.

[![Testing](https://github.com/wp-cli/widget-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/widget-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp widget

Manages widgets, including adding and moving them within sidebars.

~~~
wp widget
~~~

A [widget](https://developer.wordpress.org/themes/functionality/widgets/) adds content and features to a widget area (also called a [sidebar](https://developer.wordpress.org/themes/functionality/sidebars/)).

**EXAMPLES**

    # List widgets on a given sidebar
    $ wp widget list sidebar-1
    +----------+------------+----------+----------------------+
    | name     | id         | position | options              |
    +----------+------------+----------+----------------------+
    | meta     | meta-6     | 1        | {"title":"Meta"}     |
    | calendar | calendar-2 | 2        | {"title":"Calendar"} |
    +----------+------------+----------+----------------------+

    # Add a calendar widget to the second position on the sidebar
    $ wp widget add calendar sidebar-1 2
    Success: Added widget to sidebar.

    # Update option(s) associated with a given widget
    $ wp widget update calendar-1 --title="Calendar"
    Success: Widget updated.

    # Delete one or more widgets entirely
    $ wp widget delete calendar-2 archive-1
    Success: 2 widgets removed from sidebar.



### wp widget add

Adds a widget to a sidebar.

~~~
wp widget add <name> <sidebar-id> [<position>] [--<field>=<value>]
~~~

Creates a new widget entry in the database, and associates it with the
sidebar.

**OPTIONS**

	<name>
		Widget name.

	<sidebar-id>
		ID for the corresponding sidebar.

	[<position>]
		Widget's current position within the sidebar. Defaults to last

	[--<field>=<value>]
		Widget option to add, with its new value

**EXAMPLES**

    # Add a new calendar widget to sidebar-1 with title "Calendar"
    $ wp widget add calendar sidebar-1 2 --title="Calendar"
    Success: Added widget to sidebar.



### wp widget deactivate

Deactivates one or more widgets from an active sidebar.

~~~
wp widget deactivate <widget-id>...
~~~

Moves widgets to Inactive Widgets.

**OPTIONS**

	<widget-id>...
		Unique ID for the widget(s)

**EXAMPLES**

    # Deactivate the recent-comments-2 widget.
    $ wp widget deactivate recent-comments-2
    Success: 1 widget deactivated.



### wp widget delete

Deletes one or more widgets from a sidebar.

~~~
wp widget delete <widget-id>...
~~~

**OPTIONS**

	<widget-id>...
		Unique ID for the widget(s)

**EXAMPLES**

    # Delete the recent-comments-2 widget from its sidebar.
    $ wp widget delete recent-comments-2
    Success: Deleted 1 of 1 widgets.



### wp widget list

Lists widgets associated with a sidebar.

~~~
wp widget list <sidebar-id> [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<sidebar-id>
		ID for the corresponding sidebar.

	[--fields=<fields>]
		Limit the output to specific object fields.

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

These fields will be displayed by default for each widget:

* name
* id
* position
* options

There are no optionally available fields.

**EXAMPLES**

    $ wp widget list sidebar-1 --fields=name,id --format=csv
    name,id
    meta,meta-5
    search,search-3



### wp widget move

Moves the position of a widget.

~~~
wp widget move <widget-id> [--position=<position>] [--sidebar-id=<sidebar-id>]
~~~

Changes the order of a widget in its existing sidebar, or moves it to a
new sidebar.

**OPTIONS**

	<widget-id>
		Unique ID for the widget

	[--position=<position>]
		Assign the widget to a new position.

	[--sidebar-id=<sidebar-id>]
		Assign the widget to a new sidebar

**EXAMPLES**

    # Change position of widget
    $ wp widget move recent-comments-2 --position=2
    Success: Widget moved.

    # Move widget to Inactive Widgets
    $ wp widget move recent-comments-2 --sidebar-id=wp_inactive_widgets
    Success: Widget moved.



### wp widget reset

Resets sidebar.

~~~
wp widget reset [<sidebar-id>...] [--all]
~~~

Removes all widgets from the sidebar and places them in Inactive Widgets.

**OPTIONS**

	[<sidebar-id>...]
		One or more sidebars to reset.

	[--all]
		If set, all sidebars will be reset.

**EXAMPLES**

    # Reset a sidebar
    $ wp widget reset sidebar-1
    Success: Sidebar 'sidebar-1' reset.

    # Reset multiple sidebars
    $ wp widget reset sidebar-1 sidebar-2
    Success: Sidebar 'sidebar-1' reset.
    Success: Sidebar 'sidebar-2' reset.

    # Reset all sidebars
    $ wp widget reset --all
    Success: Sidebar 'sidebar-1' reset.
    Success: Sidebar 'sidebar-2' reset.
    Success: Sidebar 'sidebar-3' reset.



### wp widget update

Updates options for an existing widget.

~~~
wp widget update <widget-id> [--<field>=<value>]
~~~

**OPTIONS**

	<widget-id>
		Unique ID for the widget

	[--<field>=<value>]
		Field to update, with its new value

**EXAMPLES**

    # Change calendar-1 widget title to "Our Calendar"
    $ wp widget update calendar-1 --title="Our Calendar"
    Success: Widget updated.



### wp sidebar

Lists registered sidebars.

~~~
wp sidebar
~~~

A [sidebar](https://developer.wordpress.org/themes/functionality/sidebars/) is any widgetized area of your theme.

**EXAMPLES**

    # List sidebars
    $ wp sidebar list --fields=name,id --format=csv
    name,id
    "Widget Area",sidebar-1
    "Inactive Widgets",wp_inactive_widgets



### wp sidebar list

Lists registered sidebars.

~~~
wp sidebar list [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - ids
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each sidebar:

* name
* id
* description

These fields are optionally available:

* class
* before_widget
* after_widget
* before_title
* after_title

**EXAMPLES**

    $ wp sidebar list --fields=name,id --format=csv
    name,id
    "Widget Area",sidebar-1
    "Inactive Widgets",wp_inactive_widgets

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/widget-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/widget-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/widget-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/widget-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
