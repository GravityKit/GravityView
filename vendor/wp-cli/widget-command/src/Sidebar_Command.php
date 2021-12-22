<?php

use WP_CLI\Utils;
use WP_CLI\Formatter;

/**
 * Lists registered sidebars.
 *
 * A [sidebar](https://developer.wordpress.org/themes/functionality/sidebars/) is any widgetized area of your theme.
 *
 * ## EXAMPLES
 *
 *     # List sidebars
 *     $ wp sidebar list --fields=name,id --format=csv
 *     name,id
 *     "Widget Area",sidebar-1
 *     "Inactive Widgets",wp_inactive_widgets
 */
class Sidebar_Command extends WP_CLI_Command {

	private $fields = [
		'name',
		'id',
		'description',
	];

	/**
	 * Lists registered sidebars.
	 *
	 * ## OPTIONS
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
	 *   - csv
	 *   - json
	 *   - ids
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each sidebar:
	 *
	 * * name
	 * * id
	 * * description
	 *
	 * These fields are optionally available:
	 *
	 * * class
	 * * before_widget
	 * * after_widget
	 * * before_title
	 * * after_title
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp sidebar list --fields=name,id --format=csv
	 *     name,id
	 *     "Widget Area",sidebar-1
	 *     "Inactive Widgets",wp_inactive_widgets
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		global $wp_registered_sidebars;

		Utils\wp_register_unused_sidebar();

		if ( ! empty( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$sidebars = wp_list_pluck( $wp_registered_sidebars, 'id' );
		} else {
			$sidebars = $wp_registered_sidebars;
		}

		$formatter = new Formatter( $assoc_args, $this->fields );
		$formatter->display_items( $sidebars );
	}

}
