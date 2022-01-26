wp-cli/entity-command
=====================

Manage WordPress comments, menus, options, posts, sites, terms, and users.

[![Testing](https://github.com/wp-cli/entity-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/entity-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp comment

Creates, updates, deletes, and moderates comments.

~~~
wp comment
~~~

**EXAMPLES**

    # Create a new comment.
    $ wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
    Success: Created comment 932.

    # Update an existing comment.
    $ wp comment update 123 --comment_author='That Guy'
    Success: Updated comment 123.

    # Delete an existing comment.
    $ wp comment delete 1337 --force
    Success: Deleted comment 1337.

    # Delete all spam comments.
    $ wp comment delete $(wp comment list --status=spam --format=ids)
    Success: Deleted comment 264.
    Success: Deleted comment 262.



### wp comment approve

Approves a comment.

~~~
wp comment approve <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to approve.

**EXAMPLES**

    # Approve comment.
    $ wp comment approve 1337
    Success: Approved comment 1337.



### wp comment count

Counts comments, on whole blog or on a given post.

~~~
wp comment count [<post-id>]
~~~

**OPTIONS**

	[<post-id>]
		The ID of the post to count comments in.

**EXAMPLES**

    # Count comments on whole blog.
    $ wp comment count
    approved:        33
    spam:            3
    trash:           1
    post-trashed:    0
    all:             34
    moderated:       1
    total_comments:  37

    # Count comments in a post.
    $ wp comment count 42
    approved:        19
    spam:            0
    trash:           0
    post-trashed:    0
    all:             19
    moderated:       0
    total_comments:  19



### wp comment create

Creates a new comment.

~~~
wp comment create [--<field>=<value>] [--porcelain]
~~~

**OPTIONS**

	[--<field>=<value>]
		Associative args for the new comment. See wp_insert_comment().

	[--porcelain]
		Output just the new comment id.

**EXAMPLES**

    # Create comment.
    $ wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
    Success: Created comment 932.



### wp comment delete

Deletes a comment.

~~~
wp comment delete <id>... [--force]
~~~

**OPTIONS**

	<id>...
		One or more IDs of comments to delete.

	[--force]
		Skip the trash bin.

**EXAMPLES**

    # Delete comment.
    $ wp comment delete 1337 --force
    Success: Deleted comment 1337.

    # Delete multiple comments.
    $ wp comment delete 1337 2341 --force
    Success: Deleted comment 1337.
    Success: Deleted comment 2341.



### wp comment exists

Verifies whether a comment exists.

~~~
wp comment exists <id>
~~~

Displays a success message if the comment does exist.

**OPTIONS**

	<id>
		The ID of the comment to check.

**EXAMPLES**

    # Check whether comment exists.
    $ wp comment exists 1337
    Success: Comment with ID 1337 exists.



### wp comment generate

Generates some number of new dummy comments.

~~~
wp comment generate [--count=<number>] [--post_id=<post-id>] [--format=<format>]
~~~

Creates a specified number of new comments with dummy data.

**OPTIONS**

	[--count=<number>]
		How many comments to generate?
		---
		default: 100
		---

	[--post_id=<post-id>]
		Assign comments to a specific post.

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate comments for the given post.
    $ wp comment generate --format=ids --count=3 --post_id=123
    138 139 140

    # Add meta to every generated comment.
    $ wp comment generate --format=ids --count=3 | xargs -d ' ' -I % wp comment meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### wp comment get

Gets the data of a single comment.

~~~
wp comment get <id> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The comment to get.

	[--field=<field>]
		Instead of returning the whole comment, returns the value of a single field.

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

    # Get comment.
    $ wp comment get 21 --field=content
    Thanks for all the comments, everyone!



### wp comment list

Gets a list of comments.

~~~
wp comment list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Display comments based on all arguments supported by
[WP_Comment_Query()](https://developer.wordpress.org/reference/classes/WP_Comment_Query/__construct/).

**OPTIONS**

	[--<field>=<value>]
		One or more args to pass to WP_Comment_Query.

	[--field=<field>]
		Prints the value of a single field for each comment.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - ids
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each comment:

* comment_ID
* comment_post_ID
* comment_date
* comment_approved
* comment_author
* comment_author_email

These fields are optionally available:

* comment_author_url
* comment_author_IP
* comment_date_gmt
* comment_content
* comment_karma
* comment_agent
* comment_type
* comment_parent
* user_id
* url

**EXAMPLES**

    # List comment IDs.
    $ wp comment list --field=ID
    22
    23
    24

    # List comments of a post.
    $ wp comment list --post_id=1 --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 1          | 2015-06-20 09:00:10 | Mr WordPress   |
    +------------+---------------------+----------------+

    # List approved comments.
    $ wp comment list --number=3 --status=approve --fields=ID,comment_date,comment_author
    +------------+---------------------+----------------+
    | comment_ID | comment_date        | comment_author |
    +------------+---------------------+----------------+
    | 1          | 2015-06-20 09:00:10 | Mr WordPress   |
    | 30         | 2013-03-14 12:35:07 | John Doe       |
    | 29         | 2013-03-14 11:56:08 | Jane Doe       |
    +------------+---------------------+----------------+



### wp comment meta

Adds, updates, deletes, and lists comment custom fields.

~~~
wp comment meta
~~~

**EXAMPLES**

    # Set comment meta
    $ wp comment meta set 123 description "Mary is a WordPress developer."
    Success: Updated custom field 'description'.

    # Get comment meta
    $ wp comment meta get 123 description
    Mary is a WordPress developer.

    # Update comment meta
    $ wp comment meta update 123 description "Mary is an awesome WordPress developer."
    Success: Updated custom field 'description'.

    # Delete comment meta
    $ wp comment meta delete 123 description
    Success: Deleted custom field.





### wp comment meta add

Add a meta field.

~~~
wp comment meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp comment meta delete

Delete a meta field.

~~~
wp comment meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp comment meta get

Get meta field value.

~~~
wp comment meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### wp comment meta list

List all metadata associated with an object.

~~~
wp comment meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### wp comment meta patch

Update a nested value for a meta field.

~~~
wp comment meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp comment meta pluck

Get a nested value from a meta field.

~~~
wp comment meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp comment meta update

Update a meta field.

~~~
wp comment meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp comment recount

Recalculates the comment_count value for one or more posts.

~~~
wp comment recount <id>...
~~~

**OPTIONS**

	<id>...
		IDs for one or more posts to update.

**EXAMPLES**

    # Recount comment for the post.
    $ wp comment recount 123
    Updated post 123 comment count to 67.



### wp comment spam

Marks a comment as spam.

~~~
wp comment spam <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to mark as spam.

**EXAMPLES**

    # Spam comment.
    $ wp comment spam 1337
    Success: Marked as spam comment 1337.



### wp comment status

Gets the status of a comment.

~~~
wp comment status <id>
~~~

**OPTIONS**

	<id>
		The ID of the comment to check.

**EXAMPLES**

    # Get status of comment.
    $ wp comment status 1337
    approved



### wp comment trash

Trashes a comment.

~~~
wp comment trash <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to trash.

**EXAMPLES**

    # Trash comment.
    $ wp comment trash 1337
    Success: Trashed comment 1337.



### wp comment unapprove

Unapproves a comment.

~~~
wp comment unapprove <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to unapprove.

**EXAMPLES**

    # Unapprove comment.
    $ wp comment unapprove 1337
    Success: Unapproved comment 1337.



### wp comment unspam

Unmarks a comment as spam.

~~~
wp comment unspam <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to unmark as spam.

**EXAMPLES**

    # Unspam comment.
    $ wp comment unspam 1337
    Success: Unspammed comment 1337.



### wp comment untrash

Untrashes a comment.

~~~
wp comment untrash <id>...
~~~

**OPTIONS**

	<id>...
		The IDs of the comments to untrash.

**EXAMPLES**

    # Untrash comment.
    $ wp comment untrash 1337
    Success: Untrashed comment 1337.



### wp comment update

Updates one or more comments.

~~~
wp comment update <id>... --<field>=<value>
~~~

**OPTIONS**

	<id>...
		One or more IDs of comments to update.

	--<field>=<value>
		One or more fields to update. See wp_update_comment().

**EXAMPLES**

    # Update comment.
    $ wp comment update 123 --comment_author='That Guy'
    Success: Updated comment 123.



### wp menu

Lists, creates, assigns, and deletes the active theme's navigation menus.

~~~
wp menu
~~~

See the [Navigation Menus](https://developer.wordpress.org/themes/functionality/navigation-menus/) reference in the Theme Handbook.

**EXAMPLES**

    # Create a new menu
    $ wp menu create "My Menu"
    Success: Created menu 200.

    # List existing menus
    $ wp menu list
    +---------+----------+----------+-----------+-------+
    | term_id | name     | slug     | locations | count |
    +---------+----------+----------+-----------+-------+
    | 200     | My Menu  | my-menu  |           | 0     |
    | 177     | Top Menu | top-menu | primary   | 7     |
    +---------+----------+----------+-----------+-------+

    # Create a new menu link item
    $ wp menu item add-custom my-menu Apple http://apple.com --porcelain
    1922

    # Assign the 'my-menu' menu to the 'primary' location
    $ wp menu location assign my-menu primary
    Success: Assigned location to menu.



### wp menu create

Creates a new menu.

~~~
wp menu create <menu-name> [--porcelain]
~~~

**OPTIONS**

	<menu-name>
		A descriptive name for the menu.

	[--porcelain]
		Output just the new menu id.

**EXAMPLES**

    $ wp menu create "My Menu"
    Success: Created menu 200.



### wp menu delete

Deletes one or more menus.

~~~
wp menu delete <menu>...
~~~

**OPTIONS**

	<menu>...
		The name, slug, or term ID for the menu(s).

**EXAMPLES**

    $ wp menu delete "My Menu"
    Success: 1 menu deleted.



### wp menu item

List, add, and delete items associated with a menu.

~~~
wp menu item
~~~

**EXAMPLES**

    # Add an existing post to an existing menu
    $ wp menu item add-post sidebar-menu 33 --title="Custom Test Post"
    Success: Menu item added.

    # Create a new menu link item
    $ wp menu item add-custom sidebar-menu Apple http://apple.com
    Success: Menu item added.

    # Delete menu item
    $ wp menu item delete 45
    Success: 1 menu item deleted.





### wp menu item add-custom

Adds a custom menu item.

~~~
wp menu item add-custom <menu> <title> <link> [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<title>
		Title for the link.

	<link>
		Target URL for the link.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ wp menu item add-custom sidebar-menu Apple http://apple.com
    Success: Menu item added.



### wp menu item add-post

Adds a post as a menu item.

~~~
wp menu item add-post <menu> <post-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<post-id>
		Post ID to add to the menu.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ wp menu item add-post sidebar-menu 33 --title="Custom Test Post"
    Success: Menu item added.



### wp menu item add-term

Adds a taxonomy term as a menu item.

~~~
wp menu item add-term <menu> <taxonomy> <term-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>] [--porcelain]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<taxonomy>
		Taxonomy of the term to be added.

	<term-id>
		Term ID of the term to be added.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

	[--porcelain]
		Output just the new menu item id.

**EXAMPLES**

    $ wp menu item add-term sidebar-menu post_tag 24
    Success: Menu item added.



### wp menu item delete

Deletes one or more items from a menu.

~~~
wp menu item delete <db-id>...
~~~

**OPTIONS**

	<db-id>...
		Database ID for the menu item(s).

**EXAMPLES**

    $ wp menu item delete 45
    Success: 1 menu item deleted.



### wp menu item list

Gets a list of items associated with a menu.

~~~
wp menu item list <menu> [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

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
		  - ids
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each menu item:

* db_id
* type
* title
* link
* position

These fields are optionally available:

* menu_item_parent
* object_id
* object
* type
* type_label
* target
* attr_title
* description
* classes
* xfn

**EXAMPLES**

    $ wp menu item list main-menu
    +-------+-----------+-------------+---------------------------------+----------+
    | db_id | type      | title       | link                            | position |
    +-------+-----------+-------------+---------------------------------+----------+
    | 5     | custom    | Home        | http://example.com              | 1        |
    | 6     | post_type | Sample Page | http://example.com/sample-page/ | 2        |
    +-------+-----------+-------------+---------------------------------+----------+



### wp menu item update

Updates a menu item.

~~~
wp menu item update <db-id> [--title=<title>] [--link=<link>] [--description=<description>] [--attr-title=<attr-title>] [--target=<target>] [--classes=<classes>] [--position=<position>] [--parent-id=<parent-id>]
~~~

**OPTIONS**

	<db-id>
		Database ID for the menu item.

	[--title=<title>]
		Set a custom title for the menu item.

	[--link=<link>]
		Set a custom url for the menu item.

	[--description=<description>]
		Set a custom description for the menu item.

	[--attr-title=<attr-title>]
		Set a custom title attribute for the menu item.

	[--target=<target>]
		Set a custom link target for the menu item.

	[--classes=<classes>]
		Set a custom link classes for the menu item.

	[--position=<position>]
		Specify the position of this menu item.

	[--parent-id=<parent-id>]
		Make this menu item a child of another menu item.

**EXAMPLES**

    $ wp menu item update 45 --title=WordPress --link='http://wordpress.org' --target=_blank --position=2
    Success: Menu item updated.



### wp menu list

Gets a list of menus.

~~~
wp menu list [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

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
		  - ids
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each menu:

* term_id
* name
* slug
* count

These fields are optionally available:

* term_group
* term_taxonomy_id
* taxonomy
* description
* parent
* locations

**EXAMPLES**

    $ wp menu list
    +---------+----------+----------+-----------+-------+
    | term_id | name     | slug     | locations | count |
    +---------+----------+----------+-----------+-------+
    | 200     | My Menu  | my-menu  |           | 0     |
    | 177     | Top Menu | top-menu | primary   | 7     |
    +---------+----------+----------+-----------+-------+



### wp menu location

Assigns, removes, and lists a menu's locations.

~~~
wp menu location
~~~

**EXAMPLES**

    # List available menu locations
    $ wp menu location list
    +----------+-------------------+
    | location | description       |
    +----------+-------------------+
    | primary  | Primary Menu      |
    | social   | Social Links Menu |
    +----------+-------------------+

    # Assign the 'primary-menu' menu to the 'primary' location
    $ wp menu location assign primary-menu primary
    Success: Assigned location to menu.

    # Remove the 'primary-menu' menu from the 'primary' location
    $ wp menu location remove primary-menu primary
    Success: Removed location from menu.





### wp menu location assign

Assigns a location to a menu.

~~~
wp menu location assign <menu> <location>
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<location>
		Location's slug.

**EXAMPLES**

    $ wp menu location assign primary-menu primary
    Success: Assigned location primary to menu primary-menu.



### wp menu location list

Lists locations for the current theme.

~~~
wp menu location list [--format=<format>]
~~~

**OPTIONS**

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
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each location:

* name
* description

**EXAMPLES**

    $ wp menu location list
    +----------+-------------------+
    | location | description       |
    +----------+-------------------+
    | primary  | Primary Menu      |
    | social   | Social Links Menu |
    +----------+-------------------+



### wp menu location remove

Removes a location from a menu.

~~~
wp menu location remove <menu> <location>
~~~

**OPTIONS**

	<menu>
		The name, slug, or term ID for the menu.

	<location>
		Location's slug.

**EXAMPLES**

    $ wp menu location remove primary-menu primary
    Success: Removed location from menu.



### wp network meta

Gets, adds, updates, deletes, and lists network custom fields.

~~~
wp network meta
~~~

**EXAMPLES**

    # Get a list of super-admins
    $ wp network meta get 1 site_admins
    array (
      0 => 'supervisor',
    )



### wp network meta add

Add a meta field.

~~~
wp network meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp network meta delete

Delete a meta field.

~~~
wp network meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp network meta get

Get meta field value.

~~~
wp network meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### wp network meta list

List all metadata associated with an object.

~~~
wp network meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### wp network meta patch

Update a nested value for a meta field.

~~~
wp network meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp network meta pluck

Get a nested value from a meta field.

~~~
wp network meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp network meta update

Update a meta field.

~~~
wp network meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp option

Retrieves and sets site options, including plugin and WordPress settings.

~~~
wp option
~~~

See the [Plugin Settings API](https://developer.wordpress.org/plugins/settings/settings-api/) and the [Theme Options](https://developer.wordpress.org/themes/customize-api/) for more information on adding customized options.

**EXAMPLES**

    # Get site URL.
    $ wp option get siteurl
    http://example.com

    # Add option.
    $ wp option add my_option foobar
    Success: Added 'my_option' option.

    # Update option.
    $ wp option update my_option '{"foo": "bar"}' --format=json
    Success: Updated 'my_option' option.

    # Delete option.
    $ wp option delete my_option
    Success: Deleted 'my_option' option.



### wp option add

Adds a new option value.

~~~
wp option add <key> [<value>] [--format=<format>] [--autoload=<autoload>]
~~~

Errors if the option already exists.

**OPTIONS**

	<key>
		The name of the option to add.

	[<value>]
		The value of the option to add. If ommited, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

	[--autoload=<autoload>]
		Should this option be automatically loaded.
		---
		options:
		  - 'yes'
		  - 'no'
		---

**EXAMPLES**

    # Create an option by reading a JSON file.
    $ wp option add my_option --format=json < config.json
    Success: Added 'my_option' option.



### wp option delete

Deletes an option.

~~~
wp option delete <key>...
~~~

**OPTIONS**

	<key>...
		Key for the option.

**EXAMPLES**

    # Delete an option.
    $ wp option delete my_option
    Success: Deleted 'my_option' option.

    # Delete multiple options.
    $ wp option delete option_one option_two option_three
    Success: Deleted 'option_one' option.
    Success: Deleted 'option_two' option.
    Warning: Could not delete 'option_three' option. Does it exist?



### wp option get

Gets the value for an option.

~~~
wp option get <key> [--format=<format>]
~~~

**OPTIONS**

	<key>
		Key for the option.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get option.
    $ wp option get home
    http://example.com

    # Get blog description.
    $ wp option get blogdescription
    A random blog description

    # Get blog name
    $ wp option get blogname
    A random blog name

    # Get admin email.
    $ wp option get admin_email
    someone@example.com

    # Get option in JSON format.
    $ wp option get active_plugins --format=json
    {"0":"dynamically-dynamic-sidebar\/dynamically-dynamic-sidebar.php","1":"monster-widget\/monster-widget.php","2":"show-current-template\/show-current-template.php","3":"theme-check\/theme-check.php","5":"wordpress-importer\/wordpress-importer.php"}



### wp option list

Lists options and their values.

~~~
wp option list [--search=<pattern>] [--exclude=<pattern>] [--autoload=<value>] [--transients] [--unserialize] [--field=<field>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>]
~~~

**OPTIONS**

	[--search=<pattern>]
		Use wildcards ( * and ? ) to match option name.

	[--exclude=<pattern>]
		Pattern to exclude. Use wildcards ( * and ? ) to match option name.

	[--autoload=<value>]
		Match only autoload options when value is on, and only not-autoload option when off.

	[--transients]
		List only transients. Use `--no-transients` to ignore all transients.

	[--unserialize]
		Unserialize option values in output.

	[--field=<field>]
		Prints the value of a single field.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		The serialization format for the value. total_bytes displays the total size of matching options in bytes.
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - count
		  - yaml
		  - total_bytes
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: option_id
		options:
		 - option_id
		 - option_name
		 - option_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

**AVAILABLE FIELDS**

This field will be displayed by default for each matching option:

* option_name
* option_value

These fields are optionally available:

* autoload
* size_bytes

**EXAMPLES**

    # Get the total size of all autoload options.
    $ wp option list --autoload=on --format=total_bytes
    33198

    # Find biggest transients.
    $ wp option list --search="*_transient_*" --fields=option_name,size_bytes | sort -n -k 2 | tail
    option_name size_bytes
    _site_transient_timeout_theme_roots 10
    _site_transient_theme_roots 76
    _site_transient_update_themes   181
    _site_transient_update_core 808
    _site_transient_update_plugins  6645

    # List all options beginning with "i2f_".
    $ wp option list --search="i2f_*"
    +-------------+--------------+
    | option_name | option_value |
    +-------------+--------------+
    | i2f_version | 0.1.0        |
    +-------------+--------------+

    # Delete all options beginning with "theme_mods_".
    $ wp option list --search="theme_mods_*" --field=option_name | xargs -I % wp option delete %
    Success: Deleted 'theme_mods_twentysixteen' option.
    Success: Deleted 'theme_mods_twentyfifteen' option.
    Success: Deleted 'theme_mods_twentyfourteen' option.



### wp option patch

Updates a nested value in an option.

~~~
wp option patch <action> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<key>
		The option name.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp option pluck

Gets a nested value from an option.

~~~
wp option pluck <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<key>
		The option name.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml
		---



### wp option update

Updates an option value.

~~~
wp option update <key> [<value>] [--autoload=<autoload>] [--format=<format>]
~~~

**OPTIONS**

	<key>
		The name of the option to update.

	[<value>]
		The new value. If ommited, the value is read from STDIN.

	[--autoload=<autoload>]
		Requires WP 4.2. Should this option be automatically loaded.
		---
		options:
		  - 'yes'
		  - 'no'
		---

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Update an option by reading from a file.
    $ wp option update my_option < value.txt
    Success: Updated 'my_option' option.

    # Update one option on multiple sites using xargs.
    $ wp site list --field=url | xargs -n1 -I {} sh -c 'wp --url={} option update my_option my_value'
    Success: Updated 'my_option' option.
    Success: Updated 'my_option' option.

    # Update site blog name.
    $ wp option update blogname "Random blog name"
    Success: Updated 'blogname' option.

    # Update site blog description.
    $ wp option update blogdescription "Some random blog description"
    Success: Updated 'blogdescription' option.

    # Update admin email address.
    $ wp option update admin_email someone@example.com
    Success: Updated 'admin_email' option.

    # Set the default role.
    $ wp option update default_role author
    Success: Updated 'default_role' option.

    # Set the timezone string.
    $ wp option update timezone_string "America/New_York"
    Success: Updated 'timezone_string' option.



### wp post

Manages posts, content, and meta.

~~~
wp post
~~~

**EXAMPLES**

    # Create a new post.
    $ wp post create --post_type=post --post_title='A sample post'
    Success: Created post 123.

    # Update an existing post.
    $ wp post update 123 --post_status=draft
    Success: Updated post 123.

    # Delete an existing post.
    $ wp post delete 123
    Success: Trashed post 123.



### wp post create

Creates a new post.

~~~
wp post create [--post_author=<post_author>] [--post_date=<post_date>] [--post_date_gmt=<post_date_gmt>] [--post_content=<post_content>] [--post_content_filtered=<post_content_filtered>] [--post_title=<post_title>] [--post_excerpt=<post_excerpt>] [--post_status=<post_status>] [--post_type=<post_type>] [--comment_status=<comment_status>] [--ping_status=<ping_status>] [--post_password=<post_password>] [--post_name=<post_name>] [--from-post=<post_id>] [--to_ping=<to_ping>] [--pinged=<pinged>] [--post_modified=<post_modified>] [--post_modified_gmt=<post_modified_gmt>] [--post_parent=<post_parent>] [--menu_order=<menu_order>] [--post_mime_type=<post_mime_type>] [--guid=<guid>] [--post_category=<post_category>] [--tags_input=<tags_input>] [--tax_input=<tax_input>] [--meta_input=<meta_input>] [<file>] [--<field>=<value>] [--edit] [--porcelain]
~~~

**OPTIONS**

	[--post_author=<post_author>]
		The ID of the user who added the post. Default is the current user ID.

	[--post_date=<post_date>]
		The date of the post. Default is the current time.

	[--post_date_gmt=<post_date_gmt>]
		The date of the post in the GMT timezone. Default is the value of $post_date.

	[--post_content=<post_content>]
		The post content. Default empty.

	[--post_content_filtered=<post_content_filtered>]
		The filtered post content. Default empty.

	[--post_title=<post_title>]
		The post title. Default empty.

	[--post_excerpt=<post_excerpt>]
		The post excerpt. Default empty.

	[--post_status=<post_status>]
		The post status. Default 'draft'.

	[--post_type=<post_type>]
		The post type. Default 'post'.

	[--comment_status=<comment_status>]
		Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option.

	[--ping_status=<ping_status>]
		Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option.

	[--post_password=<post_password>]
		The password to access the post. Default empty.

	[--post_name=<post_name>]
		The post name. Default is the sanitized post title when creating a new post.

	[--from-post=<post_id>]
		Post id of a post to be duplicated.

	[--to_ping=<to_ping>]
		Space or carriage return-separated list of URLs to ping. Default empty.

	[--pinged=<pinged>]
		Space or carriage return-separated list of URLs that have been pinged. Default empty.

	[--post_modified=<post_modified>]
		The date when the post was last modified. Default is the current time.

	[--post_modified_gmt=<post_modified_gmt>]
		The date when the post was last modified in the GMT timezone. Default is the current time.

	[--post_parent=<post_parent>]
		Set this for the post it belongs to, if any. Default 0.

	[--menu_order=<menu_order>]
		The order the post should be displayed in. Default 0.

	[--post_mime_type=<post_mime_type>]
		The mime type of the post. Default empty.

	[--guid=<guid>]
		Global Unique ID for referencing the post. Default empty.

	[--post_category=<post_category>]
		Array of category names, slugs, or IDs. Defaults to value of the 'default_category' option.

	[--tags_input=<tags_input>]
		Array of tag names, slugs, or IDs. Default empty.

	[--tax_input=<tax_input>]
		Array of taxonomy terms keyed by their taxonomy name. Default empty.

	[--meta_input=<meta_input>]
		Array in JSON format of post meta values keyed by their post meta key. Default empty.

	[<file>]
		Read post content from <file>. If this value is present, the
		    `--post_content` argument will be ignored.

  Passing `-` as the filename will cause post content to
  be read from STDIN.

	[--<field>=<value>]
		Associative args for the new post. See wp_insert_post().

	[--edit]
		Immediately open system's editor to write or edit post content.

  If content is read from a file, from STDIN, or from the `--post_content`
  argument, that text will be loaded into the editor.

	[--porcelain]
		Output just the new post id.


**EXAMPLES**

    # Create post and schedule for future
    $ wp post create --post_type=page --post_title='A future post' --post_status=future --post_date='2020-12-01 07:00:00'
    Success: Created post 1921.

    # Create post with content from given file
    $ wp post create ./post-content.txt --post_category=201,345 --post_title='Post from file'
    Success: Created post 1922.

    # Create a post with multiple meta values.
    $ wp post create --post_title='A post' --post_content='Just a small post.' --meta_input='{"key1":"value1","key2":"value2"}'
    Success: Created post 1923.

    # Create a duplicate post from existing posts.
    $ wp post create --from-post=123 --post_title='Different Title'
    Success: Created post 2350.



### wp post delete

Deletes an existing post.

~~~
wp post delete <id>... [--force] [--defer-term-counting]
~~~

**OPTIONS**

	<id>...
		One or more IDs of posts to delete.

	[--force]
		Skip the trash bin.

	[--defer-term-counting]
		Recalculate term count in batch, for a performance boost.

**EXAMPLES**

    # Delete post skipping trash
    $ wp post delete 123 --force
    Success: Deleted post 123.

    # Delete all pages
    $ wp post delete $(wp post list --post_type='page' --format=ids)
    Success: Trashed post 1164.
    Success: Trashed post 1186.

    # Delete all posts in the trash
    $ wp post delete $(wp post list --post_status=trash --format=ids)
    Success: Deleted post 1268.
    Success: Deleted post 1294.



### wp post edit

Launches system editor to edit post content.

~~~
wp post edit <id>
~~~

**OPTIONS**

	<id>
		The ID of the post to edit.

**EXAMPLES**

    # Launch system editor to edit post
    $ wp post edit 123



### wp post exists

Verifies whether a post exists.

~~~
wp post exists <id>
~~~

Displays a success message if the post does exist.

**OPTIONS**

	<id>
		The ID of the post to check.

**EXAMPLES**

    # The post exists.
    $ wp post exists 1337
    Success: Post with ID 1337 exists.
    $ echo $?
    0

    # The post does not exist.
    $ wp post exists 10000
    $ echo $?
    1



### wp post generate

Generates some posts.

~~~
wp post generate [--count=<number>] [--post_type=<type>] [--post_status=<status>] [--post_title=<post_title>] [--post_author=<login>] [--post_date=<yyyy-mm-dd-hh-ii-ss>] [--post_date_gmt=<yyyy-mm-dd-hh-ii-ss>] [--post_content] [--max_depth=<number>] [--format=<format>]
~~~

Creates a specified number of new posts with dummy data.

**OPTIONS**

	[--count=<number>]
		How many posts to generate?
		---
		default: 100
		---

	[--post_type=<type>]
		The type of the generated posts.
		---
		default: post
		---

	[--post_status=<status>]
		The status of the generated posts.
		---
		default: publish
		---

	[--post_title=<post_title>]
		The post title.
		---
		default:
		---

	[--post_author=<login>]
		The author of the generated posts.
		---
		default:
		---

	[--post_date=<yyyy-mm-dd-hh-ii-ss>]
		The date of the generated posts. Default: current date

	[--post_date_gmt=<yyyy-mm-dd-hh-ii-ss>]
		The GMT date of the generated posts. Default: value of post_date (or current date if it's not set)

	[--post_content]
		If set, the command reads the post_content from STDIN.

	[--max_depth=<number>]
		For hierarchical post types, generate child posts down to a certain depth.
		---
		default: 1
		---

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate posts.
    $ wp post generate --count=10 --post_type=page --post_date=1999-01-04
    Generating posts  100% [================================================] 0:01 / 0:04

    # Generate posts with fetched content.
    $ curl -N http://loripsum.net/api/5 | wp post generate --post_content --count=10
      % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                     Dload  Upload   Total   Spent    Left  Speed
    100  2509  100  2509    0     0    616      0  0:00:04  0:00:04 --:--:--   616
    Generating posts  100% [================================================] 0:01 / 0:04

    # Add meta to every generated posts.
    $ wp post generate --format=ids | xargs -d ' ' -I % wp post meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### wp post get

Gets details about a post.

~~~
wp post get <id> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the post to get.

	[--field=<field>]
		Instead of returning the whole post, returns the value of a single field.

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

    # Save the post content to a file
    $ wp post get 123 --field=content > file.txt



### wp post list

Gets a list of posts.

~~~
wp post list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Display posts based on all arguments supported by [WP_Query()](https://developer.wordpress.org/reference/classes/wp_query/).
Only shows post types marked as post by default.

**OPTIONS**

	[--<field>=<value>]
		One or more args to pass to WP_Query.

	[--field=<field>]
		Prints the value of a single field for each post.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each post:

* ID
* post_title
* post_name
* post_date
* post_status

These fields are optionally available:

* post_author
* post_date_gmt
* post_content
* post_excerpt
* comment_status
* ping_status
* post_password
* to_ping
* pinged
* post_modified
* post_modified_gmt
* post_content_filtered
* post_parent
* guid
* menu_order
* post_type
* post_mime_type
* comment_count
* filter
* url

**EXAMPLES**

    # List post
    $ wp post list --field=ID
    568
    829
    1329
    1695

    # List posts in JSON
    $ wp post list --post_type=post --posts_per_page=5 --format=json
    [{"ID":1,"post_title":"Hello world!","post_name":"hello-world","post_date":"2015-06-20 09:00:10","post_status":"publish"},{"ID":1178,"post_title":"Markup: HTML Tags and Formatting","post_name":"markup-html-tags-and-formatting","post_date":"2013-01-11 20:22:19","post_status":"draft"}]

    # List all pages
    $ wp post list --post_type=page --fields=post_title,post_status
    +-------------+-------------+
    | post_title  | post_status |
    +-------------+-------------+
    | Sample Page | publish     |
    +-------------+-------------+

    # List ids of all pages and posts
    $ wp post list --post_type=page,post --format=ids
    15 25 34 37 198

    # List given posts
    $ wp post list --post__in=1,3
    +----+--------------+-------------+---------------------+-------------+
    | ID | post_title   | post_name   | post_date           | post_status |
    +----+--------------+-------------+---------------------+-------------+
    | 3  | Lorem Ipsum  | lorem-ipsum | 2016-06-01 14:34:36 | publish     |
    | 1  | Hello world! | hello-world | 2016-06-01 14:31:12 | publish     |
    +----+--------------+-------------+---------------------+-------------+

    # List given post by a specific author
    $ wp post list --author=2
    +----+-------------------+-------------------+---------------------+-------------+
    | ID | post_title        | post_name         | post_date           | post_status |
    +----+-------------------+-------------------+---------------------+-------------+
    | 14 | New documentation | new-documentation | 2021-06-18 21:05:11 | publish     |
    +----+-------------------+-------------------+---------------------+-------------+



### wp post meta

Adds, updates, deletes, and lists post custom fields.

~~~
wp post meta
~~~

**EXAMPLES**

    # Set post meta
    $ wp post meta set 123 _wp_page_template about.php
    Success: Updated custom field '_wp_page_template'.

    # Get post meta
    $ wp post meta get 123 _wp_page_template
    about.php

    # Update post meta
    $ wp post meta update 123 _wp_page_template contact.php
    Success: Updated custom field '_wp_page_template'.

    # Delete post meta
    $ wp post meta delete 123 _wp_page_template
    Success: Deleted custom field.





### wp post meta add

Add a meta field.

~~~
wp post meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp post meta delete

Delete a meta field.

~~~
wp post meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp post meta get

Get meta field value.

~~~
wp post meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### wp post meta list

List all metadata associated with an object.

~~~
wp post meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### wp post meta patch

Update a nested value for a meta field.

~~~
wp post meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp post meta pluck

Get a nested value from a meta field.

~~~
wp post meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp post meta update

Update a meta field.

~~~
wp post meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp post term

Adds, updates, removes, and lists post terms.

~~~
wp post term
~~~

**EXAMPLES**

    # Set post terms
    $ wp post term set 123 test category
    Success: Set terms.





### wp post term add

Add a term to an object.

~~~
wp post term add <id> <taxonomy> <term>... [--by=<field>]
~~~

Append the term to the existing set of terms on the object.

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be added.

	<term>...
		The slug of the term or terms to be added.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		options:
		  - slug
		  - id
		---



### wp post term list

List all terms associated with an object.

~~~
wp post term list <id> <taxonomy>... [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

	<id>
		ID for the object.

	<taxonomy>...
		One or more taxonomies to list.

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific row fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* name
* slug
* taxonomy

These fields are optionally available:

* term_taxonomy_id
* description
* term_group
* parent
* count



### wp post term remove

Remove a term from an object.

~~~
wp post term remove <id> <taxonomy> [<term>...] [--by=<field>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the term's taxonomy.

	[<term>...]
		The name of the term or terms to be removed from the object.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		options:
		  - slug
		  - id
		---

	[--all]
		Remove all terms from the object.



### wp post term set

Set object terms.

~~~
wp post term set <id> <taxonomy> <term>... [--by=<field>]
~~~

Replaces existing terms on the object.

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be updated.

	<term>...
		The slug of the term or terms to be updated.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		options:
		  - slug
		  - id
		---



### wp post update

Updates one or more existing posts.

~~~
wp post update <id>... [--post_author=<post_author>] [--post_date=<post_date>] [--post_date_gmt=<post_date_gmt>] [--post_content=<post_content>] [--post_content_filtered=<post_content_filtered>] [--post_title=<post_title>] [--post_excerpt=<post_excerpt>] [--post_status=<post_status>] [--post_type=<post_type>] [--comment_status=<comment_status>] [--ping_status=<ping_status>] [--post_password=<post_password>] [--post_name=<post_name>] [--to_ping=<to_ping>] [--pinged=<pinged>] [--post_modified=<post_modified>] [--post_modified_gmt=<post_modified_gmt>] [--post_parent=<post_parent>] [--menu_order=<menu_order>] [--post_mime_type=<post_mime_type>] [--guid=<guid>] [--post_category=<post_category>] [--tags_input=<tags_input>] [--tax_input=<tax_input>] [--meta_input=<meta_input>] [<file>] --<field>=<value> [--defer-term-counting]
~~~

**OPTIONS**

	<id>...
		One or more IDs of posts to update.

	[--post_author=<post_author>]
		The ID of the user who added the post. Default is the current user ID.

	[--post_date=<post_date>]
		The date of the post. Default is the current time.

	[--post_date_gmt=<post_date_gmt>]
		The date of the post in the GMT timezone. Default is the value of $post_date.

	[--post_content=<post_content>]
		The post content. Default empty.

	[--post_content_filtered=<post_content_filtered>]
		The filtered post content. Default empty.

	[--post_title=<post_title>]
		The post title. Default empty.

	[--post_excerpt=<post_excerpt>]
		The post excerpt. Default empty.

	[--post_status=<post_status>]
		The post status. Default 'draft'.

	[--post_type=<post_type>]
		The post type. Default 'post'.

	[--comment_status=<comment_status>]
		Whether the post can accept comments. Accepts 'open' or 'closed'. Default is the value of 'default_comment_status' option.

	[--ping_status=<ping_status>]
		Whether the post can accept pings. Accepts 'open' or 'closed'. Default is the value of 'default_ping_status' option.

	[--post_password=<post_password>]
		The password to access the post. Default empty.

	[--post_name=<post_name>]
		The post name. Default is the sanitized post title when creating a new post.

	[--to_ping=<to_ping>]
		Space or carriage return-separated list of URLs to ping. Default empty.

	[--pinged=<pinged>]
		Space or carriage return-separated list of URLs that have been pinged. Default empty.

	[--post_modified=<post_modified>]
		The date when the post was last modified. Default is the current time.

	[--post_modified_gmt=<post_modified_gmt>]
		The date when the post was last modified in the GMT timezone. Default is the current time.

	[--post_parent=<post_parent>]
		Set this for the post it belongs to, if any. Default 0.

	[--menu_order=<menu_order>]
		The order the post should be displayed in. Default 0.

	[--post_mime_type=<post_mime_type>]
		The mime type of the post. Default empty.

	[--guid=<guid>]
		Global Unique ID for referencing the post. Default empty.

	[--post_category=<post_category>]
		Array of category names, slugs, or IDs. Defaults to value of the 'default_category' option.

	[--tags_input=<tags_input>]
		Array of tag names, slugs, or IDs. Default empty.

	[--tax_input=<tax_input>]
		Array of taxonomy terms keyed by their taxonomy name. Default empty.

	[--meta_input=<meta_input>]
		Array in JSON format of post meta values keyed by their post meta key. Default empty.

	[<file>]
		Read post content from <file>. If this value is present, the
		    `--post_content` argument will be ignored.

  Passing `-` as the filename will cause post content to
  be read from STDIN.

	--<field>=<value>
		One or more fields to update. See wp_insert_post().

	[--defer-term-counting]
		Recalculate term count in batch, for a performance boost.

**EXAMPLES**

    $ wp post update 123 --post_name=something --post_status=draft
    Success: Updated post 123.

    # Update a post with multiple meta values.
    $ wp post update 123 --meta_input='{"key1":"value1","key2":"value2"}'
    Success: Updated post 123.

    # Update multiple posts at once.
    $ wp post update 123 456 --post_author=789
    Success: Updated post 123.
    Success: Updated post 456.

    # Update all posts of a given post type at once.
    $ wp post update $(wp post list --post_type=page --format=ids) --post_author=123
    Success: Updated post 123.
    Success: Updated post 456.



### wp post-type

Retrieves details on the site's registered post types.

~~~
wp post-type
~~~

Get information on WordPress' built-in and the site's [custom post types](https://developer.wordpress.org/plugins/post-types/).

**EXAMPLES**

    # Get details about a post type
    $ wp post-type get page --fields=name,label,hierarchical --format=json
    {"name":"page","label":"Pages","hierarchical":true}

    # List post types with 'post' capability type
    $ wp post-type list --capability_type=post --fields=name,public
    +---------------+--------+
    | name          | public |
    +---------------+--------+
    | post          | 1      |
    | attachment    | 1      |
    | revision      |        |
    | nav_menu_item |        |
    +---------------+--------+



### wp post-type get

Gets details about a registered post type.

~~~
wp post-type get <post-type> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<post-type>
		Post type slug

	[--field=<field>]
		Instead of returning the whole taxonomy, returns the value of a single field.

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

**AVAILABLE FIELDS**

These fields will be displayed by default for the specified post type:

* name
* label
* description
* hierarchical
* public
* capability_type
* labels
* cap
* supports

These fields are optionally available:

* count

**EXAMPLES**

    # Get details about the 'page' post type.
    $ wp post-type get page --fields=name,label,hierarchical --format=json
    {"name":"page","label":"Pages","hierarchical":true}



### wp post-type list

Lists registered post types.

~~~
wp post-type list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--<field>=<value>]
		Filter by one or more fields (see get_post_types() first parameter for a list of available fields).

	[--field=<field>]
		Prints the value of a single field for each post type.

	[--fields=<fields>]
		Limit the output to specific post type fields.

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

**AVAILABLE FIELDS**

These fields will be displayed by default for each post type:

* name
* label
* description
* hierarchical
* public
* capability_type

These fields are optionally available:

* count

**EXAMPLES**

    # List registered post types
    $ wp post-type list --format=csv
    name,label,description,hierarchical,public,capability_type
    post,Posts,,,1,post
    page,Pages,,1,1,page
    attachment,Media,,,1,post
    revision,Revisions,,,,post
    nav_menu_item,"Navigation Menu Items",,,,post

    # List post types with 'post' capability type
    $ wp post-type list --capability_type=post --fields=name,public
    +---------------+--------+
    | name          | public |
    +---------------+--------+
    | post          | 1      |
    | attachment    | 1      |
    | revision      |        |
    | nav_menu_item |        |
    +---------------+--------+



### wp site

Creates, deletes, empties, moderates, and lists one or more sites on a multisite installation.

~~~
wp site
~~~

**EXAMPLES**

    # Create site
    $ wp site create --slug=example
    Success: Site 3 created: www.example.com/example/

    # Output a simple list of site URLs
    $ wp site list --field=url
    http://www.example.com/
    http://www.example.com/subdir/

    # Delete site
    $ wp site delete 123
    Are you sure you want to delete the 'http://www.example.com/example' site? [y/n] y
    Success: The site at 'http://www.example.com/example' was deleted.



### wp site activate

Activates one or more sites.

~~~
wp site activate <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to activate.

**EXAMPLES**

    $ wp site activate 123
    Success: Site 123 activated.



### wp site archive

Archives one or more sites.

~~~
wp site archive <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to archive.

**EXAMPLES**

    $ wp site archive 123
    Success: Site 123 archived.



### wp site create

Creates a site in a multisite installation.

~~~
wp site create --slug=<slug> [--title=<title>] [--email=<email>] [--network_id=<network-id>] [--private] [--porcelain]
~~~

**OPTIONS**

	--slug=<slug>
		Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.

	[--title=<title>]
		Title of the new site. Default: prettified slug.

	[--email=<email>]
		Email for admin user. User will be created if none exists. Assignment to super admin if not included.

	[--network_id=<network-id>]
		Network to associate new site with. Defaults to current network (typically 1).

	[--private]
		If set, the new site will be non-public (not indexed)

	[--porcelain]
		If set, only the site id will be output on success.

**EXAMPLES**

    $ wp site create --slug=example
    Success: Site 3 created: http://www.example.com/example/



### wp site deactivate

Deactivates one or more sites.

~~~
wp site deactivate <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to deactivate.

**EXAMPLES**

    $ wp site deactivate 123
    Success: Site 123 deactivated.



### wp site delete

Deletes a site in a multisite installation.

~~~
wp site delete [<site-id>] [--slug=<slug>] [--yes] [--keep-tables]
~~~

**OPTIONS**

	[<site-id>]
		The id of the site to delete. If not provided, you must set the --slug parameter.

	[--slug=<slug>]
		Path of the blog to be deleted. Subdomain on subdomain installs, directory on subdirectory installs.

	[--yes]
		Answer yes to the confirmation message.

	[--keep-tables]
		Delete the blog from the list, but don't drop it's tables.

**EXAMPLES**

    $ wp site delete 123
    Are you sure you want to delete the http://www.example.com/example site? [y/n] y
    Success: The site at 'http://www.example.com/example' was deleted.



### wp site empty

Empties a site of its content (posts, comments, terms, and meta).

~~~
wp site empty [--uploads] [--yes]
~~~

Truncates posts, comments, and terms tables to empty a site of its
content. Doesn't affect site configuration (options) or users.

If running a persistent object cache, make sure to flush the cache
after emptying the site, as the cache values will be invalid otherwise.

To also empty custom database tables, you'll need to hook into command
execution:

```
WP_CLI::add_hook( 'after_invoke:site empty', function(){
    global $wpdb;
    foreach( array( 'p2p', 'p2pmeta' ) as $table ) {
        $table = $wpdb->$table;
        $wpdb->query( "TRUNCATE $table" );
    }
});
```

**OPTIONS**

	[--uploads]
		Also delete *all* files in the site's uploads directory.

	[--yes]
		Proceed to empty the site without a confirmation prompt.

**EXAMPLES**

    $ wp site empty
    Are you sure you want to empty the site at http://www.example.com of all posts, links, comments, and terms? [y/n] y
    Success: The site at 'http://www.example.com' was emptied.



### wp site list

Lists all sites in a multisite installation.

~~~
wp site list [--network=<id>] [--<field>=<value>] [--site__in=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--network=<id>]
		The network to which the sites belong.

	[--<field>=<value>]
		Filter by one or more fields (see "Available Fields" section). However,
		'url' isn't an available filter, because it's created from domain + path.

	[--site__in=<value>]
		Only list the sites with these blog_id values (comma-separated).

	[--field=<field>]
		Prints the value of a single field for each site.

	[--fields=<fields>]
		Comma-separated list of fields to show.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - count
		  - ids
		  - json
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each site:

* blog_id
* url
* last_updated
* registered

These fields are optionally available:

* site_id
* domain
* path
* public
* archived
* mature
* spam
* deleted
* lang_id

**EXAMPLES**

    # Output a simple list of site URLs
    $ wp site list --field=url
    http://www.example.com/
    http://www.example.com/subdir/



### wp site mature

Sets one or more sites as mature.

~~~
wp site mature <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to set as mature.

**EXAMPLES**

    $ wp site mature 123
    Success: Site 123 marked as mature.



### wp site option

Adds, updates, deletes, and lists site options in a multisite installation.

~~~
wp site option
~~~

**EXAMPLES**

    # Get site registration
    $ wp site option get registration
    none

    # Add site option
    $ wp site option add my_option foobar
    Success: Added 'my_option' site option.

    # Update site option
    $ wp site option update my_option '{"foo": "bar"}' --format=json
    Success: Updated 'my_option' site option.

    # Delete site option
    $ wp site option delete my_option
    Success: Deleted 'my_option' site option.





### wp site private

Sets one or more sites as private.

~~~
wp site private <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to set as private.

**EXAMPLES**

    $ wp site private 123
    Success: Site 123 marked as private.



### wp site public

Sets one or more sites as public.

~~~
wp site public <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to set as public.

**EXAMPLES**

    $ wp site public 123
    Success: Site 123 marked as public.



### wp site spam

Marks one or more sites as spam.

~~~
wp site spam <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to be marked as spam.

**EXAMPLES**

    $ wp site spam 123
    Success: Site 123 marked as spam.



### wp site unarchive

Unarchives one or more sites.

~~~
wp site unarchive <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to unarchive.

**EXAMPLES**

    $ wp site unarchive 123
    Success: Site 123 unarchived.



### wp site unmature

Sets one or more sites as immature.

~~~
wp site unmature <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to set as unmature.

**EXAMPLES**

    $ wp site general 123
    Success: Site 123 marked as unmature.



### wp site unspam

Removes one or more sites from spam.

~~~
wp site unspam <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of sites to remove from spam.

**EXAMPLES**

    $ wp site unspam 123
    Success: Site 123 removed from spam.



### wp taxonomy

Retrieves information about registered taxonomies.

~~~
wp taxonomy
~~~

See references for [built-in taxonomies](https://developer.wordpress.org/themes/basics/categories-tags-custom-taxonomies/) and [custom taxonomies](https://developer.wordpress.org/plugins/taxonomies/working-with-custom-taxonomies/).

**EXAMPLES**

    # List all taxonomies with 'post' object type.
    $ wp taxonomy list --object_type=post --fields=name,public
    +-------------+--------+
    | name        | public |
    +-------------+--------+
    | category    | 1      |
    | post_tag    | 1      |
    | post_format | 1      |
    +-------------+--------+

    # Get capabilities of 'post_tag' taxonomy.
    $ wp taxonomy get post_tag --field=cap
    {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}



### wp taxonomy get

Gets details about a registered taxonomy.

~~~
wp taxonomy get <taxonomy> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy slug.

	[--field=<field>]
		Instead of returning the whole taxonomy, returns the value of a single field.

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

**AVAILABLE FIELDS**

These fields will be displayed by default for the specified taxonomy:

* name
* label
* description
* object_type
* show_tagcloud
* hierarchical
* public
* labels
* cap

These fields are optionally available:

* count

**EXAMPLES**

    # Get details of `category` taxonomy.
    $ wp taxonomy get category --fields=name,label,object_type
    +-------------+------------+
    | Field       | Value      |
    +-------------+------------+
    | name        | category   |
    | label       | Categories |
    | object_type | ["post"]   |
    +-------------+------------+

    # Get capabilities of 'post_tag' taxonomy.
    $ wp taxonomy get post_tag --field=cap
    {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}



### wp taxonomy list

Lists registered taxonomies.

~~~
wp taxonomy list [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--<field>=<value>]
		Filter by one or more fields (see get_taxonomies() first parameter for a list of available fields).

	[--field=<field>]
		Prints the value of a single field for each taxonomy.

	[--fields=<fields>]
		Limit the output to specific taxonomy fields.

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

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* name
* label
* description
* object_type
* show_tagcloud
* hierarchical
* public

These fields are optionally available:

* count

**EXAMPLES**

    # List all taxonomies.
    $ wp taxonomy list --format=csv
    name,label,description,object_type,show_tagcloud,hierarchical,public
    category,Categories,,post,1,1,1
    post_tag,Tags,,post,1,,1
    nav_menu,"Navigation Menus",,nav_menu_item,,,
    link_category,"Link Categories",,link,1,,
    post_format,Format,,post,,,1

    # List all taxonomies with 'post' object type.
    $ wp taxonomy list --object_type=post --fields=name,public
    +-------------+--------+
    | name        | public |
    +-------------+--------+
    | category    | 1      |
    | post_tag    | 1      |
    | post_format | 1      |
    +-------------+--------+



### wp term

Manages taxonomy terms and term meta, with create, delete, and list commands.

~~~
wp term
~~~

See reference for [taxonomies and their terms](https://codex.wordpress.org/Taxonomies).

**EXAMPLES**

    # Create a new term.
    $ wp term create category Apple --description="A type of fruit"
    Success: Created category 199.

    # Get details about a term.
    $ wp term get category 199 --format=json --fields=term_id,name,slug,count
    {"term_id":199,"name":"Apple","slug":"apple","count":1}

    # Update an existing term.
    $ wp term update category 15 --name=Apple
    Success: Term updated.

    # Get the term's URL.
    $ wp term list post_tag --include=123 --field=url
    http://example.com/tag/tips-and-tricks

    # Delete post category
    $ wp term delete category 15
    Success: Deleted category 15.

    # Recount posts assigned to each categories and tags
    $ wp term recount category post_tag
    Success: Updated category term count
    Success: Updated post_tag term count



### wp term create

Creates a new term.

~~~
wp term create <taxonomy> <term> [--slug=<slug>] [--description=<description>] [--parent=<term-id>] [--porcelain]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy for the new term.

	<term>
		A name for the new term.

	[--slug=<slug>]
		A unique slug for the new term. Defaults to sanitized version of name.

	[--description=<description>]
		A description for the new term.

	[--parent=<term-id>]
		A parent for the new term.

	[--porcelain]
		Output just the new term id.

**EXAMPLES**

    # Create a new category "Apple" with a description.
    $ wp term create category Apple --description="A type of fruit"
    Success: Created category 199.



### wp term delete

Deletes an existing term.

~~~
wp term delete <taxonomy> <term>... [--by=<field>]
~~~

Errors if the term doesn't exist, or there was a problem in deleting it.

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to delete.

	<term>...
		One or more IDs or slugs of terms to delete.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

**EXAMPLES**

    # Delete post category by id
    $ wp term delete category 15
    Deleted category 15.
    Success: Deleted 1 of 1 terms.

    # Delete post category by slug
    $ wp term delete category apple --by=slug
    Deleted category 15.
    Success: Deleted 1 of 1 terms.

    # Delete all post tags
    $ wp term list post_tag --field=term_id | xargs wp term delete post_tag
    Deleted post_tag 159.
    Deleted post_tag 160.
    Deleted post_tag 161.
    Success: Deleted 3 of 3 terms.



### wp term generate

Generates some terms.

~~~
wp term generate <taxonomy> [--count=<number>] [--max_depth=<number>] [--format=<format>]
~~~

Creates a specified number of new terms with dummy data.

**OPTIONS**

	<taxonomy>
		The taxonomy for the generated terms.

	[--count=<number>]
		How many terms to generate?
		---
		default: 100
		---

	[--max_depth=<number>]
		Generate child terms down to a certain depth.
		---
		default: 1
		---

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Generate post categories.
    $ wp term generate category --count=10
    Generating terms  100% [=========] 0:02 / 0:02

    # Add meta to every generated term.
    $ wp term generate category --format=ids --count=3 | xargs -d ' ' -I % wp term meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### wp term get

Gets details about a term.

~~~
wp term get <taxonomy> <term> [--by=<field>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to get

	<term>
		ID or slug of the term to get

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

	[--field=<field>]
		Instead of returning the whole term, returns the value of a single field.

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

    # Get details about a category with id 199.
    $ wp term get category 199 --format=json
    {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}

    # Get details about a category with slug apple.
    $ wp term get category apple --by=slug --format=json
    {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}



### wp term list

Lists terms in a taxonomy.

~~~
wp term list <taxonomy>... [--<field>=<value>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<taxonomy>...
		List terms of one or more taxonomies

	[--<field>=<value>]
		Filter by one or more fields (see get_terms() $args parameter for a list of fields).

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* term_taxonomy_id
* name
* slug
* description
* parent
* count

These fields are optionally available:

* url

**EXAMPLES**

    # List post categories
    $ wp term list category --format=csv
    term_id,term_taxonomy_id,name,slug,description,parent,count
    2,2,aciform,aciform,,0,1
    3,3,antiquarianism,antiquarianism,,0,1
    4,4,arrangement,arrangement,,0,1
    5,5,asmodeus,asmodeus,,0,1

    # List post tags
    $ wp term list post_tag --fields=name,slug
    +-----------+-------------+
    | name      | slug        |
    +-----------+-------------+
    | 8BIT      | 8bit        |
    | alignment | alignment-2 |
    | Articles  | articles    |
    | aside     | aside       |
    +-----------+-------------+



### wp term meta

Adds, updates, deletes, and lists term custom fields.

~~~
wp term meta
~~~

**EXAMPLES**

    # Set term meta
    $ wp term meta set 123 bio "Mary is a WordPress developer."
    Success: Updated custom field 'bio'.

    # Get term meta
    $ wp term meta get 123 bio
    Mary is a WordPress developer.

    # Update term meta
    $ wp term meta update 123 bio "Mary is an awesome WordPress developer."
    Success: Updated custom field 'bio'.

    # Delete term meta
    $ wp term meta delete 123 bio
    Success: Deleted custom field.





### wp term meta add

Add a meta field.

~~~
wp term meta add <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to create.

	[<value>]
		The value of the meta field. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp term meta delete

Delete a meta field.

~~~
wp term meta delete <id> [<key>] [<value>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	[<key>]
		The name of the meta field to delete.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

	[--all]
		Delete all meta for the object.



### wp term meta get

Get meta field value.

~~~
wp term meta get <id> <key> [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	[--format=<format>]
		Get value in a particular format.
		---
		default: var_export
		options:
		  - var_export
		  - json
		  - yaml
		---



### wp term meta list

List all metadata associated with an object.

~~~
wp term meta list <id> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<id>
		ID for the object.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		---

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.



### wp term meta patch

Update a nested value for a meta field.

~~~
wp term meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp term meta pluck

Get a nested value from a meta field.

~~~
wp term meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp term meta update

Update a meta field.

~~~
wp term meta update <id> <key> [<value>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp term recount

Recalculates number of posts assigned to each term.

~~~
wp term recount <taxonomy>...
~~~

In instances where manual updates are made to the terms assigned to
posts in the database, the number of posts associated with a term
can become out-of-sync with the actual number of posts.

This command runs wp_update_term_count() on the taxonomy's terms
to bring the count back to the correct value.

**OPTIONS**

	<taxonomy>...
		One or more taxonomies to recalculate.

**EXAMPLES**

    # Recount posts assigned to each categories and tags
    $ wp term recount category post_tag
    Success: Updated category term count.
    Success: Updated post_tag term count.

    # Recount all listed taxonomies
    $ wp taxonomy list --field=name | xargs wp term recount
    Success: Updated category term count.
    Success: Updated post_tag term count.
    Success: Updated nav_menu term count.
    Success: Updated link_category term count.
    Success: Updated post_format term count.



### wp term update

Updates an existing term.

~~~
wp term update <taxonomy> <term> [--by=<field>] [--name=<name>] [--slug=<slug>] [--description=<description>] [--parent=<term-id>]
~~~

**OPTIONS**

	<taxonomy>
		Taxonomy of the term to update.

	<term>
		ID or slug for the term to update.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		default: id
		options:
		  - slug
		  - id
		---

	[--name=<name>]
		A new name for the term.

	[--slug=<slug>]
		A new slug for the term.

	[--description=<description>]
		A new description for the term.

	[--parent=<term-id>]
		A new parent for the term.

**EXAMPLES**

    # Change category with id 15 to use the name "Apple"
    $ wp term update category 15 --name=Apple
    Success: Term updated.

    # Change category with slug apple to use the name "Apple"
    $ wp term update category apple --by=slug --name=Apple
    Success: Term updated.



### wp user

Manages users, along with their roles, capabilities, and meta.

~~~
wp user
~~~

See references for [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities) and [WP User class](https://codex.wordpress.org/Class_Reference/WP_User).

**EXAMPLES**

    # List user IDs
    $ wp user list --field=ID
    1

    # Create a new user.
    $ wp user create bob bob@example.com --role=author
    Success: Created user 3.
    Password: k9**&I4vNH(&

    # Update an existing user.
    $ wp user update 123 --display_name=Mary --user_pass=marypass
    Success: Updated user 123.

    # Delete user 123 and reassign posts to user 567
    $ wp user delete 123 --reassign=567
    Success: Removed user 123 from http://example.com



### wp user add-cap

Adds a capability to a user.

~~~
wp user add-cap <user> <cap>
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	<cap>
		The capability to add.

**EXAMPLES**

    # Add a capability for a user
    $ wp user add-cap john create_premium_item
    Success: Added 'create_premium_item' capability for john (16).

    # Add a capability for a user
    $ wp user add-cap 15 edit_product
    Success: Added 'edit_product' capability for johndoe (15).



### wp user add-role

Adds a role for a user.

~~~
wp user add-role <user> <role>
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	<role>
		Add the specified role to the user.

**EXAMPLES**

    $ wp user add-role 12 author
    Success: Added 'author' role for johndoe (12).



### wp user create

Creates a new user.

~~~
wp user create <user-login> <user-email> [--role=<role>] [--user_pass=<password>] [--user_registered=<yyyy-mm-dd-hh-ii-ss>] [--display_name=<name>] [--user_nicename=<nice_name>] [--user_url=<url>] [--nickname=<nickname>] [--first_name=<first_name>] [--last_name=<last_name>] [--description=<description>] [--rich_editing=<rich_editing>] [--send-email] [--porcelain]
~~~

**OPTIONS**

	<user-login>
		The login of the user to create.

	<user-email>
		The email address of the user to create.

	[--role=<role>]
		The role of the user to create. Default: default role. Possible values
		include 'administrator', 'editor', 'author', 'contributor', 'subscriber'.

	[--user_pass=<password>]
		The user password. Default: randomly generated.

	[--user_registered=<yyyy-mm-dd-hh-ii-ss>]
		The date the user registered. Default: current date.

	[--display_name=<name>]
		The display name.

	[--user_nicename=<nice_name>]
		A string that contains a URL-friendly name for the user. The default is the user's username.

	[--user_url=<url>]
		A string containing the user's URL for the user's web site.

	[--nickname=<nickname>]
		The user's nickname, defaults to the user's username.

	[--first_name=<first_name>]
		The user's first name.

	[--last_name=<last_name>]
		The user's last name.

	[--description=<description>]
		A string containing content about the user.

	[--rich_editing=<rich_editing>]
		A string for whether to enable the rich editor or not. False if not empty.

	[--send-email]
		Send an email to the user with their new account details.

	[--porcelain]
		Output just the new user id.

**EXAMPLES**

    # Create user
    $ wp user create bob bob@example.com --role=author
    Success: Created user 3.
    Password: k9**&I4vNH(&

    # Create user without showing password upon success
    $ wp user create ann ann@example.com --porcelain
    4



### wp user delete

Deletes one or more users from the current site.

~~~
wp user delete <user>... [--network] [--reassign=<user-id>] [--yes]
~~~

On multisite, `wp user delete` only removes the user from the current
site. Include `--network` to also remove the user from the database, but
make sure to reassign their posts prior to deleting the user.

**OPTIONS**

	<user>...
		The user login, user email, or user ID of the user(s) to delete.

	[--network]
		On multisite, delete the user from the entire network.

	[--reassign=<user-id>]
		User ID to reassign the posts to.

	[--yes]
		Answer yes to any confirmation prompts.

**EXAMPLES**

    # Delete user 123 and reassign posts to user 567
    $ wp user delete 123 --reassign=567
    Success: Removed user 123 from http://example.com

    # Delete all contributors and reassign their posts to user 2
    $ wp user delete $(wp user list --role=contributor --field=ID) --reassign=2
    Success: Removed user 813 from http://example.com
    Success: Removed user 578 from http://example.com



### wp user generate

Generates some users.

~~~
wp user generate [--count=<number>] [--role=<role>] [--format=<format>]
~~~

Creates a specified number of new users with dummy data.

**OPTIONS**

	[--count=<number>]
		How many users to generate?
		---
		default: 100
		---

	[--role=<role>]
		The role of the generated users. Default: default role from WP

	[--format=<format>]
		Render output in a particular format.
		---
		default: progress
		options:
		  - progress
		  - ids
		---

**EXAMPLES**

    # Add meta to every generated users.
    $ wp user generate --format=ids --count=3 | xargs -d ' ' -I % wp user meta add % foo bar
    Success: Added custom field.
    Success: Added custom field.
    Success: Added custom field.



### wp user get

Gets details about a user.

~~~
wp user get <user> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[--field=<field>]
		Instead of returning the whole user, returns the value of a single field.

	[--fields=<fields>]
		Get a specific subset of the user's fields.

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

    # Get user
    $ wp user get 12 --field=login
    supervisor

    # Get user and export to JSON file
    $ wp user get bob --format=json > bob.json



### wp user import-csv

Imports users from a CSV file.

~~~
wp user import-csv <file> [--send-email] [--skip-update]
~~~

If the user already exists (matching the email address or login), then
the user is updated unless the `--skip-update` flag is used.

**OPTIONS**

	<file>
		The local or remote CSV file of users to import. If '-', then reads from STDIN.

	[--send-email]
		Send an email to new users with their account details.

	[--skip-update]
		Don't update users that already exist.

**EXAMPLES**

    # Import users from local CSV file
    $ wp user import-csv /path/to/users.csv
    Success: bobjones created
    Success: newuser1 created
    Success: existinguser created

    # Import users from remote CSV file
    $ wp user import-csv http://example.com/users.csv

    Sample users.csv file:

    user_login,user_email,display_name,role
    bobjones,bobjones@example.com,Bob Jones,contributor
    newuser1,newuser1@example.com,New User,author
    existinguser,existinguser@example.com,Existing User,administrator



### wp user list

Lists users.

~~~
wp user list [--role=<role>] [--<field>=<value>] [--network] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

Display WordPress users based on all arguments supported by
[WP_User_Query()](https://developer.wordpress.org/reference/classes/wp_user_query/prepare_query/).

**OPTIONS**

	[--role=<role>]
		Only display users with a certain role.

	[--<field>=<value>]
		Control output by one or more arguments of WP_User_Query().

	[--network]
		List all users in the network for multisite.

	[--field=<field>]
		Prints the value of a single field for each user.

	[--fields=<fields>]
		Limit the output to specific object fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - ids
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each user:

* ID
* user_login
* display_name
* user_email
* user_registered
* roles

These fields are optionally available:

* user_pass
* user_nicename
* user_url
* user_activation_key
* user_status
* spam
* deleted
* caps
* cap_key
* allcaps
* filter
* url

**EXAMPLES**

    # List user IDs
    $ wp user list --field=ID
    1

    # List users with administrator role
    $ wp user list --role=administrator --format=csv
    ID,user_login,display_name,user_email,user_registered,roles
    1,supervisor,supervisor,supervisor@gmail.com,"2016-06-03 04:37:00",administrator

    # List users with only given fields
    $ wp user list --fields=display_name,user_email --format=json
    [{"display_name":"supervisor","user_email":"supervisor@gmail.com"}]

    # List users ordered by the 'last_activity' meta value.
    $ wp user list --meta_key=last_activity --orderby=meta_value_num



### wp user list-caps

Lists all capabilities for a user.

~~~
wp user list-caps <user> [--format=<format>]
~~~

**OPTIONS**

	<user>
		User ID, user email, or login.

	[--format=<format>]
		Render output in a particular format.
		---
		default: list
		options:
		  - list
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**EXAMPLES**

    $ wp user list-caps 21
    edit_product
    create_premium_item



### wp user meta

Adds, updates, deletes, and lists user custom fields.

~~~
wp user meta
~~~

**EXAMPLES**

    # Add user meta
    $ wp user meta add 123 bio "Mary is an WordPress developer."
    Success: Added custom field.

    # List user meta
    $ wp user meta list 123 --keys=nickname,description,wp_capabilities
    +---------+-----------------+--------------------------------+
    | user_id | meta_key        | meta_value                     |
    +---------+-----------------+--------------------------------+
    | 123     | nickname        | supervisor                     |
    | 123     | description     | Mary is a WordPress developer. |
    | 123     | wp_capabilities | {"administrator":true}         |
    +---------+-----------------+--------------------------------+

    # Update user meta
    $ wp user meta update 123 bio "Mary is an awesome WordPress developer."
    Success: Updated custom field 'bio'.

    # Delete user meta
    $ wp user meta delete 123 bio
    Success: Deleted custom field.





### wp user meta add

Adds a meta field.

~~~
wp user meta add <user> <key> <value> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to add metadata for.

	<key>
		The metadata key.

	<value>
		The new metadata value.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Add user meta
    $ wp user meta add 123 bio "Mary is an WordPress developer."
    Success: Added custom field.



### wp user meta delete

Deletes a meta field.

~~~
wp user meta delete <user> <key> [<value>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to delete metadata from.

	<key>
		The metadata key.

	[<value>]
		The value to delete. If omitted, all rows with key will deleted.

**EXAMPLES**

    # Delete user meta
    $ wp user meta delete 123 bio
    Success: Deleted custom field.



### wp user meta get

Gets meta field value.

~~~
wp user meta get <user> <key> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get metadata for.

	<key>
		The metadata key.

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

    # Get user meta
    $ wp user meta get 123 bio
    Mary is an WordPress developer.



### wp user meta list

Lists all metadata associated with a user.

~~~
wp user meta list <user> [--keys=<keys>] [--fields=<fields>] [--format=<format>] [--orderby=<fields>] [--order=<order>] [--unserialize]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to get metadata for.

	[--keys=<keys>]
		Limit output to metadata of specific keys.

	[--fields=<fields>]
		Limit the output to specific row fields. Defaults to id,meta_key,meta_value.

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

	[--orderby=<fields>]
		Set orderby which field.
		---
		default: id
		options:
		 - id
		 - meta_key
		 - meta_value
		---

	[--order=<order>]
		Set ascending or descending order.
		---
		default: asc
		options:
		 - asc
		 - desc
		---

	[--unserialize]
		Unserialize meta_value output.

**EXAMPLES**

    # List user meta
    $ wp user meta list 123 --keys=nickname,description,wp_capabilities
    +---------+-----------------+--------------------------------+
    | user_id | meta_key        | meta_value                     |
    +---------+-----------------+--------------------------------+
    | 123     | nickname        | supervisor                     |
    | 123     | description     | Mary is a WordPress developer. |
    | 123     | wp_capabilities | {"administrator":true}         |
    +---------+-----------------+--------------------------------+



### wp user meta patch

Update a nested value for a meta field.

~~~
wp user meta patch <action> <id> <key> <key-path>... [<value>] [--format=<format>]
~~~

**OPTIONS**

	<action>
		Patch action to perform.
		---
		options:
		  - insert
		  - update
		  - delete
		---

	<id>
		The ID of the object.

	<key>
		The name of the meta field to update.

	<key-path>...
		The name(s) of the keys within the value to locate the value to patch.

	[<value>]
		The new value. If omitted, the value is read from STDIN.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---



### wp user meta pluck

Get a nested value from a meta field.

~~~
wp user meta pluck <id> <key> <key-path>... [--format=<format>]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<key>
		The name of the meta field to get.

	<key-path>...
		The name(s) of the keys within the value to locate the value to pluck.

	[--format=<format>]
		The output format of the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		  - yaml



### wp user meta update

Updates a meta field.

~~~
wp user meta update <user> <key> <value> [--format=<format>]
~~~

**OPTIONS**

	<user>
		The user login, user email, or user ID of the user to update metadata for.

	<key>
		The metadata key.

	<value>
		The new metadata value.

	[--format=<format>]
		The serialization format for the value.
		---
		default: plaintext
		options:
		  - plaintext
		  - json
		---

**EXAMPLES**

    # Update user meta
    $ wp user meta update 123 bio "Mary is an awesome WordPress developer."
    Success: Updated custom field 'bio'.



### wp user remove-cap

Removes a user's capability.

~~~
wp user remove-cap <user> <cap>
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	<cap>
		The capability to be removed.

**EXAMPLES**

    $ wp user remove-cap 11 publish_newsletters
    Success: Removed 'publish_newsletters' cap for supervisor (11).

    $ wp user remove-cap 11 publish_posts
    Error: The 'publish_posts' cap for supervisor (11) is inherited from a role.

    $ wp user remove-cap 11 nonexistent_cap
    Error: No such 'nonexistent_cap' cap for supervisor (11).



### wp user remove-role

Removes a user's role.

~~~
wp user remove-role <user> [<role>]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<role>]
		A specific role to remove.

**EXAMPLES**

    $ wp user remove-role 12 author
    Success: Removed 'author' role for johndoe (12).



### wp user reset-password

Resets the password for one or more users.

~~~
wp user reset-password <user>... [--skip-email]
~~~

**OPTIONS**

	<user>...
		one or more user logins or IDs.

	[--skip-email]
		Don't send an email notification to the affected user(s).

**EXAMPLES**

    # Reset the password for two users and send them the change email.
    $ wp user reset-password admin editor
    Reset password for admin.
    Reset password for editor.
    Success: Passwords reset for 2 users.



### wp user session

Destroys and lists a user's sessions.

~~~
wp user session
~~~

**EXAMPLES**

    # List a user's sessions.
    $ wp user session list admin@example.com --format=csv
    login_time,expiration_time,ip,ua
    "2016-01-01 12:34:56","2016-02-01 12:34:56",127.0.0.1,"Mozilla/5.0..."

    # Destroy the most recent session of the given user.
    $ wp user session destroy admin
    Success: Destroyed session. 3 sessions remaining.





### wp user session destroy

Destroy a session for the given user.

~~~
wp user session destroy <user> [<token>] [--all]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<token>]
		The token of the session to destroy. Defaults to the most recently created session.

	[--all]
		Destroy all of the user's sessions.

**EXAMPLES**

    # Destroy the most recent session of the given user.
    $ wp user session destroy admin
    Success: Destroyed session. 3 sessions remaining.

    # Destroy a specific session of the given user.
    $ wp user session destroy admin e073ad8540a9c2...
    Success: Destroyed session. 2 sessions remaining.

    # Destroy all the sessions of the given user.
    $ wp user session destroy admin --all
    Success: Destroyed all sessions.

    # Destroy all sessions for all users.
    $ wp user list --field=ID | xargs -n 1 wp user session destroy --all
    Success: Destroyed all sessions.
    Success: Destroyed all sessions.



### wp user session list

List sessions for the given user.

~~~
wp user session list <user> [--fields=<fields>] [--format=<format>]
~~~

Note: The `token` field does not return the actual token, but a hash of
it. The real token is not persisted and can only be found in the
corresponding cookies on the client side.

**OPTIONS**

	<user>
		User ID, user email, or user login.

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
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each session:

* token
* login_time
* expiration_time
* ip
* ua

These fields are optionally available:

* expiration
* login

**EXAMPLES**

    # List a user's sessions.
    $ wp user session list admin@example.com --format=csv
    login_time,expiration_time,ip,ua
    "2016-01-01 12:34:56","2016-02-01 12:34:56",127.0.0.1,"Mozilla/5.0..."



### wp user set-role

Sets the user role.

~~~
wp user set-role <user> [<role>]
~~~

**OPTIONS**

	<user>
		User ID, user email, or user login.

	[<role>]
		Make the user have the specified role. If not passed, the default role is
		used.

**EXAMPLES**

    $ wp user set-role 12 author
    Success: Added johndoe (12) to http://example.com as author.



### wp user spam

Marks one or more users as spam.

~~~
wp user spam <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of users to mark as spam.

**EXAMPLES**

    $ wp user spam 123
    User 123 marked as spam.
    Success: Spamed 1 of 1 users.



### wp user term

Adds, updates, removes, and lists user terms.

~~~
wp user term
~~~

**EXAMPLES**

    # Set user terms
    $ wp user term set 123 test category
    Success: Set terms.





### wp user term add

Add a term to an object.

~~~
wp user term add <id> <taxonomy> <term>... [--by=<field>]
~~~

Append the term to the existing set of terms on the object.

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be added.

	<term>...
		The slug of the term or terms to be added.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		options:
		  - slug
		  - id
		---



### wp user term list

List all terms associated with an object.

~~~
wp user term list <id> <taxonomy>... [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

	<id>
		ID for the object.

	<taxonomy>...
		One or more taxonomies to list.

	[--field=<field>]
		Prints the value of a single field for each term.

	[--fields=<fields>]
		Limit the output to specific row fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		  - count
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each term:

* term_id
* name
* slug
* taxonomy

These fields are optionally available:

* term_taxonomy_id
* description
* term_group
* parent
* count



### wp user term remove

Remove a term from an object.

~~~
wp user term remove <id> <taxonomy> [<term>...] [--by=<field>] [--all]
~~~

**OPTIONS**

	<id>
		The ID of the object.

	<taxonomy>
		The name of the term's taxonomy.

	[<term>...]
		The name of the term or terms to be removed from the object.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		options:
		  - slug
		  - id
		---

	[--all]
		Remove all terms from the object.



### wp user term set

Set object terms.

~~~
wp user term set <id> <taxonomy> <term>... [--by=<field>]
~~~

Replaces existing terms on the object.

	<id>
		The ID of the object.

	<taxonomy>
		The name of the taxonomy type to be updated.

	<term>...
		The slug of the term or terms to be updated.

	[--by=<field>]
		Explicitly handle the term value as a slug or id.
		---
		options:
		  - slug
		  - id
		---



### wp user unspam

Removes one or more users from spam.

~~~
wp user unspam <id>...
~~~

**OPTIONS**

	<id>...
		One or more IDs of users to remove from spam.

**EXAMPLES**

    $ wp user unspam 123
    User 123 removed from spam.
    Success: Unspamed 1 of 1 users.



### wp user update

Updates an existing user.

~~~
wp user update <user>... [--user_pass=<password>] [--user_nicename=<nice_name>] [--user_url=<url>] [--user_email=<email>] [--display_name=<display_name>] [--nickname=<nickname>] [--first_name=<first_name>] [--last_name=<last_name>] [--description=<description>] [--rich_editing=<rich_editing>] [--user_registered=<yyyy-mm-dd-hh-ii-ss>] [--role=<role>] --<field>=<value> [--skip-email]
~~~

**OPTIONS**

	<user>...
		The user login, user email or user ID of the user(s) to update.

	[--user_pass=<password>]
		A string that contains the plain text password for the user.

	[--user_nicename=<nice_name>]
		A string that contains a URL-friendly name for the user. The default is the user's username.

	[--user_url=<url>]
		A string containing the user's URL for the user's web site.

	[--user_email=<email>]
		A string containing the user's email address.

	[--display_name=<display_name>]
		A string that will be shown on the site. Defaults to user's username.

	[--nickname=<nickname>]
		The user's nickname, defaults to the user's username.

	[--first_name=<first_name>]
		The user's first name.

	[--last_name=<last_name>]
		The user's last name.

	[--description=<description>]
		A string containing content about the user.

	[--rich_editing=<rich_editing>]
		A string for whether to enable the rich editor or not. False if not empty.

	[--user_registered=<yyyy-mm-dd-hh-ii-ss>]
		The date the user registered.

	[--role=<role>]
		A string used to set the user's role.

	--<field>=<value>
		One or more fields to update. For accepted fields, see wp_update_user().

	[--skip-email]
		Don't send an email notification to the user.

**EXAMPLES**

    # Update user
    $ wp user update 123 --display_name=Mary --user_pass=marypass
    Success: Updated user 123.

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/entity-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/entity-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/entity-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/entity-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
