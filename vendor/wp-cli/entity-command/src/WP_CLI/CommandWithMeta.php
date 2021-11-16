<?php

namespace WP_CLI;

use Exception;
use WP_CLI;
use WP_CLI_Command;
use WP_CLI\Entity\RecursiveDataStructureTraverser;
use WP_CLI\Entity\Utils as EntityUtils;

/**
 * Base class for WP-CLI commands that deal with metadata
 *
 * @package wp-cli
 */
abstract class CommandWithMeta extends WP_CLI_Command {

	protected $meta_type;

	/**
	 * List all metadata associated with an object.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID for the object.
	 *
	 * [--keys=<keys>]
	 * : Limit output to metadata of specific keys.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific row fields. Defaults to id,meta_key,meta_value.
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
	 * ---
	 *
	 * [--orderby=<fields>]
	 * : Set orderby which field.
	 * ---
	 * default: id
	 * options:
	 *  - id
	 *  - meta_key
	 *  - meta_value
	 * ---
	 *
	 * [--order=<order>]
	 * : Set ascending or descending order.
	 * ---
	 * default: asc
	 * options:
	 *  - asc
	 *  - desc
	 * ---
	 *
	 * [--unserialize]
	 * : Unserialize meta_value output.
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		list( $object_id ) = $args;

		$keys = ! empty( $assoc_args['keys'] ) ? explode( ',', $assoc_args['keys'] ) : [];

		$object_id = $this->check_object_id( $object_id );

		$metadata = $this->get_metadata( $object_id );
		if ( ! $metadata ) {
			$metadata = [];
		}

		$items = [];
		foreach ( $metadata as $key => $values ) {

			// Skip if not requested
			if ( ! empty( $keys ) && ! in_array( $key, $keys, true ) ) {
				continue;
			}

			foreach ( $values as $item_value ) {

				if ( Utils\get_flag_value( $assoc_args, 'unserialize' ) ) {
					$item_value = maybe_unserialize( $item_value );
				}

				$items[] = (object) [
					"{$this->meta_type}_id" => $object_id,
					'meta_key'              => $key,
					'meta_value'            => $item_value,
				];
			}
		}

		$order   = Utils\get_flag_value( $assoc_args, 'order' );
		$orderby = Utils\get_flag_value( $assoc_args, 'orderby' );

		if ( 'id' !== $orderby ) {

			usort(
				$items,
				function ( $a, $b ) use ( $orderby, $order ) {
					// Sort array.
					return 'asc' === $order
						? $a->$orderby > $b->$orderby
						: $a->$orderby < $b->$orderby;
				}
			);

		} elseif ( 'id' === $orderby && 'desc' === $order ) { // Sort by default descending.
			krsort( $items );
		}

		if ( ! empty( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		} else {
			$fields = $this->get_fields();
		}

		$formatter = new Formatter( $assoc_args, $fields, $this->meta_type );
		$formatter->display_items( $items );

	}

	/**
	 * Get meta field value.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <key>
	 * : The name of the meta field to get.
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 */
	public function get( $args, $assoc_args ) {
		list( $object_id, $meta_key ) = $args;

		$object_id = $this->check_object_id( $object_id );

		$value = $this->get_metadata( $object_id, $meta_key, true );

		if ( '' === $value ) {
			die( 1 );
		}

		WP_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Delete a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * [<key>]
	 * : The name of the meta field to delete.
	 *
	 * [<value>]
	 * : The value to delete. If omitted, all rows with key will deleted.
	 *
	 * [--all]
	 * : Delete all meta for the object.
	 */
	public function delete( $args, $assoc_args ) {
		list( $object_id ) = $args;

		$meta_key   = ! empty( $args[1] ) ? $args[1] : '';
		$meta_value = ! empty( $args[2] ) ? $args[2] : '';

		if ( empty( $meta_key ) && ! Utils\get_flag_value( $assoc_args, 'all' ) ) {
			WP_CLI::error( 'Please specify a meta key, or use the --all flag.' );
		}

		$object_id = $this->check_object_id( $object_id );

		if ( Utils\get_flag_value( $assoc_args, 'all' ) ) {
			$errors = false;
			foreach ( $this->get_metadata( $object_id ) as $meta_key => $values ) {
				$success = $this->delete_metadata( $object_id, $meta_key );
				if ( $success ) {
					WP_CLI::log( "Deleted '{$meta_key}' custom field." );
				} else {
					WP_CLI::warning( "Failed to delete '{$meta_key}' custom field." );
					$errors = true;
				}
			}
			if ( $errors ) {
				WP_CLI::error( 'Failed to delete all custom fields.' );
			} else {
				WP_CLI::success( 'Deleted all custom fields.' );
			}
		} else {
			$success = $this->delete_metadata( $object_id, $meta_key, $meta_value );
			if ( $success ) {
				WP_CLI::success( 'Deleted custom field.' );
			} else {
				WP_CLI::error( 'Failed to delete custom field.' );
			}
		}
	}

	/**
	 * Add a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <key>
	 * : The name of the meta field to create.
	 *
	 * [<value>]
	 * : The value of the meta field. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 */
	public function add( $args, $assoc_args ) {
		list( $object_id, $meta_key ) = $args;

		$meta_value = WP_CLI::get_value_from_arg_or_stdin( $args, 2 );
		$meta_value = WP_CLI::read_value( $meta_value, $assoc_args );

		$object_id = $this->check_object_id( $object_id );

		$meta_value = wp_slash( $meta_value );
		$success    = $this->add_metadata( $object_id, $meta_key, $meta_value );

		if ( $success ) {
			WP_CLI::success( 'Added custom field.' );
		} else {
			WP_CLI::error( 'Failed to add custom field.' );
		}
	}

	/**
	 * Update a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <key>
	 * : The name of the meta field to update.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * @alias set
	 */
	public function update( $args, $assoc_args ) {
		list( $object_id, $meta_key ) = $args;

		$meta_value = WP_CLI::get_value_from_arg_or_stdin( $args, 2 );
		$meta_value = WP_CLI::read_value( $meta_value, $assoc_args );

		$object_id = $this->check_object_id( $object_id );

		$meta_value = sanitize_meta( $meta_key, $meta_value, $this->meta_type );
		$old_value  = sanitize_meta( $meta_key, $this->get_metadata( $object_id, $meta_key, true ), $this->meta_type );

		if ( $meta_value === $old_value ) {
			WP_CLI::success( "Value passed for custom field '{$meta_key}' is unchanged." );
		} else {
			$meta_value = wp_slash( $meta_value );
			$success    = $this->update_metadata( $object_id, $meta_key, $meta_value );

			if ( $success ) {
				WP_CLI::success( "Updated custom field '{$meta_key}'." );
			} else {
				WP_CLI::error( "Failed to update custom field '{$meta_key}'." );
			}
		}

	}

	/**
	 * Get a nested value from a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <key>
	 * : The name of the meta field to get.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to pluck.
	 *
	 * [--format=<format>]
	 * : The output format of the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 *   - yaml
	 */
	public function pluck( $args, $assoc_args ) {
		list( $object_id, $meta_key ) = $args;
		$object_id                    = $this->check_object_id( $object_id );
		$key_path                     = array_map(
			function ( $key ) {
				if ( is_numeric( $key ) && ( (string) intval( $key ) === $key ) ) {
					return (int) $key;
				}
				return $key;
			},
			array_slice( $args, 2 )
		);

		$value = $this->get_metadata( $object_id, $meta_key, true );

		$traverser = new RecursiveDataStructureTraverser( $value );

		try {
			$value = $traverser->get( $key_path );
		} catch ( Exception $exception ) {
			die( 1 );
		}

		WP_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Update a nested value for a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : Patch action to perform.
	 * ---
	 * options:
	 *   - insert
	 *   - update
	 *   - delete
	 * ---
	 *
	 * <id>
	 * : The ID of the object.
	 *
	 * <key>
	 * : The name of the meta field to update.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to patch.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 */
	public function patch( $args, $assoc_args ) {
		list( $action, $object_id, $meta_key ) = $args;
		$object_id                             = $this->check_object_id( $object_id );
		$key_path                              = array_map(
			function( $key ) {
				if ( is_numeric( $key ) && ( (string) intval( $key ) === $key ) ) {
					return (int) $key;
				}
					return $key;
			},
			array_slice( $args, 3 )
		);

		if ( 'delete' === $action ) {
			$patch_value = null;
		} else {
			$stdin_value = EntityUtils::has_stdin()
				? trim( WP_CLI::get_value_from_arg_or_stdin( $args, -1 ) )
				: null;
			$patch_value = ! empty( $stdin_value )
				? WP_CLI::read_value( $stdin_value, $assoc_args )
				: WP_CLI::read_value( array_pop( $key_path ), $assoc_args );
		}

		/* Need to make a copy of $current_meta_value here as it is modified by reference */
		$current_meta_value = sanitize_meta( $meta_key, $this->get_metadata( $object_id, $meta_key, true ), $this->meta_type );
		$old_meta_value     = $current_meta_value;
		if ( is_object( $current_meta_value ) ) {
			$old_meta_value = clone $current_meta_value;
		}

		$traverser = new RecursiveDataStructureTraverser( $current_meta_value );

		try {
			$traverser->$action( $key_path, $patch_value );
		} catch ( Exception $exception ) {
			WP_CLI::error( $exception->getMessage() );
		}

		$patched_meta_value = sanitize_meta( $meta_key, $traverser->value(), $this->meta_type );

		if ( $patched_meta_value === $old_meta_value ) {
			WP_CLI::success( "Value passed for custom field '{$meta_key}' is unchanged." );
		} else {
			$slashed = wp_slash( $patched_meta_value );
			$success = $this->update_metadata( $object_id, $meta_key, $slashed );

			if ( $success ) {
				WP_CLI::success( "Updated custom field '{$meta_key}'." );
			} else {
				WP_CLI::error( "Failed to update custom field '{$meta_key}'." );
			}
		}
	}

	/**
	 * Wrapper method for add_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param bool   $unique     Optional, default is false. Whether the
	 *                           specified metadata key should be unique for the
	 *                           object. If true, and the object already has a
	 *                           value for the specified metadata key, no change
	 *                           will be made.
	 *
	 * @return int|false The meta ID on success, false on failure.
	 */
	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( $this->meta_type, $object_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Wrapper method for update_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. If specified, only update existing
	 *                           metadata entries with the specified value.
	 *                           Otherwise, update all entries.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful
	 *                  update, false on failure.
	 */
	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( $this->meta_type, $object_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Wrapper method for get_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id ID of the object the metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified,
	 *                          retrieve all metadata for the specified object.
	 * @param bool   $single    Optional, default is false. If true, return only
	 *                          the first value of the specified meta_key. This
	 *                          parameter has no effect if meta_key is not
	 *                          specified.
	 *
	 * @return mixed Single metadata value, or array of values.
	 */
	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		return get_metadata( $this->meta_type, $object_id, $meta_key, $single );
	}

	/**
	 * Wrapper method for delete_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object metadata is for
	 * @param string $meta_key   Metadata key
	 * @param mixed $meta_value  Optional. Metadata value. Must be serializable
	 *                           if non-scalar. If specified, only delete
	 *                           metadata entries with this value. Otherwise,
	 *                           delete all entries with the specified meta_key.
	 *                           Pass `null, `false`, or an empty string to skip
	 *                           this check. For backward compatibility, it is
	 *                           not possible to pass an empty string to delete
	 *                           those entries with an empty string for a value.
	 *
	 * @return bool True on successful delete, false on failure.
	 */
	protected function delete_metadata( $object_id, $meta_key, $meta_value = '' ) {
		return delete_metadata( $this->meta_type, $object_id, $meta_key, $meta_value, false );
	}

	/**
	 * Get the fields for this object's meta
	 *
	 * @return array
	 */
	private function get_fields() {
		return [
			"{$this->meta_type}_id",
			'meta_key',
			'meta_value',
		];
	}

	/**
	 * Check that the object ID exists
	 *
	 * @param int
	 */
	protected function check_object_id( $object_id ) {
		// Needs to be set in subclass
		return $object_id;
	}

}
