<?php

/**
 * Handle caching using transients for GravityView
 */
class GravityView_Cache {

	const BLACKLIST_OPTION_NAME = 'gravityview_cache_blacklist';

	/**
	 * Form ID, or array of Form IDs
	 *
	 * @var mixed
	 */
	protected $form_ids;

	/**
	 * Extra request parameters used to generate the query. This is used to generate the unique transient key.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * The transient key used to store the cached item. 45 characters long.
	 *
	 * @var string
	 */
	private $key = '';

	/**
	 * @since 1.13.1
	 * @var array Columns in the database for leads
	 */
	private $lead_db_columns = array( 'id', 'form_id', 'post_id', 'date_created', 'is_starred', 'is_read', 'ip', 'source_url', 'user_agent', 'currency', 'payment_status', 'payment_date', 'payment_amount', 'transaction_id', 'is_fulfilled', 'created_by', 'transaction_type', 'status' );

	/**
	 *
	 * @param array|int $form_ids Form ID or array of form IDs used in a request
	 * @param array $args Extra request parameters used to generate the query. This is used to generate the unique transient key.
	 */
	function __construct( $form_ids = NULL, $args = array() ) {

		$this->add_hooks();

		if ( ! is_null( $form_ids ) ) {

			$this->form_ids = $form_ids;

			$this->args = $args;

			$this->set_key();
		}
	}

	/**
	 * Add actions for clearing out caches when entries are updated.
	 */
	function add_hooks() {

		// Schedule cleanup of expired transients
		add_action( 'wp', array( $this, 'schedule_transient_cleanup' ) );

		// Hook in to the scheduled cleanup, if scheduled
		add_action( 'gravityview-expired-transients', array( $this, 'delete_expired_transients' ) );

		// Trigger this when you need to prevent any results from being cached with forms that have been modified
		add_action( 'gravityview_clear_form_cache', array( $this, 'blacklist_add' ) );

		/**
		 * @since 1.14
		 */
		add_action( 'gravityview_clear_entry_cache', array( $this, 'entry_status_changed' ) );

		add_action( 'gform_after_update_entry', array( $this, 'entry_updated' ), 10, 2 );

		add_action( 'gform_entry_created', array( $this, 'entry_created' ), 10, 2 );

		add_action( 'gform_post_add_entry', array( $this, 'entry_added' ), 10, 2 );

		/**
		 * @see RGFormsModel::update_lead_property() Trigger when any entry property changes
		 */
		foreach( $this->lead_db_columns as $column ) {
			add_action( 'gform_update_' . $column, array( $this, 'entry_status_changed' ), 10, 3 );
		}

		add_action( 'gform_delete_lead', array( $this, 'entry_status_changed' ), 10 );
	}

	/**
	 * Force refreshing a cache when an entry is deleted.
	 *
	 * The `gform_delete_lead` action is called before the lead is deleted; we fetch the entry to find out the form ID so it can be added to the blacklist.
	 *
	 * @since  1.5.1
	 *
	 * @param  int $lead_id Entry ID
	 * @param  string $property_value Previous value of the lead status passed by gform_update_status hook
	 * @param  string $previous_value Previous value of the lead status passed by gform_update_status hook
	 *
	 * @return void
	 */
	public function entry_status_changed( $lead_id, $property_value = '', $previous_value = '' ) {

		$entry = GFAPI::get_entry( $lead_id );

		if ( is_wp_error( $entry ) ) {

			gravityview()->log->error( 'Could not retrieve entry {entry_id} to delete it: {error}', array( 'entry_id' => $lead_id, 'error' => $entry->get_error_message() ) );

			return;
		}

		gravityview()->log->debug( 'adding form {form_id} to blacklist because entry #{lead_id} was deleted', array( 'form_id' => $entry['form_id'], 'entry_id' => $lead_id, 'data' => array( 'value' => $property_value, 'previous' => $previous_value ) ) );

		$this->blacklist_add( $entry['form_id'] );
	}

	/**
	 * When an entry is updated, add the entry's form to the cache blacklist
	 *
	 * @param  array $form GF form array
	 * @param  int $lead_id Entry ID
	 *
	 * @return void
	 */
	public function entry_updated( $form, $lead_id ) {

		gravityview()->log->debug(' adding form {form_id} to blacklist because entry #{entry_id} was updated', array( 'form_id' => $form['id'], 'entry_id' => $lead_id ) );

		$this->blacklist_add( $form['id'] );
	}

	/**
	 * When an entry is created, add the entry's form to the cache blacklist
	 *
	 * We don't want old caches; when an entry is added, we want to clear the cache.
	 *
	 * @param  array $entry GF entry array
	 * @param  array $form GF form array
	 *
	 * @return void
	 */
	public function entry_created( $entry, $form ) {

		gravityview()->log->debug( 'adding form {form_id} to blacklist because entry #{entry_id} was created', array( 'form_id' => $form['id'], 'entry_id' => $entry['id'] ) );

		$this->blacklist_add( $form['id'] );
	}

	/**
	 * Clear the cache when entries are added via GFAPI::add_entry().
	 *
	 * @param array $entry The GF Entry array
	 * @param array $form  The GF Form array
	 *
	 * @return void
	 */
	public function entry_added( $entry, $form ) {
		if ( is_wp_error( $entry ) ) {
			return;
		}

		gravityview()->log->debug( 'adding form {form_id} to blacklist because entry #{entry_id} was added', array( 'form_id' => $form['id'], 'entry_id' => $entry['id'] ) );

		$this->blacklist_add( $form['id'] );
	}

	/**
	 * Calculate the prefix based on the Form IDs
	 *
	 * @param  int|array $form_ids Form IDs to generate prefix for
	 *
	 * @return string           Prefix for the cache string used in set_key()
	 */
	protected function get_cache_key_prefix( $form_ids = NULL ) {

		if ( is_null( $form_ids ) ) {
			$form_ids = $this->form_ids;
		}

		// Normally just one form, but supports multiple forms
		//
		// Array of IDs 12, 5, 14 would result in `f:12-f:5-f:14`
		$forms = 'f:' . implode( '-f:', (array) $form_ids );

		// Prefix for transient keys
		// Now the prefix would be: `gv-cache-f:12-f:5-f:14-`
		return 'gv-cache-' . $forms . '-';

	}

	/**
	 * Set the transient key based on the form IDs and the arguments passed to the class
	 */
	protected function set_key() {

		// Don't set key if no forms have been set.
		if ( empty( $this->form_ids ) ) {
			return;
		}

		$key = $this->get_cache_key_prefix() . sha1( serialize( $this->args ) );

		// The transient name column can handle up to 64 characters.
		// The `_transient_timeout_` prefix that is prepended to the string is 11 characters.
		// 64 - 19 = 45
		// We make sure the key isn't too long or else WP doesn't store data.
		$this->key = substr( $key, 0, 45 );
	}

	/**
	 * Allow public access to get transient key
	 *
	 * @return string Transient key
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Add form IDs to a "blacklist" to force the cache to be refreshed
	 *
	 *
	 *
	 * @param  int|array $form_ids Form IDs to force to be updated
	 *
	 * @return boolean           False if value was not updated and true if value was updated.
	 */
	public function blacklist_add( $form_ids ) {

		$blacklist = get_option( self::BLACKLIST_OPTION_NAME, array() );

		$form_ids = is_array( $form_ids ) ? $form_ids : array( $form_ids );

		// Add the passed form IDs
		$blacklist = array_merge( (array) $blacklist, $form_ids );

		// Don't duplicate
		$blacklist = array_unique( $blacklist );

		// Remove empty items from blacklist
		$blacklist = array_filter( $blacklist );

		$updated = update_option( self::BLACKLIST_OPTION_NAME, $blacklist );

		if ( false !== $updated ) {
			gravityview()->log->debug( 'Added form IDs to cache blacklist', array( 'data' => array(
				'$form_ids'  => $form_ids,
				'$blacklist' => $blacklist
			) ) );
		}

		return $updated;
	}

	/**
	 * Remove Form IDs from blacklist
	 *
	 * @param  int|array $form_ids Form IDs to add
	 *
	 * @return boolean           Whether the removal was successful
	 */
	public function blacklist_remove( $form_ids ) {

		$blacklist = get_option( self::BLACKLIST_OPTION_NAME, array() );

		$updated_list = array_diff( $blacklist, (array) $form_ids );

		gravityview()->log->debug( 'Removing form IDs from cache blacklist', array( 'data' => array(
			'$form_ids'     => $form_ids,
			'$blacklist'    => $blacklist,
			'$updated_list' => $updated_list
		) ) );

		return update_option( self::BLACKLIST_OPTION_NAME, $updated_list );
	}


	/**
	 * Is a form ID in the cache blacklist
	 *
	 * @param  int|array $form_ids Form IDs to check if in blacklist
	 *
	 * @return bool
	 */
	function in_blacklist( $form_ids = NULL ) {

		$blacklist = get_option( self::BLACKLIST_OPTION_NAME, array() );

		// Use object var if exists
		$form_ids = is_null( $form_ids ) ? $this->form_ids : $form_ids;

		if ( empty( $form_ids ) ) {

			gravityview()->log->debug( 'Did not add form to blacklist; empty form ID', array( 'data' => $form_ids ) );

			return false;
		}

		foreach ( (array) $form_ids as $form_id ) {

			if ( in_array( $form_id, $blacklist ) ) {

				gravityview()->log->debug( 'Form #{form_id} is in the cache blacklist', array( 'form_id' => $form_id ) );

				return true;
			}
		}

		return false;
	}


	/**
	 * Get transient result
	 *
	 * @param  string $key Transient key to fetch
	 *
	 * @return mixed      False: Not using cache or cache was a WP_Error object; NULL: no results found; Mixed: cache value
	 */
	public function get( $key = NULL ) {

		$key = is_null( $key ) ? $this->key : $key;

		if ( ! $this->use_cache() ) {

			gravityview()->log->debug( 'Not using cached results because of GravityView_Cache->use_cache() results' );

			return false;
		}

		gravityview()->log->debug( 'Fetching request with transient key {key}', array( 'key' => $key ) );

		$result = get_transient( $key );

		if ( is_wp_error( $result ) ) {

			gravityview()->log->debug( 'Fetching request resulted in error:', array( 'data' => $result ) );

			return false;

		} elseif ( $result ) {

			gravityview()->log->debug( 'Cached results found for  transient key {key}', array( 'key' => $key ) );

			return $result;
		}

		gravityview()->log->debug( 'No cached results found for  transient key {key}', array( 'key' => $key ) );

		return NULL;

	}

	/**
	 * Cache content as a transient.
	 *
	 * Cache time defaults to 1 week
	 *
	 * @param mixed $content     [description]
	 * @param string $filter_name Name used to modify the cache time. Will be set to `gravityview_cache_time_{$filter_name}`.
	 *
	 * @return bool If $content is not set, false. Otherwise, returns true if transient was set and false if not.
	 */
	public function set( $content, $filter_name = '' ) {

		// Don't cache empty results
		if ( ! empty( $content ) ) {

			/**
			 * @filter `gravityview_cache_time_{$filter_name}` Modify the cache time for a type of cache
			 * @param int $time_in_seconds Default: `DAY_IN_SECONDS`
			 */
			$cache_time = (int) apply_filters( 'gravityview_cache_time_' . $filter_name, DAY_IN_SECONDS );

			gravityview()->log->debug( 'Setting cache with transient key {key} for {cache_time} seconds', array( 'key' => $this->key, 'cache_time' => $cache_time ) );

			return set_transient( $this->key, $content, $cache_time );

		}

		gravityview()->log->debug( 'Cache not set; content is empty' );

		return false;

	}

	/**
	 * Delete cached transients based on form IDs
	 *
	 * @todo Use REGEX to match forms when array of form IDs is passed, instead of using a simple LIKE
	 * @todo  Rate limit deleting to prevent abuse
	 *
	 * @param  int|array $form_ids Form IDs to delete
	 *
	 * @return void
	 */
	public function delete( $form_ids = NULL ) {
		global $wpdb;

		// Use object var if exists
		$form_ids = is_null( $form_ids ) ? $this->form_ids : $form_ids;

		if ( empty( $form_ids ) ) {
			gravityview()->log->debug( 'Did not delete cache; empty form IDs' );

			return;
		}

		foreach ( (array) $form_ids as $form_id ) {

			$key = '_transient_gv-cache-';

			$key = $wpdb->esc_like( $key );

			$form_id = intval( $form_id );

			// Find the transients containing this form
			$key = "$key%f:$form_id-%"; // \_transient\_gv-cache-%f:1-% for example
			$sql = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE `option_name` LIKE %s", $key );

			foreach ( ( $transients = $wpdb->get_col( $sql ) ) as $transient ) {
				// We have to delete it via the API to make sure the object cache is updated appropriately
				delete_transient( preg_replace( '#^_transient_#', '', $transient ) );
			}

			gravityview()->log->debug( 'Deleting cache for form #{form_id}', array( 'form_id' => $form_id, 'data' => array(
				$sql,
				sprintf( 'Deleted results: %d', count( $transients ) )
			) ) );
		}

	}

	/**
	 * Schedule expired transient cleanup twice a day.
	 *
	 * Can be overruled by the `gravityview_cleanup_transients` filter (returns boolean)
	 *
	 * @return void
	 */
	public function schedule_transient_cleanup() {

		/**
		 * @filter `gravityview_cleanup_transients` Override GravityView cleanup of transients by setting this to false
		 * @param boolean $cleanup Whether to run the GravityView auto-cleanup of transients. Default: `true`
		 */
		$cleanup = apply_filters( 'gravityview_cleanup_transients', true );

		if ( ! $cleanup ) {
			return;
		}

		if ( ! wp_next_scheduled( 'gravityview-expired-transients' ) ) {
			wp_schedule_event( time(), 'daily', 'gravityview-expired-transients' );
		}
	}

	/**
	 * Delete expired transients.
	 *
	 * The code is copied from the Delete Expired Transients, with slight modifications to track # of results and to get the blog ID dynamically
	 *
	 * @see https://wordpress.org/plugins/delete-expired-transients/ Plugin where the code was taken from
	 * @see  DelxtransCleaners::clearBlogExpired()
	 * @return void
	 */
	public function delete_expired_transients() {
		global $wpdb;

		// Added this line, which isn't in the plugin
		$blog_id = get_current_blog_id();

		$num_results = 0;

		// get current PHP time, offset by a minute to avoid clashes with other tasks
		$threshold = time() - 60;

		// get table name for options on specified blog
		$table = $wpdb->get_blog_prefix( $blog_id ) . 'options';

		// delete expired transients, using the paired timeout record to find them
		$sql = "
			delete from t1, t2
			using $table t1
			join $table t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
			where (t1.option_name like '\_transient\_timeout\_%' or t1.option_name like '\_site\_transient\_timeout\_%')
			and t1.option_value < '$threshold'
		";

		$num_results = $wpdb->query( $sql );

		// delete orphaned transient expirations
		// also delete NextGEN Gallery 2.x display cache timeout aliases
		$sql = "
			delete from $table
			where (
				   option_name like '\_transient\_timeout\_%'
				or option_name like '\_site\_transient\_timeout\_%'
				or option_name like 'displayed\_galleries\_%'
				or option_name like 'displayed\_gallery\_rendering\_%'
			)
			and option_value < '$threshold'
		";

		$num_results += $wpdb->query( $sql );

		gravityview()->log->debug( 'Deleted {count} expired transient records from the database', array( 'count' => $num_results ) );
	}

	/**
	 * Check whether to use cached results, if available
	 *
	 * If the user can edit posts, they are able to override whether to cache results by adding `cache` or `nocache` to the URL requested.
	 *
	 * @return boolean True: use cache; False: don't use cache
	 */
	public function use_cache() {

		// Exit early if debugging (unless running PHPUnit)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! ( defined('DOING_GRAVITYVIEW_TESTS' ) && DOING_GRAVITYVIEW_TESTS ) ) {
			return apply_filters( 'gravityview_use_cache', false, $this );
		}

		$use_cache = true;

		if ( GVCommon::has_cap( 'edit_gravityviews' ) ) {

			if ( isset( $_GET['cache'] ) || isset( $_GET['nocache'] ) ) {

				gravityview()->log->debug( 'Not using cache: ?cache or ?nocache is in the URL' );

				$use_cache = false;
			}

		}

		// Has the form been flagged as having changed items in it?
		if ( $this->in_blacklist() || ! $use_cache ) {

			// Delete caches for all items with form IDs XYZ
			$this->delete( $this->form_ids );

			// Remove the form from
			$this->blacklist_remove( $this->form_ids );

		}

		/**
		 * @filter `gravityview_use_cache` Modify whether to use the cache or not
		 * @param[out,in]  boolean $use_cache Previous setting
		 * @param[out] GravityView_Cache $this The GravityView_Cache object
		 */
		$use_cache = apply_filters( 'gravityview_use_cache', $use_cache, $this );

		return (boolean) $use_cache;
	}

}

new GravityView_Cache;
