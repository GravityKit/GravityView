wp-cli/language-command
=======================

Installs, activates, and manages language packs.

[![Testing](https://github.com/wp-cli/language-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/language-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp language

Installs, activates, and manages language packs.

~~~
wp language
~~~

**EXAMPLES**

    # Install the Dutch core language pack.
    $ wp language core install nl_NL
    Success: Language installed.

    # Activate the Dutch core language pack.
    $ wp language core activate nl_NL
    Success: Language activated.

    # Install the Dutch language pack for Twenty Seventeen.
    $ wp language theme install twentyseventeen nl_NL
    Success: Language installed.

    # Install the Dutch language pack for Akismet.
    $ wp language plugin install akismet nl_NL
    Success: Language installed.



### wp language core

Installs, activates, and manages core language packs.

~~~
wp language core
~~~

**EXAMPLES**

    # Install the Dutch core language pack.
    $ wp language core install nl_NL
    Success: Language installed.

    # Activate the Dutch core language pack.
    $ wp language core activate nl_NL
    Success: Language activated.

    # Uninstall the Dutch core language pack.
    $ wp language core uninstall nl_NL
    Success: Language uninstalled.

    # List installed core language packages.
    $ wp language core list --status=installed
    +----------+--------------+-------------+-----------+-----------+---------------------+
    | language | english_name | native_name | status    | update    | updated             |
    +----------+--------------+-------------+-----------+-----------+---------------------+
    | nl_NL    | Dutch        | Nederlands  | installed | available | 2016-05-13 08:12:50 |
    +----------+--------------+-------------+-----------+-----------+---------------------+



### wp language core activate

Activates a given language.

~~~
wp language core activate <language>
~~~

**OPTIONS**

	<language>
		Language code to activate.

**EXAMPLES**

    $ wp language core activate ja
    Success: Language activated.



### wp language core is-installed

Checks if a given language is installed.

~~~
wp language core is-installed <language>
~~~

Returns exit code 0 when installed, 1 when uninstalled.

**OPTIONS**

	<language>
		The language code to check.

**EXAMPLES**

    # Check whether the German language is installed; exit status 0 if installed, otherwise 1.
    $ wp language core is-installed de_DE
    $ echo $?
    1



### wp language core install

Installs a given language.

~~~
wp language core install <language>... [--activate]
~~~

Downloads the language pack from WordPress.org.

**OPTIONS**

	<language>...
		Language code to install.

	[--activate]
		If set, the language will be activated immediately after install.

**EXAMPLES**

    # Install the Japanese language.
    $ wp language core install ja
    Downloading translation from https://downloads.wordpress.org/translation/core/4.9.8/ja.zip...
    Unpacking the update...
    Installing the latest version...
    Translation updated successfully.
    Language 'ja' installed.
    Success: Installed 1 of 1 languages.



### wp language core list

Lists all available languages.

~~~
wp language core list [--field=<field>] [--<field>=<value>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--field=<field>]
		Display the value of a single field

	[--<field>=<value>]
		Filter results by key=value pairs.

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

These fields will be displayed by default for each translation:

* language
* english_name
* native_name
* status
* update
* updated

**EXAMPLES**

    # List language,english_name,status fields of available languages.
    $ wp language core list --fields=language,english_name,status
    +----------------+-------------------------+-------------+
    | language       | english_name            | status      |
    +----------------+-------------------------+-------------+
    | ar             | Arabic                  | uninstalled |
    | ary            | Moroccan Arabic         | uninstalled |
    | az             | Azerbaijani             | uninstalled |



### wp language core uninstall

Uninstalls a given language.

~~~
wp language core uninstall <language>...
~~~

**OPTIONS**

	<language>...
		Language code to uninstall.

**EXAMPLES**

    $ wp language core uninstall ja
    Success: Language uninstalled.



### wp language core update

Updates installed languages for core.

~~~
wp language core update [--dry-run]
~~~

**OPTIONS**

	[--dry-run]
		Preview which translations would be updated.

**EXAMPLES**

    $ wp language core update
    Updating 'Japanese' translation for WordPress 4.9.2...
    Downloading translation from https://downloads.wordpress.org/translation/core/4.9.2/ja.zip...
    Translation updated successfully.
    Success: Updated 1/1 translation.



### wp language plugin

Installs, activates, and manages plugin language packs.

~~~
wp language plugin
~~~

**EXAMPLES**

    # Install the Dutch plugin language pack.
    $ wp language plugin install hello-dolly nl_NL
    Success: Language installed.

    # Uninstall the Dutch plugin language pack.
    $ wp language plugin uninstall hello-dolly nl_NL
    Success: Language uninstalled.

    # List installed plugin language packages.
    $ wp language plugin list --status=installed
    +----------+--------------+-------------+-----------+-----------+---------------------+
    | language | english_name | native_name | status    | update    | updated             |
    +----------+--------------+-------------+-----------+-----------+---------------------+
    | nl_NL    | Dutch        | Nederlands  | installed | available | 2016-05-13 08:12:50 |
    +----------+--------------+-------------+-----------+-----------+---------------------+



### wp language plugin is-installed

Checks if a given language is installed.

~~~
wp language plugin is-installed <plugin> <language>...
~~~

Returns exit code 0 when installed, 1 when uninstalled.

**OPTIONS**

	<plugin>
		Plugin to check for.

	<language>...
		The language code to check.

**EXAMPLES**

    # Check whether the German language is installed for Akismet; exit status 0 if installed, otherwise 1.
    $ wp language plugin is-installed akismet de_DE
    $ echo $?
    1



### wp language plugin install

Installs a given language for a plugin.

~~~
wp language plugin install [<plugin>] [--all] <language>... [--format=<format>]
~~~

Downloads the language pack from WordPress.org.

**OPTIONS**

	[<plugin>]
		Plugin to install language for.

	[--all]
		If set, languages for all plugins will be installed.

	<language>...
		Language code to install.

	[--format=<format>]
		Render output in a particular format. Used when installing languages for all plugins.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - summary
		---

**EXAMPLES**

    # Install the Japanese language for Akismet.
    $ wp language plugin install akismet ja
    Downloading translation from https://downloads.wordpress.org/translation/plugin/akismet/4.0.3/ja.zip...
    Unpacking the update...
    Installing the latest version...
    Translation updated successfully.
    Language 'ja' installed.
    Success: Installed 1 of 1 languages.



### wp language plugin list

Lists all available languages for one or more plugins.

~~~
wp language plugin list [<plugin>...] [--all] [--field=<field>] [--<field>=<value>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to list languages for.

	[--all]
		If set, available languages for all plugins will be listed.

	[--field=<field>]
		Display the value of a single field.

	[--<field>=<value>]
		Filter results by key=value pairs.

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

These fields will be displayed by default for each translation:

* plugin
* language
* english_name
* native_name
* status
* update
* updated

**EXAMPLES**

    # List language,english_name,status fields of available languages.
    $ wp language plugin list --fields=language,english_name,status
    +----------------+-------------------------+-------------+
    | language       | english_name            | status      |
    +----------------+-------------------------+-------------+
    | ar             | Arabic                  | uninstalled |
    | ary            | Moroccan Arabic         | uninstalled |
    | az             | Azerbaijani             | uninstalled |



### wp language plugin uninstall

Uninstalls a given language for a plugin.

~~~
wp language plugin uninstall <plugin> <language>...
~~~

**OPTIONS**

	<plugin>
		Plugin to uninstall language for.

	<language>...
		Language code to uninstall.

**EXAMPLES**

    $ wp language plugin uninstall hello-dolly ja
    Success: Language uninstalled.



### wp language plugin update

Updates installed languages for one or more plugins.

~~~
wp language plugin update [<plugin>...] [--all] [--dry-run]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to update languages for.

	[--all]
		If set, languages for all plugins will be updated.

	[--dry-run]
		Preview which translations would be updated.

**EXAMPLES**

    $ wp language plugin update --all
    Updating 'Japanese' translation for Akismet 3.1.11...
    Downloading translation from https://downloads.wordpress.org/translation/plugin/akismet/3.1.11/ja.zip...
    Translation updated successfully.
    Success: Updated 1/1 translation.



### wp language theme

Installs, activates, and manages theme language packs.

~~~
wp language theme
~~~

**EXAMPLES**

    # Install the Dutch theme language pack.
    $ wp language theme install twentyten nl_NL
    Success: Language installed.

    # Uninstall the Dutch theme language pack.
    $ wp language theme uninstall twentyten nl_NL
    Success: Language uninstalled.

    # List installed theme language packages.
    $ wp language theme list --status=installed
    +----------+--------------+-------------+-----------+-----------+---------------------+
    | language | english_name | native_name | status    | update    | updated             |
    +----------+--------------+-------------+-----------+-----------+---------------------+
    | nl_NL    | Dutch        | Nederlands  | installed | available | 2016-05-13 08:12:50 |
    +----------+--------------+-------------+-----------+-----------+---------------------+



### wp language theme is-installed

Checks if a given language is installed.

~~~
wp language theme is-installed <theme> <language>...
~~~

Returns exit code 0 when installed, 1 when uninstalled.

**OPTIONS**

	<theme>
		Theme to check for.

	<language>...
		The language code to check.

**EXAMPLES**

    # Check whether the German language is installed for Twenty Seventeen; exit status 0 if installed, otherwise 1.
    $ wp language theme is-installed twentyseventeen de_DE
    $ echo $?
    1



### wp language theme install

Installs a given language for a theme.

~~~
wp language theme install [<theme>] [--all] <language>... [--format=<format>]
~~~

Downloads the language pack from WordPress.org.

**OPTIONS**

	[<theme>]
		Theme to install language for.

	[--all]
		If set, languages for all themes will be installed.

	<language>...
		Language code to install.

	[--format=<format>]
		Render output in a particular format. Used when installing languages for all themes.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - summary
		---

**EXAMPLES**

    # Install the Japanese language for Twenty Seventeen.
    $ wp language theme install twentyseventeen ja
    Downloading translation from https://downloads.wordpress.org/translation/theme/twentyseventeen/1.3/ja.zip...
    Unpacking the update...
    Installing the latest version...
    Translation updated successfully.
    Language 'ja' installed.
    Success: Installed 1 of 1 languages.



### wp language theme list

Lists all available languages for one or more themes.

~~~
wp language theme list [<theme>...] [--all] [--field=<field>] [--<field>=<value>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[<theme>...]
		One or more themes to list languages for.

	[--all]
		If set, available languages for all themes will be listed.

	[--field=<field>]
		Display the value of a single field.

	[--<field>=<value>]
		Filter results by key=value pairs.

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

These fields will be displayed by default for each translation:

* theme
* language
* english_name
* native_name
* status
* update
* updated

**EXAMPLES**

    # List language,english_name,status fields of available languages.
    $ wp language theme list --fields=language,english_name,status
    +----------------+-------------------------+-------------+
    | language       | english_name            | status      |
    +----------------+-------------------------+-------------+
    | ar             | Arabic                  | uninstalled |
    | ary            | Moroccan Arabic         | uninstalled |
    | az             | Azerbaijani             | uninstalled |



### wp language theme uninstall

Uninstalls a given language for a theme.

~~~
wp language theme uninstall <theme> <language>...
~~~

**OPTIONS**

	<theme>
		Theme to uninstall language for.

	<language>...
		Language code to uninstall.

**EXAMPLES**

    $ wp language theme uninstall twentyten ja
    Success: Language uninstalled.



### wp language theme update

Updates installed languages for one or more themes.

~~~
wp language theme update [<theme>...] [--all] [--dry-run]
~~~

**OPTIONS**

	[<theme>...]
		One or more themes to update languages for.

	[--all]
		If set, languages for all themes will be updated.

	[--dry-run]
		Preview which translations would be updated.

**EXAMPLES**

    $ wp language theme update --all
    Updating 'Japanese' translation for Twenty Fifteen 1.5...
    Downloading translation from https://downloads.wordpress.org/translation/theme/twentyfifteen/1.5/ja.zip...
    Translation updated successfully.
    Success: Updated 1/1 translation.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/language-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/language-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/language-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/language-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
