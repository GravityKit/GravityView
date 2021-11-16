wp-cli/cache-command
====================

Manages object and transient caches.

[![Testing](https://github.com/wp-cli/cache-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/cache-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp cache

Adds, removes, fetches, and flushes the WP Object Cache object.

~~~
wp cache
~~~

By default, the WP Object Cache exists in PHP memory for the length of the
request (and is emptied at the end). Use a persistent object cache drop-in
to persist the object cache between requests.

[Read the codex article](https://codex.wordpress.org/Class_Reference/WP_Object_Cache)
for more detail.

**EXAMPLES**

    # Set cache.
    $ wp cache set my_key my_value my_group 300
    Success: Set object 'my_key' in group 'my_group'.

    # Get cache.
    $ wp cache get my_key my_group
    my_value



### wp cache add

Adds a value to the object cache.

~~~
wp cache add <key> <value> [<group>] [<expiration>]
~~~

Errors if a value already exists for the key, which means the value can't
be added.

**OPTIONS**

	<key>
		Cache key.

	<value>
		Value to add to the key.

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

	[<expiration>]
		Define how long to keep the value, in seconds. `0` means as long as possible.
		---
		default: 0
		---

**EXAMPLES**

    # Add cache.
    $ wp cache add my_key my_group my_value 300
    Success: Added object 'my_key' in group 'my_value'.



### wp cache decr

Decrements a value in the object cache.

~~~
wp cache decr <key> [<offset>] [<group>]
~~~

Errors if the value can't be decremented.

**OPTIONS**

	<key>
		Cache key.

	[<offset>]
		The amount by which to decrement the item's value.
		---
		default: 1
		---

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

**EXAMPLES**

    # Decrease cache value.
    $ wp cache decr my_key 2 my_group
    48



### wp cache delete

Removes a value from the object cache.

~~~
wp cache delete <key> [<group>]
~~~

Errors if the value can't be deleted.

**OPTIONS**

	<key>
		Cache key.

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

**EXAMPLES**

    # Delete cache.
    $ wp cache delete my_key my_group
    Success: Object deleted.



### wp cache flush

Flushes the object cache.

~~~
wp cache flush 
~~~

For WordPress multisite instances using a persistent object cache,
flushing the object cache will typically flush the cache for all sites.
Beware of the performance impact when flushing the object cache in
production.

Errors if the object cache can't be flushed.

**EXAMPLES**

    # Flush cache.
    $ wp cache flush
    Success: The cache was flushed.



### wp cache get

Gets a value from the object cache.

~~~
wp cache get <key> [<group>]
~~~

Errors if the value doesn't exist.

**OPTIONS**

	<key>
		Cache key.

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

**EXAMPLES**

    # Get cache.
    $ wp cache get my_key my_group
    my_value



### wp cache incr

Increments a value in the object cache.

~~~
wp cache incr <key> [<offset>] [<group>]
~~~

Errors if the value can't be incremented.

**OPTIONS**

	<key>
		Cache key.

	[<offset>]
		The amount by which to increment the item's value.
		---
		default: 1
		---

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

**EXAMPLES**

    # Increase cache value.
    $ wp cache incr my_key 2 my_group
    50



### wp cache replace

Replaces a value in the object cache, if the value already exists.

~~~
wp cache replace <key> <value> [<group>] [<expiration>]
~~~

Errors if the value can't be replaced.

**OPTIONS**

	<key>
		Cache key.

	<value>
		Value to replace.

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

	[<expiration>]
		Define how long to keep the value, in seconds. `0` means as long as possible.
		---
		default: 0
		---

**EXAMPLES**

    # Replace cache.
    $ wp cache replace my_key new_value my_group
    Success: Replaced object 'my_key' in group 'my_group'.



### wp cache set

Sets a value to the object cache, regardless of whether it already exists.

~~~
wp cache set <key> <value> [<group>] [<expiration>]
~~~

Errors if the value can't be set.

**OPTIONS**

	<key>
		Cache key.

	<value>
		Value to set on the key.

	[<group>]
		Method for grouping data within the cache which allows the same key to be used across groups.
		---
		default: default
		---

	[<expiration>]
		Define how long to keep the value, in seconds. `0` means as long as possible.
		---
		default: 0
		---

**EXAMPLES**

    # Set cache.
    $ wp cache set my_key my_value my_group 300
    Success: Set object 'my_key' in group 'my_group'.



### wp cache type

Attempts to determine which object cache is being used.

~~~
wp cache type 
~~~

Note that the guesses made by this function are based on the
WP_Object_Cache classes that define the 3rd party object cache extension.
Changes to those classes could render problems with this function's
ability to determine which object cache is being used.

**EXAMPLES**

    # Check cache type.
    $ wp cache type
    Default



### wp transient

Adds, gets, and deletes entries in the WordPress Transient Cache.

~~~
wp transient
~~~

By default, the transient cache uses the WordPress database to persist values
between requests. On a single site installation, values are stored in the
`wp_options` table. On a multisite installation, values are stored in the
`wp_options` or the `wp_sitemeta` table, depending on use of the `--network`
flag.

When a persistent object cache drop-in is installed (e.g. Redis or Memcached),
the transient cache skips the database and simply wraps the WP Object Cache.

**EXAMPLES**

    # Set transient.
    $ wp transient set sample_key "test data" 3600
    Success: Transient added.

    # Get transient.
    $ wp transient get sample_key
    test data

    # Delete transient.
    $ wp transient delete sample_key
    Success: Transient deleted.

    # Delete expired transients.
    $ wp transient delete --expired
    Success: 12 expired transients deleted from the database.

    # Delete all transients.
    $ wp transient delete --all
    Success: 14 transients deleted from the database.



### wp transient delete

Deletes a transient value.

~~~
wp transient delete [<key>] [--network] [--all] [--expired]
~~~

For a more complete explanation of the transient cache, including the
network|site cache, please see docs for `wp transient`.

**OPTIONS**

	[<key>]
		Key for the transient.

	[--network]
		Delete the value of a network|site transient. On single site, this is
		is a specially-named cache key. On multisite, this is a global cache
		(instead of local to the site).

	[--all]
		Delete all transients.

	[--expired]
		Delete all expired transients.

**EXAMPLES**

    # Delete transient.
    $ wp transient delete sample_key
    Success: Transient deleted.

    # Delete expired transients.
    $ wp transient delete --expired
    Success: 12 expired transients deleted from the database.

    # Delete expired site transients.
    $ wp transient delete --expired --network
    Success: 1 expired transient deleted from the database.

    # Delete all transients.
    $ wp transient delete --all
    Success: 14 transients deleted from the database.

    # Delete all site transients.
    $ wp transient delete --all --network
    Success: 2 transients deleted from the database.

    # Delete all transients in a multsite.
    $ wp transient delete --all --network && wp site list --field=url | xargs -n1 -I % wp --url=% transient delete --all



### wp transient get

Gets a transient value.

~~~
wp transient get <key> [--format=<format>] [--network]
~~~

For a more complete explanation of the transient cache, including the
network|site cache, please see docs for `wp transient`.

**OPTIONS**

	<key>
		Key for the transient.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

	[--network]
		Get the value of a network|site transient. On single site, this is
		is a specially-named cache key. On multisite, this is a global cache
		(instead of local to the site).

**EXAMPLES**

    $ wp transient get sample_key
    test data

    $ wp transient get random_key
    Warning: Transient with key "random_key" is not set.



### wp transient set

Sets a transient value.

~~~
wp transient set <key> <value> [<expiration>] [--network]
~~~

`<expiration>` is the time until expiration, in seconds.

For a more complete explanation of the transient cache, including the
network|site cache, please see docs for `wp transient`.

**OPTIONS**

	<key>
		Key for the transient.

	<value>
		Value to be set for the transient.

	[<expiration>]
		Time until expiration, in seconds.

	[--network]
		Set the value of a network|site transient. On single site, this is
		is a specially-named cache key. On multisite, this is a global cache
		(instead of local to the site).

**EXAMPLES**

    $ wp transient set sample_key "test data" 3600
    Success: Transient added.



### wp transient type

Determines the type of transients implementation.

~~~
wp transient type 
~~~

Indicates whether the transients API is using an object cache or the
database.

For a more complete explanation of the transient cache, including the
network|site cache, please see docs for `wp transient`.

**EXAMPLES**

    $ wp transient type
    Transients are saved to the database.



### wp transient list

Lists transients and their values.

~~~
wp transient list [--search=<pattern>] [--exclude=<pattern>] [--network] [--unserialize] [--human-readable] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--search=<pattern>]
		Use wildcards ( * and ? ) to match transient name.

	[--exclude=<pattern>]
		Pattern to exclude. Use wildcards ( * and ? ) to match transient name.

	[--network]
		Get the values of network|site transients. On single site, this is
		a specially-named cache key. On multisite, this is a global cache
		(instead of local to the site).

	[--unserialize]
		Unserialize transient values in output.

	[--human-readable]
		Human-readable output for expirations.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		The serialization format for the value.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

This field will be displayed by default for each matching option:

* name
* value
* expiration

**EXAMPLES**

    # List all transients
    $ wp transient list
     +------+-------+---------------+
     | name | value | expiration    |
     +------+-------+---------------+
     | foo  | bar   | 39 mins       |
     | foo2 | bar2  | no expiration |
     | foo3 | bar2  | expired       |
     | foo4 | bar4  | 4 hours       |
     +------+-------+---------------+

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/cache-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/cache-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/cache-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/cache-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
