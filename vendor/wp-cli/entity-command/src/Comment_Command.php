<?php

use WP_CLI\CommandWithDBObject;
use WP_CLI\Fetchers\Comment as CommentFetcher;
use WP_CLI\Utils;

/**
 * Creates, updates, deletes, and moderates comments.
 *
 * ## EXAMPLES
 *
 *     # Create a new comment.
 *     $ wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
 *     Success: Created comment 932.
 *
 *     # Update an existing comment.
 *     $ wp comment update 123 --comment_author='That Guy'
 *     Success: Updated comment 123.
 *
 *     # Delete an existing comment.
 *     $ wp comment delete 1337 --force
 *     Success: Deleted comment 1337.
 *
 *     # Delete all spam comments.
 *     $ wp comment delete $(wp comment list --status=spam --format=ids)
 *     Success: Deleted comment 264.
 *     Success: Deleted comment 262.
 *
 * @package wp-cli
 */
class Comment_Command extends CommandWithDBObject {

	protected $obj_type   = 'comment';
	protected $obj_id_key = 'comment_ID';
	protected $obj_fields = [
		'comment_ID',
		'comment_post_ID',
		'comment_date',
		'comment_approved',
		'comment_author',
		'comment_author_email',
	];

	public function __construct() {
		$this->fetcher = new CommentFetcher();
	}

	/**
	 * Creates a new comment.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new comment. See wp_insert_comment().
	 *
	 * [--porcelain]
	 * : Output just the new comment id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create comment.
	 *     $ wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
	 *     Success: Created comment 932.
	 */
	public function create( $args, $assoc_args ) {
		$assoc_args = wp_slash( $assoc_args );
		parent::_create(
			$args,
			$assoc_args,
			function ( $params ) {
				if ( isset( $params['comment_post_ID'] ) ) {
					$post_id = $params['comment_post_ID'];
					$post    = get_post( $post_id );
					if ( ! $post ) {
						return new WP_Error( 'no_post', "Can't find post {$post_id}." );
					}
				} else {
					// Make sure it's set for older WP versions else get undefined PHP notice.
					$params['comment_post_ID'] = 0;
				}

				// We use wp_insert_comment() instead of wp_new_comment() to stay at a low level and
				// avoid wp_die() formatted messages or notifications
				$comment_id = wp_insert_comment( $params );

				if ( ! $comment_id ) {
					return new WP_Error( 'db_error', 'Could not create comment.' );
				}

				return $comment_id;
			}
		);
	}

	/**
	 * Updates one or more comments.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of comments to update.
	 *
	 * --<field>=<value>
	 * : One or more fields to update. See wp_update_comment().
	 *
	 * ## EXAMPLES
	 *
	 *     # Update comment.
	 *     $ wp comment update 123 --comment_author='That Guy'
	 *     Success: Updated comment 123.
	 */
	public function update( $args, $assoc_args ) {
		$assoc_args = wp_slash( $assoc_args );
		parent::_update(
			$args,
			$assoc_args,
			function ( $params ) {
				if ( ! wp_update_comment( $params ) ) {
					return new WP_Error( 'Could not update comment.' );
				}

				return true;
			}
		);
	}

	/**
	 * Generates some number of new dummy comments.
	 *
	 * Creates a specified number of new comments with dummy data.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many comments to generate?
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--post_id=<post-id>]
	 * : Assign comments to a specific post.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *   - progress
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate comments for the given post.
	 *     $ wp comment generate --format=ids --count=3 --post_id=123
	 *     138 139 140
	 *
	 *     # Add meta to every generated comment.
	 *     $ wp comment generate --format=ids --count=3 | xargs -d ' ' -I % wp comment meta add % foo bar
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 */
	public function generate( $args, $assoc_args ) {

		$defaults   = [
			'count'   => 100,
			'post_id' => 0,
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$format = Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = Utils\make_progress_bar( 'Generating comments', $assoc_args['count'] );
		}

		$comment_count = wp_count_comments();
		$total         = (int) $comment_count->total_comments;
		$limit         = $total + $assoc_args['count'];

		for ( $index = $total; $index < $limit; $index++ ) {
			$comment_id = wp_insert_comment(
				[
					'comment_content' => "Comment {$index}",
					'comment_post_ID' => $assoc_args['post_id'],
				]
			);
			if ( 'progress' === $format ) {
				$notify->tick();
			} elseif ( 'ids' === $format ) {
				echo $comment_id;
				if ( $index < $limit - 1 ) {
					echo ' ';
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		}

	}

	/**
	 * Gets the data of a single comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The comment to get.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole comment, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get comment.
	 *     $ wp comment get 21 --field=content
	 *     Thanks for all the comments, everyone!
	 */
	public function get( $args, $assoc_args ) {
		$comment_id = (int) $args[0];
		$comment    = get_comment( $comment_id );
		if ( empty( $comment ) ) {
			WP_CLI::error( 'Invalid comment ID.' );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$comment_array        = get_object_vars( $comment );
			$assoc_args['fields'] = array_keys( $comment_array );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $comment );
	}

	/**
	 * Gets a list of comments.
	 *
	 * Display comments based on all arguments supported by
	 * [WP_Comment_Query()](https://developer.wordpress.org/reference/classes/WP_Comment_Query/__construct/).
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Comment_Query.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each comment.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each comment:
	 *
	 * * comment_ID
	 * * comment_post_ID
	 * * comment_date
	 * * comment_approved
	 * * comment_author
	 * * comment_author_email
	 *
	 * These fields are optionally available:
	 *
	 * * comment_author_url
	 * * comment_author_IP
	 * * comment_date_gmt
	 * * comment_content
	 * * comment_karma
	 * * comment_agent
	 * * comment_type
	 * * comment_parent
	 * * user_id
	 * * url
	 *
	 * ## EXAMPLES
	 *
	 *     # List comment IDs.
	 *     $ wp comment list --field=ID
	 *     22
	 *     23
	 *     24
	 *
	 *     # List comments of a post.
	 *     $ wp comment list --post_id=1 --fields=ID,comment_date,comment_author
	 *     +------------+---------------------+----------------+
	 *     | comment_ID | comment_date        | comment_author |
	 *     +------------+---------------------+----------------+
	 *     | 1          | 2015-06-20 09:00:10 | Mr WordPress   |
	 *     +------------+---------------------+----------------+
	 *
	 *     # List approved comments.
	 *     $ wp comment list --number=3 --status=approve --fields=ID,comment_date,comment_author
	 *     +------------+---------------------+----------------+
	 *     | comment_ID | comment_date        | comment_author |
	 *     +------------+---------------------+----------------+
	 *     | 1          | 2015-06-20 09:00:10 | Mr WordPress   |
	 *     | 30         | 2013-03-14 12:35:07 | John Doe       |
	 *     | 29         | 2013-03-14 11:56:08 | Jane Doe       |
	 *     +------------+---------------------+----------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$assoc_args['fields'] = 'comment_ID';
		}

		$assoc_args = self::process_csv_arguments_to_arrays( $assoc_args );

		if ( 'count' === $formatter->format ) {
			$assoc_args['count'] = true;
		}

		if ( ! empty( $assoc_args['comment__in'] )
			&& ! empty( $assoc_args['orderby'] )
			&& 'comment__in' === $assoc_args['orderby']
			&& Utils\wp_version_compare( '4.4', '<' ) ) {
			$comments = [];
			foreach ( $assoc_args['comment__in'] as $comment_id ) {
				$comment = get_comment( $comment_id );
				if ( $comment ) {
					$comments[] = $comment;
				} else {
					WP_CLI::warning( "Invalid comment {$comment_id}." );
				}
			}
		} else {
			$query    = new WP_Comment_Query();
			$comments = $query->query( $assoc_args );
		}

		if ( 'count' === $formatter->format ) {
			echo $comments;
		} else {
			if ( 'ids' === $formatter->format ) {
				$comments = wp_list_pluck( $comments, 'comment_ID' );
			} elseif ( is_array( $comments ) ) {
				$comments = array_map(
					function( $comment ) {
							$comment->url = get_comment_link( $comment->comment_ID );
							return $comment;
					},
					$comments
				);
			}
			$formatter->display_items( $comments );
		}
	}

	/**
	 * Deletes a comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of comments to delete.
	 *
	 * [--force]
	 * : Skip the trash bin.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete comment.
	 *     $ wp comment delete 1337 --force
	 *     Success: Deleted comment 1337.
	 *
	 *     # Delete multiple comments.
	 *     $ wp comment delete 1337 2341 --force
	 *     Success: Deleted comment 1337.
	 *     Success: Deleted comment 2341.
	 */
	public function delete( $args, $assoc_args ) {
		parent::_delete(
			$args,
			$assoc_args,
			function ( $comment_id, $assoc_args ) {
				$force = Utils\get_flag_value( $assoc_args, 'force' );

				$status = wp_get_comment_status( $comment_id );
				$result = wp_delete_comment( $comment_id, $force );

				if ( ! $result ) {
					return [ 'error', "Failed deleting comment {$comment_id}." ];
				}

				$verb = ( $force || 'trash' === $status ) ? 'Deleted' : 'Trashed';
				return [ 'success', "{$verb} comment {$comment_id}." ];
			}
		);
	}

	private function call( $args, $status, $success, $failure ) {
		$comment_id = absint( $args );

		$func = "wp_{$status}_comment";

		if ( ! $func( $comment_id ) ) {
			WP_CLI::error( sprintf( $failure, "comment {$comment_id}" ) );
		}
		WP_CLI::success( sprintf( $success, "comment {$comment_id}" ) );
	}

	private function set_status( $args, $status, $success ) {
		$comment = $this->fetcher->get_check( $args );

		$result = wp_set_comment_status( $comment->comment_ID, $status, true );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result );
		}

		WP_CLI::success( "{$success} comment {$comment->comment_ID}." );
	}

	/**
	 * Warns if `$_SERVER['SERVER_NAME']` not set as used in email from-address sent to post author in `wp_notify_postauthor()`.
	 */
	private function check_server_name() {
		if ( empty( $_SERVER['SERVER_NAME'] ) ) {
			WP_CLI::warning( 'Site url not set - defaulting to \'example.com\'. Any notification emails sent to post author may appear to come from \'example.com\'.' );
			$_SERVER['SERVER_NAME'] = 'example.com';
		}
	}

	/**
	 * Trashes a comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The IDs of the comments to trash.
	 *
	 * ## EXAMPLES
	 *
	 *     # Trash comment.
	 *     $ wp comment trash 1337
	 *     Success: Trashed comment 1337.
	 */
	public function trash( $args, $assoc_args ) {
		foreach ( $args as $id ) {
			$this->call( $id, __FUNCTION__, 'Trashed %s.', 'Failed trashing %s.' );
		}
	}

	/**
	 * Untrashes a comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The IDs of the comments to untrash.
	 *
	 * ## EXAMPLES
	 *
	 *     # Untrash comment.
	 *     $ wp comment untrash 1337
	 *     Success: Untrashed comment 1337.
	 */
	public function untrash( $args, $assoc_args ) {
		$this->check_server_name();
		foreach ( $args as $id ) {
			$this->call( $id, __FUNCTION__, 'Untrashed %s.', 'Failed untrashing %s.' );
		}
	}

	/**
	 * Marks a comment as spam.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The IDs of the comments to mark as spam.
	 *
	 * ## EXAMPLES
	 *
	 *     # Spam comment.
	 *     $ wp comment spam 1337
	 *     Success: Marked as spam comment 1337.
	 */
	public function spam( $args, $assoc_args ) {
		foreach ( $args as $id ) {
			$this->call( $id, __FUNCTION__, 'Marked %s as spam.', 'Failed marking %s as spam.' );
		}
	}

	/**
	 * Unmarks a comment as spam.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The IDs of the comments to unmark as spam.
	 *
	 * ## EXAMPLES
	 *
	 *     # Unspam comment.
	 *     $ wp comment unspam 1337
	 *     Success: Unspammed comment 1337.
	 */
	public function unspam( $args, $assoc_args ) {
		$this->check_server_name();
		foreach ( $args as $id ) {
			$this->call( $id, __FUNCTION__, 'Unspammed %s.', 'Failed unspamming %s.' );
		}
	}

	/**
	 * Approves a comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The IDs of the comments to approve.
	 *
	 * ## EXAMPLES
	 *
	 *     # Approve comment.
	 *     $ wp comment approve 1337
	 *     Success: Approved comment 1337.
	 */
	public function approve( $args, $assoc_args ) {
		$this->check_server_name();
		foreach ( $args as $id ) {
			$this->set_status( $id, 'approve', 'Approved' );
		}
	}

	/**
	 * Unapproves a comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The IDs of the comments to unapprove.
	 *
	 * ## EXAMPLES
	 *
	 *     # Unapprove comment.
	 *     $ wp comment unapprove 1337
	 *     Success: Unapproved comment 1337.
	 */
	public function unapprove( $args, $assoc_args ) {
		$this->check_server_name();
		foreach ( $args as $id ) {
			$this->set_status( $id, 'hold', 'Unapproved' );
		}
	}

	/**
	 * Counts comments, on whole blog or on a given post.
	 *
	 * ## OPTIONS
	 *
	 * [<post-id>]
	 * : The ID of the post to count comments in.
	 *
	 * ## EXAMPLES
	 *
	 *     # Count comments on whole blog.
	 *     $ wp comment count
	 *     approved:        33
	 *     spam:            3
	 *     trash:           1
	 *     post-trashed:    0
	 *     all:             34
	 *     moderated:       1
	 *     total_comments:  37
	 *
	 *     # Count comments in a post.
	 *     $ wp comment count 42
	 *     approved:        19
	 *     spam:            0
	 *     trash:           0
	 *     post-trashed:    0
	 *     all:             19
	 *     moderated:       0
	 *     total_comments:  19
	 */
	public function count( $args, $assoc_args ) {
		$post_id = Utils\get_flag_value( $args, 0, 0 );

		$count = wp_count_comments( $post_id );

		// Move total_comments to the end of the object
		$total = $count->total_comments;
		unset( $count->total_comments );
		$count->total_comments = $total;

		foreach ( $count as $status => $count ) {
			WP_CLI::line( str_pad( "$status:", 17 ) . $count );
		}
	}

	/**
	 * Recalculates the comment_count value for one or more posts.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : IDs for one or more posts to update.
	 *
	 * ## EXAMPLES
	 *
	 *     # Recount comment for the post.
	 *     $ wp comment recount 123
	 *     Updated post 123 comment count to 67.
	 */
	public function recount( $args ) {
		foreach ( $args as $id ) {
			wp_update_comment_count( $id );
			$post = get_post( $id );
			if ( $post ) {
				WP_CLI::log( "Updated post {$post->ID} comment count to {$post->comment_count}." );
			} else {
				WP_CLI::warning( "Post {$post->ID} doesn't exist." );
			}
		}
	}

	/**
	 * Gets the status of a comment.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the comment to check.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get status of comment.
	 *     $ wp comment status 1337
	 *     approved
	 */
	public function status( $args, $assoc_args ) {
		list( $comment_id ) = $args;

		$status = wp_get_comment_status( $comment_id );

		if ( false === $status ) {
			WP_CLI::error( "Could not check status of comment {$comment_id}." );
		} else {
			WP_CLI::line( $status );
		}
	}

	/**
	 * Verifies whether a comment exists.
	 *
	 * Displays a success message if the comment does exist.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the comment to check.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether comment exists.
	 *     $ wp comment exists 1337
	 *     Success: Comment with ID 1337 exists.
	 */
	public function exists( $args ) {
		if ( $this->fetcher->get( $args[0] ) ) {
			WP_CLI::success( "Comment with ID {$args[0]} exists." );
		}
	}
}
