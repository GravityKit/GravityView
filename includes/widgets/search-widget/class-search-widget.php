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

if( class_exists('GravityView_Widget') ):

class GravityView_Widget_Search extends GravityView_Widget {

	static $file;
	static $instance;

	private $search_filters = array();

	function __construct() {

		$this->widget_description = __('Search form for searching entries.', 'gravityview' );

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		$default_values = array( 'header' => 0, 'footer' => 0 );

		$settings = array(
			'search_fields' => array(
				'type' => 'hidden',
				'label' => '',
				'class' => 'gv-search-fields-value'
			),
			'search_layout' => array(
				'type' => 'radio',
				'full_width' => true,
				'label' => __( 'Search Layout', 'gravityview' ),
				'value' => 'horizontal',
				'options' => array(
					'horizontal' => __( 'Horizontal', 'gravityview' ),
					'vertical' => __( 'Vertical', 'gravityview' )
				),
			),
			'search_clear' => array(
				'type' => 'checkbox',
				'label' => __( 'Show Clear button', 'gravityview' ),
				'value' => false,
			),
		);
		parent::__construct( __( 'Search Bar', 'gravityview' ) , 'search_bar', $default_values, $settings );

		// frontend - filter entries
		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 1 );

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );


		// admin - add scripts - run at 1100 to make sure GravityView_Admin_Views::add_scripts_and_styles() runs first at 999
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 1100 );
		add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict') );

		// ajax - get the searchable fields
		add_action( 'wp_ajax_gv_searchable_fields', array( 'GravityView_Widget_Search', 'get_searchable_fields' ) );

	}

	static function getInstance() {
		if( empty( self::$instance ) ) {
			self::$instance = new GravityView_Widget_Search;
		}
		return self::$instance;
	}


	/**
	 * Add script to Views edit screen (admin)
	 * @param  mixed $hook
	 */
	function add_scripts_and_styles( $hook ) {
		global $pagenow;

		// Don't process any scripts below here if it's not a GravityView page or the widgets screen
		if( !gravityview_is_admin_page( $hook ) && ( 'widgets.php' !== $pagenow ) ) { return; }

		$script_min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		$script_source = empty( $script_min ) ? '/source' : '';

		wp_enqueue_script( 'gravityview_searchwidget_admin', plugins_url( 'assets/js'.$script_source.'/admin-search-widget'.$script_min.'.js', __FILE__ ), array( 'jquery', 'gravityview_views_scripts' ), GravityView_Plugin::version );


		/**
		 * Input Type labels l10n
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_labels = array(
			'input_text' => esc_html__( 'Text', 'gravityview'),
			'date' => esc_html__('Date', 'gravityview'),
			'select' => esc_html__( 'Select', 'gravityview' ),
			'multiselect' => esc_html__( 'Select (multiple values)', 'gravityview' ),
			'radio' => esc_html__('Radio', 'gravityview'),
			'checkbox' => esc_html__( 'Checkbox', 'gravityview' ),
			'single_checkbox' => esc_html__( 'Checkbox', 'gravityview' ),
			'link' => esc_html__('Links', 'gravityview')
		);

		/**
		 * Input Type groups
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_types = array(
			'text' => array( 'input_text' ),
			'address' => array( 'input_text' ),
			'date' => array( 'date' ),
			'boolean' => array( 'single_checkbox' ),
			'select' => array( 'select', 'radio', 'link' ),
			'multi' => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),
		);

		wp_localize_script( 'gravityview_searchwidget_admin', 'gvSearchVar', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxsearchwidget'),
			'label_nofields' =>  esc_html__( 'No search fields configured yet.', 'gravityview' ),
			'label_addfield' =>  esc_html__( 'Add Search Field', 'gravityview' ),
			'label_searchfield' => esc_html__( 'Search Field', 'gravityview' ),
			'label_inputtype' => esc_html__( 'Input Type', 'gravityview' ),
			'input_labels' => json_encode( $input_labels ),
			'input_types' => json_encode( $input_types ),
		) );

	}

	/**
	 * Add admin script to the whitelist
	 */
	function register_no_conflict( $required ) {
		$required[] = 'gravityview_searchwidget_admin';
		return $required;
	}

	/**
	 * Ajax
	 * Returns the form fields ( only the searchable ones )
	 *
	 * @access public
	 * @return void
	 */
	static function get_searchable_fields() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'gravityview_ajaxsearchwidget' ) ) {
			exit(0);
		}
		$form = '';

		// Fetch the form for the current View
		if( !empty( $_POST['view_id'] ) ) {

			$form = gravityview_get_form_id( $_POST['view_id'] );

		} elseif( !empty( $_POST['formid'] ) ) {

			$form = (int) $_POST['formid'];

		} elseif( !empty( $_POST['template_id'] ) && class_exists('GravityView_Ajax') ) {

			$form = GravityView_Ajax::pre_get_form_fields( $_POST['template_id'] );

		}

		// fetch form id assigned to the view
		$response = GravityView_Widget_Search::render_searchable_fields( $form );

		exit( $response );
	}

	/**
	 * Generates html for the available Search Fields dropdown
	 * @param  string $form_id
	 * @param  string $current (for future use)
	 * @return string
	 */
	static function render_searchable_fields( $form_id = null, $current = '' ) {

		if( is_null( $form_id ) ) {
			return '';
		}

		// Get fields with sub-inputs and no parent
		$fields = gravityview_get_form_fields( $form_id, true, true );

		// start building output

		$output = '<select class="gv-search-fields">';

		$output .= '<option value="search_all" '. selected( 'search_all', $current, false ).' data-inputtypes="text">'. esc_html__( 'Search Everything', 'gravityview') .'</option>';
		$output .= '<option value="entry_date" '. selected( 'entry_date', $current, false ).' data-inputtypes="date">'. esc_html__( 'Entry Date', 'gravityview') .'</option>';
		$output .= '<option value="entry_id" '. selected( 'entry_id', $current, false ).' data-inputtypes="text">'. esc_html__( 'Entry ID', 'gravityview') .'</option>';

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array( 'fileupload', 'post_image', 'post_id'), NULL );

			foreach( $fields as $id => $field ) {

				if( in_array( $field['type'], $blacklist_field_types ) ) { continue; }

				$types = GravityView_Widget_Search::get_search_input_types( $id, $field['type'] );

				$output .= '<option value="'. $id .'" '. selected( $id, $current, false ).'data-inputtypes="'. esc_attr( $types ) .'">'. esc_html( $field['label'] ) .'</option>';
			}

		}

		$output .= '</select>';

		return $output;

	}

	/**
	 * Assign an input type according to the form field type
	 * @see admin-search-widget.js
	 *
	 * @param  string $field_type
	 * @return string
	 */
	static function get_search_input_types( $id = '', $field_type = null ) {

		// @todo - This needs to be improved - many fields have . including products and addresses
		if( false !== strpos( (string)$id, '.' ) && in_array( $field_type, array( 'checkbox' ) ) || in_array( $id, array( 'is_fulfilled' ) ) ) {
			// on/off checkbox
			$types = 'boolean';
		} elseif( in_array( $field_type, array( 'checkbox', 'post_category', 'multiselect' ) ) ) {
			//multiselect
			$types = 'multi';

		} elseif( in_array( $field_type, array( 'select', 'radio' ) ) ) {
			//single select
			$types = 'select';

		} elseif( in_array( $field_type, array( 'date' ) ) || in_array( $id, array( 'payment_date' ) ) ) {
			// date
			$types = 'date';
		} else {
			// input type = text
			$types = 'text';
		}

		return apply_filters( 'gravityview/extension/search/input_type', $types, $field_type );

	}


	/** --- Frontend --- */

	/**
	 * Calculate the search criteria to filter entries
	 * @param  array $search_criteria
	 * @return array
	 */
	function filter_entries( $search_criteria ) {

		do_action( 'gravityview_log_debug', sprintf( '%s[filter_entries] Requested $_GET: ', get_class( $this ) ), $_GET );

		if( empty( $_GET ) || !is_array( $_GET ) ) {
			return $search_criteria;
		}

		// add free search
		if( !empty( $_GET['gv_search'] ) ) {

			// Search for a piece
			$words = explode( ' ',  stripslashes_deep( urldecode( $_GET['gv_search'] ) ) );

			$words = array_filter( $words );

			foreach ( $words as $word ) {
				$search_criteria['field_filters'][] = array(
					'key' => null, // The field ID to search
					'value' => $word, // The value to search
					'operator' => 'contains', // What to search in. Options: `is` or `contains`
				);
			}
		}

		//start date & end date
		$curr_start = esc_attr(rgget('gv_start'));
		$curr_end = esc_attr(rgget('gv_end'));

		if( !empty( $curr_start ) && !empty( $curr_end ) ) {
			$search_criteria['start_date'] = $curr_start;
			$search_criteria['end_date'] = $curr_end;
		}

		// search for a specific entry ID
		if( !empty( $_GET[ 'gv_id' ] ) ) {
			$search_criteria['field_filters'][] = array(
				'key' => 'id',
				'value' => (int)$_GET[ 'gv_id' ],
				'operator' => '='
			);
		}

		// get the other search filters
		foreach( $_GET as $key => $value ) {

			if( 0 !== strpos( $key, 'filter_' ) || empty( $value ) || ( is_array( $value ) && count( $value ) === 1 && empty( $value[0] ) ) ) {
				continue;
			}

			// could return simple filter or multiple filters
			$filter = $this->prepare_field_filter( $key, $value );

			if( isset( $filter[0]['value'] ) ) {
				$search_criteria['field_filters'] = array_merge( $search_criteria['field_filters'], $filter );
			} else {
				$search_criteria['field_filters'][] = $filter;
			}

		}

		/**
		 * Set the Search Mode
		 * - Match ALL filters
		 * - Match ANY filter (default)
		 *
		 * @since 1.5.1
		 */
		$search_criteria['field_filters']['mode'] = apply_filters( 'gravityview/search/mode', 'any' );

		do_action( 'gravityview_log_debug', sprintf( '%s[filter_entries] Returned Search Criteria: ', get_class( $this ) ), $search_criteria );

		return $search_criteria;
	}

	/**
	 * Prepare the field filters to GFAPI
	 *
	 * The type post_category, multiselect and checkbox support multi-select search - each value needs to be separated in an independent filter so we could apply the ANY search mode.
	 *
	 * Format searched values
	 * @param  string $key   $_GET search key
	 * @param  string $value $_GET search value
	 * @return array        1 or 2 deph levels
	 */
	function prepare_field_filter( $key, $value ) {

		$gravityview_view = GravityView_View::getInstance();

		// calculates field_id, removing 'filter_' and for '_' for advanced fields ( like name or checkbox )
		$field_id = str_replace( '_', '.', str_replace( 'filter_', '', $key ) );

		// get form field array
		$form = $gravityview_view->getForm();
		$form_field = gravityview_get_field( $form, $field_id );

		// default filter array
		$filter = array(
			'key' => $field_id,
			'value' => $value
		);

		switch( $form_field['type'] ) {

			case 'post_category':

				if( !is_array( $value ) ) {
					$value = array( $value );
				}

				unset( $filter );

				foreach( $value as $val ) {
					$cat = get_term( $val, 'category' );
					$filter[] = array( 'key' => $field_id, 'value' => esc_attr( $cat->name ) . ':' . $val );
				}

				break;

			case 'multiselect':

				if( !is_array( $value ) ) {
					break;
				}

				unset( $filter );
				foreach( $value as $val ) {
					$filter[] = array( 'key' => $field_id, 'value' => $val );
				}

				break;

			case 'checkbox':
				// convert checkbox on/off into the correct search filter
				if( false !== strpos( $field_id, '.' ) && !empty( $form_field['inputs'] ) && !empty( $form_field['choices'] )) {
					foreach( $form_field['inputs'] as $k => $input ) {
						if( $input['id'] == $field_id ) {
							$filter['value'] = $form_field['choices'][ $k ]['value'];
							break;
						}
					}
				} elseif( is_array( $value ) ) {
					unset( $filter );
					foreach ( $value as $val ) {
						$filter[] = array( 'key' => $field_id, 'value' => $val );
					}
				}

				break;

			case 'name':
			case 'address':

				if( false === strpos( $field_id, '.' ) ) {

					$words = explode( ' ', $value );

					foreach( $words as $word ) {
						if( !empty( $word ) && strlen( $word ) > 1 ) {
							// Keep the same key for each filter
							$filter['value'] = $word;
							// Add a search for the value
							$filters[] = $filter;
						}
					}

					$filter = $filters;

				}

				break;

			case 'date':

				$date = date_create( $value );

				if( $date ) {
					$filter['value'] = $date->format('Y-m-d');
				} else {
					do_action( 'gravityview_log_debug', sprintf( '%s[filter_entries] Date format not valid: ', get_class( $this ) ), $value );
				}

				break;


		} // switch field type

		return $filter;
	}


	/**
	 * Include this extension templates path
	 * @param array $file_paths List of template paths ordered
	 */
	function add_template_path( $file_paths ) {

		// Index 100 is the default GravityView template path.
		$file_paths[102] = self::$file . 'templates/';

		return $file_paths;
	}

	/**
	 * Renders the Search Widget
	 * @param type $widget_args
	 * @param type $content
	 * @param type $context
	 * @return type
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
		$gravityview_view = GravityView_View::getInstance();

		if( empty( $gravityview_view ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend]: $gravityview_view not instantiated yet.', get_class($this)) );
			return;
		}

		// get configured search fields
		$search_fields = !empty( $widget_args['search_fields'] ) ? json_decode( $widget_args['search_fields'], true ) : '';

		if( empty( $search_fields ) || !is_array( $search_fields ) ) {
			do_action('gravityview_log_debug', sprintf( '%s[render_frontend] No search fields configured for widget:', get_class( $this ) ), $widget_args );
			return;
		}

		$has_date = false;

		// prepare fields
		foreach( $search_fields as $k => $field ) {

			switch( $field['field'] ) {

				case 'search_all':
					$search_fields[ $k ]['label'] =  __( 'Search Entries:', 'gravityview' );
					$search_fields[ $k ]['key'] = 'search_all';
					$search_fields[ $k ]['input'] = 'search_all';
					$search_fields[ $k ]['value'] = esc_attr( stripslashes_deep( rgget('gv_search') ) );
					break;

				case 'entry_date':
					$search_fields[ $k ]['label'] = __( 'Filter by date:', 'gravityview' );
					$search_fields[ $k ]['key'] = 'entry_date';
					$search_fields[ $k ]['input'] = 'entry_date';
					$search_fields[ $k ]['value'] = array(
						'start' => esc_attr( stripslashes_deep( rgget('gv_start') ) ),
						'end' => esc_attr( stripslashes_deep( rgget('gv_end') ) )
					);
					$has_date = true;
					break;

				case 'entry_id':
					$search_fields[ $k ]['label'] = __( 'Entry ID:', 'gravityview' );
					$search_fields[ $k ]['key'] = 'entry_id';
					$search_fields[ $k ]['input'] = 'entry_id';
					$search_fields[ $k ]['value'] = esc_attr( stripslashes_deep( rgget( 'gv_id' ) ) );
					break;

				default:
					if( $field['input'] === 'date' ) {
						$has_date = true;
					}
					$search_fields[ $k ] = $this->get_search_filter_details( $field );
					break;

			}

		}

		do_action( 'gravityview_log_debug', sprintf( '%s[render_frontend] Calculated Search Fields: ', get_class( $this ) ), $search_fields );

		/**
		 * Modify what fields are shown. The order of the fields in the $search_filters array controls the order as displayed in the search bar widget.
		 * @param array $search_fields Array of search filters with `key`, `label`, `value`, `type` keys
		 * @param $this Current widget object
		 * @var array
		 */
		$gravityview_view->search_fields = apply_filters( 'gravityview_widget_search_filters', $search_fields, $this );

		$gravityview_view->search_layout = !empty( $widget_args['search_layout'] ) ? $widget_args['search_layout'] : 'horizontal';

		$custom_class = !empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';

		$gravityview_view->search_class = self::get_search_class( $custom_class );

		$gravityview_view->search_clear = !empty( $widget_args['search_clear'] ) ? $widget_args['search_clear'] : false;

		if( $has_date ) {
			// enqueue datepicker stuff only if needed!
			$this->enqueue_datepicker();
		}

		$gravityview_view->render('widget', 'search', false );
	}

	/**
	 * Get the search class for a search form
	 *
	 * @since 1.5.4
	 *
	 * @return string Sanitized CSS class for the search form
	 */
	static function get_search_class( $custom_class = '' ) {
		$gravityview_view = GravityView_View::getInstance();

		$search_class = 'gv-search-'.$gravityview_view->search_layout;

		if( !empty( $custom_class )  ) {
			$search_class .= ' '.$custom_class;
		}

		/**
		 * Modify the CSS class for the search form
		 *
		 * @param string $search_class The CSS class for the search form
		 */
		$search_class = apply_filters( 'gravityview_search_class', $search_class );

		// Is there an active search being performed? Used by fe-views.js
		$search_class .= GravityView_frontend::getInstance()->is_search ? ' gv-is-search' : '';

		return gravityview_sanitize_html_class( $search_class );
	}


	/**
	 * Calculate the search form action
	 * @since 1.6
	 *
	 * @return string
	 */
	static function get_search_form_action() {
		$gravityview_view = GravityView_View::getInstance();

		if( 'wp_widget' == $gravityview_view->getContext() ) {
			$post_id = $gravityview_view->getPostId() ? $gravityview_view->getPostId() : $gravityview_view->getViewId();
			$url = add_query_arg( array(), get_permalink( $post_id ) );
		} else {
			$url = add_query_arg( array() );
		}

		return esc_url( $url );
	}

	/**
	 * Get the label for a search form field
	 * @param  array $field      Field setting as sent by the GV configuration - has `field` and `input` (input type) keys
	 * @param  array $form_field Form field data, as fetched by `gravityview_get_field()`
	 * @return string             Label for the search form
	 */
	private function get_field_label( $field, $form_field ) {

		$label = isset( $form_field['label'] ) ? $form_field['label'] : '';

		// If this is a field input, not a field
		if( strpos( $field['field'], '.' ) > 0 && !empty( $form_field['inputs'] ) ) {

			// Get the label for the field in question, which returns an array
			$items = wp_list_filter( $form_field['inputs'], array('id' => $field['field']) );

			// Get the item with the `label` key
			$values = wp_list_pluck( $items, 'label' );

			// There will only one item in the array, but this is easier
			foreach ( $values as $value ) {
				$label = $value;
				break;
			}

		} elseif( 'is_fulfilled' === $field['field'] ) {
			$label = __( 'Is Fulfilled', 'gravityview' );
		}

		/**
		 * Modify the label for a search field
		 * @param string $label Existing label text
		 * @param array $form_field Gravity Forms field array, as returned by `GFFormsModel::get_field()`
		 */
		$label = apply_filters( 'gravityview_search_field_label', $label, $form_field );

		return esc_attr( $label );
	}

	/**
	 * Prepare search fields to frontend render with other details (label, field type, searched values)
	 *
	 * @param type $field
	 * @return type
	 */
	private function get_search_filter_details( $field ) {

		$gravityview_view = GravityView_View::getInstance();

		$form = $gravityview_view->getForm();

		// for advanced field ids (eg, first name / last name )
		$name = 'filter_' . str_replace( '.', '_', $field['field'] );

		// get searched value from $_GET (string or array)
		$value = rgget( $name );

		// get form field details
		$form_field = gravityview_get_field( $form, $field['field'] );

		$filter = array(
			'key' => $field['field'],
			'name' => $name,
			'label' => $this->get_field_label( $field, $form_field ),
			'input' => $field['input'],
			'value' => $value,
			'type' => $form_field['type'],
		);

		// assign the correct label in case it is a boolean field
		if( 'checkbox' === $form_field['type'] && false !== strpos( $filter['key'], '.' ) && !empty( $form_field['inputs'] ) ) {
			foreach( $form_field['inputs'] as $input ) {
				if( $input['id'] == $filter['key'] ) {
					$filter['label'] = $input['label'];
					break;
				}
			}
		}

		// collect choices
		if( 'post_category' === $form_field['type'] && !empty( $form_field['displayAllCategories'] ) && empty( $form_field['choices'] ) ) {
			$filter['choices'] = self::get_post_categories_choices();
		} elseif( !empty( $form_field['choices'] ) ) {
			$filter['choices'] = $form_field['choices'];
		}


		return $filter;

	}


	static private function get_post_categories_choices() {
		$args = array(
			'type'                     => 'post',
			'child_of'                 => 0,
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 0,
			'hierarchical'             => 1,
			'taxonomy'                 => 'category',
		);
		$categories = get_categories( $args );

		if( empty( $categories ) ) {
			return array();
		}

		$choices = array();

		foreach( $categories as $category ) {
			$choices[] = array( 'text' => $category->name, 'value' => $category->term_id );
		}

		return $choices;
	}


	/**
	 * Output the Clear Search Results button
	 * @since 1.5.4
	 */
	public static function the_clear_search_button() {
		$gravityview_view = GravityView_View::getInstance();

		if( $gravityview_view->search_clear ) {

			$url = strtok( add_query_arg( array() ), '?' );

			echo gravityview_get_link( $url, esc_html__( 'Clear', 'gravityview' ), 'class=button gv-search-clear' );

		}
	}



	/**
	 * Require the datepicker script for the frontend GV script
	 * @param array $js_dependencies Array of existing required scripts for the fe-views.js script
	 */
	function add_datepicker_js_dependency( $js_dependencies ) {

		$js_dependencies[] = 'jquery-ui-datepicker';

		return $js_dependencies;
	}

	function add_datepicker_localization( $localizations = array(), $data = array() ) {
		global $wp_locale;

		/**
		 * Modify the datepicker settings
		 *
		 * @link http://api.jqueryui.com/datepicker/ Learn what settings are available
		 * @link http://www.renegadetechconsulting.com/tutorials/jquery-datepicker-and-wordpress-i18n Thanks for the helpful information on $wp_locale
		 * @param array $array Default settings
		 * @var array
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
		), $data );

		$localizations['datepicker'] = $datepicker_settings;

		return $localizations;

	}

	/**
	 * Enqueue the datepicker script
	 *
	 * It sets the $gravityview->datepicker_class parameter
	 *
	 * @todo Use own datepicker javascript instead of GF datepicker.js - that way, we can localize the settings and not require the changeMonth and changeYear pickers.
	 * @filter gravityview_search_datepicker_class Modify the datepicker input class. See
	 * @return void
	 */
	function enqueue_datepicker() {
		$gravityview_view = GravityView_View::getInstance();

		wp_enqueue_script( 'jquery-ui-datepicker' );

		add_filter( 'gravityview_js_dependencies', array( $this, 'add_datepicker_js_dependency') );
		add_filter( 'gravityview_js_localization', array( $this, 'add_datepicker_localization' ), 10, 2 );

		$scheme = is_ssl() ? 'https://' : 'http://';
		wp_enqueue_style( 'jquery-ui-datepicker', $scheme.'ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );

		/**
		 * Modify the CSS class for the datepicker, used by the CSS class is used by Gravity Forms' javascript to determine the format for the date picker.
		 *
		 * The `gv-datepicker` class is required by the GravityView datepicker javascript.
		 *
		 * Options are:
		 *
		 * - `mdy` mm/dd/yyyy
		 * - `dmy` dd/mm/yyyy
		 * - `dmy_dash` dd-mm-yyyy
		 * - `dmy_dot` dd.mm.yyyy
		 * - `ymp_slash` yyyy/mm/dd
		 * - `ymd_dash` yyyy-mm-dd
		 * - `ymp_dot` yyyy.mm.dd
		 *
		 * @param string Existing CSS class
		 * @var string
		 */
		$datepicker_class = apply_filters( 'gravityview_search_datepicker_class', 'gv-datepicker datepicker mdy' );

		$gravityview_view->datepicker_class = $datepicker_class;

	}


} // end class

new GravityView_Widget_Search;

endif; // class exists
