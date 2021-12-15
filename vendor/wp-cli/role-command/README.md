wp-cli/role-command
===================

Adds, removes, lists, and resets roles and capabilities.

[![Testing](https://github.com/wp-cli/role-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/role-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp role

Manages user roles, including creating new roles and resetting to defaults.

~~~
wp role
~~~

See references for [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities) and [WP User class](https://codex.wordpress.org/Class_Reference/WP_User).

**EXAMPLES**

    # List roles.
    $ wp role list --fields=role --format=csv
    role
    administrator
    editor
    author
    contributor
    subscriber

    # Check to see if a role exists.
    $ wp role exists editor
    Success: Role with ID 'editor' exists.

    # Create a new role.
    $ wp role create approver Approver
    Success: Role with key 'approver' created.

    # Delete an existing role.
    $ wp role delete approver
    Success: Role with key 'approver' deleted.

    # Reset existing roles to their default capabilities.
    $ wp role reset administrator author contributor
    Success: Reset 3/3 roles.



### wp role create

Creates a new role.

~~~
wp role create <role-key> <role-name> [--clone=<role>]
~~~

**OPTIONS**

	<role-key>
		The internal name of the role.

	<role-name>
		The publicly visible name of the role.

	[--clone=<role>]
		Clone capabilities from an existing role.

**EXAMPLES**

    # Create role for Approver.
    $ wp role create approver Approver
    Success: Role with key 'approver' created.

    # Create role for Product Administrator.
    $ wp role create productadmin "Product Administrator"
    Success: Role with key 'productadmin' created.



### wp role delete

Deletes an existing role.

~~~
wp role delete <role-key>
~~~

**OPTIONS**

	<role-key>
		The internal name of the role.

**EXAMPLES**

    # Delete approver role.
    $ wp role delete approver
    Success: Role with key 'approver' deleted.

    # Delete productadmin role.
    wp role delete productadmin
    Success: Role with key 'productadmin' deleted.



### wp role exists

Checks if a role exists.

~~~
wp role exists <role-key>
~~~

Exits with return code 0 if the role exists, 1 if it does not.

**OPTIONS**

	<role-key>
		The internal name of the role.

**EXAMPLES**

    # Check if a role exists.
    $ wp role exists editor
    Success: Role with ID 'editor' exists.



### wp role list

Lists all roles.

~~~
wp role list [--fields=<fields>] [--field=<field>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--field=<field>]
		Prints the value of a single field.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each role:

* name
* role

There are no optional fields.

**EXAMPLES**

    # List roles.
    $ wp role list --fields=role --format=csv
    role
    administrator
    editor
    author
    contributor
    subscriber



### wp role reset

Resets any default role to default capabilities.

~~~
wp role reset [<role-key>...] [--all]
~~~

**OPTIONS**

	[<role-key>...]
		The internal name of one or more roles to reset.

	[--all]
		If set, all default roles will be reset.

**EXAMPLES**

    # Reset role.
    $ wp role reset administrator author contributor
    Success: Reset 1/3 roles.

    # Reset all default roles.
    $ wp role reset --all
    Success: All default roles reset.



### wp cap

Adds, removes, and lists capabilities of a user role.

~~~
wp cap
~~~

See references for [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities) and [WP User class](https://codex.wordpress.org/Class_Reference/WP_User).

**EXAMPLES**

    # Add 'spectate' capability to 'author' role.
    $ wp cap add 'author' 'spectate'
    Success: Added 1 capability to 'author' role.

    # Add all caps from 'editor' role to 'author' role.
    $ wp cap list 'editor' | xargs wp cap add 'author'
    Success: Added 24 capabilities to 'author' role.

    # Remove all caps from 'editor' role that also appear in 'author' role.
    $ wp cap list 'author' | xargs wp cap remove 'editor'
    Success: Removed 34 capabilities from 'editor' role.



### wp cap add

Adds capabilities to a given role.

~~~
wp cap add <role> <cap>... [--grant]
~~~

**OPTIONS**

	<role>
		Key for the role.

	<cap>...
		One or more capabilities to add.

	[--grant]
		Adds the capability as an explicit boolean value, instead of implicitly defaulting to `true`.
		---
		default: true
		options:
		  - true
		  - false
		---

**EXAMPLES**

    # Add 'spectate' capability to 'author' role.
    $ wp cap add author spectate
    Success: Added 1 capability to 'author' role.



### wp cap list

Lists capabilities for a given role.

~~~
wp cap list <role> [--format=<format>] [--show-grant]
~~~

**OPTIONS**

	<role>
		Key for the role.

	[--format=<format>]
		Render output in a particular format.
		---
		default: list
		options:
		  - list
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

	[--show-grant]
		Display all capabilities defined for a role including grant.
		---
		default: false
		---

**EXAMPLES**

    # Display alphabetical list of Contributor capabilities.
    $ wp cap list 'contributor' | sort
    delete_posts
    edit_posts
    level_0
    level_1
    read



### wp cap remove

Removes capabilities from a given role.

~~~
wp cap remove <role> <cap>...
~~~

**OPTIONS**

	<role>
		Key for the role.

	<cap>...
		One or more capabilities to remove.

**EXAMPLES**

    # Remove 'spectate' capability from 'author' role.
    $ wp cap remove author spectate
    Success: Removed 1 capability from 'author' role.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/role-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/role-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/role-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/role-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
