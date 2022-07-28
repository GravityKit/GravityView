wp-cli/media-command
====================

Imports files as attachments, regenerates thumbnails, or lists registered image sizes.

[![Testing](https://github.com/wp-cli/media-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/media-command/actions/workflows/testing.yml) [![Build Status](https://travis-ci.org/wp-cli/media-command.svg?branch=master)](https://travis-ci.org/wp-cli/media-command)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp media

Imports files as attachments, regenerates thumbnails, or lists registered image sizes.

~~~
wp media
~~~

**EXAMPLES**

    # Re-generate all thumbnails, without confirmation.
    $ wp media regenerate --yes
    Found 3 images to regenerate.
    1/3 Regenerated thumbnails for "Sydney Harbor Bridge" (ID 760).
    2/3 Regenerated thumbnails for "Boardwalk" (ID 757).
    3/3 Regenerated thumbnails for "Sunburst Over River" (ID 756).
    Success: Regenerated 3 of 3 images.

    # Import a local image and set it to be the featured image for a post.
    $ wp media import ~/Downloads/image.png --post_id=123 --title="A downloaded picture" --featured_image
    Success: Imported file '/home/person/Downloads/image.png' as attachment ID 1753 and attached to post 123 as featured image.

    # List all registered image sizes
    $ wp media image-size
    +---------------------------+-------+--------+-------+
    | name                      | width | height | crop  |
    +---------------------------+-------+--------+-------+
    | full                      |       |        | N/A   |
    | twentyfourteen-full-width | 1038  | 576    | hard  |
    | large                     | 1024  | 1024   | soft  |
    | medium_large              | 768   | 0      | soft  |
    | medium                    | 300   | 300    | soft  |
    | thumbnail                 | 150   | 150    | hard  |
    +---------------------------+-------+--------+-------+



### wp media import

Creates attachments from local files or URLs.

~~~
wp media import <file>... [--post_id=<post_id>] [--title=<title>] [--caption=<caption>] [--alt=<alt_text>] [--desc=<description>] [--skip-copy] [--preserve-filetime] [--featured_image] [--porcelain]
~~~

**OPTIONS**

	<file>...
		Path to file or files to be imported. Supports the glob(3) capabilities of the current shell.
		    If file is recognized as a URL (for example, with a scheme of http or ftp), the file will be
		    downloaded to a temp file before being sideloaded.

	[--post_id=<post_id>]
		ID of the post to attach the imported files to.

	[--title=<title>]
		Attachment title (post title field).

	[--caption=<caption>]
		Caption for attachment (post excerpt field).

	[--alt=<alt_text>]
		Alt text for image (saved as post meta).

	[--desc=<description>]
		"Description" field (post content) of attachment post.

	[--skip-copy]
		If set, media files (local only) are imported to the library but not moved on disk.
		File names will not be run through wp_unique_filename() with this set.

	[--preserve-filetime]
		Use the file modified time as the post published & modified dates.
		Remote files will always use the current time.

	[--featured_image]
		If set, set the imported image as the Featured Image of the post its attached to.

	[--porcelain]
		Output just the new attachment ID.

**EXAMPLES**

    # Import all jpgs in the current user's "Pictures" directory, not attached to any post.
    $ wp media import ~/Pictures/**\/*.jpg
    Imported file '/home/person/Pictures/landscape-photo.jpg' as attachment ID 1751.
    Imported file '/home/person/Pictures/fashion-icon.jpg' as attachment ID 1752.
    Success: Imported 2 of 2 items.

    # Import a local image and set it to be the post thumbnail for a post.
    $ wp media import ~/Downloads/image.png --post_id=123 --title="A downloaded picture" --featured_image
    Imported file '/home/person/Downloads/image.png' as attachment ID 1753 and attached to post 123 as featured image.
    Success: Imported 1 of 1 images.

    # Import a local image, but set it as the featured image for all posts.
    # 1. Import the image and get its attachment ID.
    # 2. Assign the attachment ID as the featured image for all posts.
    $ ATTACHMENT_ID="$(wp media import ~/Downloads/image.png --porcelain)"
    $ wp post list --post_type=post --format=ids | xargs -d ' ' -I % wp post meta add % _thumbnail_id $ATTACHMENT_ID
    Success: Added custom field.
    Success: Added custom field.

    # Import an image from the web.
    $ wp media import http://s.wordpress.org/style/images/wp-header-logo.png --title='The WordPress logo' --alt="Semantic personal publishing"
    Imported file 'http://s.wordpress.org/style/images/wp-header-logo.png' as attachment ID 1755.
    Success: Imported 1 of 1 images.

    # Get the URL for an attachment after import.
    $ wp media import http://s.wordpress.org/style/images/wp-header-logo.png --porcelain | xargs -I {} wp post list --post__in={} --field=url --post_type=attachment
    http://wordpress-develop.dev/wp-header-logo/



### wp media regenerate

Regenerates thumbnails for one or more attachments.

~~~
wp media regenerate [<attachment-id>...] [--image_size=<image_size>] [--skip-delete] [--only-missing] [--yes]
~~~

**OPTIONS**

	[<attachment-id>...]
		One or more IDs of the attachments to regenerate.

	[--image_size=<image_size>]
		Name of the image size to regenerate. Only thumbnails of this image size will be regenerated, thumbnails of other image sizes will not.

	[--skip-delete]
		Skip deletion of the original thumbnails. If your thumbnails are linked from sources outside your control, it's likely best to leave them around. Defaults to false.

	[--only-missing]
		Only generate thumbnails for images missing image sizes.

	[--yes]
		Answer yes to the confirmation message. Confirmation only shows when no IDs passed as arguments.

**EXAMPLES**

    # Regenerate thumbnails for given attachment IDs.
    $ wp media regenerate 123 124 125
    Found 3 images to regenerate.
    1/3 Regenerated thumbnails for "Vertical Image" (ID 123).
    2/3 Regenerated thumbnails for "Horizontal Image" (ID 124).
    3/3 Regenerated thumbnails for "Beautiful Picture" (ID 125).
    Success: Regenerated 3 of 3 images.

    # Regenerate all thumbnails, without confirmation.
    $ wp media regenerate --yes
    Found 3 images to regenerate.
    1/3 Regenerated thumbnails for "Sydney Harbor Bridge" (ID 760).
    2/3 Regenerated thumbnails for "Boardwalk" (ID 757).
    3/3 Regenerated thumbnails for "Sunburst Over River" (ID 756).
    Success: Regenerated 3 of 3 images.

    # Re-generate all thumbnails that have IDs between 1000 and 2000.
    $ seq 1000 2000 | xargs wp media regenerate
    Found 4 images to regenerate.
    1/4 Regenerated thumbnails for "Vertical Featured Image" (ID 1027).
    2/4 Regenerated thumbnails for "Horizontal Featured Image" (ID 1022).
    3/4 Regenerated thumbnails for "Unicorn Wallpaper" (ID 1045).
    4/4 Regenerated thumbnails for "I Am Worth Loving Wallpaper" (ID 1023).
    Success: Regenerated 4 of 4 images.

    # Re-generate only the thumbnails of "large" image size for all images.
    $ wp media regenerate --image_size=large
    Do you really want to regenerate the "large" image size for all images? [y/n] y
    Found 3 images to regenerate.
    1/3 Regenerated "large" thumbnail for "Sydney Harbor Bridge" (ID 760).
    2/3 No "large" thumbnail regeneration needed for "Boardwalk" (ID 757).
    3/3 Regenerated "large" thumbnail for "Sunburst Over River" (ID 756).
    Success: Regenerated 3 of 3 images.



### wp media image-size

Lists image sizes registered with WordPress.

~~~
wp media image-size [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a specific format
		---
		default: table
		options:
		  - table
		  - json
		  - csv
		  - yaml
		  - count
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each image size:
* name
* width
* height
* crop
* ratio

**EXAMPLES**

    # List all registered image sizes
    $ wp media image-size
    +---------------------------+-------+--------+-------+-------+
    | name                      | width | height | crop  | ratio |
    +---------------------------+-------+--------+-------+-------+
    | full                      |       |        | N/A   | N/A   |
    | twentyfourteen-full-width | 1038  | 576    | hard  | 173:96|
    | large                     | 1024  | 1024   | soft  | N/A   |
    | medium_large              | 768   | 0      | soft  | N/A   |
    | medium                    | 300   | 300    | soft  | N/A   |
    | thumbnail                 | 150   | 150    | hard  | 1:1   |
    +---------------------------+-------+--------+-------+-------+

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/media-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/media-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/media-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/media-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
