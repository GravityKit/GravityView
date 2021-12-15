wp-cli/extension-command
========================

Manages plugins and themes, including installs, activations, and updates.

[![Testing](https://github.com/wp-cli/extension-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/extension-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp plugin

Manages plugins, including installs, activations, and updates.

~~~
wp plugin
~~~

See the WordPress [Plugin Handbook](https://developer.wordpress.org/plugins/) developer resource for more information on plugins.

**EXAMPLES**

    # Activate plugin
    $ wp plugin activate hello
    Plugin 'hello' activated.
    Success: Activated 1 of 1 plugins.

    # Deactivate plugin
    $ wp plugin deactivate hello
    Plugin 'hello' deactivated.
    Success: Deactivated 1 of 1 plugins.

    # Delete plugin
    $ wp plugin delete hello
    Deleted 'hello' plugin.
    Success: Deleted 1 of 1 plugins.

    # Install the latest version from wordpress.org and activate
    $ wp plugin install bbpress --activate
    Installing bbPress (2.5.9)
    Downloading install package from https://downloads.wordpress.org/plugin/bbpress.2.5.9.zip...
    Using cached file '/home/vagrant/.wp-cli/cache/plugin/bbpress-2.5.9.zip'...
    Unpacking the package...
    Installing the plugin...
    Plugin installed successfully.
    Activating 'bbpress'...
    Plugin 'bbpress' activated.
    Success: Installed 1 of 1 plugins.



### wp plugin activate

Activates one or more plugins.

~~~
wp plugin activate [<plugin>...] [--all] [--network]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to activate.

	[--all]
		If set, all plugins will be activated.

	[--network]
		If set, the plugin will be activated for the entire multisite network.

**EXAMPLES**

    # Activate plugin
    $ wp plugin activate hello
    Plugin 'hello' activated.
    Success: Activated 1 of 1 plugins.

    # Activate plugin in entire multisite network
    $ wp plugin activate hello --network
    Plugin 'hello' network activated.
    Success: Network activated 1 of 1 plugins.



### wp plugin deactivate

Deactivates one or more plugins.

~~~
wp plugin deactivate [<plugin>...] [--uninstall] [--all] [--network]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to deactivate.

	[--uninstall]
		Uninstall the plugin after deactivation.

	[--all]
		If set, all plugins will be deactivated.

	[--network]
		If set, the plugin will be deactivated for the entire multisite network.

**EXAMPLES**

    # Deactivate plugin
    $ wp plugin deactivate hello
    Plugin 'hello' deactivated.
    Success: Deactivated 1 of 1 plugins.



### wp plugin delete

Deletes plugin files without deactivating or uninstalling.

~~~
wp plugin delete [<plugin>...] [--all]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to delete.

	[--all]
		If set, all plugins will be deleted.

**EXAMPLES**

    # Delete plugin
    $ wp plugin delete hello
    Deleted 'hello' plugin.
    Success: Deleted 1 of 1 plugins.

    # Delete inactive plugins
    $ wp plugin delete $(wp plugin list --status=inactive --field=name)
    Deleted 'tinymce-templates' plugin.
    Success: Deleted 1 of 1 plugins.



### wp plugin get

Gets details about an installed plugin.

~~~
wp plugin get <plugin> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<plugin>
		The plugin to get.

	[--field=<field>]
		Instead of returning the whole plugin, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

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

**EXAMPLES**

    $ wp plugin get bbpress --format=json
    {"name":"bbpress","title":"bbPress","author":"The bbPress Contributors","version":"2.6-alpha","description":"bbPress is forum software with a twist from the creators of WordPress.","status":"active"}



### wp plugin install

Installs one or more plugins.

~~~
wp plugin install <plugin|zip|url>... [--version=<version>] [--force] [--activate] [--activate-network] [--insecure]
~~~

**OPTIONS**

	<plugin|zip|url>...
		One or more plugins to install. Accepts a plugin slug, the path to a local zip file, or a URL to a remote zip file.

	[--version=<version>]
		If set, get that particular version from wordpress.org, instead of the
		stable version.

	[--force]
		If set, the command will overwrite any installed version of the plugin, without prompting
		for confirmation.

	[--activate]
		If set, the plugin will be activated immediately after install.

	[--activate-network]
		If set, the plugin will be network activated immediately after install

	[--insecure]
		Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.

**EXAMPLES**

    # Install the latest version from wordpress.org and activate
    $ wp plugin install bbpress --activate
    Installing bbPress (2.5.9)
    Downloading install package from https://downloads.wordpress.org/plugin/bbpress.2.5.9.zip...
    Using cached file '/home/vagrant/.wp-cli/cache/plugin/bbpress-2.5.9.zip'...
    Unpacking the package...
    Installing the plugin...
    Plugin installed successfully.
    Activating 'bbpress'...
    Plugin 'bbpress' activated.
    Success: Installed 1 of 1 plugins.

    # Install the development version from wordpress.org
    $ wp plugin install bbpress --version=dev
    Installing bbPress (Development Version)
    Downloading install package from https://downloads.wordpress.org/plugin/bbpress.zip...
    Unpacking the package...
    Installing the plugin...
    Plugin installed successfully.
    Success: Installed 1 of 1 plugins.

    # Install from a local zip file
    $ wp plugin install ../my-plugin.zip
    Unpacking the package...
    Installing the plugin...
    Plugin installed successfully.
    Success: Installed 1 of 1 plugins.

    # Install from a remote zip file
    $ wp plugin install http://s3.amazonaws.com/bucketname/my-plugin.zip?AWSAccessKeyId=123&Expires=456&Signature=abcdef
    Downloading install package from http://s3.amazonaws.com/bucketname/my-plugin.zip?AWSAccessKeyId=123&Expires=456&Signature=abcdef
    Unpacking the package...
    Installing the plugin...
    Plugin installed successfully.
    Success: Installed 1 of 1 plugins.

    # Update from a remote zip file
    $ wp plugin install https://github.com/envato/wp-envato-market/archive/master.zip --force
    Downloading install package from https://github.com/envato/wp-envato-market/archive/master.zip
    Unpacking the package...
    Installing the plugin...
    Renamed Github-based project from 'wp-envato-market-master' to 'wp-envato-market'.
    Plugin updated successfully
    Success: Installed 1 of 1 plugins.

    # Forcefully re-install all installed plugins
    $ wp plugin install $(wp plugin list --field=name) --force
    Installing Akismet (3.1.11)
    Downloading install package from https://downloads.wordpress.org/plugin/akismet.3.1.11.zip...
    Unpacking the package...
    Installing the plugin...
    Removing the old version of the plugin...
    Plugin updated successfully
    Success: Installed 1 of 1 plugins.



### wp plugin is-installed

Checks if a given plugin is installed.

~~~
wp plugin is-installed <plugin>
~~~

Returns exit code 0 when installed, 1 when uninstalled.

**OPTIONS**

	<plugin>
		The plugin to check.

**EXAMPLES**

    # Check whether plugin is installed; exit status 0 if installed, otherwise 1
    $ wp plugin is-installed hello
    $ echo $?
    1



### wp plugin list

Gets a list of plugins.

~~~
wp plugin list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>] [--status=<status>]
~~~

Displays a list of the plugins installed on the site with activation
status, whether or not there's an update available, etc.

Use `--status=dropin` to list installed dropins (e.g. `object-cache.php`).

**OPTIONS**

	[--<field>=<value>]
		Filter results based on the value of a field.

	[--field=<field>]
		Prints the value of a single field for each plugin.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - count
		  - json
		  - yaml
		---

	[--status=<status>]
		Filter the output by plugin status.
		---
		options:
		  - active
		  - active-network
		  - dropin
		  - inactive
		  - must-use
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each plugin:

* name
* status
* update
* version

These fields are optionally available:

* update_version
* update_package
* update_id
* title
* description
* file

**EXAMPLES**

    # List active plugins on the site.
    $ wp plugin list --status=active --format=json
    [{"name":"dynamic-hostname","status":"active","update":"none","version":"0.4.2"},{"name":"tinymce-templates","status":"active","update":"none","version":"4.4.3"},{"name":"wp-multibyte-patch","status":"active","update":"none","version":"2.4"},{"name":"wp-total-hacks","status":"active","update":"none","version":"2.0.1"}]

    # List plugins on each site in a network.
    $ wp site list --field=url | xargs -I % wp plugin list --url=%
    +---------+----------------+--------+---------+
    | name    | status         | update | version |
    +---------+----------------+--------+---------+
    | akismet | active-network | none   | 3.1.11  |
    | hello   | inactive       | none   | 1.6     |
    +---------+----------------+--------+---------+
    +---------+----------------+--------+---------+
    | name    | status         | update | version |
    +---------+----------------+--------+---------+
    | akismet | active-network | none   | 3.1.11  |
    | hello   | inactive       | none   | 1.6     |
    +---------+----------------+--------+---------+



### wp plugin path

Gets the path to a plugin or to the plugin directory.

~~~
wp plugin path [<plugin>] [--dir]
~~~

**OPTIONS**

	[<plugin>]
		The plugin to get the path to. If not set, will return the path to the
		plugins directory.

	[--dir]
		If set, get the path to the closest parent directory, instead of the
		plugin file.

**EXAMPLES**

    $ cd $(wp plugin path) && pwd
    /var/www/wordpress/wp-content/plugins



### wp plugin search

Searches the WordPress.org plugin directory.

~~~
wp plugin search <search> [--page=<page>] [--per-page=<per-page>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Displays plugins in the WordPress.org plugin directory matching a given
search query.

**OPTIONS**

	<search>
		The string to search for.

	[--page=<page>]
		Optional page to display.
		---
		default: 1
		---

	[--per-page=<per-page>]
		Optional number of results to display.
		---
		default: 10
		---

	[--field=<field>]
		Prints the value of a single field for each plugin.

	[--fields=<fields>]
		Ask for specific fields from the API. Defaults to name,slug,author_profile,rating. Acceptable values:

    **name**: Plugin Name
    **slug**: Plugin Slug
    **version**: Current Version Number
    **author**: Plugin Author
    **author_profile**: Plugin Author Profile
    **contributors**: Plugin Contributors
    **requires**: Plugin Minimum Requirements
    **tested**: Plugin Tested Up To
    **compatibility**: Plugin Compatible With
    **rating**: Plugin Rating in Percent and Total Number
    **ratings**: Plugin Ratings for each star (1-5)
    **num_ratings**: Number of Plugin Ratings
    **homepage**: Plugin Author's Homepage
    **description**: Plugin's Description
    **short_description**: Plugin's Short Description
    **sections**: Plugin Readme Sections: description, installation, FAQ, screenshots, other notes, and changelog
    **downloaded**: Plugin Download Count
    **last_updated**: Plugin's Last Update
    **added**: Plugin's Date Added to wordpress.org Repository
    **tags**: Plugin's Tags
    **versions**: Plugin's Available Versions with D/L Link
    **donate_link**: Plugin's Donation Link
    **banners**: Plugin's Banner Image Link
    **icons**: Plugin's Icon Image Link
    **active_installs**: Plugin's Number of Active Installs
    **contributors**: Plugin's List of Contributors
    **url**: Plugin's URL on wordpress.org

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - count
		  - json
		  - yaml
		---

**EXAMPLES**

    $ wp plugin search dsgnwrks --per-page=20 --format=json
    Success: Showing 3 of 3 plugins.
    [{"name":"DsgnWrks Instagram Importer Debug","slug":"dsgnwrks-instagram-importer-debug","rating":0},{"name":"DsgnWrks Instagram Importer","slug":"dsgnwrks-instagram-importer","rating":84},{"name":"DsgnWrks Twitter Importer","slug":"dsgnwrks-twitter-importer","rating":80}]

    $ wp plugin search dsgnwrks --fields=name,version,slug,rating,num_ratings
    Success: Showing 3 of 3 plugins.
    +-----------------------------------+---------+-----------------------------------+--------+-------------+
    | name                              | version | slug                              | rating | num_ratings |
    +-----------------------------------+---------+-----------------------------------+--------+-------------+
    | DsgnWrks Instagram Importer Debug | 0.1.6   | dsgnwrks-instagram-importer-debug | 0      | 0           |
    | DsgnWrks Instagram Importer       | 1.3.7   | dsgnwrks-instagram-importer       | 84     | 23          |
    | DsgnWrks Twitter Importer         | 1.1.1   | dsgnwrks-twitter-importer         | 80     | 1           |
    +-----------------------------------+---------+-----------------------------------+--------+-------------+



### wp plugin status

Reveals the status of one or all plugins.

~~~
wp plugin status [<plugin>]
~~~

**OPTIONS**

	[<plugin>]
		A particular plugin to show the status for.

**EXAMPLES**

    # Displays status of all plugins
    $ wp plugin status
    5 installed plugins:
      I akismet                3.1.11
      I easy-digital-downloads 2.5.16
      A theme-check            20160523.1
      I wen-logo-slider        2.0.3
      M ns-pack                1.0.0
    Legend: I = Inactive, A = Active, M = Must Use

    # Displays status of a plugin
    $ wp plugin status theme-check
    Plugin theme-check details:
        Name: Theme Check
        Status: Active
        Version: 20160523.1
        Author: Otto42, pross
        Description: A simple and easy way to test your theme for all the latest WordPress standards and practices. A great theme development tool!



### wp plugin toggle

Toggles a plugin's activation state.

~~~
wp plugin toggle <plugin>... [--network]
~~~

If the plugin is active, then it will be deactivated. If the plugin is
inactive, then it will be activated.

**OPTIONS**

	<plugin>...
		One or more plugins to toggle.

	[--network]
		If set, the plugin will be toggled for the entire multisite network.

**EXAMPLES**

    # Akismet is currently activated
    $ wp plugin toggle akismet
    Plugin 'akismet' deactivated.
    Success: Toggled 1 of 1 plugins.

    # Akismet is currently deactivated
    $ wp plugin toggle akismet
    Plugin 'akismet' activated.
    Success: Toggled 1 of 1 plugins.



### wp plugin uninstall

Uninstalls one or more plugins.

~~~
wp plugin uninstall [<plugin>...] [--deactivate] [--skip-delete] [--all]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to uninstall.

	[--deactivate]
		Deactivate the plugin before uninstalling. Default behavior is to warn and skip if the plugin is active.

	[--skip-delete]
		If set, the plugin files will not be deleted. Only the uninstall procedure
		will be run.

	[--all]
		If set, all plugins will be uninstalled.

**EXAMPLES**

    $ wp plugin uninstall hello
    Uninstalled and deleted 'hello' plugin.
    Success: Uninstalled 1 of 1 plugins.



### wp plugin update

Updates one or more plugins.

~~~
wp plugin update [<plugin>...] [--all] [--exclude=<name>] [--minor] [--patch] [--format=<format>] [--version=<version>] [--dry-run] [--insecure]
~~~

**OPTIONS**

	[<plugin>...]
		One or more plugins to update.

	[--all]
		If set, all plugins that have updates will be updated.

	[--exclude=<name>]
		Comma separated list of plugin names that should be excluded from updating.

	[--minor]
		Only perform updates for minor releases (e.g. from 1.3 to 1.4 instead of 2.0)

	[--patch]
		Only perform updates for patch releases (e.g. from 1.3 to 1.3.3 instead of 1.4)

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - summary
		---

	[--version=<version>]
		If set, the plugin will be updated to the specified version.

	[--dry-run]
		Preview which plugins would be updated.

	[--insecure]
		Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.

**EXAMPLES**

    $ wp plugin update bbpress --version=dev
    Installing bbPress (Development Version)
    Downloading install package from https://downloads.wordpress.org/plugin/bbpress.zip...
    Unpacking the package...
    Installing the plugin...
    Removing the old version of the plugin...
    Plugin updated successfully.
    Success: Updated 1 of 2 plugins.

    $ wp plugin update --all
    Enabling Maintenance mode...
    Downloading update from https://downloads.wordpress.org/plugin/akismet.3.1.11.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the plugin...
    Plugin updated successfully.
    Downloading update from https://downloads.wordpress.org/plugin/nginx-champuru.3.2.0.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the plugin...
    Plugin updated successfully.
    Disabling Maintenance mode...
    +------------------------+-------------+-------------+---------+
    | name                   | old_version | new_version | status  |
    +------------------------+-------------+-------------+---------+
    | akismet                | 3.1.3       | 3.1.11      | Updated |
    | nginx-cache-controller | 3.1.1       | 3.2.0       | Updated |
    +------------------------+-------------+-------------+---------+
    Success: Updated 2 of 2 plugins.

    $ wp plugin update --all --exclude=akismet
    Enabling Maintenance mode...
    Downloading update from https://downloads.wordpress.org/plugin/nginx-champuru.3.2.0.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the plugin...
    Plugin updated successfully.
    Disabling Maintenance mode...
    +------------------------+-------------+-------------+---------+
    | name                   | old_version | new_version | status  |
    +------------------------+-------------+-------------+---------+
    | nginx-cache-controller | 3.1.1       | 3.2.0       | Updated |
    +------------------------+-------------+-------------+---------+



### wp theme

Manages themes, including installs, activations, and updates.

~~~
wp theme
~~~

See the WordPress [Theme Handbook](https://developer.wordpress.org/themes/) developer resource for more information on themes.

**EXAMPLES**

    # Install the latest version of a theme from wordpress.org and activate
    $ wp theme install twentysixteen --activate
    Installing Twenty Sixteen (1.2)
    Downloading install package from http://downloads.wordpress.org/theme/twentysixteen.1.2.zip...
    Unpacking the package...
    Installing the theme...
    Theme installed successfully.
    Activating 'twentysixteen'...
    Success: Switched to 'Twenty Sixteen' theme.

    # Get details of an installed theme
    $ wp theme get twentysixteen --fields=name,title,version
    +---------+----------------+
    | Field   | Value          |
    +---------+----------------+
    | name    | Twenty Sixteen |
    | title   | Twenty Sixteen |
    | version | 1.2            |
    +---------+----------------+

    # Get status of theme
    $ wp theme status twentysixteen
    Theme twentysixteen details:
         Name: Twenty Sixteen
         Status: Active
         Version: 1.2
         Author: the WordPress team



### wp theme activate

Activates a theme.

~~~
wp theme activate <theme>
~~~

**OPTIONS**

	<theme>
		The theme to activate.

**EXAMPLES**

    $ wp theme activate twentysixteen
    Success: Switched to 'Twenty Sixteen' theme.



### wp theme delete

Deletes one or more themes.

~~~
wp theme delete [<theme>...] [--all] [--force]
~~~

Removes the theme or themes from the filesystem.

**OPTIONS**

	[<theme>...]
		One or more themes to delete.

	[--all]
		If set, all themes will be deleted except active theme.

	[--force]
		To delete active theme use this.

**EXAMPLES**

    $ wp theme delete twentytwelve
    Deleted 'twentytwelve' theme.
    Success: Deleted 1 of 1 themes.



### wp theme disable

Disables a theme on a WordPress multisite install.

~~~
wp theme disable <theme> [--network]
~~~

Removes ability for a theme to be activated from the dashboard of a site
on a WordPress multisite install.

**OPTIONS**

	<theme>
		The theme to disable.

	[--network]
		If set, the theme is disabled on the network level. Note that
		individual sites may still have this theme enabled if it was
		enabled for them independently.

**EXAMPLES**

    # Disable theme
    $ wp theme disable twentysixteen
    Success: Disabled the 'Twenty Sixteen' theme.

    # Disable theme in network level
    $ wp theme disable twentysixteen --network
    Success: Network disabled the 'Twenty Sixteen' theme.



### wp theme enable

Enables a theme on a WordPress multisite install.

~~~
wp theme enable <theme> [--network] [--activate]
~~~

Permits theme to be activated from the dashboard of a site on a WordPress
multisite install.

**OPTIONS**

	<theme>
		The theme to enable.

	[--network]
		If set, the theme is enabled for the entire network

	[--activate]
		If set, the theme is activated for the current site. Note that
		the "network" flag has no influence on this.

**EXAMPLES**

    # Enable theme
    $ wp theme enable twentysixteen
    Success: Enabled the 'Twenty Sixteen' theme.

    # Network enable theme
    $ wp theme enable twentysixteen --network
    Success: Network enabled the 'Twenty Sixteen' theme.

    # Network enable and activate theme for current site
    $ wp theme enable twentysixteen --activate
    Success: Enabled the 'Twenty Sixteen' theme.
    Success: Switched to 'Twenty Sixteen' theme.



### wp theme get

Gets details about a theme.

~~~
wp theme get <theme> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<theme>
		The theme to get.

	[--field=<field>]
		Instead of returning the whole theme, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

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

**EXAMPLES**

    $ wp theme get twentysixteen --fields=name,title,version
    +---------+----------------+
    | Field   | Value          |
    +---------+----------------+
    | name    | Twenty Sixteen |
    | title   | Twenty Sixteen |
    | version | 1.2            |
    +---------+----------------+



### wp theme install

Installs one or more themes.

~~~
wp theme install <theme|zip|url>... [--version=<version>] [--force] [--activate] [--insecure]
~~~

**OPTIONS**

	<theme|zip|url>...
		One or more themes to install. Accepts a theme slug, the path to a local zip file, or a URL to a remote zip file.

	[--version=<version>]
		If set, get that particular version from wordpress.org, instead of the
		stable version.

	[--force]
		If set, the command will overwrite any installed version of the theme, without prompting
		for confirmation.

	[--activate]
		If set, the theme will be activated immediately after install.

	[--insecure]
		Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.

**EXAMPLES**

    # Install the latest version from wordpress.org and activate
    $ wp theme install twentysixteen --activate
    Installing Twenty Sixteen (1.2)
    Downloading install package from http://downloads.wordpress.org/theme/twentysixteen.1.2.zip...
    Unpacking the package...
    Installing the theme...
    Theme installed successfully.
    Activating 'twentysixteen'...
    Success: Switched to 'Twenty Sixteen' theme.

    # Install from a local zip file
    $ wp theme install ../my-theme.zip

    # Install from a remote zip file
    $ wp theme install http://s3.amazonaws.com/bucketname/my-theme.zip?AWSAccessKeyId=123&Expires=456&Signature=abcdef



### wp theme is-installed

Checks if a given theme is installed.

~~~
wp theme is-installed <theme>
~~~

Returns exit code 0 when installed, 1 when uninstalled.

**OPTIONS**

	<theme>
		The theme to check.

**EXAMPLES**

    # Check whether theme is installed; exit status 0 if installed, otherwise 1
    $ wp theme is-installed hello
    $ echo $?
    1



### wp theme list

Gets a list of themes.

~~~
wp theme list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>] [--status=<status>]
~~~

**OPTIONS**

	[--<field>=<value>]
		Filter results based on the value of a field.

	[--field=<field>]
		Prints the value of a single field for each theme.

	[--fields=<fields>]
		Limit the output to specific object fields.

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

	[--status=<status>]
		Filter the output by theme status.
		---
		options:
		  - active
		  - parent
		  - inactive
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each theme:

* name
* status
* update
* version

These fields are optionally available:

* update_version
* update_package
* update_id
* title
* description

**EXAMPLES**

    # List themes
    $ wp theme list --status=inactive --format=csv
    name,status,update,version
    twentyfourteen,inactive,none,1.7
    twentysixteen,inactive,available,1.1



### wp theme mod

Sets, gets, and removes theme mods.

~~~
wp theme mod
~~~

**EXAMPLES**

    # Set the 'background_color' theme mod to '000000'.
    $ wp theme mod set background_color 000000
    Success: Theme mod background_color set to 000000

    # Get single theme mod in JSON format.
    $ wp theme mod get background_color --format=json
    [{"key":"background_color","value":"dd3333"}]

    # Remove all theme mods.
    $ wp theme mod remove --all
    Success: Theme mods removed.





### wp theme mod get

Gets one or more theme mods.

~~~
wp theme mod get [<mod>...] [--field=<field>] [--all] [--format=<format>]
~~~

**OPTIONS**

	[<mod>...]
		One or more mods to get.

	[--field=<field>]
		Returns the value of a single field.

	[--all]
		List all theme mods

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - yaml
		---

**EXAMPLES**

    # Get all theme mods.
    $ wp theme mod get --all
    +------------------+---------+
    | key              | value   |
    +------------------+---------+
    | background_color | dd3333  |
    | link_color       | #dd9933 |
    | main_text_color  | #8224e3 |
    +------------------+---------+

    # Get single theme mod in JSON format.
    $ wp theme mod get background_color --format=json
    [{"key":"background_color","value":"dd3333"}]

    # Get value of a single theme mod.
    $ wp theme mod get background_color --field=value
    dd3333

    # Get multiple theme mods.
    $ wp theme mod get background_color header_textcolor
    +------------------+--------+
    | key              | value  |
    +------------------+--------+
    | background_color | dd3333 |
    | header_textcolor |        |
    +------------------+--------+



### wp theme mod set

Sets the value of a theme mod.

~~~
wp theme mod set <mod> <value>
~~~

**OPTIONS**

	<mod>
		The name of the theme mod to set or update.

	<value>
		The new value.

**EXAMPLES**

    # Set theme mod
    $ wp theme mod set background_color 000000
    Success: Theme mod background_color set to 000000



### wp theme mod remove

Removes one or more theme mods.

~~~
wp theme mod remove [<mod>...] [--all]
~~~

**OPTIONS**

	[<mod>...]
		One or more mods to remove.

	[--all]
		Remove all theme mods.

**EXAMPLES**

    # Remove all theme mods.
    $ wp theme mod remove --all
    Success: Theme mods removed.

    # Remove single theme mod.
    $ wp theme mod remove background_color
    Success: 1 mod removed.

    # Remove multiple theme mods.
    $ wp theme mod remove background_color header_textcolor
    Success: 2 mods removed.



### wp theme path

Gets the path to a theme or to the theme directory.

~~~
wp theme path [<theme>] [--dir]
~~~

**OPTIONS**

	[<theme>]
		The theme to get the path to. Path includes "style.css" file.
		If not set, will return the path to the themes directory.

	[--dir]
		If set, get the path to the closest parent directory, instead of the
		theme's "style.css" file.

**EXAMPLES**

    # Get theme path
    $ wp theme path
    /var/www/example.com/public_html/wp-content/themes

    # Change directory to theme path
    $ cd $(wp theme path)



### wp theme search

Searches the WordPress.org theme directory.

~~~
wp theme search <search> [--page=<page>] [--per-page=<per-page>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Displays themes in the WordPress.org theme directory matching a given
search query.

**OPTIONS**

	<search>
		The string to search for.

	[--page=<page>]
		Optional page to display.
		---
		default: 1
		---

	[--per-page=<per-page>]
		Optional number of results to display. Defaults to 10.

	[--field=<field>]
		Prints the value of a single field for each theme.

	[--fields=<fields>]
		Ask for specific fields from the API. Defaults to name,slug,author,rating. Acceptable values:

    **name**: Theme Name
    **slug**: Theme Slug
    **version**: Current Version Number
    **author**: Theme Author
    **preview_url**: Theme Preview URL
    **screenshot_url**: Theme Screenshot URL
    **rating**: Theme Rating
    **num_ratings**: Number of Theme Ratings
    **homepage**: Theme Author's Homepage
    **description**: Theme Description
    **url**: Theme's URL on wordpress.org

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

**EXAMPLES**

    $ wp theme search photo --per-page=6
    Success: Showing 6 of 203 themes.
    +----------------------+----------------------+--------+
    | name                 | slug                 | rating |
    +----------------------+----------------------+--------+
    | Photos               | photos               | 100    |
    | Infinite Photography | infinite-photography | 100    |
    | PhotoBook            | photobook            | 100    |
    | BG Photo Frame       | bg-photo-frame       | 0      |
    | fPhotography         | fphotography         | 0      |
    | Photo Perfect        | photo-perfect        | 98     |
    +----------------------+----------------------+--------+



### wp theme status

Reveals the status of one or all themes.

~~~
wp theme status [<theme>]
~~~

**OPTIONS**

	[<theme>]
		A particular theme to show the status for.

**EXAMPLES**

    $ wp theme status twentysixteen
    Theme twentysixteen details:
         Name: Twenty Sixteen
         Status: Inactive
         Version: 1.2
         Author: the WordPress team



### wp theme update

Updates one or more themes.

~~~
wp theme update [<theme>...] [--all] [--exclude=<theme-names>] [--format=<format>] [--version=<version>] [--dry-run] [--insecure]
~~~

**OPTIONS**

	[<theme>...]
		One or more themes to update.

	[--all]
		If set, all themes that have updates will be updated.

	[--exclude=<theme-names>]
		Comma separated list of theme names that should be excluded from updating.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - summary
		---

	[--version=<version>]
		If set, the theme will be updated to the specified version.

	[--dry-run]
		Preview which themes would be updated.

	[--insecure]
		Retry downloads without certificate validation if TLS handshake fails. Note: This makes the request vulnerable to a MITM attack.

**EXAMPLES**

    # Update multiple themes
    $ wp theme update twentyfifteen twentysixteen
    Downloading update from https://downloads.wordpress.org/theme/twentyfifteen.1.5.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the theme...
    Theme updated successfully.
    Downloading update from https://downloads.wordpress.org/theme/twentysixteen.1.2.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the theme...
    Theme updated successfully.
    +---------------+-------------+-------------+---------+
    | name          | old_version | new_version | status  |
    +---------------+-------------+-------------+---------+
    | twentyfifteen | 1.4         | 1.5         | Updated |
    | twentysixteen | 1.1         | 1.2         | Updated |
    +---------------+-------------+-------------+---------+
    Success: Updated 2 of 2 themes.

    # Exclude themes updates when bulk updating the themes
    $ wp theme update --all --exclude=twentyfifteen
    Downloading update from https://downloads.wordpress.org/theme/astra.1.0.5.1.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the theme...
    Theme updated successfully.
    Downloading update from https://downloads.wordpress.org/theme/twentyseventeen.1.2.zip...
    Unpacking the update...
    Installing the latest version...
    Removing the old version of the theme...
    Theme updated successfully.
    +-----------------+----------+---------+----------------+
    | name            | status   | version | update_version |
    +-----------------+----------+---------+----------------+
    | astra           | inactive | 1.0.1   | 1.0.5.1        |
    | twentyseventeen | inactive | 1.1     | 1.2            |
    +-----------------+----------+---------+----------------+
    Success: Updated 2 of 2 themes.

    # Update all themes
    $ wp theme update --all



### wp theme mod list

Gets a list of theme mods.

~~~
wp theme mod list [--field=<field>] [--format=<format>]
~~~

**OPTIONS**

	[--field=<field>]
		Returns the value of a single field.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - yaml
		---

**EXAMPLES**

    # Gets a list of theme mods.
    $ wp theme mod list
    +------------------+---------+
    | key              | value   |
    +------------------+---------+
    | background_color | dd3333  |
    | link_color       | #dd9933 |
    | main_text_color  | #8224e3 |
    +------------------+---------+

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/extension-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/extension-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/extension-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/extension-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
