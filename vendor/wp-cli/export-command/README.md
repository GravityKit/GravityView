wp-cli/export-command
=====================

Exports WordPress content to a WXR file.

[![Testing](https://github.com/wp-cli/export-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/export-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

~~~
wp export [--dir=<dirname>] [--stdout] [--skip_comments] [--max_file_size=<MB>] [--start_date=<date>] [--end_date=<date>] [--post_type=<post-type>] [--post_type__not_in=<post-type>] [--post__in=<pid>] [--with_attachments] [--start_id=<pid>] [--max_num_posts=<num>] [--author=<author>] [--category=<name|id>] [--post_status=<status>] [--filename_format=<format>]
~~~

Generates one or more WXR files containing authors, terms, posts,
comments, and attachments. WXR files do not include site configuration
(options) or the attachment files themselves.

**OPTIONS**

	[--dir=<dirname>]
		Full path to directory where WXR export files should be stored. Defaults
		to current working directory.

	[--stdout]
		Output the whole XML using standard output (incompatible with --dir=)

	[--skip_comments]
		Don't include comments in the WXR export file.

	[--max_file_size=<MB>]
		A single export file should have this many megabytes. -1 for unlimited.
		---
		default: 15
		---

**FILTERS**

	[--start_date=<date>]
		Export only posts published after this date, in format YYYY-MM-DD.

	[--end_date=<date>]
		Export only posts published before this date, in format YYYY-MM-DD.

	[--post_type=<post-type>]
		Export only posts with this post_type. Separate multiple post types with a
		comma.
		---
		default: any
		---

	[--post_type__not_in=<post-type>]
		Export all post types except those identified. Separate multiple post types
		with a comma. Defaults to none.

	[--post__in=<pid>]
		Export all posts specified as a comma- or space-separated list of IDs.
		Post's attachments won't be exported unless --with_attachments is specified.

	[--with_attachments]
		Force including attachments in case --post__in has been specified.

	[--start_id=<pid>]
		Export only posts with IDs greater than or equal to this post ID.

	[--max_num_posts=<num>]
		Export no more than <num> posts (excluding attachments).

	[--author=<author>]
		Export only posts by this author. Can be either user login or user ID.

	[--category=<name|id>]
		Export only posts in this category.

	[--post_status=<status>]
		Export only posts with this status.

	[--filename_format=<format>]
		Use a custom format for export filenames. Defaults to '{site}.wordpress.{date}.{n}.xml'.

**EXAMPLES**

    # Export posts published by the user between given start and end date
    $ wp export --dir=/tmp/ --user=admin --post_type=post --start_date=2011-01-01 --end_date=2011-12-31
    Starting export process...
    Writing to file /tmp/staging.wordpress.2016-05-24.000.xml
    Success: All done with export.

    # Export posts by IDs
    $ wp export --dir=/tmp/ --post__in=123,124,125
    Starting export process...
    Writing to file /tmp/staging.wordpress.2016-05-24.000.xml
    Success: All done with export.

    # Export a random subset of content
    $ wp export --post__in="$(wp post list --post_type=post --orderby=rand --posts_per_page=8 --format=ids)"
    Starting export process...
    Writing to file /var/www/example.com/public_html/staging.wordpress.2016-05-24.000.xml
    Success: All done with export.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/export-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/export-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/export-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/export-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
