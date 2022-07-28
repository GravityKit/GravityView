wp-cli/embed-command
====================

Inspects oEmbed providers, clears embed cache, and more.

[![Testing](https://github.com/wp-cli/embed-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/embed-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp embed

Inspects oEmbed providers, clears embed cache, and more.

~~~
wp embed
~~~





### wp embed fetch

Attempts to convert a URL into embed HTML.

~~~
wp embed fetch <url> [--width=<width>] [--height=<height>] [--post-id=<id>] [--discover] [--skip-cache] [--skip-sanitization] [--do-shortcode] [--limit-response-size=<size>] [--raw] [--raw-format=<json|xml>]
~~~

In non-raw mode, starts by checking the URL against the regex of the registered embed handlers.
If none of the regex matches and it's enabled, then the URL will be given to the WP_oEmbed class.

In raw mode, checks the providers directly and returns the data.

**OPTIONS**

	<url>
		URL to retrieve oEmbed data for.

	[--width=<width>]
		Width of the embed in pixels.

	[--height=<height>]
		Height of the embed in pixels.

	[--post-id=<id>]
		Cache oEmbed response for a given post.

	[--discover]
		Enable oEmbed discovery. Defaults to true.

	[--skip-cache]
		Ignore already cached oEmbed responses. Has no effect if using the 'raw' option, which doesn't use the cache.

	[--skip-sanitization]
		Remove the filter that WordPress from 4.4 onwards uses to sanitize oEmbed responses. Has no effect if using the 'raw' option, which by-passes sanitization.

	[--do-shortcode]
		If the URL is handled by a registered embed handler and returns a shortcode, do shortcode and return result. Has no effect if using the 'raw' option, which by-passes handlers.

	[--limit-response-size=<size>]
		Limit the size of the resulting HTML when using discovery. Default 150 KB (the standard WordPress limit). Not compatible with 'no-discover'.

	[--raw]
		Return the raw oEmbed response instead of the resulting HTML. Ignores the cache and does not sanitize responses or use registered embed handlers.

	[--raw-format=<json|xml>]
		Render raw oEmbed data in a particular format. Defaults to json. Can only be specified in conjunction with the 'raw' option.
		---
		options:
		  - json
		  - xml
		---

**EXAMPLES**

    # Get embed HTML for a given URL.
    $ wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ
    <iframe width="525" height="295" src="https://www.youtube.com/embed/dQw4w9WgXcQ?feature=oembed" ...

    # Get raw oEmbed data for a given URL.
    $ wp embed fetch https://www.youtube.com/watch?v=dQw4w9WgXcQ --raw
    {"author_url":"https:\/\/www.youtube.com\/user\/RickAstleyVEVO","width":525,"version":"1.0", ...



### wp embed provider

Retrieves oEmbed providers.

~~~
wp embed provider
~~~





### wp embed provider list

Lists all available oEmbed providers.

~~~
wp embed provider list [--field=<field>] [--fields=<fields>] [--format=<format>] [--force-regex]
~~~

**OPTIONS**

	[--field=<field>]
		Display the value of a single field

	[--fields=<fields>]
		Limit the output to specific fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		---

	[--force-regex]
		Turn the asterisk-type provider URLs into regexes.

**AVAILABLE FIELDS**

These fields will be displayed by default for each provider:

* format
* endpoint

This field is optionally available:

* regex

**EXAMPLES**

    # List format,endpoint fields of available providers.
    $ wp embed provider list --fields=format,endpoint
    +------------------------------+-----------------------------------------+
    | format                       | endpoint                                |
    +------------------------------+-----------------------------------------+
    | #https?://youtu\.be/.*#i     | https://www.youtube.com/oembed          |
    | #https?://flic\.kr/.*#i      | https://www.flickr.com/services/oembed/ |
    | #https?://wordpress\.tv/.*#i | https://wordpress.tv/oembed/            |



### wp embed provider match

Gets the matching provider for a given URL.

~~~
wp embed provider match <url> [--discover] [--limit-response-size=<size>] [--link-type=<json|xml>]
~~~

**OPTIONS**

	<url>
		URL to retrieve provider for.

	[--discover]
		Whether to use oEmbed discovery or not. Defaults to true.

	[--limit-response-size=<size>]
		Limit the size of the resulting HTML when using discovery. Default 150 KB (the standard WordPress limit). Not compatible with 'no-discover'.

	[--link-type=<json|xml>]
		Whether to accept only a certain link type when using discovery. Defaults to any (json or xml), preferring json. Not compatible with 'no-discover'.
		---
		options:
		  - json
		  - xml
		---

**EXAMPLES**

    # Get the matching provider for the URL.
    $ wp embed provider match https://www.youtube.com/watch?v=dQw4w9WgXcQ
    https://www.youtube.com/oembed



### wp embed handler

Retrieves embed handlers.

~~~
wp embed handler
~~~





### wp embed handler list

Lists all available embed handlers.

~~~
wp embed handler list [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--field=<field>]
		Display the value of a single field

	[--fields=<fields>]
		Limit the output to specific fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each handler:

* id
* regex

These fields are optionally available:

* callback
* priority

**EXAMPLES**

    # List id,regex,priority fields of available handlers.
    $ wp embed handler list --fields=priority,id
    +----------+-------------------+
    | priority | id                |
    +----------+-------------------+
    | 10       | youtube_embed_url |
    | 9999     | audio             |
    | 9999     | video             |



### wp embed cache

Finds, triggers, and deletes oEmbed caches.

~~~
wp embed cache
~~~





### wp embed cache clear

Deletes all oEmbed caches for a given post.

~~~
wp embed cache clear <post_id>
~~~

oEmbed caches for a post are stored in the post's metadata.

**OPTIONS**

	<post_id>
		ID of the post to clear the cache for.

**EXAMPLES**

    # Clear cache for a post
    $ wp embed cache clear 123
    Success: Cleared oEmbed cache.



### wp embed cache find

Finds an oEmbed cache post ID for a given URL.

~~~
wp embed cache find <url> [--width=<width>] [--height=<height>] [--discover]
~~~

Starting with WordPress 4.9, embeds that aren't associated with a specific post will be cached in
a new oembed_cache post type. There can be more than one such entry for a url depending on attributes and context.

Not to be confused with oEmbed caches for a given post which are stored in the post's metadata.

**OPTIONS**

	<url>
		URL to retrieve oEmbed data for.

	[--width=<width>]
		Width of the embed in pixels. Part of cache key so must match. Defaults to `content_width` if set else 500px, so is theme and context dependent.

	[--height=<height>]
		Height of the embed in pixels. Part of cache key so must match. Defaults to 1.5 * default width (`content_width` or 500px), to a maximum of 1000px.

	[--discover]
		Whether to search with the discover attribute set or not. Part of cache key so must match. If not given, will search with attribute: unset, '1', '0', returning first.

**EXAMPLES**

    # Find cache post ID for a given URL.
    $ wp embed cache find https://www.youtube.com/watch?v=dQw4w9WgXcQ --width=500
    123



### wp embed cache trigger

Triggers the caching of all oEmbed results for a given post.

~~~
wp embed cache trigger <post_id>
~~~

oEmbed caches for a post are stored in the post's metadata.

**OPTIONS**

	<post_id>
		ID of the post to do the caching for.

**EXAMPLES**

    # Triggers cache for a post
    $ wp embed cache trigger 456
    Success: Caching triggered!

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/embed-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/embed-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/embed-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/embed-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
