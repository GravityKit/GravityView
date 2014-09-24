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
				'type' => 'select',
				'label' => __( 'Search Layout', 'gravity-view' ),
				'options' => array(
					'horizontal' => __( 'Horizontal', 'gravity-view' ),
					'vertical' => __( 'Vertical', 'gravity-view' )
				),
			),
		);
		parent::__construct( __( 'Show Search Bar', 'gravity-view' ) , 'search_bar', $default_values, $settings );

		// frontend - filter entries
		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );

		// frontend - add template path
		add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );


		// admin - add scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 999 );
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
		// Don't process any scripts below here if it's not a GravityView page.
		if( !gravityview_is_admin_page( $hook ) ) { return; }

		$script_min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		$script_source = empty( $script_min ) ? '/source' : '';
		wp_enqueue_script( 'gravityview_searchwidget_admin', plugins_url( 'assets/js'.$script_source.'/admin-search-widget'.$script_min.'.js', __FILE__ ), array( 'jquery' ), GravityView_Plugin::version );


		/**
		 * Input Type labels l10n
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_labels = array(
			'input_text' => esc_html__( 'Text', 'gravity-view'),
			'date' => esc_html__('Date', 'gravity-view'),
			'select' => esc_html__( 'Select', 'gravity-view' ),
			'multiselect' => esc_html__( 'Select (multiple values)', 'gravity-view' ),
			'radio' => esc_html__('Radio', 'gravity-view'),
			'checkbox' => esc_html__( 'Checkbox', 'gravity-view' ),
			'link' => esc_html__('Links', 'gravity-view')
		);

		/**
		 * Input Type groups
		 * @see admin-search-widget.js (getSelectInput)
		 * @var array
		 */
		$input_types = array(
			'text' => array( 'input_text' ),
			'date' => array( 'date' ),
			'select' => array( 'select', 'radio', 'link' ),
			'multi' => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),
		);

		wp_localize_script( 'gravityview_searchwidget_admin', 'gvSearchVar', array(
			'nonce' => wp_create_nonce( 'gravityview_ajaxsearchwidget'),
			'label_nofields' =>  esc_html__( 'No search fields configured yet.', 'gravity-view' ),
			'label_addfield' =>  esc_html__( 'Add Search Field', 'gravity-view' ),
			'label_searchfield' => esc_html__( 'Search Field', 'gravity-view' ),
			'label_inputtype' => esc_html__( 'Input Type', 'gravity-view' ),
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
		if( !empty( $_POST['formid'] ) ) {
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

		$output .= '<option value="search_all" '. selected( 'search_all', $current, false ).' data-inputtypes="text">'. esc_html__( 'Search Everything', 'gravity-view') .'</option>';
		$output .= '<option value="entry_date" '. selected( 'entry_date', $current, false ).' data-inputtypes="date">'. esc_html__( 'Entry Date', 'gravity-view') .'</option>';

		if( !empty( $fields ) ) {

			$blacklist_field_types = apply_filters( 'gravityview_blacklist_field_types', array( 'fileupload', 'post_image') );

			foreach( $fields as $id => $field ) {

				if( in_array( $field['type'], $blacklist_field_types ) ) { continue; }

				$types = GravityView_Widget_Search::get_search_input_types( $field['type'] );

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
	static function get_search_input_types( $field_type = null ) {

		if( in_array( $field_type, array( 'checkbox', 'post_category', 'multiselect' ) ) ) {
			//multiselect
			$types = 'multi';

		} elseif( in_array( $field_type, array( 'select', 'radio' ) ) ) {
			//single select
			$types = 'select';

		} elseif( in_array( $field_type, array( 'date' ) ) ) {
			// date
			$types = 'date';
		} else {
			// input type = text
			$types = 'text';
		}

		return apply_filters( 'gravityview/extension/search/input_type', $types, $field_type );

	}


	/** Frontend */

	/**
	 * Calculate the search criteria to filter entries
	 * @param  [type] $search_criteria [description]
	 * @return [type]                  [description]
	 */
	function filter_entries( $search_criteria ) {
error_log( 'this: $_GET ' . print_r( $_GET , true ) );
		// add free search
		if( !empty( $_GET['gv_search'] ) ) {

			// Search for a piece
			$words = explode( ' ',  stripslashes_deep( urldecode( $_GET['gv_search'] ) ) );

			foreach ( $words as $word ) {
				$search_criteria['field_filters'][] = array(
					'key' => null, // The field ID to search
					'value' => esc_attr( $word ), // The value to search
					'operator' => 'contains', // What to search in. Options: `is` or `contains`
				);
			}
		}

		// add specific fields search
		$search_filters = $this->get_search_filters();

		if( !empty( $search_filters ) && is_array( $search_filters ) ) {
			foreach( $search_filters as $k => $filter ) {

				if( !empty( $filter['value'] ) ) {

					// for the fake advanced fields (e.g. fullname), explode the search words
					if( false === strpos('.', $filter['key'] ) && ( 'name' === $filter['type'] || 'address' === $filter['type'] ) ) {

						unset( $filter['type'] );

						$words = explode( ' ', $filter['value'] );

						foreach( $words as $word ) {
							if( !empty( $word ) && strlen( $word ) > 1 ) {
								// Keep the same key, label for each filter
								$filter['value'] = $word;

								// Add a search for the value
								$search_criteria['field_filters'][] = $filter;
							}
						}

						// next field
						continue;
					}

					unset( $filter['type'] );
					$search_criteria['field_filters'][] = $filter;
				}
			}
		}

		//start date & end date
		$curr_start = esc_attr(rgget('gv_start'));
		$curr_end = esc_attr(rgget('gv_end'));

		if( !empty( $curr_start ) && !empty( $curr_end ) ) {
			$search_criteria['start_date'] = $curr_start;
			$search_criteria['end_date'] = $curr_end;
		}

		return $search_criteria;
	}


	/**
	 * Fetch the fields configured as searchable
	 * @return array
	 */
	private function get_search_filters() {
		global $gravityview_view;

		if( !empty( $this->search_filters ) ) {
			return $this->search_filters;
		}

		// hold the output
		$search_filters = array();

		// to avoid repeated fields & generic fields (search_all and entry_date are treated separately)
		$field_ids = array( 'search_all', 'entry_date' );

		if( empty( $gravityview_view ) ) { return $search_filters; }

		$view_data = gravityview_get_current_view_data( $gravityview_view->view_id );
		$form = $gravityview_view->form;

		// get configured search filters (fields)
		if( array_key_exists( 'widgets', $view_data ) && is_array( $view_data['widgets'] ) ) {
			foreach( $view_data['widgets'] as $a => $area ) {

				foreach( $area as $k => $widget ) {

					// If this is not a search widget, don't process.
					if( empty( $widget['search_fields'] ) ) { continue; }

					$search_fields = json_decode( $widget['search_fields'], true );

					if( empty( $search_fields ) || !is_array( $search_fields ) ) { continue; }

					foreach( $search_fields as $field ) {

						// do not repeat fields
						if( in_array( $field['field'], $field_ids ) ) { continue; }

						$field_ids[] = $field['field'];

						$search_filters[] = $this->get_search_filter_details( $field );

					} // endforeach
				} // endforeach
			} // endforeach
		} //endif

		/**
		 * Modify what fields are shown. The order of the fields in the $search_filters array controls the order as displayed in the search bar widget.
		 * @param array $search_filters Array of search filters with `key`, `label`, `value`, `type` keys
		 * @param  GravityView_Widget_Page_Links $this Current widget object
		 * @var array
		 */
		$this->search_filters = apply_filters( 'gravityview_widget_search_filters', $search_filters, $this );

		return $search_filters;
	}

	/**
	 * Populate search fields with other details (label, field type, searched value)
	 * Depending on $context:
	 * 	- 'render' : when the goal is to render the search fields
	 *  - 'filter' : when the goal is to filter the entries
	 *
	 * @param type $field
	 * @param type $context
	 * @return type
	 */
	private function get_search_filter_details( $field, $context = 'filter' ) {

		global $gravityview_view;

		$form = $gravityview_view->form;

		// for advanced field ids (eg, first name / last name )
		$name = 'filter_' . str_replace( '.', '_', $field['field'] );

		// get searched value from $_GET (string or array)
		$value = rgget( $name );

		// get form field details
		$form_field = gravityview_get_field( $form, $field['field'] );

		$filter = array(
			'key' => $field['field'],
			'value' => $value,
			'type' => $form_field['type'],
		);


		switch( $context ) {

			case 'filter':

				// prepare value for filtering
				if( empty( $value ) ) {
					break;
				}

				if( !is_array( $value ) ) {
					$value = array( $value );
				}

				if( 'post_category' === $form_field['type'] ) {
					foreach( $value as $val ) {
						$cat = get_term( $val, 'category' );
						$vals[] = esc_attr( $cat->name ) . ':' . $val;
					}
					$value = implode( ',', $vals );
				} else {
					$value = implode( ',', $value );
				}

				$filter['value'] = $value;

				break;

			case 'render':

				$filter['name'] = $name;
				$filter['label'] = $form_field['label'];
				$filter['input'] = $field['input'];

				if( 'post_category' === $form_field['type'] && !empty( $form_field['displayAllCategories'] ) && empty( $form_field['choices'] ) ) {
					$filter['choices'] = self::get_post_categories_choices();
				} elseif( !empty( $form_field['choices'] ) ) {
					$filter['choices'] = $form_field['choices'];
				}

				break;

		} // end switch

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
		global $gravityview_view;

		if( empty( $gravityview_view )) {
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

			if( $field['field'] === 'search_all' ) {
				$search_fields[ $k ]['key'] = 'search_all';
				$search_fields[ $k ]['input'] = 'search_all';
				$search_fields[ $k ]['value'] = esc_attr( stripslashes_deep( rgget('gv_search') ) );

			} elseif( $field['field'] === 'entry_date' ) {
				$search_fields[ $k ]['key'] = 'entry_date';
				$search_fields[ $k ]['input'] = 'entry_date';
				$search_fields[ $k ]['value'] = array(
					'start' => esc_attr( stripslashes_deep( rgget('gv_start') ) ),
					'end' => esc_attr( stripslashes_deep( rgget('gv_end') ) )
				);
				$has_date = true;
			} else {
				$search_fields[ $k ] = $this->get_search_filter_details( $field, 'render' );
			}

		}

		$gravityview_view->search_fields = $search_fields;
		$gravityview_view->search_layout = !empty( $widget_args['search_layout'] ) ? $widget_args['search_layout'] : 'horizontal';

		if( $has_date ) {
			// enqueue datepicker stuff only if needed!
			$this->enqueue_datepicker();
		}

		$gravityview_view->render('widget', 'search' );
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
			'closeText' => esc_attr_x( 'Close', 'Close calendar', 'gravity-view' ),
			'prevText' => esc_attr_x( 'Prev', 'Previous month in calendar', 'gravity-view' ),
			'nextText' => esc_attr_x( 'Next', 'Next month in calendar', 'gravity-view' ),
			'currentText' => esc_attr_x( 'Today', 'Today in calendar', 'gravity-view' ),
			'weekHeader' => esc_attr_x( 'Week', 'Week in calendar', 'gravity-view' ),
			'monthStatus'       => __( 'Show a different month', 'gravity-view' ),
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
		global $gravityview_view;

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
