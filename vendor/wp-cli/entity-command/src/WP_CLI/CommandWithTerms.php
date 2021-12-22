<?php

namespace WP_CLI;

use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Utils;

/**
 * Base class for WP-CLI commands that deal with terms
 *
 * @package wp-cli
 */
abstract class CommandWithTerms extends WP_CLI_Command {

	/**
	 * @var string $object_type WordPress' expected name for the object.
	 */
	protected $obj_type;

	/**
	 * @var string $object_id WordPress' object id.
	 */
	protected $obj_id;

	/**
	 * @var array $obj_fields Default fields to display for each object.
	 */
	protected $obj_fields = [
		'term_id',
		'name',
		'slug',
		'taxonomy',
	];

	/**
	 * List all terms associated with an object.
	 *
	 * <id>
	 * : ID for the object.
	 *
	 * <taxonomy>...
	 * : One or more taxonomies to list.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each term.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific row fields.
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
	 *   - count
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each term:
	 *
	 * * term_id
	 * * name
	 * * slug
	 * * taxonomy
	 *
	 * These fields are optionally available:
	 *
	 * * term_taxonomy_id
	 * * description
	 * * term_group
	 * * parent
	 * * count
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$defaults   = [
			'format' => 'table',
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$object_id      = array_shift( $args );
		$taxonomy_names = $args;
		$taxonomy_args  = [];

		$this->set_obj_id( $object_id );

		foreach ( $taxonomy_names as $taxonomy ) {
			$this->taxonomy_exists( $taxonomy );
		}

		if ( 'ids' === $assoc_args['format'] ) {
			$taxonomy_args['fields'] = 'ids';
		}

		$items = wp_get_object_terms( $object_id, $taxonomy_names, $taxonomy_args );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );

	}


	/**
	 * Remove a term from an object.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <taxonomy>
	 * : The name of the term's taxonomy.
	 *
	 * [<term>...]
	 * : The name of the term or terms to be removed from the object.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * [--all]
	 * : Remove all terms from the object.
	 */
	public function remove( $args, $assoc_args ) {
		$object_id = array_shift( $args );
		$taxonomy  = array_shift( $args );
		$terms     = $args;

		$this->set_obj_id( $object_id );

		$this->taxonomy_exists( $taxonomy );

		$field = Utils\get_flag_value( $assoc_args, 'by' );
		if ( $field ) {
			$terms = $this->prepare_terms( $field, $terms, $taxonomy );
		}

		if ( Utils\get_flag_value( $assoc_args, 'all' ) ) {

			// No need to specify terms while removing all terms.
			if ( $terms ) {
				WP_CLI::error( 'No need to specify terms while removing all terms.' );
			}

			// Remove all set categories from post.
			$result = wp_delete_object_term_relationships( $object_id, $taxonomy );

			$message = 'Removed all terms.';
			if ( 'category' === $taxonomy ) {

				// Set default category to post.
				$default_category = (int) get_option( 'default_category' );
				$default_category = ( ! empty( $default_category ) ) ? $default_category : 1;
				$default_category = wp_set_object_terms( $object_id, [ $default_category ], $taxonomy, true );

				if ( is_wp_error( $default_category ) ) {
					WP_CLI::error( 'Failed to set default term.' );
				}

				$message = 'Removed all terms and set default term.';
			}

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( 'Failed to remove all terms.' );
			}

			WP_CLI::success( $message );

			return;
		}

		// Abort if no terms are specified.
		if ( ! $terms ) {
			WP_CLI::error( 'Please specify one or more terms, or use --all.' );
		}

		// Remove term from post.
		$result = wp_remove_object_terms( $object_id, $terms, $taxonomy );

		$label = count( $terms ) > 1 ? 'terms' : 'term';

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( "Failed to remove {$label}." );
		}

		WP_CLI::success( "Removed {$label}." );
	}

	/**
	 * Add a term to an object.
	 *
	 * Append the term to the existing set of terms on the object.
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <taxonomy>
	 * : The name of the taxonomy type to be added.
	 *
	 * <term>...
	 * : The slug of the term or terms to be added.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 */
	public function add( $args, $assoc_args ) {
		$object_id = array_shift( $args );
		$taxonomy  = array_shift( $args );
		$terms     = $args;

		$this->set_obj_id( $object_id );

		$this->taxonomy_exists( $taxonomy );

		$field = Utils\get_flag_value( $assoc_args, 'by' );
		if ( $field ) {
			$terms = $this->prepare_terms( $field, $terms, $taxonomy );
		}
		$result = wp_set_object_terms( $object_id, $terms, $taxonomy, true );

		$label = count( $terms ) > 1 ? 'terms' : 'term';
		if ( ! is_wp_error( $result ) ) {
			WP_CLI::success( "Added {$label}." );
		} else {
			WP_CLI::error( "Failed to add {$label}." );
		}
	}

	/**
	 * Set object terms.
	 *
	 * Replaces existing terms on the object.
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <taxonomy>
	 * : The name of the taxonomy type to be updated.
	 *
	 * <term>...
	 * : The slug of the term or terms to be updated.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 */
	public function set( $args, $assoc_args ) {
		$object_id = array_shift( $args );
		$taxonomy  = array_shift( $args );
		$terms     = $args;

		$this->set_obj_id( $object_id );

		$this->taxonomy_exists( $taxonomy );

		$field = Utils\get_flag_value( $assoc_args, 'by' );
		if ( $field ) {
			$terms = $this->prepare_terms( $field, $terms, $taxonomy );
		}
		$result = wp_set_object_terms( $object_id, $terms, $taxonomy, false );

		$label = count( $terms ) > 1 ? 'terms' : 'term';
		if ( ! is_wp_error( $result ) ) {
			WP_CLI::success( "Set {$label}." );
		} else {
			WP_CLI::error( "Failed to set {$label}." );
		}
	}

	/**
	 * Check if taxonomy exists
	 *
	 * @param $taxonomy
	 */
	protected function taxonomy_exists( $taxonomy ) {

		$taxonomy_names = get_object_taxonomies( $this->get_object_type() );

		if ( ! in_array( $taxonomy, $taxonomy_names, true ) ) {
			WP_CLI::error( "Invalid taxonomy {$taxonomy}." );
		}
	}

	/**
	 * Prepare terms if `--by=<field>` flag is used
	 *
	 * @param array $terms
	 * @param string $field
	 * @param string $taxonomy
	 */
	protected function prepare_terms( $field, $terms, $taxonomy ) {
		if ( 'id' === $field ) {
			$new_terms = [];
			foreach ( $terms as $term_id ) {
				$term = get_term_by( 'term_id', $term_id, $taxonomy );
				if ( $term ) {
					$new_terms[] = $term->slug;
				}
			}
			$terms = $new_terms;
		}
		return $terms;
	}

	/**
	 * Set obj_id Class variable
	 *
	 * @param string $obj_id
	 */
	protected function set_obj_id( $obj_id ) {
		$this->obj_id = $obj_id;
	}

	/**
	 * Get obj_id Class variable
	 *
	 * @return string
	 */
	protected function get_obj_id() {
		return $this->obj_id;
	}


	/**
	 * Get obj_type Class variable
	 *
	 * @return string $obj_type
	 */
	protected function get_object_type() {
		return $this->obj_type;
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 *
	 * @return WP_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->obj_fields, $this->obj_type );
	}
}

