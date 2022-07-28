wp-cli/eval-command
===================

Executes arbitrary PHP code or files.

[![Testing](https://github.com/wp-cli/eval-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/eval-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp eval

Executes arbitrary PHP code.

~~~
wp eval <php-code> [--skip-wordpress]
~~~

Note: because code is executed within a method, global variables need
to be explicitly globalized.

**OPTIONS**

	<php-code>
		The code to execute, as a string.

	[--skip-wordpress]
		Execute code without loading WordPress.

**EXAMPLES**

    # Display WordPress content directory.
    $ wp eval 'echo WP_CONTENT_DIR;'
    /var/www/wordpress/wp-content

    # Generate a random number.
    $ wp eval 'echo rand();' --skip-wordpress
    479620423



### wp eval-file

Loads and executes a PHP file.

~~~
wp eval-file <file> [<arg>...] [--skip-wordpress]
~~~

Note: because code is executed within a method, global variables need
to be explicitly globalized.

**OPTIONS**

	<file>
		The path to the PHP file to execute.  Use '-' to run code from STDIN.

	[<arg>...]
		One or more arguments to pass to the file. They are placed in the $args variable.

	[--skip-wordpress]
		Load and execute file without loading WordPress.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/eval-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/eval-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/eval-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/eval-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
