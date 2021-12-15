wp-cli/server-command
=====================

Launches PHP's built-in web server for a specific WordPress installation.

[![Testing](https://github.com/wp-cli/server-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/server-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

~~~
wp server [--host=<host>] [--port=<port>] [--docroot=<path>] [--config=<file>]
~~~

Uses `php -S` to launch a web server serving the WordPress webroot.
<http://php.net/manual/en/features.commandline.webserver.php>

Importantly, PHP's built-in web server doesn't support `.htaccess` files.
If this is a requirement, please use a more advanced web server.

**OPTIONS**

	[--host=<host>]
		The hostname to bind the server to.
		---
		default: localhost
		---

	[--port=<port>]
		The port number to bind the server to.
		---
		default: 8080
		---

	[--docroot=<path>]
		The path to use as the document root. If the path global parameter is
		set, the default value is it.

	[--config=<file>]
		Configure the server with a specific .ini file.

**EXAMPLES**

    # Make the instance available on any address (with port 8080)
    $ wp server --host=0.0.0.0
    PHP 5.6.9 Development Server started at Tue May 24 01:27:11 2016
    Listening on http://0.0.0.0:8080
    Document root is /
    Press Ctrl-C to quit.

    # Run on port 80 (for multisite)
    $ wp server --host=localhost.localdomain --port=80
    PHP 5.6.9 Development Server started at Tue May 24 01:30:06 2016
    Listening on http://localhost1.localdomain1:80
    Document root is /
    Press Ctrl-C to quit.

    # Configure the server with a specific .ini file
    $ wp server --config=development.ini
    PHP 7.0.9 Development Server started at Mon Aug 22 12:09:04 2016
    Listening on http://localhost:8080
    Document root is /
    Press Ctrl-C to quit.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/server-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/server-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/server-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/server-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
