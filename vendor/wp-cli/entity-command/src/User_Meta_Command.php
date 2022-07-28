<?php

use WP_CLI\CommandWithMeta;
use WP_CLI\Fetchers\User as UserFetcher;

/**
 * Adds, updates, deletes, and lists user custom fields.
 *
 * ## EXAMPLES
 *
 *     # Add user meta
 *     $ wp user meta add 123 bio "Mary is an WordPress developer."
 *     Success: Added custom field.
 *
 *     # List user meta
 *     $ wp user meta list 123 --keys=nickname,description,wp_capabilities
 *     +---------+-----------------+--------------------------------+
 *     | user_id | meta_key        | meta_value                     |
 *     +---------+-----------------+--------------------------------+
 *     | 123     | nickname        | supervisor                     |
 *     | 123     | description     | Mary is a WordPress developer. |
 *     | 123     | wp_capabilities | {"administrator":true}         |
 *     +---------+-----------------+--------------------------------+
 *
 *     # Update user meta
 *     $ wp user meta update 123 bio "Mary is an awesome WordPress developer."
 *     Success: Updated custom field 'bio'.
 *
 *     # Delete user meta
 *     $ wp user meta delete 123 bio
 *     Success: Deleted custom field.
 */
class User_Meta_Command extends CommandWithMeta {
	protected $meta_type = 'user';

	public function __construct() {
		$this->fetcher = new UserFetcher();
	}

	/**
	 * Lists all metadata associated with a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to get metadata for.
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
	 *   - count
	 *   - yaml
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
	 * ## EXAMPLES
	 *
	 *     # List user meta
	 *     $ wp user meta list 123 --keys=nickname,description,wp_capabilities
	 *     +---------+-----------------+--------------------------------+
	 *     | user_id | meta_key        | meta_value                     |
	 *     +---------+-----------------+--------------------------------+
	 *     | 123     | nickname        | supervisor                     |
	 *     | 123     | description     | Mary is a WordPress developer. |
	 *     | 123     | wp_capabilities | {"administrator":true}         |
	 *     +---------+-----------------+--------------------------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );
		parent::list_( $args, $assoc_args );
	}

	/**
	 * Gets meta field value.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to get metadata for.
	 *
	 * <key>
	 * : The metadata key.
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
	 *     # Get user meta
	 *     $ wp user meta get 123 bio
	 *     Mary is an WordPress developer.
	 */
	public function get( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );
		parent::get( $args, $assoc_args );
	}

	/**
	 * Deletes a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to delete metadata from.
	 *
	 * <key>
	 * : The metadata key.
	 *
	 * [<value>]
	 * : The value to delete. If omitted, all rows with key will deleted.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete user meta
	 *     $ wp user meta delete 123 bio
	 *     Success: Deleted custom field.
	 */
	public function delete( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );
		parent::delete( $args, $assoc_args );
	}

	/**
	 * Adds a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to add metadata for.
	 *
	 * <key>
	 * : The metadata key.
	 *
	 * <value>
	 * : The new metadata value.
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
	 * ## EXAMPLES
	 *
	 *     # Add user meta
	 *     $ wp user meta add 123 bio "Mary is an WordPress developer."
	 *     Success: Added custom field.
	 */
	public function add( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );
		parent::add( $args, $assoc_args );
	}

	/**
	 * Updates a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email, or user ID of the user to update metadata for.
	 *
	 * <key>
	 * : The metadata key.
	 *
	 * <value>
	 * : The new metadata value.
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
	 * ## EXAMPLES
	 *
	 *     # Update user meta
	 *     $ wp user meta update 123 bio "Mary is an awesome WordPress developer."
	 *     Success: Updated custom field 'bio'.
	 *
	 * @alias set
	 */
	public function update( $args, $assoc_args ) {
		$args = $this->replace_login_with_user_id( $args );
		parent::update( $args, $assoc_args );
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
		return add_user_meta( $object_id, $meta_key, $meta_value, $unique );
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
		return update_user_meta( $object_id, $meta_key, $meta_value, $prev_value );
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
		return get_user_meta( $object_id, $meta_key, $single );
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
		return delete_user_meta( $object_id, $meta_key, $meta_value );
	}

	/**
	 * Replaces user_login value with user ID
	 * user meta is a special case that also supports user_login
	 *
	 * @param array
	 * @return array
	 */
	private function replace_login_with_user_id( $args ) {
		$user    = $this->fetcher->get_check( $args[0] );
		$args[0] = $user->ID;
		return $args;
	}

}
