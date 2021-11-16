<?php

use WP_CLI\Formatter;

/**
 * Retrieves details on the site's registered post types.
 *
 * Get information on WordPress' built-in and the site's [custom post types](https://developer.wordpress.org/plugins/post-types/).
 *
 * ## EXAMPLES
 *
 *     # Get details about a post type
 *     $ wp post-type get page --fields=name,label,hierarchical --format=json
 *     {"name":"page","label":"Pages","hierarchical":true}
 *
 *     # List post types with 'post' capability type
 *     $ wp post-type list --capability_type=post --fields=name,public
 *     +---------------+--------+
 *     | name          | public |
 *     +---------------+--------+
 *     | post          | 1      |
 *     | attachment    | 1      |
 *     | revision      |        |
 *     | nav_menu_item |        |
 *     +---------------+--------+
 *
 * @package wp-cli
 */
class Post_Type_Command extends WP_CLI_Command {

	private $fields = array(
		'name',
		'label',
		'description',
		'hierarchical',
		'public',
		'capability_type',
	);

	/**
	 * Gets the post counts for each supplied post type.
	 *
	 * @param array $post_types Post types to fetch counts for.
	 * @return array Associative array of post counts keyed by post type.
	 */
	protected function get_counts( $post_types ) {
		global $wpdb;

		if ( count( $post_types ) <= 0 ) {
			return [];
		}

		$query = $wpdb->prepare(
			"SELECT `post_type`, COUNT(*) AS `count`
			FROM $wpdb->posts
			WHERE `post_type` IN (" . implode( ',', array_fill( 0, count( $post_types ), '%s' ) ) . ')
			GROUP BY `post_type`',
			$post_types
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is already prepared above.
		$counts = $wpdb->get_results( $query );

		// Make sure there's a count for every item.
		$counts = array_merge(
			array_fill_keys( $post_types, 0 ),
			wp_list_pluck( $counts, 'count', 'post_type' )
		);

		return $counts;
	}

	/**
	 * Lists registered post types.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see get_post_types() first parameter for a list of available fields).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each post type.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific post type fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each post type:
	 *
	 * * name
	 * * label
	 * * description
	 * * hierarchical
	 * * public
	 * * capability_type
	 *
	 * These fields are optionally available:
	 *
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     # List registered post types
	 *     $ wp post-type list --format=csv
	 *     name,label,description,hierarchical,public,capability_type
	 *     post,Posts,,,1,post
	 *     page,Pages,,1,1,page
	 *     attachment,Media,,,1,post
	 *     revision,Revisions,,,,post
	 *     nav_menu_item,"Navigation Menu Items",,,,post
	 *
	 *     # List post types with 'post' capability type
	 *     $ wp post-type list --capability_type=post --fields=name,public
	 *     +---------------+--------+
	 *     | name          | public |
	 *     +---------------+--------+
	 *     | post          | 1      |
	 *     | attachment    | 1      |
	 *     | revision      |        |
	 *     | nav_menu_item |        |
	 *     +---------------+--------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$fields = $formatter->fields;
		$types  = get_post_types( $assoc_args, 'objects' );
		$counts = [];

		if ( count( $types ) > 0 && in_array( 'count', $fields, true ) ) {
			$counts = $this->get_counts( wp_list_pluck( $types, 'name' ) );
		}

		$types = array_map(
			function( $type ) use ( $counts ) {
					$type->count = isset( $counts[ $type->name ] ) ? $counts[ $type->name ] : 0;
					return $type;
			},
			$types
		);

		$formatter->display_items( $types );
	}

	/**
	 * Gets details about a registered post type.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : Post type slug
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole taxonomy, returns the value of a single field.
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
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for the specified post type:
	 *
	 * * name
	 * * label
	 * * description
	 * * hierarchical
	 * * public
	 * * capability_type
	 * * labels
	 * * cap
	 * * supports
	 *
	 * These fields are optionally available:
	 *
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details about the 'page' post type.
	 *     $ wp post-type get page --fields=name,label,hierarchical --format=json
	 *     {"name":"page","label":"Pages","hierarchical":true}
	 */
	public function get( $args, $assoc_args ) {
		$post_type = get_post_type_object( $args[0] );

		if ( ! $post_type ) {
			WP_CLI::error( "Post type {$args[0]} doesn't exist." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$default_fields = array_merge(
				$this->fields,
				array(
					'labels',
					'cap',
					'supports',
				)
			);

			$assoc_args['fields'] = $default_fields;
		}

		$formatter = $this->get_formatter( $assoc_args );
		$fields    = $formatter->fields;
		$count     = 0;

		if ( in_array( 'count', $fields, true ) ) {
			$count = $this->get_counts( [ $post_type->name ] );
			$count = $count[ $post_type->name ];
		}

		$data = array(
			'name'            => $post_type->name,
			'label'           => $post_type->label,
			'description'     => $post_type->description,
			'hierarchical'    => $post_type->hierarchical,
			'public'          => $post_type->public,
			'capability_type' => $post_type->capability_type,
			'labels'          => $post_type->labels,
			'cap'             => $post_type->cap,
			'supports'        => get_all_post_type_supports( $post_type->name ),
			'count'           => $count,
		);
		$formatter->display_item( $data );
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'post-type' );
	}
}
