wp-cli/i18n-command
===================

Provides internationalization tools for WordPress projects.

[![Testing](https://github.com/wp-cli/i18n-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/i18n-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp i18n

Provides internationalization tools for WordPress projects.

~~~
wp i18n
~~~

**EXAMPLES**

    # Create a POT file for the WordPress plugin/theme in the current directory
    $ wp i18n make-pot . languages/my-plugin.pot



### wp i18n make-pot

Create a POT file for a WordPress project.

~~~
wp i18n make-pot <source> [<destination>] [--slug=<slug>] [--domain=<domain>] [--ignore-domain] [--merge[=<paths>]] [--subtract=<paths>] [--subtract-and-merge] [--include=<paths>] [--exclude=<paths>] [--headers=<headers>] [--location] [--skip-js] [--skip-php] [--skip-blade] [--skip-block-json] [--skip-theme-json] [--skip-audit] [--file-comment=<file-comment>] [--package-name=<name>]
~~~

Scans PHP, Blade-PHP and JavaScript files for translatable strings, as well as theme stylesheets and plugin files
if the source directory is detected as either a plugin or theme.

**OPTIONS**

	<source>
		Directory to scan for string extraction.

	[<destination>]
		Name of the resulting POT file.

	[--slug=<slug>]
		Plugin or theme slug. Defaults to the source directory's basename.

	[--domain=<domain>]
		Text domain to look for in the source code, unless the `--ignore-domain` option is used.
		By default, the "Text Domain" header of the plugin or theme is used.
		If none is provided, it falls back to the project slug.

	[--ignore-domain]
		Ignore the text domain completely and extract strings with any text domain.

	[--merge[=<paths>]]
		Comma-separated list of POT files whose contents should be merged with the extracted strings.
		If left empty, defaults to the destination POT file. POT file headers will be ignored.

	[--subtract=<paths>]
		Comma-separated list of POT files whose contents should act as some sort of denylist for string extraction.
		Any string which is found on that denylist will not be extracted.
		This can be useful when you want to create multiple POT files from the same source directory with slightly
		different content and no duplicate strings between them.

	[--subtract-and-merge]
		Whether source code references and comments from the generated POT file should be instead added to the POT file
		used for subtraction. Warning: this modifies the files passed to `--subtract`!

	[--include=<paths>]
		Comma-separated list of files and paths that should be used for string extraction.
		If provided, only these files and folders will be taken into account for string extraction.
		For example, `--include="src,my-file.php` will ignore anything besides `my-file.php` and files in the `src`
		directory. Simple glob patterns can be used, i.e. `--include=foo-*.php` includes any PHP file with the `foo-`
		prefix. Leading and trailing slashes are ignored, i.e. `/my/directory/` is the same as `my/directory`.

	[--exclude=<paths>]
		Comma-separated list of files and paths that should be skipped for string extraction.
		For example, `--exclude=".github,myfile.php` would ignore any strings found within `myfile.php` or the `.github`
		folder. Simple glob patterns can be used, i.e. `--exclude=foo-*.php` excludes any PHP file with the `foo-`
		prefix. Leading and trailing slashes are ignored, i.e. `/my/directory/` is the same as `my/directory`. The
		following files and folders are always excluded: node_modules, .git, .svn, .CVS, .hg, vendor, *.min.js.

	[--headers=<headers>]
		Array in JSON format of custom headers which will be added to the POT file. Defaults to empty array.

	[--location]
		Whether to write `#: filename:line` lines.
		Defaults to true, use `--no-location` to skip the removal.
		Note that disabling this option makes it harder for technically skilled translators to understand each message’s context.

	[--skip-js]
		Skips JavaScript string extraction. Useful when this is done in another build step, e.g. through Babel.

	[--skip-php]
		Skips PHP string extraction.

	[--skip-blade]
		Skips Blade-PHP string extraction.

	[--skip-block-json]
		Skips string extraction from block.json files.

	[--skip-theme-json]
		Skips string extraction from theme.json files.

	[--skip-audit]
		Skips string audit where it tries to find possible mistakes in translatable strings. Useful when running in an
		automated environment.

	[--file-comment=<file-comment>]
		String that should be added as a comment to the top of the resulting POT file.
		By default, a copyright comment is added for WordPress plugins and themes in the following manner:

     ```
     Copyright (C) 2018 Example Plugin Author
     This file is distributed under the same license as the Example Plugin package.
     ```

     If a plugin or theme specifies a license in their main plugin file or stylesheet, the comment looks like
     this:

     ```
     Copyright (C) 2018 Example Plugin Author
     This file is distributed under the GPLv2.
     ```

	[--package-name=<name>]
		Name to use for package name in the resulting POT file's `Project-Id-Version` header.
		Overrides plugin or theme name, if applicable.

**EXAMPLES**

    # Create a POT file for the WordPress plugin/theme in the current directory
    $ wp i18n make-pot . languages/my-plugin.pot

    # Create a POT file for the continents and cities list in WordPress core.
    $ wp i18n make-pot . continents-and-cities.pot --include="wp-admin/includes/continents-cities.php"
    --ignore-domain



### wp i18n make-json

Extract JavaScript strings from PO files and add them to individual JSON files.

~~~
wp i18n make-json <source> [<destination>] [--purge] [--update-mo-files] [--pretty-print] [--use-map=<paths_or_maps>]
~~~

For JavaScript internationalization purposes, WordPress requires translations to be split up into
one Jed-formatted JSON file per JavaScript source file.

See https://make.wordpress.org/core/2018/11/09/new-javascript-i18n-support-in-wordpress/ to learn more
about WordPress JavaScript internationalization.

**OPTIONS**

	<source>
		Path to an existing PO file or a directory containing multiple PO files.

	[<destination>]
		Path to the destination directory for the resulting JSON files. Defaults to the source directory.

	[--purge]
		Whether to purge the strings that were extracted from the original source file. Defaults to true, use `--no-purge` to skip the removal.

	[--update-mo-files]
		Whether MO files should be updated as well after updating PO files.
		Only has an effect when used in combination with `--purge`.

	[--pretty-print]
		Pretty-print resulting JSON files.

	[--use-map=<paths_or_maps>]
		Whether to use a mapping file for the strings, as a JSON value, array to specify multiple.
		Each element can either be a string (file path) or object (map).

**EXAMPLES**

    # Create JSON files for all PO files in the languages directory
    $ wp i18n make-json languages

    # Create JSON files for my-plugin-de_DE.po and leave the PO file untouched.
    $ wp i18n make-json my-plugin-de_DE.po /tmp --no-purge

    # Create JSON files with mapping
    $ wp i18n make-json languages --use-map=build/map.json

    # Create JSON files with multiple mappings
    $ wp i18n make-json languages '--use-map=["build/map.json","build/map2.json"]'

    # Create JSON files with object mapping
    $ wp i18n make-json languages '--use-map={"source/index.js":"build/index.js"}'



### wp i18n make-mo

Create MO files from PO files.

~~~
wp i18n make-mo <source> [<destination>]
~~~

**OPTIONS**

	<source>
		Path to an existing PO file or a directory containing multiple PO files.

	[<destination>]
		Path to the destination directory for the resulting MO files. Defaults to the source directory.

**EXAMPLES**

    # Create MO files for all PO files in the current directory.
    $ wp i18n make-mo .

    # Create a MO file from a single PO file in a specific directory.
    $ wp i18n make-mo example-plugin-de_DE.po languages



### wp i18n update-po

Update PO files from a POT file.

~~~
wp i18n update-po <source> [<destination>]
~~~

This behaves similarly to the [msgmerge](https://www.gnu.org/software/gettext/manual/html_node/msgmerge-Invocation.html) command.

**OPTIONS**

	<source>
		Path to an existing POT file to use for updating

	[<destination>]
		PO file to update or a directory containing multiple PO files.
		  Defaults to all PO files in the source directory.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/i18n-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/i18n-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/i18n-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/i18n-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
