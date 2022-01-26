wp-cli/scaffold-command
=======================

Generates code for post types, taxonomies, blocks, plugins, child themes, etc.

[![Testing](https://github.com/wp-cli/scaffold-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/scaffold-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp scaffold

Generates code for post types, taxonomies, plugins, child themes, etc.

~~~
wp scaffold
~~~

**EXAMPLES**

    # Generate a new plugin with unit tests
    $ wp scaffold plugin sample-plugin
    Success: Created plugin files.
    Success: Created test files.

    # Generate theme based on _s
    $ wp scaffold _s sample-theme --theme_name="Sample Theme" --author="John Doe"
    Success: Created theme 'Sample Theme'.

    # Generate code for post type registration in given theme
    $ wp scaffold post-type movie --label=Movie --theme=simple-life
    Success: Created /var/www/example.com/public_html/wp-content/themes/simple-life/post-types/movie.php



### wp scaffold underscores

Generates starter code for a theme based on _s.

~~~
wp scaffold underscores <slug> [--activate] [--enable-network] [--theme_name=<title>] [--author=<full-name>] [--author_uri=<uri>] [--sassify] [--woocommerce] [--force]
~~~

See the [Underscores website](https://underscores.me/) for more details.

**OPTIONS**

	<slug>
		The slug for the new theme, used for prefixing functions.

	[--activate]
		Activate the newly downloaded theme.

	[--enable-network]
		Enable the newly downloaded theme for the entire network.

	[--theme_name=<title>]
		What to put in the 'Theme Name:' header in 'style.css'.

	[--author=<full-name>]
		What to put in the 'Author:' header in 'style.css'.

	[--author_uri=<uri>]
		What to put in the 'Author URI:' header in 'style.css'.

	[--sassify]
		Include stylesheets as SASS.

	[--woocommerce]
		Include WooCommerce boilerplate files.

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate a theme with name "Sample Theme" and author "John Doe"
    $ wp scaffold _s sample-theme --theme_name="Sample Theme" --author="John Doe"
    Success: Created theme 'Sample Theme'.



### wp scaffold block

Generates PHP, JS and CSS code for registering a Gutenberg block for a plugin or theme.

~~~
wp scaffold block <slug> [--title=<title>] [--dashicon=<dashicon>] [--category=<category>] [--theme] [--plugin=<plugin>] [--force]
~~~

Blocks are the fundamental element of the Gutenberg editor. They are the primary way in which plugins and themes can register their own functionality and extend the capabilities of the editor.

Visit the [Gutenberg handbook](https://wordpress.org/gutenberg/handbook/block-api/) to learn more about Block API.

When you scaffold a block you must use either the theme or plugin option. The latter is recommended.

**OPTIONS**

	<slug>
		The internal name of the block.

	[--title=<title>]
		The display title for your block.

	[--dashicon=<dashicon>]
		The dashicon to make it easier to identify your block.

	[--category=<category>]
		The category name to help users browse and discover your block.
		---
		default: widgets
		options:
		  - common
		  - embed
		  - formatting
		  - layout
		  - widgets
		---

	[--theme]
		Create files in the active theme directory. Specify a theme with `--theme=<theme>` to have the file placed in that theme.

	[--plugin=<plugin>]
		Create files in the given plugin's directory.

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate a 'movie' block for the 'movies' plugin
    $ wp scaffold block movie --title="Movie block" --plugin=movies
    Success: Created block 'Movie block'.

    # Generate a 'movie' block for the 'simple-life' theme
    $ wp scaffold block movie --title="Movie block" --theme=simple-life
     Success: Created block 'Movie block'.

    # Create a new plugin and add two blocks
    # Create plugin called books
    $ wp scaffold plugin books
    # Add a block called book to plugin books
    $ wp scaffold block book --title="Book" --plugin=books
    # Add a second block to plugin called books.
    $ wp scaffold block books --title="Book List" --plugin=books



### wp scaffold child-theme

Generates child theme based on an existing theme.

~~~
wp scaffold child-theme <slug> --parent_theme=<slug> [--theme_name=<title>] [--author=<full-name>] [--author_uri=<uri>] [--theme_uri=<uri>] [--activate] [--enable-network] [--force]
~~~

Creates a child theme folder with `functions.php` and `style.css` files.

**OPTIONS**

	<slug>
		The slug for the new child theme.

	--parent_theme=<slug>
		What to put in the 'Template:' header in 'style.css'.

	[--theme_name=<title>]
		What to put in the 'Theme Name:' header in 'style.css'.

	[--author=<full-name>]
		What to put in the 'Author:' header in 'style.css'.

	[--author_uri=<uri>]
		What to put in the 'Author URI:' header in 'style.css'.

	[--theme_uri=<uri>]
		What to put in the 'Theme URI:' header in 'style.css'.

	[--activate]
		Activate the newly created child theme.

	[--enable-network]
		Enable the newly created child theme for the entire network.

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate a 'sample-theme' child theme based on TwentySixteen
    $ wp scaffold child-theme sample-theme --parent_theme=twentysixteen
    Success: Created '/var/www/example.com/public_html/wp-content/themes/sample-theme'.



### wp scaffold plugin

Generates starter code for a plugin.

~~~
wp scaffold plugin <slug> [--dir=<dirname>] [--plugin_name=<title>] [--plugin_description=<description>] [--plugin_author=<author>] [--plugin_author_uri=<url>] [--plugin_uri=<url>] [--skip-tests] [--ci=<provider>] [--activate] [--activate-network] [--force]
~~~

The following files are always generated:

* `plugin-slug.php` is the main PHP plugin file.
* `readme.txt` is the readme file for the plugin.
* `package.json` needed by NPM holds various metadata relevant to the project. Packages: `grunt`, `grunt-wp-i18n` and `grunt-wp-readme-to-markdown`. Scripts: `start`, `readme`, `i18n`.
* `Gruntfile.js` is the JS file containing Grunt tasks. Tasks: `i18n` containing `addtextdomain` and `makepot`, `readme` containing `wp_readme_to_markdown`.
* `.editorconfig` is the configuration file for Editor.
* `.gitignore` tells which files (or patterns) git should ignore.
* `.distignore` tells which files and folders should be ignored in distribution.

The following files are also included unless the `--skip-tests` is used:

* `phpunit.xml.dist` is the configuration file for PHPUnit.
* `.travis.yml` is the configuration file for Travis CI. Use `--ci=<provider>` to select a different service.
* `bin/install-wp-tests.sh` configures the WordPress test suite and a test database.
* `tests/bootstrap.php` is the file that makes the current plugin active when running the test suite.
* `tests/test-sample.php` is a sample file containing test cases.
* `.phpcs.xml.dist` is a collection of PHP_CodeSniffer rules.

**OPTIONS**

	<slug>
		The internal name of the plugin.

	[--dir=<dirname>]
		Put the new plugin in some arbitrary directory path. Plugin directory will be path plus supplied slug.

	[--plugin_name=<title>]
		What to put in the 'Plugin Name:' header.

	[--plugin_description=<description>]
		What to put in the 'Description:' header.

	[--plugin_author=<author>]
		What to put in the 'Author:' header.

	[--plugin_author_uri=<url>]
		What to put in the 'Author URI:' header.

	[--plugin_uri=<url>]
		What to put in the 'Plugin URI:' header.

	[--skip-tests]
		Don't generate files for unit testing.

	[--ci=<provider>]
		Choose a configuration file for a continuous integration provider.
		---
		default: travis
		options:
		  - travis
		  - circle
		  - gitlab
		---

	[--activate]
		Activate the newly generated plugin.

	[--activate-network]
		Network activate the newly generated plugin.

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    $ wp scaffold plugin sample-plugin
    Success: Created plugin files.
    Success: Created test files.



### wp scaffold plugin-tests

Generates files needed for running PHPUnit tests in a plugin.

~~~
wp scaffold plugin-tests [<plugin>] [--dir=<dirname>] [--ci=<provider>] [--force]
~~~

The following files are generated by default:

* `phpunit.xml.dist` is the configuration file for PHPUnit.
* `.travis.yml` is the configuration file for Travis CI. Use `--ci=<provider>` to select a different service.
* `bin/install-wp-tests.sh` configures the WordPress test suite and a test database.
* `tests/bootstrap.php` is the file that makes the current plugin active when running the test suite.
* `tests/test-sample.php` is a sample file containing the actual tests.
* `.phpcs.xml.dist` is a collection of PHP_CodeSniffer rules.

Learn more from the [plugin unit tests documentation](https://make.wordpress.org/cli/handbook/plugin-unit-tests/).

**ENVIRONMENT**

The `tests/bootstrap.php` file looks for the WP_TESTS_DIR environment
variable.

**OPTIONS**

	[<plugin>]
		The name of the plugin to generate test files for.

	[--dir=<dirname>]
		Generate test files for a non-standard plugin path. If no plugin slug is specified, the directory name is used.

	[--ci=<provider>]
		Choose a configuration file for a continuous integration provider.
		---
		default: travis
		options:
		  - travis
		  - circle
		  - gitlab
		  - bitbucket
		---

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate unit test files for plugin 'sample-plugin'.
    $ wp scaffold plugin-tests sample-plugin
    Success: Created test files.



### wp scaffold post-type

Generates PHP code for registering a custom post type.

~~~
wp scaffold post-type <slug> [--label=<label>] [--textdomain=<textdomain>] [--dashicon=<dashicon>] [--theme] [--plugin=<plugin>] [--raw] [--force]
~~~

**OPTIONS**

	<slug>
		The internal name of the post type.

	[--label=<label>]
		The text used to translate the update messages.

	[--textdomain=<textdomain>]
		The textdomain to use for the labels.

	[--dashicon=<dashicon>]
		The dashicon to use in the menu.

	[--theme]
		Create a file in the active theme directory, instead of sending to
		STDOUT. Specify a theme with `--theme=<theme>` to have the file placed in that theme.

	[--plugin=<plugin>]
		Create a file in the given plugin's directory, instead of sending to STDOUT.

	[--raw]
		Just generate the `register_post_type()` call and nothing else.

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate a 'movie' post type for the 'simple-life' theme
    $ wp scaffold post-type movie --label=Movie --theme=simple-life
    Success: Created '/var/www/example.com/public_html/wp-content/themes/simple-life/post-types/movie.php'.



### wp scaffold taxonomy

Generates PHP code for registering a custom taxonomy.

~~~
wp scaffold taxonomy <slug> [--post_types=<post-types>] [--label=<label>] [--textdomain=<textdomain>] [--theme] [--plugin=<plugin>] [--raw] [--force]
~~~

**OPTIONS**

	<slug>
		The internal name of the taxonomy.

	[--post_types=<post-types>]
		Post types to register for use with the taxonomy.

	[--label=<label>]
		The text used to translate the update messages.

	[--textdomain=<textdomain>]
		The textdomain to use for the labels.

	[--theme]
		Create a file in the active theme directory, instead of sending to
		STDOUT. Specify a theme with `--theme=<theme>` to have the file placed in that theme.

	[--plugin=<plugin>]
		Create a file in the given plugin's directory, instead of sending to STDOUT.

	[--raw]
		Just generate the `register_taxonomy()` call and nothing else.

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate PHP code for registering a custom taxonomy and save in a file
    $ wp scaffold taxonomy venue --post_types=event,presentation > taxonomy.php



### wp scaffold theme-tests

Generates files needed for running PHPUnit tests in a theme.

~~~
wp scaffold theme-tests [<theme>] [--dir=<dirname>] [--ci=<provider>] [--force]
~~~

The following files are generated by default:

* `phpunit.xml.dist` is the configuration file for PHPUnit.
* `.travis.yml` is the configuration file for Travis CI. Use `--ci=<provider>` to select a different service.
* `bin/install-wp-tests.sh` configures the WordPress test suite and a test database.
* `tests/bootstrap.php` is the file that makes the current theme active when running the test suite.
* `tests/test-sample.php` is a sample file containing the actual tests.
* `.phpcs.xml.dist` is a collection of PHP_CodeSniffer rules.

Learn more from the [plugin unit tests documentation](https://make.wordpress.org/cli/handbook/plugin-unit-tests/).

**ENVIRONMENT**

The `tests/bootstrap.php` file looks for the WP_TESTS_DIR environment
variable.

**OPTIONS**

	[<theme>]
		The name of the theme to generate test files for.

	[--dir=<dirname>]
		Generate test files for a non-standard theme path. If no theme slug is specified, the directory name is used.

	[--ci=<provider>]
		Choose a configuration file for a continuous integration provider.
		---
		default: travis
		options:
		  - travis
		  - circle
		  - gitlab
		  - bitbucket
		---

	[--force]
		Overwrite files that already exist.

**EXAMPLES**

    # Generate unit test files for theme 'twentysixteenchild'.
    $ wp scaffold theme-tests twentysixteenchild
    Success: Created test files.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/scaffold-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/scaffold-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/scaffold-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/scaffold-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
