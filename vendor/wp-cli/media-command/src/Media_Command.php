<?php

use WP_CLI\Utils;

/**
 * Imports files as attachments, regenerates thumbnails, or lists registered image sizes.
 *
 * ## EXAMPLES
 *
 *     # Re-generate all thumbnails, without confirmation.
 *     $ wp media regenerate --yes
 *     Found 3 images to regenerate.
 *     1/3 Regenerated thumbnails for "Sydney Harbor Bridge" (ID 760).
 *     2/3 Regenerated thumbnails for "Boardwalk" (ID 757).
 *     3/3 Regenerated thumbnails for "Sunburst Over River" (ID 756).
 *     Success: Regenerated 3 of 3 images.
 *
 *     # Import a local image and set it to be the featured image for a post.
 *     $ wp media import ~/Downloads/image.png --post_id=123 --title="A downloaded picture" --featured_image
 *     Success: Imported file '/home/person/Downloads/image.png' as attachment ID 1753 and attached to post 123 as featured image.
 *
 *     # List all registered image sizes
 *     $ wp media image-size
 *     +---------------------------+-------+--------+-------+
 *     | name                      | width | height | crop  |
 *     +---------------------------+-------+--------+-------+
 *     | full                      |       |        | N/A   |
 *     | twentyfourteen-full-width | 1038  | 576    | hard  |
 *     | large                     | 1024  | 1024   | soft  |
 *     | medium_large              | 768   | 0      | soft  |
 *     | medium                    | 300   | 300    | soft  |
 *     | thumbnail                 | 150   | 150    | hard  |
 *     +---------------------------+-------+--------+-------+
 *
 * @package wp-cli
 */
class Media_Command extends WP_CLI_Command {

	/**
	 * Clear the WP object cache after this many regenerations/imports.
	 *
	 * @var integer
	 */
	const WP_CLEAR_OBJECT_CACHE_INTERVAL = 500;

	/**
	 * Regenerates thumbnails for one or more attachments.
	 *
	 * ## OPTIONS
	 *
	 * [<attachment-id>...]
	 * : One or more IDs of the attachments to regenerate.
	 *
	 * [--image_size=<image_size>]
	 * : Name of the image size to regenerate. Only thumbnails of this image size will be regenerated, thumbnails of other image sizes will not.
	 *
	 * [--skip-delete]
	 * : Skip deletion of the original thumbnails. If your thumbnails are linked from sources outside your control, it's likely best to leave them around. Defaults to false.
	 *
	 * [--only-missing]
	 * : Only generate thumbnails for images missing image sizes.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message. Confirmation only shows when no IDs passed as arguments.
	 *
	 * ## EXAMPLES
	 *
	 *     # Regenerate thumbnails for given attachment IDs.
	 *     $ wp media regenerate 123 124 125
	 *     Found 3 images to regenerate.
	 *     1/3 Regenerated thumbnails for "Vertical Image" (ID 123).
	 *     2/3 Regenerated thumbnails for "Horizontal Image" (ID 124).
	 *     3/3 Regenerated thumbnails for "Beautiful Picture" (ID 125).
	 *     Success: Regenerated 3 of 3 images.
	 *
	 *     # Regenerate all thumbnails, without confirmation.
	 *     $ wp media regenerate --yes
	 *     Found 3 images to regenerate.
	 *     1/3 Regenerated thumbnails for "Sydney Harbor Bridge" (ID 760).
	 *     2/3 Regenerated thumbnails for "Boardwalk" (ID 757).
	 *     3/3 Regenerated thumbnails for "Sunburst Over River" (ID 756).
	 *     Success: Regenerated 3 of 3 images.
	 *
	 *     # Re-generate all thumbnails that have IDs between 1000 and 2000.
	 *     $ seq 1000 2000 | xargs wp media regenerate
	 *     Found 4 images to regenerate.
	 *     1/4 Regenerated thumbnails for "Vertical Featured Image" (ID 1027).
	 *     2/4 Regenerated thumbnails for "Horizontal Featured Image" (ID 1022).
	 *     3/4 Regenerated thumbnails for "Unicorn Wallpaper" (ID 1045).
	 *     4/4 Regenerated thumbnails for "I Am Worth Loving Wallpaper" (ID 1023).
	 *     Success: Regenerated 4 of 4 images.
	 *
	 *     # Re-generate only the thumbnails of "large" image size for all images.
	 *     $ wp media regenerate --image_size=large
	 *     Do you really want to regenerate the "large" image size for all images? [y/n] y
	 *     Found 3 images to regenerate.
	 *     1/3 Regenerated "large" thumbnail for "Sydney Harbor Bridge" (ID 760).
	 *     2/3 No "large" thumbnail regeneration needed for "Boardwalk" (ID 757).
	 *     3/3 Regenerated "large" thumbnail for "Sunburst Over River" (ID 756).
	 *     Success: Regenerated 3 of 3 images.
	 */
	public function regenerate( $args, $assoc_args = array() ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			[ 'image_size' => '' ]
		);

		$image_size = $assoc_args['image_size'];
		if ( $image_size && ! in_array( $image_size, get_intermediate_image_sizes(), true ) ) {
			WP_CLI::error( sprintf( 'Unknown image size "%s".', $image_size ) );
		}

		if ( empty( $args ) ) {
			if ( $image_size ) {
				WP_CLI::confirm( sprintf( 'Do you really want to regenerate the "%s" image size for all images?', $image_size ), $assoc_args );
			} else {
				WP_CLI::confirm( 'Do you really want to regenerate all images?', $assoc_args );
			}
		}

		$skip_delete  = Utils\get_flag_value( $assoc_args, 'skip-delete' );
		$only_missing = Utils\get_flag_value( $assoc_args, 'only-missing' );
		if ( $only_missing ) {
			$skip_delete = true;
		}

		$additional_mime_types = array();

		if ( Utils\wp_version_compare( '4.7', '>=' ) ) {
			$additional_mime_types[] = 'application/pdf';
		}

		$images = $this->get_images( $args, $additional_mime_types );
		$count  = $images->post_count;

		if ( ! $count ) {
			WP_CLI::warning( 'No images found.' );
			return;
		}

		WP_CLI::log(
			sprintf(
				'Found %1$d %2$s to regenerate.',
				$count,
				_n( 'image', 'images', $count )
			)
		);

		if ( $image_size ) {
			$image_size_filters = $this->add_image_size_filters( $image_size );
		}

		$number    = 0;
		$successes = 0;
		$errors    = 0;
		$skips     = 0;
		foreach ( $images->posts as $post ) {
			$number++;
			if ( 0 === $number % self::WP_CLEAR_OBJECT_CACHE_INTERVAL ) {
				Utils\wp_clear_object_cache();
			}
			$this->process_regeneration( $post->ID, $skip_delete, $only_missing, $image_size, $number . '/' . $count, $successes, $errors, $skips );
		}

		if ( $image_size ) {
			$this->remove_image_size_filters( $image_size_filters );
		}

		Utils\report_batch_operation_results( 'image', 'regenerate', $count, $successes, $errors, $skips );
	}

	/**
	 * Creates attachments from local files or URLs.
	 *
	 * ## OPTIONS
	 *
	 * <file>...
	 * : Path to file or files to be imported. Supports the glob(3) capabilities of the current shell.
	 *     If file is recognized as a URL (for example, with a scheme of http or ftp), the file will be
	 *     downloaded to a temp file before being sideloaded.
	 *
	 * [--post_id=<post_id>]
	 * : ID of the post to attach the imported files to.
	 *
	 * [--title=<title>]
	 * : Attachment title (post title field).
	 *
	 * [--caption=<caption>]
	 * : Caption for attachent (post excerpt field).
	 *
	 * [--alt=<alt_text>]
	 * : Alt text for image (saved as post meta).
	 *
	 * [--desc=<description>]
	 * : "Description" field (post content) of attachment post.
	 *
	 * [--skip-copy]
	 * : If set, media files (local only) are imported to the library but not moved on disk.
	 * File names will not be run through wp_unique_filename() with this set.
	 *
	 * [--preserve-filetime]
	 * : Use the file modified time as the post published & modified dates.
	 * Remote files will always use the current time.
	 *
	 * [--featured_image]
	 * : If set, set the imported image as the Featured Image of the post its attached to.
	 *
	 * [--porcelain]
	 * : Output just the new attachment ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import all jpgs in the current user's "Pictures" directory, not attached to any post.
	 *     $ wp media import ~/Pictures/**\/*.jpg
	 *     Imported file '/home/person/Pictures/landscape-photo.jpg' as attachment ID 1751.
	 *     Imported file '/home/person/Pictures/fashion-icon.jpg' as attachment ID 1752.
	 *     Success: Imported 2 of 2 items.
	 *
	 *     # Import a local image and set it to be the post thumbnail for a post.
	 *     $ wp media import ~/Downloads/image.png --post_id=123 --title="A downloaded picture" --featured_image
	 *     Imported file '/home/person/Downloads/image.png' as attachment ID 1753 and attached to post 123 as featured image.
	 *     Success: Imported 1 of 1 images.
	 *
	 *     # Import a local image, but set it as the featured image for all posts.
	 *     # 1. Import the image and get its attachment ID.
	 *     # 2. Assign the attachment ID as the featured image for all posts.
	 *     $ ATTACHMENT_ID="$(wp media import ~/Downloads/image.png --porcelain)"
	 *     $ wp post list --post_type=post --format=ids | xargs -d ' ' -I % wp post meta add % _thumbnail_id $ATTACHMENT_ID
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 *
	 *     # Import an image from the web.
	 *     $ wp media import http://s.wordpress.org/style/images/wp-header-logo.png --title='The WordPress logo' --alt="Semantic personal publishing"
	 *     Imported file 'http://s.wordpress.org/style/images/wp-header-logo.png' as attachment ID 1755.
	 *     Success: Imported 1 of 1 images.
	 *
	 *     # Get the URL for an attachment after import.
	 *     $ wp media import http://s.wordpress.org/style/images/wp-header-logo.png --porcelain | xargs -I {} wp post list --post__in={} --field=url --post_type=attachment
	 *     http://wordpress-develop.dev/wp-header-logo/
	 */
	public function import( $args, $assoc_args = array() ) {
		$assoc_args = wp_parse_args(
			$assoc_args,
			array(
				'title'   => '',
				'caption' => '',
				'alt'     => '',
				'desc'    => '',
			)
		);

		// Assume the most generic term
		$noun = 'item';

		// Current site's timezone offset.
		$gmt_offset = get_option( 'gmt_offset' );

		// Use the noun `image` when sure the media file is an image
		if ( Utils\get_flag_value( $assoc_args, 'featured_image' ) || $assoc_args['alt'] ) {
			$noun = 'image';
		}

		if ( isset( $assoc_args['post_id'] ) ) {
			if ( ! get_post( $assoc_args['post_id'] ) ) {
				WP_CLI::warning( 'Invalid --post_id' );
				$assoc_args['post_id'] = false;
			}
		} else {
			$assoc_args['post_id'] = false;
		}

		$number    = 0;
		$successes = 0;
		$errors    = 0;
		foreach ( $args as $file ) {
			$number++;
			if ( 0 === $number % self::WP_CLEAR_OBJECT_CACHE_INTERVAL ) {
				Utils\wp_clear_object_cache();
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- parse_url will only be used in absence of wp_parse_url.
			$is_file_remote = function_exists( 'wp_parse_url' ) ? wp_parse_url( $file, PHP_URL_HOST ) : parse_url( $file, PHP_URL_HOST );
			$orig_filename  = $file;
			$file_time      = '';

			if ( empty( $is_file_remote ) ) {
				if ( ! file_exists( $file ) ) {
					WP_CLI::warning( "Unable to import file '$file'. Reason: File doesn't exist." );
					$errors++;
					continue;
				}
				if ( Utils\get_flag_value( $assoc_args, 'skip-copy' ) ) {
					$tempfile = $file;
				} else {
					$tempfile = $this->make_copy( $file );
				}
				$name = Utils\basename( $file );

				if ( Utils\get_flag_value( $assoc_args, 'preserve-filetime' ) ) {
					$file_time = @filemtime( $file );
				}
			} else {
				$tempfile = download_url( $file );
				if ( is_wp_error( $tempfile ) ) {
					WP_CLI::warning(
						sprintf(
							"Unable to import file '%s'. Reason: %s",
							$file,
							implode( ', ', $tempfile->get_error_messages() )
						)
					);
					$errors++;
					continue;
				}
				$name = strtok( Utils\basename( $file ), '?' );
			}

			$file_array = array(
				'tmp_name' => $tempfile,
				'name'     => $name,
			);

			$post_array = array(
				'post_title'   => $assoc_args['title'],
				'post_excerpt' => $assoc_args['caption'],
				'post_content' => $assoc_args['desc'],
			);

			if ( ! empty( $file_time ) ) {
				$post_array['post_date']         = gmdate( 'Y-m-d H:i:s', $file_time + ( $gmt_offset * HOUR_IN_SECONDS ) );
				$post_array['post_date_gmt']     = gmdate( 'Y-m-d H:i:s', $file_time );
				$post_array['post_modified']     = gmdate( 'Y-m-d H:i:s', $file_time + ( $gmt_offset * HOUR_IN_SECONDS ) );
				$post_array['post_modified_gmt'] = gmdate( 'Y-m-d H:i:s', $file_time );
			}

			$post_array = wp_slash( $post_array );

			// use image exif/iptc data for title and caption defaults if possible
			if ( empty( $post_array['post_title'] ) || empty( $post_array['post_excerpt'] ) ) {
				// @codingStandardsIgnoreStart
				$image_meta = @wp_read_image_metadata( $tempfile );
				// @codingStandardsIgnoreEnd
				if ( ! empty( $image_meta ) ) {
					if ( empty( $post_array['post_title'] ) && trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
						$post_array['post_title'] = $image_meta['title'];
					}

					if ( empty( $post_array['post_excerpt'] ) && trim( $image_meta['caption'] ) ) {
						$post_array['post_excerpt'] = $image_meta['caption'];
					}
				}
			}

			if ( empty( $post_array['post_title'] ) ) {
				$post_array['post_title'] = preg_replace( '/\.[^.]+$/', '', Utils\basename( $file ) );
			}

			if ( Utils\get_flag_value( $assoc_args, 'skip-copy' ) ) {
				$wp_filetype                  = wp_check_filetype( $file, null );
				$post_array['post_mime_type'] = $wp_filetype['type'];
				$post_array['post_status']    = 'inherit';

				$success = wp_insert_attachment( $post_array, $file, $assoc_args['post_id'] );
				if ( is_wp_error( $success ) ) {
					WP_CLI::warning(
						sprintf(
							"Unable to insert file '%s'. Reason: %s",
							$orig_filename,
							implode( ', ', $success->get_error_messages() )
						)
					);
					$errors++;
					continue;
				}
				wp_update_attachment_metadata( $success, wp_generate_attachment_metadata( $success, $file ) );
			} else {
				// Deletes the temporary file.
				$success = media_handle_sideload( $file_array, $assoc_args['post_id'], $assoc_args['title'], $post_array );
				if ( is_wp_error( $success ) ) {
					WP_CLI::warning(
						sprintf(
							"Unable to import file '%s'. Reason: %s",
							$orig_filename,
							implode( ', ', $success->get_error_messages() )
						)
					);
					$errors++;
					continue;
				}
			}

			// Set alt text
			if ( $assoc_args['alt'] ) {
				update_post_meta( $success, '_wp_attachment_image_alt', wp_slash( $assoc_args['alt'] ) );
			}

			// Set as featured image, if --post_id and --featured_image are set
			if ( $assoc_args['post_id'] && Utils\get_flag_value( $assoc_args, 'featured_image' ) ) {
				update_post_meta( $assoc_args['post_id'], '_thumbnail_id', $success );
			}

			$attachment_success_text = '';
			if ( $assoc_args['post_id'] ) {
				$attachment_success_text = " and attached to post {$assoc_args['post_id']}";
				if ( Utils\get_flag_value( $assoc_args, 'featured_image' ) ) {
					$attachment_success_text .= ' as featured image';
				}
			}

			if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
				WP_CLI::line( $success );
			} else {
				WP_CLI::log(
					sprintf(
						"Imported file '%s' as attachment ID %d%s.",
						$orig_filename,
						$success,
						$attachment_success_text
					)
				);
			}
			$successes++;
		}

		// Report the result of the operation
		if ( ! Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			Utils\report_batch_operation_results( $noun, 'import', count( $args ), $successes, $errors );
		} elseif ( $errors ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Lists image sizes registered with WordPress.
	 *
	 * ## OPTIONS
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a specific format
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each image size:
	 * * name
	 * * width
	 * * height
	 * * crop
	 * * ratio
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered image sizes
	 *     $ wp media image-size
	 *     +---------------------------+-------+--------+-------+-------+
	 *     | name                      | width | height | crop  | ratio |
	 *     +---------------------------+-------+--------+-------+-------+
	 *     | full                      |       |        | N/A   | N/A   |
	 *     | twentyfourteen-full-width | 1038  | 576    | hard  | 173:96|
	 *     | large                     | 1024  | 1024   | soft  | N/A   |
	 *     | medium_large              | 768   | 0      | soft  | N/A   |
	 *     | medium                    | 300   | 300    | soft  | N/A   |
	 *     | thumbnail                 | 150   | 150    | hard  | 1:1   |
	 *     +---------------------------+-------+--------+-------+-------+
	 *
	 * @subcommand image-size
	 */
	public function image_size( $args, $assoc_args ) {
		$assoc_args = array_merge(
			array(
				'fields' => 'name,width,height,crop,ratio',
			),
			$assoc_args
		);

		$sizes = $this->get_registered_image_sizes();

		usort(
			$sizes,
			function( $a, $b ) {
				if ( $a['width'] === $b['width'] ) {
					return 0;
				}
				return ( $a['width'] < $b['width'] ) ? 1 : -1;
			}
		);
		array_unshift(
			$sizes,
			array(
				'name'   => 'full',
				'width'  => '',
				'height' => '',
				'crop'   => 'N/A',
				'ratio'  => 'N/A',
			)
		);
		WP_CLI\Utils\format_items( $assoc_args['format'], $sizes, explode( ',', $assoc_args['fields'] ) );
	}

	private function get_ratio( $width, $height ) {
		if ( 0 === $height ) {
			return "0:{$width}";
		}

		if ( 0 === $width ) {
			return "{$height}:0";
		}

		$gcd          = $this->gcd( $width, $height );
		$width_ratio  = $width / $gcd;
		$height_ratio = $height / $gcd;

		return "{$width_ratio}:{$height_ratio}";
	}

	private function gcd( $num1, $num2 ) {
		while ( 0 !== $num2 ) {
			$t    = $num1 % $num2;
			$num1 = $num2;
			$num2 = $t;
		}
		return $num1;
	}

	// wp_tempnam() inexplicably forces a .tmp extension, which spoils MIME type detection
	private function make_copy( $path ) {
		$dir      = get_temp_dir();
		$filename = Utils\basename( $path );
		if ( empty( $filename ) ) {
			$filename = time();
		}

		$filename = $dir . wp_unique_filename( $dir, $filename );
		if ( ! copy( $path, $filename ) ) {
			WP_CLI::error( "Could not create temporary file for $path." );
		}

		return $filename;
	}

	private function process_regeneration( $id, $skip_delete, $only_missing, $image_size, $progress, &$successes, &$errors, &$skips ) {

		$title = get_the_title( $id );
		if ( '' === $title ) {
			// If audio or video cover art then the id is the sub attachment id, which has no title.
			if ( metadata_exists( 'post', $id, '_cover_hash' ) ) {
				// Unfortunately the only way to get the attachment title would be to do a non-indexed query against the meta value of `_thumbnail_id`. So don't.
				$att_desc = sprintf( 'cover attachment (ID %d)', $id );
			} else {
				$att_desc = sprintf( '"(no title)" (ID %d)', $id );
			}
		} else {
			$att_desc = sprintf( '"%1$s" (ID %2$d)', $title, $id );
		}
		$thumbnail_desc = $image_size ? sprintf( '"%s" thumbnail', $image_size ) : 'thumbnail';

		$fullsizepath = $this->get_attached_file( $id );

		if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
			WP_CLI::warning( "Can't find $att_desc." );
			$errors++;
			return;
		}

		$is_pdf = 'application/pdf' === get_post_mime_type( $id );

		$needs_regeneration = $this->needs_regeneration( $id, $fullsizepath, $is_pdf, $image_size, $skip_delete, $skip_it );

		if ( $skip_it ) {
			WP_CLI::log( "$progress Skipped $thumbnail_desc regeneration for $att_desc." );
			$skips++;
			return;
		}

		if ( $only_missing && ! $needs_regeneration ) {
			WP_CLI::log( "$progress No $thumbnail_desc regeneration needed for $att_desc." );
			$successes++;
			return;
		}

		$metadata = wp_generate_attachment_metadata( $id, $fullsizepath );
		if ( is_wp_error( $metadata ) ) {
			WP_CLI::warning( sprintf( '%s (ID %d)', $metadata->get_error_message(), $id ) );
			WP_CLI::log( "$progress Couldn't regenerate thumbnails for $att_desc." );
			$errors++;
			return;
		}

		// Note it's possible for no metadata to be generated for PDFs if restricted to a specific image size.
		if ( empty( $metadata ) && ! ( $is_pdf && $image_size ) ) {
			WP_CLI::warning( sprintf( 'No metadata. (ID %d)', $id ) );
			WP_CLI::log( "$progress Couldn't regenerate thumbnails for $att_desc." );
			$errors++;
			return;
		}

		if ( $image_size ) {
			if ( $this->update_attachment_metadata_for_image_size( $id, $metadata, $image_size ) ) {
				WP_CLI::log( "$progress Regenerated $thumbnail_desc for $att_desc." );
			} else {
				WP_CLI::log( "$progress No $thumbnail_desc regeneration needed for $att_desc." );
			}
		} else {
			wp_update_attachment_metadata( $id, $metadata );

			WP_CLI::log( "$progress Regenerated thumbnails for $att_desc." );
		}
		$successes++;
	}

	private function remove_old_images( $metadata, $fullsizepath, $image_size ) {

		if ( empty( $metadata['sizes'] ) ) {
			return;
		}

		if ( $image_size ) {
			if ( empty( $metadata['sizes'][ $image_size ] ) ) {
				return;
			}
			$metadata['sizes'] = array( $image_size => $metadata['sizes'][ $image_size ] );
		}

		$dir_path = dirname( $fullsizepath ) . '/';

		foreach ( $metadata['sizes'] as $size_info ) {
			$intermediate_path = $dir_path . $size_info['file'];

			if ( $intermediate_path === $fullsizepath ) {
				continue;
			}

			if ( file_exists( $intermediate_path ) ) {
				unlink( $intermediate_path );
			}
		}
	}

	private function needs_regeneration( $att_id, $fullsizepath, $is_pdf, $image_size, $skip_delete, &$skip_it ) {

		// Assume not skipping.
		$skip_it = false;

		// Note: zero-length string returned if no metadata, for instance if PDF or non-standard image (eg an SVG).
		$metadata = wp_get_attachment_metadata( $att_id );

		$image_sizes = $this->get_intermediate_image_sizes_for_attachment( $fullsizepath, $is_pdf, $metadata );

		// First check if no applicable editor currently available (non-destructive - ie old thumbnails not removed).
		if ( is_wp_error( $image_sizes ) && 'image_no_editor' === $image_sizes->get_error_code() ) {
			// Warn unless PDF or non-standard image.
			if ( ! $is_pdf && is_array( $metadata ) && ! empty( $metadata['sizes'] ) ) {
				WP_CLI::warning( sprintf( '%s (ID %d)', $image_sizes->get_error_message(), $att_id ) );
			}
			$skip_it = true;
			return false;
		}

		// If uploaded when applicable image editor such as Imagick unavailable, the metadata or sizes metadata may not exist.
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}
		// If set `$metadata['sizes']` should be array but explicitly check as following code depends on it.
		if ( ! isset( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
			$metadata['sizes'] = array();
		}

		// Remove any old thumbnails (so now destructive).
		if ( ! $skip_delete ) {
			$this->remove_old_images( $metadata, $fullsizepath, $image_size );
		}

		// Check for any other error (such as load error) apart from no editor available.
		if ( is_wp_error( $image_sizes ) ) {
			// Warn but assume it may be possible to regenerate and allow processing to continue and possibly fail.
			WP_CLI::warning( sprintf( '%s (ID %d)', $image_sizes->get_error_message(), $att_id ) );
			return true;
		}

		// Have sizes - check whether there're new ones or they've changed. Note that an attachment can have no sizes if it's on or below the thumbnail threshold.

		if ( $image_size ) {
			if ( empty( $image_sizes[ $image_size ] ) ) {
				return false;
			}
			if ( empty( $metadata['sizes'][ $image_size ] ) ) {
				return true;
			}
			$metadata['sizes'] = array( $image_size => $metadata['sizes'][ $image_size ] );
		}

		if ( $this->image_sizes_differ( $image_sizes, $metadata['sizes'] ) ) {
			return true;
		}

		$dir_path = dirname( $fullsizepath ) . '/';

		// Check that the thumbnail files exist.
		foreach ( $metadata['sizes'] as $size_info ) {
			$intermediate_path = $dir_path . $size_info['file'];

			if ( $intermediate_path === $fullsizepath ) {
				continue;
			}

			if ( ! file_exists( $intermediate_path ) ) {
				return true;
			}
		}
		return false;
	}

	// Whether there's new image sizes or the width/height of existing image sizes have changed.
	private function image_sizes_differ( $image_sizes, $meta_sizes ) {
		// Check if have new image size(s).
		if ( array_diff( array_keys( $image_sizes ), array_keys( $meta_sizes ) ) ) {
			return true;
		}
		// Check if image sizes have changed.
		foreach ( $image_sizes as $name => $image_size ) {
			if ( $image_size['width'] !== $meta_sizes[ $name ]['width'] || $image_size['height'] !== $meta_sizes[ $name ]['height'] ) {
				return true;
			}
		}
		return false;
	}

	// Like WP's get_intermediate_image_sizes(), but removes sizes that won't be generated for a particular attachment due to its being on or below their thresholds,
	// and returns associative array with size name => width/height entries, resolved to crop values if applicable.
	private function get_intermediate_image_sizes_for_attachment( $fullsizepath, $is_pdf, $metadata ) {

		// Need to get width, height of attachment for image_resize_dimensions().
		$editor = wp_get_image_editor( $fullsizepath );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}
		$result = $editor->load();
		if ( is_wp_error( $result ) ) {
			unset( $editor );
			return $result;
		}
		list( $width, $height ) = array_values( $editor->get_size() );
		unset( $editor );

		$sizes = array();
		foreach ( $this->get_intermediate_sizes( $is_pdf, $metadata ) as $name => $size ) {
			// Need to check destination and original width or height differ before calling image_resize_dimensions(), otherwise it will return non-false.
			$dims = image_resize_dimensions( $width, $height, $size['width'], $size['height'], $size['crop'] );
			if ( ( $width !== $size['width'] || $height !== $size['height'] ) && $dims ) {
				list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;
				$sizes[ $name ] = array(
					'width'  => $dst_w,
					'height' => $dst_h,
				);
			}
		}
		return $sizes;
	}

	// Like WP's get_intermediate_image_sizes(), but returns associative array with name => size info entries (and caters for PDFs also).
	private function get_intermediate_sizes( $is_pdf, $metadata ) {
		if ( $is_pdf ) {
			// Copied from wp_generate_attachment_metadata() in "wp-admin/includes/image.php".
			$fallback_sizes = array(
				'thumbnail',
				'medium',
				'large',
			);
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
			$intermediate_image_sizes = apply_filters( 'fallback_intermediate_image_sizes', $fallback_sizes, $metadata );
		} else {
			$intermediate_image_sizes = get_intermediate_image_sizes();
		}

		// Adapted from wp_generate_attachment_metadata() in "wp-admin/includes/image.php".

		if ( function_exists( 'wp_get_additional_image_sizes' ) ) {
			$_wp_additional_image_sizes = wp_get_additional_image_sizes();
		} else {
			// For WP < 4.7.0.
			global $_wp_additional_image_sizes;
			if ( ! $_wp_additional_image_sizes ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Used as a fallback for WordPress version less than 4.7.0 as function wp_get_additional_image_sizes didn't exist then.
				$_wp_additional_image_sizes = array();
			}
		}

		$sizes = array();
		foreach ( $intermediate_image_sizes as $s ) {
			if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
				$sizes[ $s ]['width'] = (int) $_wp_additional_image_sizes[ $s ]['width'];
			} else {
				$sizes[ $s ]['width'] = (int) get_option( "{$s}_size_w" );
			}

			if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
				$sizes[ $s ]['height'] = (int) $_wp_additional_image_sizes[ $s ]['height'];
			} else {
				$sizes[ $s ]['height'] = (int) get_option( "{$s}_size_h" );
			}

			if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
				$sizes[ $s ]['crop'] = (bool) $_wp_additional_image_sizes[ $s ]['crop'];
			} else {
				// Force PDF thumbnails to be soft crops.
				if ( $is_pdf && 'thumbnail' === $s ) {
					$sizes[ $s ]['crop'] = false;
				} else {
					$sizes[ $s ]['crop'] = (bool) get_option( "{$s}_crop" );
				}
			}
		}

		// Check here that not PDF (as filter not applied in core if is) and `$metadata` is array (as may not be and filter only applied in core when is).
		if ( ! $is_pdf && is_array( $metadata ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling native WordPress hook.
			$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes, $metadata );
		}

		return $sizes;
	}

	// Add filters to only process a particular intermediate image size in wp_generate_attachment_metadata().
	private function add_image_size_filters( $image_size ) {
		$image_size_filters = array();

		// For images.
		$image_size_filters['intermediate_image_sizes_advanced'] = function ( $sizes ) use ( $image_size ) {
			// $sizes is associative array of name => size info entries.
			if ( isset( $sizes[ $image_size ] ) ) {
				return array( $image_size => $sizes[ $image_size ] );
			}
			return array();
		};

		// For PDF previews.
		$image_size_filters['fallback_intermediate_image_sizes'] = function ( $fallback_sizes ) use ( $image_size ) {
			// $fallback_sizes is indexed array of size names.
			if ( in_array( $image_size, $fallback_sizes, true ) ) {
				return array( $image_size );
			}
			return array();
		};

		foreach ( $image_size_filters as $name => $filter ) {
			add_filter( $name, $filter, PHP_INT_MAX );
		}

		return $image_size_filters;
	}

	// Remove above intermediate image size filters.
	private function remove_image_size_filters( $image_size_filters ) {
		foreach ( $image_size_filters as $name => $filter ) {
			remove_filter( $name, $filter, PHP_INT_MAX );
		}
	}

	// Update attachment sizes metadata just for a particular intermediate image size.
	private function update_attachment_metadata_for_image_size( $id, $new_metadata, $image_size ) {
		$metadata = wp_get_attachment_metadata( $id );

		if ( ! is_array( $metadata ) ) {
			return false;
		}

		// If have metadata for image_size.
		if ( ! empty( $new_metadata['sizes'][ $image_size ] ) ) {
			$metadata['sizes'][ $image_size ] = $new_metadata['sizes'][ $image_size ];
			wp_update_attachment_metadata( $id, $metadata );
			return true;
		}

		// Else remove unused metadata if any.
		if ( ! empty( $metadata['sizes'][ $image_size ] ) ) {
			unset( $metadata['sizes'][ $image_size ] );
			wp_update_attachment_metadata( $id, $metadata );
			// Treat removing unused metadata as no change.
		}
		return false;
	}

	/**
	 * Get images from the installation.
	 *
	 * @param array $args                  The query arguments to use. Optional.
	 * @param array $additional_mime_types The additional mime types to search for. Optional.
	 *
	 * @return WP_Query The query result.
	 */
	private function get_images( $args = array(), $additional_mime_types = array() ) {
		$mime_types = array_merge( array( 'image' ), $additional_mime_types );

		$query_args = array(
			'post_type'      => 'attachment',
			'post__in'       => $args,
			'post_mime_type' => $mime_types,
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);

		return new WP_Query( $query_args );
	}

	/**
	 * Get the metadata for the passed intermediate image size.
	 *
	 * @param string $size The image size to get the metadata for.
	 *
	 * @return array The image size metadata.
	 */
	private function get_intermediate_size_metadata( $size ) {
		$width  = intval( get_option( "{$size}_size_w" ) );
		$height = intval( get_option( "{$size}_size_h" ) );
		$crop   = get_option( "{$size}_crop" );

		return array(
			'name'   => $size,
			'width'  => $width,
			'height' => $height,
			'crop'   => false !== $crop ? 'hard' : 'soft',
			'ratio'  => false !== $crop ? $this->get_ratio( $width, $height ) : 'N/A',
		);
	}

	/**
	 * Get all the registered image sizes along with their dimensions.
	 *
	 * @global array $_wp_additional_image_sizes The additional image sizes to parse.
	 *
	 * @link https://wordpress.stackexchange.com/a/251602 Original solution.
	 *
	 * @return array $image_sizes The image sizes
	 */
	private function get_registered_image_sizes() {
		global $_wp_additional_image_sizes;

		$image_sizes         = array();
		$default_image_sizes = get_intermediate_image_sizes();

		foreach ( $default_image_sizes as $size ) {
			$image_sizes[] = $this->get_intermediate_size_metadata( $size );
		}

		if ( is_array( $_wp_additional_image_sizes ) ) {
			foreach ( $_wp_additional_image_sizes as $size => $size_args ) {
				$crop          = filter_var( $size_args['crop'], FILTER_VALIDATE_BOOLEAN );
				$image_sizes[] = array(
					'name'   => $size,
					'width'  => $size_args['width'],
					'height' => $size_args['height'],
					'crop'   => empty( $crop ) || is_array( $size_args['crop'] ) ? 'soft' : 'hard',
					'ratio'  => empty( $crop ) || is_array( $size_args['crop'] ) ? 'N/A' : $this->get_ratio( $size_args['width'], $size_args['height'] ),
				);
			}
		}

		return $image_sizes;
	}

	/**
	 * Fix image orientation for one or more attachments.
	 *
	 * ## OPTIONS
	 *
	 * [<attachment-id>...]
	 * : One or more IDs of the attachments to regenerate.
	 *
	 * [--dry-run]
	 * : Check images needing orientation without performing the operation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Fix orientation for all images.
	 *     $ wp media fix-orientation
	 *     1/3 Fixing orientation for "Landscape_4" (ID 62).
	 *     2/3 Fixing orientation for "Landscape_3" (ID 61).
	 *     3/3 Fixing orientation for "Landscape_2" (ID 60).
	 *     Success: Fixed 3 of 3 images.
	 *
	 *     # Fix orientation dry run.
	 *     $ wp media fix-orientation 63 -dry run
	 *     1/1 "Portrait_6" (ID 63) will be affected.
	 *     Success: 1 of 1 image will be affected.
	 *
	 *     # Fix orientation for specific images.
	 *     $ wp media fix-orientation 63
	 *     1/1 Fixing orientation for "Portrait_6" (ID 63).
	 *     Success: Fixed 1 of 1 images.
	 *
	 * @subcommand fix-orientation
	 */
	public function fix_orientation( $args, $assoc_args ) {

		// EXIF is required to read image metadata for orientation.
		if ( ! extension_loaded( 'exif' ) ) {
			WP_CLI::error( "'EXIF' extension is not loaded, it is required for this operation." );
		} elseif ( ! function_exists( 'exif_read_data' ) ) {
			WP_CLI::error( "Function 'exif_read_data' does not exist, it is required for this operation." );
		}

		$images  = $this->get_images( $args );
		$count   = $images->post_count;
		$dry_run = Utils\get_flag_value( $assoc_args, 'dry-run' );

		if ( ! $count ) {
			WP_CLI::error( 'No images found.' );
		}

		$number    = 0;
		$successes = 0;
		$errors    = 0;
		foreach ( $images->posts as $post ) {
			$number++;
			if ( 0 === $number % self::WP_CLEAR_OBJECT_CACHE_INTERVAL ) {
				Utils\wp_clear_object_cache();
			}
			$this->process_orientation_fix( $post->ID, "{$number}/{$count}", $successes, $errors, $dry_run );
		}

		if ( Utils\get_flag_value( $assoc_args, 'dry-run' ) ) {
			WP_CLI::success( sprintf( '%s of %s %s will be affected.', $successes, $count, Utils\pluralize( 'image', $count ) ) );
		} else {
			Utils\report_batch_operation_results( 'image', 'fix', $count, $successes, $errors );
		}
	}

	/**
	 * Perform orientation fix on attachments.
	 *
	 * @param int    $id        Attachment Id.
	 * @param string $progress  Current progress string.
	 * @param int    $successes Count of success in current operation.
	 * @param int    $errors    Count of errors in current operation.
	 * @param bool   $dry_run   Is this a dry run?
	 */
	private function process_orientation_fix( $id, $progress, &$successes, &$errors, $dry_run ) {
		$title = get_the_title( $id );
		if ( '' === $title ) {
			// If audio or video cover art then the id is the sub attachment id, which has no title.
			if ( metadata_exists( 'post', $id, '_cover_hash' ) ) {
				// Unfortunately the only way to get the attachment title would be to do a non-indexed query against the meta value of `_thumbnail_id`. So don't.
				$att_desc = sprintf( 'cover attachment (ID %d)', $id );
			} else {
				$att_desc = sprintf( '"(no title)" (ID %d)', $id );
			}
		} else {
			$att_desc = sprintf( '"%1$s" (ID %2$d)', $title, $id );
		}

		$full_size_path = $this->get_attached_file( $id );

		if ( false === $full_size_path || ! file_exists( $full_size_path ) ) {
			WP_CLI::warning( "Can't find {$att_desc}." );
			$errors++;
			return;
		}

		// Get current metadata of the attachment.
		$metadata   = wp_generate_attachment_metadata( $id, $full_size_path );
		$image_meta = ! empty( $metadata['image_meta'] ) ? $metadata['image_meta'] : [];

		if ( isset( $image_meta['orientation'] ) && absint( $image_meta['orientation'] ) > 1 ) {
			if ( ! $dry_run ) {
				WP_CLI::log( "{$progress} Fixing orientation for {$att_desc}." );
				if ( false !== $this->flip_rotate_image( $id, $metadata, $image_meta, $full_size_path ) ) {
					$successes++;
				} else {
					$errors++;
					WP_CLI::log( "Couldn't fix orientation for {$att_desc}." );
				}
			} else {
				WP_CLI::log( "{$progress} {$att_desc} will be affected." );
				$successes++;
			}
		} else {
			WP_CLI::log( "{$progress} No orientation fix required for {$att_desc}." );
		}
	}

	/**
	 * Perform image rotate operations on the image.
	 *
	 * @param int    $id             Attachment Id.
	 * @param array  $metadata       Attachment Metadata.
	 * @param array  $image_meta     `image_meta` information for the attachment.
	 * @param string $full_size_path Path to original image.
	 *
	 * @return bool Whether the image rotation operation succeeded.
	 */
	private function flip_rotate_image( $id, $metadata, $image_meta, $full_size_path ) {
		$editor = wp_get_image_editor( $full_size_path );

		if ( ! is_wp_error( $editor ) ) {
			$operations = $this->calculate_transformation( (int) $image_meta['orientation'] );

			// Rotate image if required.
			if ( true === $operations['rotate'] ) {
				$editor->rotate( $operations['degree'] );
			}

			// Flip image if required.
			if ( false !== $operations['flip'] ) {
				$editor->flip( $operations['flip'][0], $operations['flip'][1] );
			}

			// Save the image and generate metadata.
			$editor->save( $full_size_path );
			$metadata   = wp_generate_attachment_metadata( $id, $full_size_path );
			$image_meta = empty( $metadata['image_meta'] ) ? [] : $metadata['image_meta'];

			// Update attachment metadata with newly generated data.
			wp_update_attachment_metadata( $id, $metadata );

			if ( isset( $image_meta['orientation'] ) && absint( $image_meta['orientation'] ) === 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return array of operations to be done for provided orientation value.
	 *
	 * @param int $orientation EXIF orientation value.
	 *
	 * @return array
	 */
	private function calculate_transformation( $orientation ) {
		$rotate = false;
		$flip   = false;
		$degree = 0;
		switch ( $orientation ) {
			case 2:
				$flip = [ false, true ]; // $flip image along given axis [ horizontal, vertical ]
				break;
			case 3:
				$flip = [ true, true ];
				break;
			case 4:
				$flip = [ true, false ];
				break;
			case 5:
				$degree = -90;
				$rotate = true;
				$flip   = [ false, true ];
				break;
			case 6:
				$degree = -90;
				$rotate = true;
				break;
			case 7:
				$degree = 90;
				$rotate = true;
				$flip   = [ false, true ];
				break;
			case 8:
				$degree = 90;
				$rotate = true;
				break;
			default:
				$degree = 0;
				$rotate = true;
				break;
		}

		return [
			'flip'   => $flip,
			'degree' => $degree,
			'rotate' => $rotate,
		];
	}

	/**
	 * Add compatibility indirection to get_attached_file().
	 *
	 * In WordPress 5.3, behavior changed to account for automatic resizing of
	 * big image files.
	 *
	 * @see https://core.trac.wordpress.org/ticket/47873
	 *
	 * @param int $attachment_id ID of the attachment to get the filepath for.
	 * @return string|false Filepath of the attachment, or false if not found.
	 */
	private function get_attached_file( $attachment_id ) {
		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$filepath = wp_get_original_image_path( $attachment_id );

			if ( false !== $filepath ) {
				return $filepath;
			}
		}

		return get_attached_file( $attachment_id );
	}
}
