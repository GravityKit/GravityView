wp-cli/checksum-command
=======================

Verifies file integrity by comparing to published checksums.

[![Testing](https://github.com/wp-cli/checksum-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/checksum-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp core verify-checksums

Verifies WordPress files against WordPress.org's checksums.

~~~
wp core verify-checksums [--version=<version>] [--locale=<locale>] [--insecure]
~~~

Downloads md5 checksums for the current version from WordPress.org, and
compares those checksums against the currently installed files.

For security, avoids loading WordPress when verifying checksums.

If you experience issues verifying from this command, ensure you are
passing the relevant `--locale` and `--version` arguments according to
the values from the `Dashboard->Updates` menu in the admin area of the
site.

**OPTIONS**

	[--version=<version>]
		Verify checksums against a specific version of WordPress.

	[--locale=<locale>]
		Verify checksums against a specific locale of WordPress.

	[--insecure]
		Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.

**EXAMPLES**

    # Verify checksums
    $ wp core verify-checksums
    Success: WordPress installation verifies against checksums.

    # Verify checksums for given WordPress version
    $ wp core verify-checksums --version=4.0
    Success: WordPress installation verifies against checksums.

    # Verify checksums for given locale
    $ wp core verify-checksums --locale=en_US
    Success: WordPress installation verifies against checksums.

    # Verify checksums for given locale
    $ wp core verify-checksums --locale=ja
    Warning: File doesn't verify against checksum: wp-includes/version.php
    Warning: File doesn't verify against checksum: readme.html
    Warning: File doesn't verify against checksum: wp-config-sample.php
    Error: WordPress installation doesn't verify against checksums.



### wp plugin verify-checksums

Verifies plugin files against WordPress.org's checksums.

~~~
wp plugin verify-checksums [<plugin>...] [--all] [--strict] [--format=<format>] [--insecure]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to verify.

	[--all]
		If set, all plugins will be verified.

	[--strict]
		If set, even "soft changes" like readme.txt changes will trigger
		checksum errors.

	[--format=<format>]
		Render output in a specific format.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - yaml
		  - count
		---

	[--insecure]
		Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.

**EXAMPLES**

    # Verify the checksums of all installed plugins
    $ wp plugin verify-checksums --all
    Success: Verified 8 of 8 plugins.

    # Verify the checksums of a single plugin, Akismet in this case
    $ wp plugin verify-checksums akismet
    Success: Verified 1 of 1 plugins.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/checksum-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/checksum-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/checksum-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/checksum-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
