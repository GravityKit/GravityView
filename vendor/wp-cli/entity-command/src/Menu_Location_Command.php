<?php

use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Assigns, removes, and lists a menu's locations.
 *
 * ## EXAMPLES
 *
 *     # List available menu locations
 *     $ wp menu location list
 *     +----------+-------------------+
 *     | location | description       |
 *     +----------+-------------------+
 *     | primary  | Primary Menu      |
 *     | social   | Social Links Menu |
 *     +----------+-------------------+
 *
 *     # Assign the 'primary-menu' menu to the 'primary' location
 *     $ wp menu location assign primary-menu primary
 *     Success: Assigned location to menu.
 *
 *     # Remove the 'primary-menu' menu from the 'primary' location
 *     $ wp menu location remove primary-menu primary
 *     Success: Removed location from menu.
 */
class Menu_Location_Command extends WP_CLI_Command {

	/**
	 * Lists locations for the current theme.
	 *
	 * ## OPTIONS
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
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each location:
	 *
	 * * name
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp menu location list
	 *     +----------+-------------------+
	 *     | location | description       |
	 *     +----------+-------------------+
	 *     | primary  | Primary Menu      |
	 *     | social   | Social Links Menu |
	 *     +----------+-------------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$locations     = get_registered_nav_menus();
		$location_objs = [];
		foreach ( $locations as $location => $description ) {
			$location_obj              = new stdClass();
			$location_obj->location    = $location;
			$location_obj->description = $description;
			$location_objs[]           = $location_obj;
		}

		$formatter = new Formatter( $assoc_args, [ 'location', 'description' ] );

		if ( 'ids' === $formatter->format ) {
			$ids = array_map(
				function( $o ) {
					return $o->location;
				},
				$location_objs
			);
			$formatter->display_items( $ids );
		} else {
			$formatter->display_items( $location_objs );
		}
	}

	/**
	 * Assigns a location to a menu.
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The name, slug, or term ID for the menu.
	 *
	 * <location>
	 * : Location's slug.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp menu location assign primary-menu primary
	 *     Success: Assigned location primary to menu primary-menu.
	 *
	 * @subcommand assign
	 */
	public function assign( $args, $assoc_args ) {

		list( $menu, $location ) = $args;

		$menu_obj = wp_get_nav_menu_object( $menu );
		if ( ! $menu_obj ) {
			WP_CLI::error( "Invalid menu {$menu}." );
		}

		$locations = get_registered_nav_menus();
		if ( ! array_key_exists( $location, $locations ) ) {
			WP_CLI::error( "Invalid location {$location}." );
		}

		$locations              = get_nav_menu_locations();
		$locations[ $location ] = $menu_obj->term_id;

		set_theme_mod( 'nav_menu_locations', $locations );

		WP_CLI::success( "Assigned location {$location} to menu {$menu}." );
	}

	/**
	 * Removes a location from a menu.
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The name, slug, or term ID for the menu.
	 *
	 * <location>
	 * : Location's slug.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp menu location remove primary-menu primary
	 *     Success: Removed location from menu.
	 *
	 * @subcommand remove
	 */
	public function remove( $args, $assoc_args ) {

		list( $menu, $location ) = $args;

		$menu = wp_get_nav_menu_object( $menu );
		if ( ! $menu || is_wp_error( $menu ) ) {
			WP_CLI::error( 'Invalid menu.' );
		}

		$locations = get_nav_menu_locations();
		if ( Utils\get_flag_value( $locations, $location ) !== $menu->term_id ) {
			WP_CLI::error( "Menu isn't assigned to location." );
		}

		$locations[ $location ] = 0;
		set_theme_mod( 'nav_menu_locations', $locations );

		WP_CLI::success( 'Removed location from menu.' );

	}

}
