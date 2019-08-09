<?php
/**
 * The GravityView New Search widget
 *
 * @package   GravityView-DataTables-Ext
 * @license   GPL2+
 * @author    Katz Web Services, Inc.
 * @link      http://gravityview.co
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class GravityView_Widget_Search extends \GV\Widget {

	public static $file;
	public static $instance;

	private $search_filters = array();

	/**
	 * whether search method is GET or POST ( default: GET )
	 * @since 1.16.4
	 * @var string
	 */
	private $search_method = 'get';

	public function __construct() {

		$this->widget_id = 'search_bar';
		$this->widget_description = esc_html__( 'Search form for searching entries.', 'gravityview' );

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		$default_values = array( 'header' => 0, 'footer' => 0 );

		$settings = array(
			'search_layout' => array(
				'type' => 'radio',
				'full_width' => true,
				'label' => esc_html__( 'Search Layout', 'gravityview' ),
				'value' => 'horizontal',
				'options' => array(
					'horizontal' => esc_html__( 'Horizontal', 'gravityview' ),
					'vertical' => esc_html__( 'Vertical', 'gravityview' ),
				),
			),
			'search_clear' => array(
				'type' => 'checkbox',
				'label' => __( 'Show Clear button', 'gravityview' ),
				'value' => false,
			),
			'search_fields' => array(
				'type' => 'hidden',
				'label' => '',
				'class' => 'gv-search-fields-value',
				'value' => '[{"field":"search_all","input":"input_text"}]', // Default: Search Everything text box
			),
			'search_mode' => array(
				'type' => 'radio',
				'full_width' => true,
				'label' => esc_html__( 'Search Mode', 'gravityview' ),
				'desc' => __('Should search results match all search fields, or any?', 'gravityview'),
				'value' => 'any',
				'class' => 'hide-if-js',
				'options' => array(
					'any' => esc_html__( 'Match Any Fields', 'gravityview' ),
					'all' => esc_html__( 'Match All Fields', 'gravityview' ),
				),
			),
		);

		if ( ! $this->is_registered() ) {
			// frontend - filter entries
			add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 3 );

			// frontend - add template path
			add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

			// Add hidden fields for "Default" permalink structure
			add_filter( 'gravityview_widget_search_filters', array( $this, 'add_no_permalink_fields' ), 10, 3 );

			// admin - add scripts - run at 1100 to make sure GravityView_Admin_Views::add_scripts_and_styles() runs first at 999
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 1100 );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts') );
			add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict' ) );

			// ajax - get the searchable fields
			add_action( 'wp_ajax_gv_searchable_fields', array( 'GravityView_Widget_Search', 'get_searchable_fields' ) );

			add_action( 'gravityview_search_widget_fields_after', array( $this, 'add_preview_inputs' ) );
		}

		parent::__construct( esc_html__( 'Search Bar', 'gravityview' ), null, $default_values, $settings );

		// calculate the search method (POST / GET)
		$this->set_search_method();
	}

	/**
	 * @return GravityView_Widget_Search
	 */
	public static function getInstance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new GravityView_Widget_Search;
		}
		return self::$instance;
	}

	/**
	 * Sets the search method to GET (default) or POST
	 * @since 1.16.4
	 */
	private function set_search_method() {
		/**
		 * @filter `gravityview/search/method` Modify the search form method (GET / POST)
		 * @since 1.16.4
		 * @param string $search_method Assign an input type according to the form field type. Defaults: `boolean`, `multi`, `select`, `date`, `text`
		 * @param string $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
		 */
		$method = apply_filters( 'gravityview/search/method', $this->search_method );

		$method = strtolower( $method );

		$this->search_method = in_array( $method, array( 'get', 'post' ) ) ? $method : 'get';
	}

	/**
	 * Returns the search method
	 * @since 1.16.4
	 * @return string
	 */
	public function get_search_method() {
		return $this->search_method;
	}

	/**
	 * Get the input types available for different field types
	 *
	 * @since 1.17.5
	 *
	 * @return array [field type name] => (array|string) search bar input types
	 */
	public static function get_input_types_by_field_type() {
		/**
		 * Input Type groups
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_types = array(
			'text' => array( 'input_text' ),
			'address' => array( 'input_text' ),
			'number' => array( 'input_text' ),
			'date' => array( 'date', 'date_range' ),
			'boolean' => array( 'single_checkbox' ),
			'select' => array( 'select', 'radio', 'link' ),
			'multi' => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),

			// hybrids
			'created_by' => array( 'select', 'radio', 'checkbox', 'multiselect', 'link', 'input_text' ),
			'product'   => array( 'select', 'radio', 'link', 'input_text' ),
		);

		/**
		 * @filter `gravityview/search/input_types` Change the types of search fields available to a field type
		 * @see GravityView_Widget_Search::get_search_input_labels() for the available input types
		 * @param array $input_types Associative array: key is field `name`, value is array of GravityView input types (note: use `input_text` for `text`)
		 */
		$input_types = apply_filters( 'gravityview/search/input_types', $input_types );

		return $input_types;
	}

	/**
	 * Get labels for different types of search bar inputs
	 *
	 * @since 1.17.5
	 *
	 * @return array [input type] => input type label
	 */
	public static function get_search_input_labels() {
		/**
		 * Input Type labels l10n
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_labels = array(
			'input_text' => esc_html__( 'Text', 'gravityview' ),
			'date' => esc_html__( 'Date', 'gravityview' ),
			'select' => esc_html__( 'Select', 'gravityview' ),
			'multiselect' => esc_html__( 'Select (multiple values)', 'gravityview' ),
			'radio' => esc_html__( 'Radio', 'gravityview' ),
			'checkbox' => esc_html__( 'Checkbox', 'gravityview' ),
			'single_checkbox' => esc_html__( 'Checkbox', 'gravityview' ),
			'link' => esc_html__( 'Links', 'gravityview' ),
			'date_range' => esc_html__( 'Date range', 'gravityview' ),
		);

		/**
		 * @filter `gravityview/search/input_types` Change the label of search field input types
		 * @param array $input_types Associative array: key is input type name, value is label
		 */
		$input_labels = apply_filters( 'gravityview/search/input_labels', $input_labels );

		return $input_labels;
	}

	public static function get_search_input_label( $input_type ) {
		$labels = self::get_search_input_labels();

		return \GV\Utils::get( $labels, $input_type, false );
	}

	/**
	 * Add script to Views edit screen (admin)
	 * @param  mixed $hook
	 */
	public function add_scripts_and_styles( $hook ) {
		global $pagenow;

		// Don't process any scripts below here if it's not a GravityView page or the widgets screen
		if ( ! gravityview()->request->is_admin( $hook, 'single' ) && ( 'widgets.php' !== $pagenow ) ) {
			return;
		}

		$script_min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$script_source = empty( $script_min ) ? '/source' : '';

		wp_enqueue_script( 'gravityview_searchwidget_admin', plugins_url( 'assets/js'.$script_source.'/admin-search-widget'.$script_min.'.js', __FILE__ ), array( 'jquery', 'gravityview_views_scripts' ), \GV\Plugin::$version );

		wp_localize_script( 'gravityview_searchwidget_admin', 'gvSearchVar', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxsearchwidget' ),
			'label_nofields' => esc_html__( 'No search fields configured yet.', 'gravityview' ),
			'label_addfield' => esc_html__( 'Add Search Field', 'gravityview' ),
			'label_label' => esc_html__( 'Label', 'gravityview' ),
			'label_searchfield' => esc_html__( 'Search Field', 'gravityview' ),
			'label_inputtype' => esc_html__( 'Input Type', 'gravityview' ),
			'label_ajaxerror' => esc_html__( 'There was an error loading searchable fields. Save the View or refresh the page to fix this issue.', 'gravityview' ),
			'input_labels' => json_encode( self::get_search_input_labels() ),
			'input_types' => json_encode( self::get_input_types_by_field_type() ),
		) );

	}

	/**
	 * Add admin script to the no-conflict scripts whitelist
	 * @param array $allowed Scripts allowed in no-conflict mode
	 * @return array Scripts allowed in no-conflict mode, plus the search widget script
	 */
	public function register_no_conflict( $allowed ) {
		$allowed[] = 'gravityview_searchwidget_admin';
		return $allowed;
	}

	/**
	 * Ajax
	 * Returns the form fields ( only the searchable ones )
	 *
	 * @access public
	 * @return void
	 */
	public static function get_searchable_fields() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxsearchwidget' ) ) {
			exit( '0' );
		}

		$form = '';

		// Fetch the form for the current View
		if ( ! empty( $_POST['view_id'] ) ) {

			$form = gravityview_get_form_id( $_POST['view_id'] );

		} elseif ( ! empty( $_POST['formid'] ) ) {

			$form = (int) $_POST['formid'];

		} elseif ( ! empty( $_POST['template_id'] ) && class_exists( 'GravityView_Ajax' ) ) {

			$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );

		}

		// fetch form id assigned to the view
		$response = self::render_searchable_fields( $form );

		exit( $response );
	}

	/**
	 * Generates html for the available Search Fields dropdown
	 * @param  int $form_id
	 * @param  string $current (for future use)
	 * @return string
	 */
	public static function render_searchable_fields( $form_id = null, $current = '' ) {

		if ( is_null( $form_id ) ) {
			return '';
		}

		// start building output

		$output = '<select class="gv-search-fields">';

		$custom_fields = array(
			'search_all' => array(
				'text' => esc_html__( 'Search Everything', 'gravityview' ),
				'type' => 'text',
			),
			'entry_date' => array(
				'text' => esc_html__( 'Entry Date', 'gravityview' ),
				'type' => 'date',
			),
			'entry_id' => array(
				'text' => esc_html__( 'Entry ID', 'gravityview' ),
				'type' => 'text',
			),
			'created_by' => array(
				'text' => esc_html__( 'Entry Creator', 'gravityview' ),
				'type' => 'created_by',
			),
			'is_starred' => array(
				'text' => esc_html__( 'Is Starred', 'gravityview' ),
				'type' => 'boolean',
			),
		);

		if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$custom_fields['is_approved'] = array(
				'text' => esc_html__( 'Approval Status', 'gravityview' ),
				'type' => 'multi',
			);
		}

		foreach( $custom_fields as $custom_field_key => $custom_field ) {
			$output .= sprintf( '<option value="%s" %s data-inputtypes="%s" data-placeholder="%s">%s</option>', $custom_field_key, selected( $custom_field_key, $current, false ), $custom_field['type'], self::get_field_label( array('field' => $custom_field_key ) ), $custom_field['text'] );
		}

		// Get fields with sub-inputs and no parent
		$fields = gravityview_get_form_fields( $form_id, true, true );

		/**
		 * @filter `gravityview/search/searchable_fields` Modify the fields that are displayed as searchable in the Search Bar dropdown\n
		 * @since 1.17
		 * @see gravityview_get_form_fields() Used to fetch the fields
		 * @see GravityView_Widget_Search::get_search_input_types See this method to modify the type of input types allowed for a field
		 * @param array $fields Array of searchable fields, as fetched by gravityview_get_form_fields()
		 * @param  int $form_id
		 */
		$fields = apply_filters( 'gravityview/search/searchable_fields', $fields, $form_id );

		if ( ! empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array( 'fileupload', 'post_image', 'post_id', 'section' ), null );

			foreach ( $fields as $id => $field ) {

				if ( in_array( $field['type'], $blacklist_field_types ) ) {
					continue;
				}

				$types = self::get_search_input_types( $id, $field['type'] );

				$output .= '<option value="'. $id .'" '. selected( $id, $current, false ).'data-inputtypes="'. esc_attr( $types ) .'">'. esc_html( $field['label'] ) .'</option>';
			}
		}

		$output .= '</select>';

		return $output;

	}

	/**
	 * Assign an input type according to the form field type
	 *
	 * @see admin-search-widget.js
	 *
	 * @param string|int|float $field_id Gravity Forms field ID
	 * @param string $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
	 *
	 * @return string GV field search input type ('multi', 'boolean', 'select', 'date', 'text')
	 */
	public static function get_search_input_types( $field_id = '', $field_type = null ) {

		// @todo - This needs to be improved - many fields have . including products and addresses
		if ( false !== strpos( (string) $field_id, '.' ) && in_array( $field_type, array( 'checkbox' ) ) || in_array( $field_id, array( 'is_fulfilled' ) ) ) {
			$input_type = 'boolean'; // on/off checkbox
		} elseif ( in_array( $field_type, array( 'checkbox', 'post_category', 'multiselect' ) ) ) {
			$input_type = 'multi'; //multiselect
		} elseif ( in_array( $field_type, array( 'select', 'radio' ) ) ) {
			$input_type = 'select';
		} elseif ( in_array( $field_type, array( 'date' ) ) || in_array( $field_id, array( 'payment_date' ) ) ) {
			$input_type = 'date';
		} elseif ( in_array( $field_type, array( 'number' ) ) || in_array( $field_id, array( 'payment_amount' ) ) ) {
			$input_type = 'number';
		} elseif ( in_array( $field_type, array( 'product' ) ) ) {
			$input_type = 'product';
		} else {
			$input_type = 'text';
		}

		/**
		 * @filter `gravityview/extension/search/input_type` Modify the search form input type based on field type
		 * @since 1.2
		 * @since 1.19.2 Added $field_id parameter
		 * @param string $input_type Assign an input type according to the form field type. Defaults: `boolean`, `multi`, `select`, `date`, `text`
		 * @param string $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
		 * @param string|int|float $field_id ID of the field being processed
		 */
		$input_type = apply_filters( 'gravityview/extension/search/input_type', $input_type, $field_type, $field_id );

		return $input_type;
	}

	/**
	 * Display hidden fields to add support for sites using Default permalink structure
	 *
	 * @since 1.8
	 * @return array Search fields, modified if not using permalinks
	 */
	public function add_no_permalink_fields( $search_fields, $object, $widget_args = array() ) {
		/** @global WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		// Support default permalink structure
		if ( false === $wp_rewrite->using_permalinks() ) {

			// By default, use current post.
			$post_id = 0;

			// We're in the WordPress Widget context, and an overriding post ID has been set.
			if ( ! empty( $widget_args['post_id'] ) ) {
				$post_id = absint( $widget_args['post_id'] );
			}
			// We're in the WordPress Widget context, and the base View ID should be used
			else if ( ! empty( $widget_args['view_id'] ) ) {
				$post_id = absint( $widget_args['view_id'] );
			}

			$args = gravityview_get_permalink_query_args( $post_id );

			// Add hidden fields to the search form
			foreach ( $args as $key => $value ) {
				$search_fields[] = array(
					'name'  => $key,
					'input' => 'hidden',
					'value' => $value,
				);
			}
		}

		return $search_fields;
	}

	/**
	 * Get the fields that are searchable for a View
	 *
	 * @since 2.0
	 * @since 2.0.9 Added $with_full_field parameter
	 *
	 * @param \GV\View|null $view
	 * @param bool $with_full_field Return full field array, or just field ID? Default: false (just field ID)
	 *
	 * TODO: Move to \GV\View, perhaps? And return a Field_Collection
	 * TODO: Use in gravityview()->request->is_search() to calculate whether a valid search
	 *
	 * @return array If no View, returns empty array. Otherwise, returns array of fields configured in widgets and Search Bar for a View
	 */
	private function get_view_searchable_fields( $view, $with_full_field = false ) {

		/**
		 * Find all search widgets on the view and get the searchable fields settings.
		 */
		$searchable_fields = array();

		if ( ! $view ) {
			return $searchable_fields;
		}

		/**
		 * Include the sidebar Widgets.
		 */
		$widgets = (array) get_option( 'widget_gravityview_search', array() );

		foreach ( $widgets as $widget ) {
			if ( ! empty( $widget['view_id'] ) && $widget['view_id'] == $view->ID ) {
				if( $_fields = json_decode( $widget['search_fields'], true ) ) {
					foreach ( $_fields as $field ) {
						if ( empty( $field['form_id'] ) ) {
							$field['form_id'] = $view->form ? $view->form->ID : 0;
						}
						$searchable_fields[] = $with_full_field ? $field : $field['field'];
					}
				}
			}
		}

		foreach ( $view->widgets->by_id( $this->get_widget_id() )->all() as $widget ) {
			if( $_fields = json_decode( $widget->configuration->get( 'search_fields' ), true ) ) {
				foreach ( $_fields as $field ) {
					if ( empty( $field['form_id'] ) ) {
						$field['form_id'] = $view->form ? $view->form->ID : 0;
					}
					$searchable_fields[] = $with_full_field ? $field : $field['field'];
				}
			}
		}

		return $searchable_fields;
	}

	/** --- Frontend --- */

	/**
	 * Calculate the search criteria to filter entries
	 * @param array $search_criteria The search criteria
	 * @param int $form_id The form ID
	 * @param array $args Some args
	 *
	 * @param bool $force_search_criteria Whether to suppress GF_Query filter, internally used in self::gf_query_filter
	 *
	 * @return array
	 */
	public function filter_entries( $search_criteria, $form_id = null, $args = array(), $force_search_criteria = false ) {
		if ( ! $force_search_criteria && gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			/**
			 * If GF_Query is available, we can construct custom conditions with nested
			 * booleans on the query, giving up the old ways of flat search_criteria field_filters.
			 */
			add_action( 'gravityview/view/query', array( $this, 'gf_query_filter' ), 10, 3 );
			return $search_criteria; // Return the original criteria, GF_Query modification kicks in later
		}

		if( 'post' === $this->search_method ) {
			$get = $_POST;
		} else {
			$get = $_GET;
		}

		$view = \GV\View::by_id( \GV\Utils::get( $args, 'id' ) );

		gravityview()->log->debug( 'Requested $_{method}: ', array( 'method' => $this->search_method, 'data' => $get ) );

		if ( empty( $get ) || ! is_array( $get ) ) {
			return $search_criteria;
		}

		$get = stripslashes_deep( $get );

		$get = gv_map_deep( $get, 'rawurldecode' );

		// Make sure array key is set up
		$search_criteria['field_filters'] = \GV\Utils::get( $search_criteria, 'field_filters', array() );

		$searchable_fields = $this->get_view_searchable_fields( $view );
		$searchable_field_objects = $this->get_view_searchable_fields( $view, true );

		// add free search
		if ( isset( $get['gv_search'] ) && '' !== $get['gv_search'] && in_array( 'search_all', $searchable_fields ) ) {

			$search_all_value = trim( $get['gv_search'] );

			/**
			 * @filter `gravityview/search-all-split-words` Search for each word separately or the whole phrase?
			 * @since 1.20.2
			 * @param bool $split_words True: split a phrase into words; False: search whole word only [Default: true]
			 */
			$split_words = apply_filters( 'gravityview/search-all-split-words', true );

			if ( $split_words ) {

				// Search for a piece
				$words = explode( ' ', $search_all_value );

				$words = array_filter( $words );

			} else {

				// Replace multiple spaces with one space
				$search_all_value = preg_replace( '/\s+/ism', ' ', $search_all_value );

				$words = array( $search_all_value );
			}

			foreach ( $words as $word ) {
				$search_criteria['field_filters'][] = array(
					'key' => null, // The field ID to search
					'value' => $word, // The value to search
					'operator' => 'contains', // What to search in. Options: `is` or `contains`
				);
			}
		}

		// start date & end date
		if ( in_array( 'entry_date', $searchable_fields ) ) {
			/**
			 * Get and normalize the dates according to the input format.
			 */
			if ( $curr_start = ! empty( $get['gv_start'] ) ? $get['gv_start'] : '' ) {
				if( $curr_start_date = date_create_from_format( $this->get_datepicker_format( true ), $curr_start ) ) {
					$curr_start = $curr_start_date->format( 'Y-m-d' );
				}
			}

			if ( $curr_end = ! empty( $get['gv_start'] ) ? ( ! empty( $get['gv_end'] ) ? $get['gv_end'] : '' ) : '' ) {
				if( $curr_end_date = date_create_from_format( $this->get_datepicker_format( true ), $curr_end ) ) {
					$curr_end = $curr_end_date->format( 'Y-m-d' );
				}
			}

			if ( $view ) {
				/**
				 * Override start and end dates if View is limited to some already.
				 */
				if ( $start_date = $view->settings->get( 'start_date' ) ) {
					if ( $start_timestamp = strtotime( $curr_start ) ) {
						$curr_start = $start_timestamp < strtotime( $start_date ) ? $start_date : $curr_start;
					}
				}
				if ( $end_date = $view->settings->get( 'end_date' ) ) {
					if ( $end_timestamp = strtotime( $curr_end ) ) {
						$curr_end = $end_timestamp > strtotime( $end_date ) ? $end_date : $curr_end;
					}
				}
			}

			/**
			 * @filter `gravityview_date_created_adjust_timezone` Whether to adjust the timezone for entries. \n
			 * date_created is stored in UTC format. Convert search date into UTC (also used on templates/fields/date_created.php)
			 * @since 1.12
			 * @param[out,in] boolean $adjust_tz  Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting. Default: true
			 * @param[in] string $context Where the filter is being called from. `search` in this case.
			 */
			$adjust_tz = apply_filters( 'gravityview_date_created_adjust_timezone', true, 'search' );

			/**
			 * Don't set $search_criteria['start_date'] if start_date is empty as it may lead to bad query results (GFAPI::get_entries)
			 */
			if ( ! empty( $curr_start ) ) {
				$curr_start = date( 'Y-m-d H:i:s', strtotime( $curr_start ) );
				$search_criteria['start_date'] = $adjust_tz ? get_gmt_from_date( $curr_start ) : $curr_start;
			}

			if ( ! empty( $curr_end ) ) {
				// Fast-forward 24 hour on the end time
				$curr_end = date( 'Y-m-d H:i:s', strtotime( $curr_end ) + DAY_IN_SECONDS );
				$search_criteria['end_date'] = $adjust_tz ? get_gmt_from_date( $curr_end ) : $curr_end;
				if ( strpos( $search_criteria['end_date'], '00:00:00' ) ) { // See https://github.com/gravityview/GravityView/issues/1056
					$search_criteria['end_date'] = date( 'Y-m-d H:i:s', strtotime( $search_criteria['end_date'] ) - 1 );
				}
			}
		}

		// search for a specific entry ID
		if ( ! empty( $get[ 'gv_id' ] ) && in_array( 'entry_id', $searchable_fields ) ) {
			$search_criteria['field_filters'][] = array(
				'key' => 'id',
				'value' => absint( $get[ 'gv_id' ] ),
				'operator' => $this->get_operator( $get, 'gv_id', array( '=' ), '=' ),
			);
		}

		// search for a specific Created_by ID
		if ( ! empty( $get[ 'gv_by' ] ) && in_array( 'created_by', $searchable_fields ) ) {
			$search_criteria['field_filters'][] = array(
				'key' => 'created_by',
				'value' => $get['gv_by'],
				'operator' => $this->get_operator( $get, 'gv_by', array( '=' ), '=' ),
			);
		}

		// Get search mode passed in URL
		$mode = isset( $get['mode'] ) && in_array( $get['mode'], array( 'any', 'all' ) ) ?  $get['mode'] : 'any';

		// get the other search filters
		foreach ( $get as $key => $value ) {

			if ( 0 !== strpos( $key, 'filter_' ) || gv_empty( $value, false, false ) || ( is_array( $value ) && count( $value ) === 1 && gv_empty( $value[0], false, false ) ) ) {
				continue; // Not a filter, or empty
			}

			if ( strpos( $key, '|op' ) !== false ) {
				continue; // This is an operator
			}

			$filter_key = $this->convert_request_key_to_filter_key( $key );

			if ( ! $filter = $this->prepare_field_filter( $filter_key, $value, $view, $searchable_field_objects, $get ) ) {
				continue;
			}

			if ( ! isset( $filter['operator'] ) ) {
				$filter['operator'] = $this->get_operator( $get, $key, array( 'contains' ), 'contains' );
			}

			if ( isset( $filter[0]['value'] ) ) {
				$search_criteria['field_filters'] = array_merge( $search_criteria['field_filters'], $filter );

				// if date range type, set search mode to ALL
				if ( ! empty( $filter[0]['operator'] ) && in_array( $filter[0]['operator'], array( '>=', '<=', '>', '<' ) ) ) {
					$mode = 'all';
				}
			} elseif( !empty( $filter ) ) {
				$search_criteria['field_filters'][] = $filter;
			}
		}

		/**
		 * @filter `gravityview/search/mode` Set the Search Mode (`all` or `any`)
		 * @since 1.5.1
		 * @param[out,in] string $mode Search mode (`any` vs `all`)
		 */
		$search_criteria['field_filters']['mode'] = apply_filters( 'gravityview/search/mode', $mode );

		gravityview()->log->debug( 'Returned Search Criteria: ', array( 'data' => $search_criteria ) );

		unset( $get );

		return $search_criteria;
	}

	/**
	 * Filters the \GF_Query with advanced logic.
	 *
	 * Dropin for the legacy flat filters when \GF_Query is available.
	 *
	 * @param \GF_Query $query The current query object reference
	 * @param \GV\View $this The current view object
	 * @param \GV\Request $request The request object
	 */
	public function gf_query_filter( &$query, $view, $request ) {
		/**
		 * This is a shortcut to get all the needed search criteria.
		 * We feed these into an new GF_Query and tack them onto the current object.
		 */
		$search_criteria = $this->filter_entries( array(), null, array( 'id' => $view->ID ), true /** force search_criteria */ );

		/**
		 * Call any userland filters that they might have.
		 */
		remove_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 3 );
		$search_criteria = apply_filters( 'gravityview_fe_search_criteria', $search_criteria, $view->form->ID, $view->settings->as_atts() );
		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 3 );

		$query_class = $view->get_query_class();

		if ( empty( $search_criteria['field_filters'] ) ) {
			return;
		}

		$widgets = $view->widgets->by_id( $this->widget_id );
		if ( $widgets->count() ) {
			$widgets = $widgets->all();
			$widget  = $widgets[0];

			$search_fields = json_decode( $widget->configuration->get( 'search_fields' ), true );

			foreach ( (array) $search_fields as $search_field ) {
				if ( 'created_by' === $search_field['field'] && 'input_text' === $search_field['input'] ) {
					$created_by_text_mode = true;
				}
			}
		}

		$extra_conditions = array();
		$mode = 'any';

		foreach ( $search_criteria['field_filters'] as &$filter ) {
			if ( ! is_array( $filter ) ) {
				if ( in_array( strtolower( $filter ), array( 'any', 'all' ) ) ) {
					$mode = $filter;
				}
				continue;
			}

			// Construct a manual query for unapproved statuses
			if ( 'is_approved' === $filter['key'] && in_array( \GravityView_Entry_Approval_Status::UNAPPROVED, (array) $filter['value'] ) ) {
				$_tmp_query       = new $query_class( $view->form->ID, array(
					'field_filters' => array(
						array(
							'operator' => 'in',
							'key'      => 'is_approved',
							'value'    => (array) $filter['value'],
						),
						array(
							'operator' => 'is',
							'key'      => 'is_approved',
							'value'    => '',
						),
						'mode' => 'any'
					),
				) );
				$_tmp_query_parts = $_tmp_query->_introspect();

				$extra_conditions[] = $_tmp_query_parts['where'];

				$filter = false;
				continue;
			}

			// Construct manual query for text mode creator search
			if ( 'created_by' === $filter['key'] && ! empty( $created_by_text_mode ) ) {
				$extra_conditions[] = new GravityView_Widget_Search_Author_GF_Query_Condition( $filter, $view );
				$filter = false;
				continue;
			}

			// By default, we want searches to be wildcard for each field.
			$filter['operator'] = empty( $filter['operator'] ) ? 'contains' : $filter['operator'];

			// For multichoice, let's have an in (OR) search.
			if ( is_array( $filter['value'] ) ) {
				$filter['operator'] = 'in'; // @todo what about in contains (OR LIKE chains)?
			}

			// Default form with joins functionality
			if ( empty( $filter['form_id'] ) ) {
				$filter['form_id'] = $view->form ? $view->form->ID : 0;
			}

			/**
			 * @filter `gravityview_search_operator` Modify the search operator for the field (contains, is, isnot, etc)
			 * @param string $operator Existing search operator
			 * @param array $filter array with `key`, `value`, `operator`, `type` keys
			 * @since develop
			 * @param \GV\View $view The View we're operating on.
			 */
			$filter['operator'] = apply_filters( 'gravityview_search_operator', $filter['operator'], $filter, $view );
		}

		if ( ! empty( $search_criteria['start_date'] ) || ! empty( $search_criteria['end_date'] ) ) {
			$date_criteria = array();

			if ( isset( $search_criteria['start_date'] ) ) {
				$date_criteria['start_date'] = $search_criteria['start_date'];
			}

			if ( isset( $search_criteria['end_date'] ) ) {
				$date_criteria['end_date'] = $search_criteria['end_date'];
			}

			$_tmp_query         = new $query_class( $view->form->ID, $date_criteria );
			$_tmp_query_parts   = $_tmp_query->_introspect();
			$extra_conditions[] = $_tmp_query_parts['where'];
		}

		$search_conditions = array();

		if ( $filters = array_filter( $search_criteria['field_filters'] ) ) {

			foreach ( $filters as $filter ) {
				if ( ! is_array( $filter ) ) {
					continue;
				}

				/**
				 * Parse the filter criteria to generate the needed
				 * WHERE condition. This is a trick to not write our own generation
				 * code by reusing what's inside GF_Query already as they
				 * take care of many small things like forcing numeric, etc.
				 */
				$_tmp_query       = new $query_class( $filter['form_id'], array( 'mode' => 'any', 'field_filters' => array( $filter ) ) );
				$_tmp_query_parts = $_tmp_query->_introspect();
				$search_condition = $_tmp_query_parts['where'];

				if ( empty( $filter['key'] ) && $search_condition->expressions ) {
					$search_conditions[] = $search_condition;
				} else {
					$left = $search_condition->left;
					$alias = $query->_alias( $left->field_id, $left->source, $left->is_entry_column() ? 't' : 'm' );

					if ( $view->joins && $left->field_id == GF_Query_Column::META ) {
						$search_conditions[] = $search_condition;
					} else {
						$search_conditions[] = new GF_Query_Condition(
							new GF_Query_Column( $left->field_id, $left->source, $alias ),
							$search_condition->operator,
							$search_condition->right
						);
					}
				}
			}

			if ( $search_conditions ) {
				$search_conditions = array( call_user_func_array( '\GF_Query_Condition::' . ( $mode == 'all' ? '_and' : '_or' ), $search_conditions ) );
			}
		}

		/**
		 * Grab the current clauses. We'll be combining them shortly.
		 */
		$query_parts = $query->_introspect();

		/**
		 * Combine the parts as a new WHERE clause.
		 */
		$where = call_user_func_array( '\GF_Query_Condition::_and', array_merge( array( $query_parts['where'] ), $search_conditions, $extra_conditions ) );
		$query->where( $where );
	}

	/**
	 * Convert $_GET/$_POST key to the field/meta ID
	 *
	 * Examples:
	 * - `filter_is_starred` => `is_starred`
	 * - `filter_1_2` => `1.2`
	 * - `filter_5` => `5`
	 *
	 * @since 2.0
	 *
	 * @param string $key $_GET/_$_POST search key
	 *
	 * @return string
	 */
	private function convert_request_key_to_filter_key( $key ) {

		$field_id = str_replace( 'filter_', '', $key );

		// calculates field_id, removing 'filter_' and for '_' for advanced fields ( like name or checkbox )
		if ( preg_match('/^[0-9_]+$/ism', $field_id ) ) {
			$field_id = str_replace( '_', '.', $field_id );
		}

		return $field_id;
	}

	/**
	 * Prepare the field filters to GFAPI
	 *
	 * The type post_category, multiselect and checkbox support multi-select search - each value needs to be separated in an independent filter so we could apply the ANY search mode.
	 *
	 * Format searched values
	 *
	 * @param  string $filter_key ID of the field, or entry meta key
	 * @param  string $value $_GET/$_POST search value
	 * @param  \GV\View $view The view we're looking at
	 * @param array[] $searchable_fields The searchable fields as configured by the widget.
	 * @param string[] $get The $_GET/$_POST array.
	 *
	 * @since develop Added 5th $get parameter for operator overrides.
	 * @todo Set function as private.
	 *
	 * @return array|false 1 or 2 deph levels, false if not allowed
	 */
	public function prepare_field_filter( $filter_key, $value, $view, $searchable_fields, $get = array() ) {
		$key = $filter_key;
		$filter_key = explode( ':', $filter_key ); // field_id, form_id

		$form = null;

		if ( count( $filter_key ) > 1 ) {
			// form is specified
			list( $field_id, $form_id ) = $filter_key;

			if ( $forms = \GV\View::get_joined_forms( $view->ID ) ) {
				if ( ! $form = \GV\GF_Form::by_id( $form_id ) ) {
					return false;
				}
			}

			// form is allowed
			$found = false;
			foreach ( $forms as $form ) {
				if ( $form->ID == $form_id ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}

			// form is in searchable fields
			$found = false;
			foreach ( $searchable_fields as $field ) {
				if ( $field_id == $field['field'] && $form->ID == $field['form_id'] ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				return false;
			}
		} else {
			$field_id = reset( $filter_key );
			$searchable_fields = wp_list_pluck( $searchable_fields, 'field' );
			if ( ! in_array( 'search_all', $searchable_fields ) && ! in_array( $field_id, $searchable_fields ) ) {
				return false;
			}
		}
		
		if ( ! $form ) {
			// fallback
			$form = $view->form;
		}

		// get form field array
		$form_field = is_numeric( $field_id ) ? \GV\GF_Field::by_id( $form, $field_id ) : \GV\Internal_Field::by_id( $field_id );

		// default filter array
		$filter = array(
			'key'   => $field_id,
			'value' => $value,
			'form_id' => $form->ID,
		);

		switch ( $form_field->type ) {

			case 'select':
			case 'radio':
				$filter['operator'] = $this->get_operator( $get, $key, array( 'is' ), 'is' );
				break;

			case 'post_category':

				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}

				// Reset filter variable
				$filter = array();

				foreach ( $value as $val ) {
					$cat = get_term( $val, 'category' );
					$filter[] = array(
						'key'      => $field_id,
						'value'    => esc_attr( $cat->name ) . ':' . $val,
						'operator' => $this->get_operator( $get, $key, array( 'is' ), 'is' ),
					);
				}

				break;

			case 'multiselect':

				if ( ! is_array( $value ) ) {
					break;
				}

				// Reset filter variable
				$filter = array();

				foreach ( $value as $val ) {
					$filter[] = array( 'key' => $field_id, 'value' => $val );
				}

				break;

			case 'checkbox':
				// convert checkbox on/off into the correct search filter
				if ( false !== strpos( $field_id, '.' ) && ! empty( $form_field->inputs ) && ! empty( $form_field->choices ) ) {
					foreach ( $form_field->inputs as $k => $input ) {
						if ( $input['id'] == $field_id ) {
							$filter['value'] = $form_field->choices[ $k ]['value'];
							$filter['operator'] = $this->get_operator( $get, $key, array( 'is' ), 'is' );
							break;
						}
					}
				} elseif ( is_array( $value ) ) {

					// Reset filter variable
					$filter = array();

					foreach ( $value as $val ) {
						$filter[] = array(
							'key'      => $field_id,
							'value'    => $val,
							'operator' => $this->get_operator( $get, $key, array( 'is' ), 'is' ),
						);
					}
				}

				break;

			case 'name':
			case 'address':

				if ( false === strpos( $field_id, '.' ) ) {

					$words = explode( ' ', $value );

					$filters = array();
					foreach ( $words as $word ) {
						if ( ! empty( $word ) && strlen( $word ) > 1 ) {
							// Keep the same key for each filter
							$filter['value'] = $word;
							// Add a search for the value
							$filters[] = $filter;
						}
					}

					$filter = $filters;
				}

				// State/Province should be exact matches
				if ( 'address' === $form_field->field->type ) {

					$searchable_fields = $this->get_view_searchable_fields( $view, true );

					foreach ( $searchable_fields as $searchable_field ) {

						if( $form_field->ID !== $searchable_field['field'] ) {
							continue;
						}

						// Only exact-match dropdowns, not text search
						if( in_array( $searchable_field['input'], array( 'text', 'search' ), true ) ) {
							continue;
						}

						$input_id = gravityview_get_input_id_from_id( $form_field->ID );

						if ( 4 === $input_id ) {
							$filter['operator'] = $this->get_operator( $get, $key, array( 'is' ), 'is' );
						};
					}
				}

				break;

			case 'date':

				$date_format = $this->get_datepicker_format( true );

				if ( is_array( $value ) ) {

					// Reset filter variable
					$filter = array();

					foreach ( $value as $k => $date ) {
						if ( empty( $date ) ) {
							continue;
						}
						$operator = 'start' === $k ? '>=' : '<=';

						/**
						 * @hack
						 * @since 1.16.3
						 * Safeguard until GF implements '<=' operator
						 */
						if( !GFFormsModel::is_valid_operator( $operator ) && $operator === '<=' ) {
							$operator = '<';
							$date = date( 'Y-m-d', strtotime( self::get_formatted_date( $date, 'Y-m-d', $date_format ) . ' +1 day' ) );
						}

						$filter[] = array(
							'key'      => $field_id,
							'value'    => self::get_formatted_date( $date, 'Y-m-d', $date_format ),
							'operator' => $this->get_operator( $get, $key, array( $operator ), $operator ),
						);
					}
				} else {
					$date = $value;
					$filter['value'] = self::get_formatted_date( $date, 'Y-m-d', $date_format );
					$filter['operator'] = $this->get_operator( $get, $key, array( 'is' ), 'is' );
				}

				break;


		} // switch field type

		return $filter;
	}

	/**
	 * Get the Field Format form GravityForms
	 *
	 * @param GF_Field_Date $field The field object
	 * @since 1.10
	 *
	 * @return string Format of the date in the database
	 */
	public static function get_date_field_format( GF_Field_Date $field ) {
		$format = 'm/d/Y';
		$datepicker = array(
			'mdy' => 'm/d/Y',
			'dmy' => 'd/m/Y',
			'dmy_dash' => 'd-m-Y',
			'dmy_dot' => 'd.m.Y',
			'ymd_slash' => 'Y/m/d',
			'ymd_dash' => 'Y-m-d',
			'ymd_dot' => 'Y.m.d',
		);

		if ( ! empty( $field->dateFormat ) && isset( $datepicker[ $field->dateFormat ] ) ){
			$format = $datepicker[ $field->dateFormat ];
		}

		return $format;
	}

	/**
	 * Format a date value
	 *
	 * @param string $value Date value input
	 * @param string $format Wanted formatted date
	 *
	 * @since 2.1.2
	 * @param string $value_format The value format. Default: Y-m-d
	 *
	 * @return string
	 */
	public static function get_formatted_date( $value = '', $format = 'Y-m-d', $value_format = 'Y-m-d' ) {

		$date = date_create_from_format( $value_format, $value );

		if ( empty( $date ) ) {
			gravityview()->log->debug( 'Date format not valid: {value}', array( 'value' => $value ) );
			return '';
		}
		return $date->format( $format );
	}


	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	public function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		$file_paths[102] = self::$file . 'templates/';

		return $file_paths;
	}

	/**
	 * Check whether the configured search fields have a date field
	 *
	 * @since 1.17.5
	 *
	 * @param array $search_fields
	 *
	 * @return bool True: has a `date` or `date_range` field
	 */
	private function has_date_field( $search_fields ) {

		$has_date = false;

		foreach ( $search_fields as $k => $field ) {
			if ( in_array( $field['input'], array( 'date', 'date_range', 'entry_date' ) ) ) {
				$has_date = true;
				break;
			}
		}

		return $has_date;
	}

	/**
	 * Renders the Search Widget
	 * @param array $widget_args
	 * @param string $content
	 * @param string $context
	 *
	 * @return void
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
		/** @var GravityView_View $gravityview_view */
		$gravityview_view = GravityView_View::getInstance();

		if ( empty( $gravityview_view ) ) {
			gravityview()->log->debug( '$gravityview_view not instantiated yet.' );
			return;
		}

		// get configured search fields
		$search_fields = ! empty( $widget_args['search_fields'] ) ? json_decode( $widget_args['search_fields'], true ) : '';

		if ( empty( $search_fields ) || ! is_array( $search_fields ) ) {
			gravityview()->log->debug( 'No search fields configured for widget:', array( 'data' => $widget_args ) );
			return;
		}

		$view = \GV\View::by_id( $gravityview_view->view_id );

		// prepare fields
		foreach ( $search_fields as $k => $field ) {

			$updated_field = $field;

			$updated_field = $this->get_search_filter_details( $updated_field );

			switch ( $field['field'] ) {

				case 'search_all':
					$updated_field['key'] = 'search_all';
					$updated_field['input'] = 'search_all';
					$updated_field['value'] = $this->rgget_or_rgpost( 'gv_search' );
					break;

				case 'entry_date':
					$updated_field['key'] = 'entry_date';
					$updated_field['input'] = 'entry_date';
					$updated_field['value'] = array(
						'start' => $this->rgget_or_rgpost( 'gv_start' ),
						'end' => $this->rgget_or_rgpost( 'gv_end' ),
					);
					break;

				case 'entry_id':
					$updated_field['key'] = 'entry_id';
					$updated_field['input'] = 'entry_id';
					$updated_field['value'] = $this->rgget_or_rgpost( 'gv_id' );
					break;

				case 'created_by':
					$updated_field['key'] = 'created_by';
					$updated_field['name'] = 'gv_by';
					$updated_field['value'] = $this->rgget_or_rgpost( 'gv_by' );
					$updated_field['choices'] = self::get_created_by_choices( $view );
					break;
				
				case 'is_approved':
					$updated_field['key'] = 'is_approved';
					$updated_field['value'] = $this->rgget_or_rgpost( 'filter_is_approved' );
					$updated_field['choices'] = self::get_is_approved_choices();
					break;
			}

			$search_fields[ $k ] = $updated_field;
		}

		gravityview()->log->debug( 'Calculated Search Fields: ', array( 'data' => $search_fields ) );

		/**
		 * @filter `gravityview_widget_search_filters` Modify what fields are shown. The order of the fields in the $search_filters array controls the order as displayed in the search bar widget.
		 * @param array $search_fields Array of search filters with `key`, `label`, `value`, `type`, `choices` keys
		 * @param GravityView_Widget_Search $this Current widget object
		 * @param array $widget_args Args passed to this method. {@since 1.8}
		 * @param \GV\Template_Context $context {@since 2.0}
		 * @var array
		 */
		$gravityview_view->search_fields = apply_filters( 'gravityview_widget_search_filters', $search_fields, $this, $widget_args, $context );

		$gravityview_view->search_layout = ! empty( $widget_args['search_layout'] ) ? $widget_args['search_layout'] : 'horizontal';

		/** @since 1.14 */
		$gravityview_view->search_mode = ! empty( $widget_args['search_mode'] ) ? $widget_args['search_mode'] : 'any';

		$custom_class = ! empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';

		$gravityview_view->search_class = self::get_search_class( $custom_class );

		$gravityview_view->search_clear = ! empty( $widget_args['search_clear'] ) ? $widget_args['search_clear'] : false;

		if ( $this->has_date_field( $search_fields ) ) {
			// enqueue datepicker stuff only if needed!
			$this->enqueue_datepicker();
		}

		$this->maybe_enqueue_flexibility();

		$gravityview_view->render( 'widget', 'search', false );
	}

	/**
	 * Get the search class for a search form
	 *
	 * @since 1.5.4
	 *
	 * @return string Sanitized CSS class for the search form
	 */
	public static function get_search_class( $custom_class = '' ) {
		$gravityview_view = GravityView_View::getInstance();

		$search_class = 'gv-search-'.$gravityview_view->search_layout;

		if ( ! empty( $custom_class )  ) {
			$search_class .= ' '.$custom_class;
		}

		/**
		 * @filter `gravityview_search_class` Modify the CSS class for the search form
		 * @param string $search_class The CSS class for the search form
		 */
		$search_class = apply_filters( 'gravityview_search_class', $search_class );

		// Is there an active search being performed? Used by fe-views.js
		$search_class .= GravityView_frontend::getInstance()->isSearch() ? ' gv-is-search' : '';

		return gravityview_sanitize_html_class( $search_class );
	}


	/**
	 * Calculate the search form action
	 * @since 1.6
	 *
	 * @return string
	 */
	public static function get_search_form_action() {
		$gravityview_view = GravityView_View::getInstance();

		$post_id = $gravityview_view->getPostId() ? $gravityview_view->getPostId() : $gravityview_view->getViewId();

		$url = add_query_arg( array(), get_permalink( $post_id ) );

		return esc_url( $url );
	}

	/**
	 * Get the label for a search form field
	 * @param  array $field      Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys
	 * @param  array $form_field Form field data, as fetched by `gravityview_get_field()`
	 * @return string             Label for the search form
	 */
	private static function get_field_label( $field, $form_field = array() ) {

		$label = \GV\Utils::_GET( 'label', \GV\Utils::get( $field, 'label' ) );

		if ( ! $label ) {

			$label = isset( $form_field['label'] ) ? $form_field['label'] : '';

			switch( $field['field'] ) {
				case 'search_all':
					$label = __( 'Search Entries:', 'gravityview' );
					break;
				case 'entry_date':
					$label = __( 'Filter by date:', 'gravityview' );
					break;
				case 'entry_id':
					$label = __( 'Entry ID:', 'gravityview' );
					break;
				default:
					// If this is a field input, not a field
					if ( strpos( $field['field'], '.' ) > 0 && ! empty( $form_field['inputs'] ) ) {

						// Get the label for the field in question, which returns an array
						$items = wp_list_filter( $form_field['inputs'], array( 'id' => $field['field'] ) );

						// Get the item with the `label` key
						$values = wp_list_pluck( $items, 'label' );

						// There will only one item in the array, but this is easier
						foreach ( $values as $value ) {
							$label = $value;
							break;
						}
					}
			}
		}

		/**
		 * @filter `gravityview_search_field_label` Modify the label for a search field. Supports returning HTML
		 * @since 1.17.3 Added $field parameter
		 * @param[in,out] string $label Existing label text, sanitized.
		 * @param[in] array $form_field Gravity Forms field array, as returned by `GFFormsModel::get_field()`
		 * @param[in] array $field Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys
		 */
		$label = apply_filters( 'gravityview_search_field_label', esc_attr( $label ), $form_field, $field );

		return $label;
	}

	/**
	 * Prepare search fields to frontend render with other details (label, field type, searched values)
	 *
	 * @param array $field
	 * @return array
	 */
	private function get_search_filter_details( $field ) {

		$gravityview_view = GravityView_View::getInstance();

		$form = $gravityview_view->getForm();

		// for advanced field ids (eg, first name / last name )
		$name = 'filter_' . str_replace( '.', '_', $field['field'] );

		// get searched value from $_GET/$_POST (string or array)
		$value = $this->rgget_or_rgpost( $name );

		// get form field details
		$form_field = gravityview_get_field( $form, $field['field'] );

		$filter = array(
			'key' => $field['field'],
			'name' => $name,
			'label' => self::get_field_label( $field, $form_field ),
			'input' => $field['input'],
			'value' => $value,
			'type' => $form_field['type'],
		);

		// collect choices
		if ( 'post_category' === $form_field['type'] && ! empty( $form_field['displayAllCategories'] ) && empty( $form_field['choices'] ) ) {
			$filter['choices'] = gravityview_get_terms_choices();
		} elseif ( ! empty( $form_field['choices'] ) ) {
			$filter['choices'] = $form_field['choices'];
		}

		if ( 'date_range' === $field['input'] && empty( $value ) ) {
			$filter['value'] = array( 'start' => '', 'end' => '' );
		}

		return $filter;

	}

	/**
	 * Calculate the search choices for the users
	 *
	 * @param \GV\View $view The view
	 * @since develop
	 *
	 * @since 1.8
	 *
	 * @return array Array of user choices (value = ID, text = display name)
	 */
	private static function get_created_by_choices( $view ) {

		/**
		 * filter gravityview/get_users/search_widget
		 * @see \GVCommon::get_users
		 */
		$users = GVCommon::get_users( 'search_widget', array( 'fields' => array( 'ID', 'display_name' ) ) );

		$choices = array();
		foreach ( $users as $user ) {
			/**
			 * @filter `gravityview/search/created_by/text` Filter the display text in created by search choices
			 * @since develop
			 * @param string[in,out] The text. Default: $user->display_name
			 * @param \WP_User $user The user.
			 * @param \GV\View $view The view.
			 */
			$text = apply_filters( 'gravityview/search/created_by/text', $user->display_name, $user, $view );
			$choices[] = array(
				'value' => $user->ID,
				'text' => $text,
			);
		}

		return $choices;
	}

	/**
	 * Calculate the search checkbox choices for approval status
	 *
	 * @since develop
	 *
	 * @return array Array of approval status choices (value = status, text = display name)
	 */
	private static function get_is_approved_choices() {

		$choices = array();
		foreach ( GravityView_Entry_Approval_Status::get_all() as $status ) {
			$choices[] = array(
				'value' => $status['value'],
				'text' => $status['label'],
			);
		}

		return $choices;
	}

	/**
	 * Output the Clear Search Results button
	 * @since 1.5.4
	 */
	public static function the_clear_search_button() {
		$gravityview_view = GravityView_View::getInstance();

		if ( $gravityview_view->search_clear ) {

			$url = strtok( add_query_arg( array() ), '?' );

			echo gravityview_get_link( $url, esc_html__( 'Clear', 'gravityview' ), 'class=button gv-search-clear' );

		}
	}

	/**
	 * Based on the search method, fetch the value for a specific key
	 *
	 * @since 1.16.4
	 *
	 * @param string $name Name of the request key to fetch the value for
	 *
	 * @return mixed|string Value of request at $name key. Empty string if empty.
	 */
	private function rgget_or_rgpost( $name ) {
		$value = \GV\Utils::_REQUEST( $name );

		$value = stripslashes_deep( $value );

		$value = gv_map_deep( $value, 'rawurldecode' );

		$value = gv_map_deep( $value, '_wp_specialchars' );

		return $value;
	}


	/**
	 * Require the datepicker script for the frontend GV script
	 * @param array $js_dependencies Array of existing required scripts for the fe-views.js script
	 * @return array Array required scripts, with `jquery-ui-datepicker` added
	 */
	public function add_datepicker_js_dependency( $js_dependencies ) {

		$js_dependencies[] = 'jquery-ui-datepicker';

		return $js_dependencies;
	}

	/**
	 * Modify the array passed to wp_localize_script()
	 *
	 * @param array $js_localization The data padded to the Javascript file
	 * @param array $view_data View data array with View settings
	 *
	 * @return array
	 */
	public function add_datepicker_localization( $localizations = array(), $view_data = array() ) {
		global $wp_locale;

		/**
		 * @filter `gravityview_datepicker_settings` Modify the datepicker settings
		 * @see http://api.jqueryui.com/datepicker/ Learn what settings are available
		 * @see http://www.renegadetechconsulting.com/tutorials/jquery-datepicker-and-wordpress-i18n Thanks for the helpful information on $wp_locale
		 * @param array $js_localization The data padded to the Javascript file
		 * @param array $view_data View data array with View settings
		 */
		$datepicker_settings = apply_filters( 'gravityview_datepicker_settings', array(
			'yearRange' => '-5:+5',
			'changeMonth' => true,
			'changeYear' => true,
			'closeText' => esc_attr_x( 'Close', 'Close calendar', 'gravityview' ),
			'prevText' => esc_attr_x( 'Prev', 'Previous month in calendar', 'gravityview' ),
			'nextText' => esc_attr_x( 'Next', 'Next month in calendar', 'gravityview' ),
			'currentText' => esc_attr_x( 'Today', 'Today in calendar', 'gravityview' ),
			'weekHeader' => esc_attr_x( 'Week', 'Week in calendar', 'gravityview' ),
			'monthStatus'       => __( 'Show a different month', 'gravityview' ),
			'monthNames'        => array_values( $wp_locale->month ),
			'monthNamesShort'   => array_values( $wp_locale->month_abbrev ),
			'dayNames'          => array_values( $wp_locale->weekday ),
			'dayNamesShort'     => array_values( $wp_locale->weekday_abbrev ),
			'dayNamesMin'       => array_values( $wp_locale->weekday_initial ),
			// get the start of week from WP general setting
			'firstDay'          => get_option( 'start_of_week' ),
			// is Right to left language? default is false
			'isRTL'             => is_rtl(),
		), $view_data );

		$localizations['datepicker'] = $datepicker_settings;

		return $localizations;

	}

	/**
	 * Register search widget scripts, including Flexibility
	 *
	 * @see https://github.com/10up/flexibility
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script( 'gv-flexibility', plugins_url( 'assets/lib/flexibility/flexibility.js', GRAVITYVIEW_FILE ), array(), \GV\Plugin::$version, true );
	}

	/**
	 * If the current visitor is running IE 8 or 9, enqueue Flexibility
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	private function maybe_enqueue_flexibility() {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/MSIE [8-9]/', $_SERVER['HTTP_USER_AGENT'] ) ) {
			wp_enqueue_script( 'gv-flexibility' );
		}
	}

	/**
	 * Enqueue the datepicker script
	 *
	 * It sets the $gravityview->datepicker_class parameter
	 *
	 * @todo Use own datepicker javascript instead of GF datepicker.js - that way, we can localize the settings and not require the changeMonth and changeYear pickers.
	 * @return void
	 */
	public function enqueue_datepicker() {
		$gravityview_view = GravityView_View::getInstance();

		wp_enqueue_script( 'jquery-ui-datepicker' );

		add_filter( 'gravityview_js_dependencies', array( $this, 'add_datepicker_js_dependency' ) );
		add_filter( 'gravityview_js_localization', array( $this, 'add_datepicker_localization' ), 10, 2 );

		$scheme = is_ssl() ? 'https://' : 'http://';
		wp_enqueue_style( 'jquery-ui-datepicker', $scheme.'ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );

		/**
		 * @filter `gravityview_search_datepicker_class`
		 * Modify the CSS class for the datepicker, used by the CSS class is used by Gravity Forms' javascript to determine the format for the date picker. The `gv-datepicker` class is required by the GravityView datepicker javascript.
		 * @param string $css_class CSS class to use. Default: `gv-datepicker datepicker mdy` \n
		 * Options are:
		 * - `mdy` (mm/dd/yyyy)
		 * - `dmy` (dd/mm/yyyy)
		 * - `dmy_dash` (dd-mm-yyyy)
		 * - `dmy_dot` (dd.mm.yyyy)
		 * - `ymd_slash` (yyyy/mm/dd)
		 * - `ymd_dash` (yyyy-mm-dd)
		 * - `ymd_dot` (yyyy.mm.dd)
		 */
		$datepicker_class = apply_filters( 'gravityview_search_datepicker_class', "gv-datepicker datepicker " . $this->get_datepicker_format() );

		$gravityview_view->datepicker_class = $datepicker_class;
	}

	/**
	 * Retrieve the datepicker format.
	 *
	 * @param bool $date_format Whether to return the PHP date format or the datpicker class name. Default: false.
	 *
	 * @see https://docs.gravityview.co/article/115-changing-the-format-of-the-search-widgets-date-picker
	 *
	 * @return string The datepicker format placeholder, or the PHP date format.
	 */
	private function get_datepicker_format( $date_format = false ) {

		$default_format = 'mdy';

		/**
		 * @filter `gravityview/widgets/search/datepicker/format`
		 * @since 2.1.1
		 * @param string           $format Default: mdy
		 * Options are:
		 * - `mdy` (mm/dd/yyyy)
		 * - `dmy` (dd/mm/yyyy)
		 * - `dmy_dash` (dd-mm-yyyy)
		 * - `dmy_dot` (dd.mm.yyyy)
		 * - `ymd_slash` (yyyy/mm/dd)
		 * - `ymd_dash` (yyyy-mm-dd)
		 * - `ymd_dot` (yyyy.mm.dd)
		 */
		$format = apply_filters( 'gravityview/widgets/search/datepicker/format', $default_format );

		$gf_date_formats = array(
			'mdy' => 'm/d/Y',

			'dmy_dash' => 'd-m-Y',
			'dmy_dot' => 'd.m.Y',
			'dmy' => 'd/m/Y',

			'ymd_slash' => 'Y/m/d',
			'ymd_dash' => 'Y-m-d',
			'ymd_dot' => 'Y.m.d',
		);

		if ( ! $date_format ) {
			// If the format key isn't valid, return default format key
			return isset( $gf_date_formats[ $format ] ) ? $format : $default_format;
		}

		// If the format key isn't valid, return default format value
		return \GV\Utils::get( $gf_date_formats, $format, $gf_date_formats[ $default_format ] );
	}

	/**
	 * If previewing a View or page with embedded Views, make the search work properly by adding hidden fields with query vars
	 *
	 * @since 2.2.1
	 *
	 * @return void
	 */
	public function add_preview_inputs() {
		global $wp;

		if ( ! is_preview() || ! current_user_can( 'publish_gravityviews') ) {
			return;
		}

		// Outputs `preview` and `post_id` variables
		foreach ( $wp->query_vars as $key => $value ) {
			printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $key ), esc_attr( $value ) );
		}

	}

	/**
	 * Get an operator URL override.
	 *
	 * @param array  $get     Where to look for the operator.
	 * @param string $key     The filter key to look for.
	 * @param array  $allowed The allowed operators (whitelist).
	 * @param string $default The default operator.
	 *
	 * @return string The operator.
	 */
	private function get_operator( $get, $key, $allowed, $default ) {
		$operator = \GV\Utils::get( $get, "$key|op", $default );

		/**
		 * @filter `gravityview/search/operator_whitelist` An array of allowed operators for a field.
		 * @param[in,out] string[] A whitelist of allowed operators.
		 * @param string The filter name.
		 */
		$allowed = apply_filters( 'gravityview/search/operator_whitelist', $allowed, $key );

		if ( ! in_array( $operator, $allowed, true ) ) {
			$operator = $default;
		}

		return $operator;
	}


} // end class

new GravityView_Widget_Search;

if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
	return;
}

/**
 * A GF_Query condition that allows user data searches.
 */
class GravityView_Widget_Search_Author_GF_Query_Condition extends \GF_Query_Condition {
	public function __construct( $filter, $view ) {
		$this->value = $filter['value'];
		$this->view = $view;
	}

	public function sql( $query ) {
		global $wpdb;

		$user_meta_fields = array(
			'nickname', 'first_name', 'last_name',
		);

		/**
		 * @filter `gravityview/widgets/search/created_by/user_meta_fields` Filter the user meta fields to search.
		 * @param[in,out] array The user meta fields.
		 * @param \GV\View $view The view.
		 */
		$user_meta_fields = apply_filters( 'gravityview/widgets/search/created_by/user_meta_fields', $user_meta_fields, $this->view );

		$user_fields = array(
			'user_nicename', 'user_login', 'display_name', 'user_email', 
		);

		/**
		 * @filter `gravityview/widgets/search/created_by/user_fields` Filter the user fields to search.
		 * @param[in,out] array The user fields.
		 * @param \GV\View $view The view.
		 */
		$user_fields = apply_filters( 'gravityview/widgets/search/created_by/user_fields', $user_fields, $this->view );

		$conditions = array();

		foreach ( $user_fields as $user_field ) {
			$conditions[] = $wpdb->prepare( "`u`.`$user_field` LIKE %s", '%' . $wpdb->esc_like( $this->value ) .  '%' );
		}

		foreach ( $user_meta_fields as $meta_field ) {
			$conditions[] = $wpdb->prepare( "(`um`.`meta_key` = %s AND `um`.`meta_value` LIKE %s)", $meta_field, '%' . $wpdb->esc_like( $this->value ) .  '%' );
		}

		$conditions = '(' . implode( ' OR ', $conditions ) . ')';

		$alias = $query->_alias( null );

		return "(EXISTS (SELECT 1 FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id WHERE (u.ID = `$alias`.`created_by` AND $conditions)))";
	}
}
