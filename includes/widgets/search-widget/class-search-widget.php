<?php
/**
 * The GravityView New Search widget
 *
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

use GV\GF_Form;
use GV\Grid;
use GV\Search\Fields\Search_Field;
use GV\Search\Fields\Search_Field_All;
use GV\Search\Fields\Search_Field_Gravity_Forms;
use GV\Search\Fields\Search_Field_Search_Mode;
use GV\Search\Fields\Search_Field_Submit;
use GV\Search\Search_Field_Collection;
use GV\View;

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/settings/class-search-widget-settings-visible-fields-only.php';

class GravityView_Widget_Search extends \GV\Widget {

	public        $icon           = 'dashicons-search';

	public static $file;

	public static $instance;

	/**
	 * whether search method is GET or POST ( default: GET )
	 *
	 * @since 1.16.4
	 * @var string $search_method
	 */
	private $search_method = 'get';

	/**
	 * Holds the recorded areas for rendering the settings.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	private array $area_settings = [];

	/**
	 * Contains the context for the search fields to render.
	 *
	 * @since 2.42
	 *
	 * @var array{template_id: string, form_id: int}
	 */
	private array $search_fields_context = [];

	public function __construct() {
		$this->widget_id          = 'search_bar';
		$this->widget_description = esc_html__( 'Search form for searching entries.', 'gk-gravityview' );
		$this->widget_subtitle    = '';

		self::$instance = &$this;
		self::$file = plugin_dir_path( __FILE__ );

		$settings = [
			'search_fields_section' => [
				'type' => 'html',
				'desc' => \Closure::fromCallable( [ $this, 'get_search_sections' ] ),
			],
		];

		if ( ! $this->is_registered() ) {
			// frontend - filter entries
			add_filter( 'gravityview_fe_search_criteria', [ $this, 'filter_entries' ], 10, 3 );

			// frontend - add template path
			add_filter( 'gravityview_template_paths', [ $this, 'add_template_path' ] );

			// admin - add scripts - run at 1100 to make sure GravityView_Admin_Views::add_scripts_and_styles() runs first at 999
			add_action( 'admin_enqueue_scripts', [ $this, 'add_scripts_and_styles' ], 1100 );
			add_filter( 'gravityview_noconflict_scripts', [ $this, 'register_no_conflict' ] );

			// ajax - get the searchable fields
			add_action( 'wp_ajax_gv_searchable_fields', [ 'GravityView_Widget_Search', 'get_searchable_fields' ] );
			add_action( 'wp_ajax_nopriv_gv_searchable_fields', [ 'GravityView_Widget_Search', 'get_searchable_fields' ] );

			add_action( 'gravityview_search_widget_fields_after', [ $this, 'add_preview_inputs' ] );

			add_filter( 'gravityview/api/reserved_query_args', [ $this, 'add_reserved_args' ] );

			add_filter( 'gk/gravityview/search/available-fields', [ $this, 'add_form_search_fields' ], 0, 2 );
			add_filter( 'gravityview_template_search_options', [ $this, 'set_search_field_options' ], 10, 6 );

			add_action( 'gravityview_render_search_active_areas', [ $this, 'render_search_active_areas' ], 10, 3 );
			add_action( 'gravityview_render_available_search_fields', [ $this, 'render_available_search_fields' ], 10, 2 );
			add_action( 'gk/gravityview/template/before-field-render', [ $this, 'record_search_field_context' ], 9, 5 );

			add_action( 'gk/gravityview/admin-views/row/before', [ $this, 'reset_area_recording' ], 10, 4 );
			add_action( 'gk/gravityview/admin-views/row/after', [ $this, 'render_area_settings' ], 10, 5 );
			add_action( 'gk/gravityview/admin-views/area/actions', [ $this, 'add_search_area_settings_button' ], 10, 6 );
			add_filter( 'gravityview_template_area_options', [ $this, 'add_search_area_settings' ], 10, 3 );
		}

		parent::__construct( esc_html__( 'Search Bar', 'gk-gravityview' ), null, [], $settings );

		// calculate the search method (POST / GET)
		$this->set_search_method();
	}

	/**
	 * @return GravityView_Widget_Search
	 */
	public static function getInstance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new GravityView_Widget_Search();
		}

		return self::$instance;
	}

	/**
	 * @since 2.10
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function add_reserved_args( $args ) {
		$args[] = 'gv_search';
		$args[] = 'gv_start';
		$args[] = 'gv_end';
		$args[] = 'gv_id';
		$args[] = 'gv_by';
		$args[] = 'mode';

		/**
		 * Add additional reserved arguments for the search widget.
		 *
		 * @since 2.42
		 *
		 * @param array $args The reserved arguments.
		 */
		$additional_args = apply_filters( 'gk/gravityview/search/additional-reserved-args', [] );

		// Maintain required arguments and add additional arguments.
		$args = array_unique( array_merge( $args, $additional_args ) );

		$get = (array) $_GET;

		// If the fields being searched as reserved; not to be considered user-passed variables
		foreach ( $get as $key => $value ) {
			if ( $key !== $this->convert_request_key_to_filter_key( $key ) ) {
				$args[] = $key;
			}
		}

		return $args;
	}

	/**
	 * Sets the search method to GET (default) or POST
	 *
	 * @since 1.16.4
	 */
	private function set_search_method() {
		/**
		 * @filter `gravityview/search/method` Modify the search form method (GET / POST).
		 * @since  1.16.4
		 *
		 * @param string $search_method Assign an input type according to the form field type. Defaults: `boolean`, `multi`, `select`, `date`, `text`
		 * @param string $field_type    Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
		 */
		$method = apply_filters( 'gravityview/search/method', $this->search_method );

		$method = strtolower( $method );

		$this->search_method = in_array( $method, [ 'get', 'post' ] ) ? $method : 'get';
	}

	/**
	 * Returns the search method
	 *
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
		 *
		 * @see admin-search-widget.js (getSelectInput)
		 */
		$input_types = [
			'text'        => [ 'input_text' ],
			'address'     => [ 'input_text' ],
			'number'      => [ 'input_text', 'number_range' ],
			'date'        => [ 'date', 'date_range' ],
			'entry_date'  => [ 'date_range', 'date' ], // `date_range` is the default for backwards compatibility.
			'boolean'     => [ 'single_checkbox' ],
			'select'      => [ 'select', 'radio', 'link' ],
			'multi'       => [ 'select', 'multiselect', 'radio', 'checkbox', 'link' ],
			'multiselect' => [ 'select', 'multiselect', 'radio', 'checkbox', 'link' ],
			'checkbox'    => [ 'select', 'multiselect', 'radio', 'checkbox', 'link' ],
			'submit'      => [ 'submit' ],
			'search_mode' => [ 'hidden', 'radio' ],

			// hybrids
			'created_by'  => [ 'select', 'radio', 'checkbox', 'multiselect', 'link', 'input_text' ],
			'multi_text'  => [ 'select', 'radio', 'checkbox', 'multiselect', 'link', 'input_text' ],
			'product'     => [ 'select', 'radio', 'link', 'input_text', 'number_range' ],
		];

		/**
		 * Change the types of search fields available to a field type.
		 *
		 * @see GravityView_Widget_Search::get_search_input_labels() for the available input types
		 *
		 * @param array $input_types Associative array: key is field `name`, value is array of GravityView input types (note: use `input_text` for `text`)
		 */
		$input_types = apply_filters( 'gravityview/search/input_types', $input_types );

		return $input_types;
	}

	public static function get_input_types_by_gf_field( $gf_field ) {
		if ( ! $gf_field instanceof GF_Field ) {
			return [ 'input_text' ];
		}

		$field_type = $gf_field->get_input_type();

		$input_types = self::get_input_types_by_field_type();

		// If the field type is not in the array, use the default input type
		if ( ! isset( $input_types[ $field_type ] ) ) {
			$field_type = 'input_text';
		}

		return $input_types[ $field_type ] ?? [ 'input_text' ];
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
		 *
		 * @see admin-search-widget.js (getSelectInput)
		 */
		$input_labels = [
			'input_text'      => esc_html__( 'Text', 'gk-gravityview' ),
			'date'            => esc_html__( 'Date', 'gk-gravityview' ),
			'select'          => esc_html__( 'Select', 'gk-gravityview' ),
			'multiselect'     => esc_html__( 'Select (multiple values)', 'gk-gravityview' ),
			'radio'           => esc_html__( 'Radio', 'gk-gravityview' ),
			'checkbox'        => esc_html__( 'Checkbox', 'gk-gravityview' ),
			'single_checkbox' => esc_html__( 'Checkbox', 'gk-gravityview' ),
			'link'            => esc_html__( 'Links', 'gk-gravityview' ),
			'date_range'      => esc_html__( 'Date range', 'gk-gravityview' ),
			'number_range'    => esc_html__( 'Number range', 'gk-gravityview' ),
			'submit'          => esc_html__( 'Submit Button', 'gk-gravityview' ),
			'hidden'          => esc_html__( 'Hidden Field', 'gk-gravityview' ),
		];

		/**
		 * Change the label of search field input types.
		 *
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
	 *
	 * @param mixed $hook
	 */
	public function add_scripts_and_styles( $hook ) {
		global $pagenow;

		// Don't process any scripts below here if it's not a GravityView page or the widgets screen
		if ( ! gravityview()->request->is_admin( $hook, 'single' ) && ( 'widgets.php' !== $pagenow ) ) {
			return;
		}

		$script_min    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$script_source = empty( $script_min ) ? '/source' : '';

		wp_enqueue_script( 'gravityview_searchwidget_admin',
			plugins_url( 'assets/js' . $script_source . '/admin-search-widget' . $script_min . '.js', __FILE__ ),
			[ 'jquery', 'gravityview_views_scripts' ],
			\GV\Plugin::$version );

		wp_localize_script(
			'gravityview_searchwidget_admin',
			'gvSearchVar',
			[
				'nonce'             => wp_create_nonce( 'gravityview_ajaxsearchwidget' ),
				'label_nofields'    => esc_html__( 'No search fields configured yet.', 'gk-gravityview' ),
				'label_addfield'    => esc_html__( 'Add Search Field', 'gk-gravityview' ),
				'label_label'       => esc_html__( 'Label', 'gk-gravityview' ),
				'label_searchfield' => esc_html__( 'Search Field', 'gk-gravityview' ),
				'label_inputtype'   => esc_html__( 'Input Type', 'gk-gravityview' ),
				'label_ajaxerror'   => esc_html__( 'There was an error loading searchable fields. Save the View or refresh the page to fix this issue.',
					'gk-gravityview' ),
				'input_labels'      => json_encode( self::get_search_input_labels() ),
				'input_types'       => json_encode( self::get_input_types_by_field_type() ),
			]
		);
	}

	/**
	 * Add admin script to the no-conflict scripts allowlist
	 *
	 * @param array $allowed Scripts allowed in no-conflict mode
	 *
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
	 *
	 * @param int|array $form
	 * @param string    $current (for future use)
	 *
	 * @return string
	 */
	public static function render_searchable_fields( $form = null, $current = '' ) {
		if ( is_null( $form ) ) {
			return '';
		}

		$form_id = (int) ( $form['id'] ?? $form );
		// start building output

		$output = '<select class="gv-search-fields">';

		$search_fields = Search_Field_Collection::available_fields( $form_id );

		foreach ( $search_fields->all() as $field ) {
			if (
				$field instanceof Search_Field_Submit
				|| $field instanceof Search_Field_Search_Mode
			) {
				continue;
			}
			$custom_field = $field->to_legacy_format();

			$output .= sprintf(
				'<option value="%s" %s data-inputtypes="%s" data-placeholder="%s">%s</option>',
				$custom_field['field'],
				selected( $custom_field['field'], $current, false ),
				$custom_field['input'],
				$field->get_frontend_label(),
				$custom_field['title'],
			);
		}

		$output .= '</select>';

		return $output;
	}

	/**
	 * Assign an input type according to the form field type
	 *
	 * @see admin-search-widget.js
	 *
	 * @param string|int|float $field_id   Gravity Forms field ID
	 * @param string           $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field
	 *                                     classes)
	 *
	 * @return string GV field search input type ('multi', 'boolean', 'select', 'date', 'text')
	 */
	public static function get_search_input_types( $field_id = '', $field_type = null ) {
		// @todo - This needs to be improved - many fields have . including products and addresses
		if ( false !== strpos( (string) $field_id, '.' ) && in_array( $field_type,
				[ 'checkbox' ] ) || in_array( $field_id, [ 'is_fulfilled' ] ) ) {
			$input_type = 'boolean'; // on/off checkbox
		} elseif ( in_array( $field_type,
			[ 'checkbox', 'post_category', 'multiselect', 'image_choice', 'multi_choice' ] ) ) {
			$input_type = 'multi'; // multiselect
		} elseif ( in_array( $field_id, [ 'payment_status' ] ) ) {
			$input_type = 'multi_text';
		} elseif ( in_array( $field_type, [ 'select', 'radio' ] ) ) {
			$input_type = 'select';
		} elseif ( in_array( $field_type, [ 'date' ] ) || in_array( $field_id, [ 'payment_date' ] ) ) {
			$input_type = 'date';
		} elseif ( in_array( $field_type, [ 'number', 'quantity', 'total' ] ) || in_array( $field_id,
				[ 'payment_amount' ] ) ) {
			$input_type = 'number';
		} elseif ( in_array( $field_type, [ 'product' ] ) ) {
			$input_type = 'product';
		} else {
			$input_type = 'text';
		}

		/**
		 * Modify the search form input type based on field type.
		 *
		 * @since 1.2
		 * @since 1.19.2 Added $field_id parameter
		 *
		 * @param string           $input_type Assign an input type according to the form field type. Defaults: `boolean`, `multi`, `select`, `date`, `text`
		 * @param string           $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
		 * @param string|int|float $field_id   ID of the field being processed
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
	public function add_no_permalink_fields( $search_fields, $object, $widget_args = [] ) {
		/** @global WP_Rewrite $wp_rewrite */
		global $wp_rewrite;

		// Support default permalink structure
		if ( false === $wp_rewrite->using_permalinks() ) {
			// By default, use current post.
			$post_id = 0;

			// We're in the WordPress Widget context, and an overriding post ID has been set.
			if ( ! empty( $widget_args['post_id'] ) ) {
				$post_id = absint( $widget_args['post_id'] );
			} // We're in the WordPress Widget context, and the base View ID should be used
			elseif ( ! empty( $widget_args['view_id'] ) ) {
				$post_id = absint( $widget_args['view_id'] );
			}

			$args = gravityview_get_permalink_query_args( $post_id );

			// Add hidden fields to the search form
			foreach ( $args as $key => $value ) {
				$search_fields[] = [
					'name'  => $key,
					'input' => 'hidden',
					'value' => $value,
				];
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
	 * @param bool          $with_full_field Return full field array, or just field ID? Default: false (just field ID)
	 *
	 *          TODO: Move to \GV\View, perhaps? And return a Field_Collection
	 *          TODO: Use in gravityview()->request->is_search() to calculate whether a valid search
	 *
	 * @return array If no View, returns empty array. Otherwise, returns array of fields configured in widgets and
	 *               Search Bar for a View
	 */
	private function get_view_searchable_fields( $view, $with_full_field = false ) {
		/**
		 * Find all search widgets on the view and get the searchable fields settings.
		 */
		$searchable_fields = [];

		if ( ! $view ) {
			return $searchable_fields;
		}

		/**
		 * Include the sidebar Widgets.
		 */
		$widgets = (array) get_option( 'widget_gravityview_search', [] );

		foreach ( $widgets as $widget ) {
			if ( ! empty( $widget['view_id'] ) && $widget['view_id'] == $view->ID ) {
				if ( $_fields = json_decode( $widget['search_fields'], true ) ) {
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
			if ( ! $widget instanceof self ) {
				continue;
			}

			foreach ( $widget->get_search_fields( $view ) as $field ) {
				if ( empty( $field['form_id'] ) ) {
					$field['form_id'] = $view->form ? $view->form->ID : 0;
				}
				$searchable_fields[] = $with_full_field ? $field : $field['field'];
			}
		}

		if ( ! $with_full_field ) {
			$searchable_fields = array_values( array_unique( $searchable_fields ) );
		}

		/**
		 * @since     2.5.1
		 * @depecated 2.14
		 */
		$searchable_fields = apply_filters_deprecated(
			'gravityview/search/searchable_fields/whitelist',
			[ $searchable_fields, $view, $with_full_field ],
			'2.14',
			'gravityview/search/searchable_fields/allowlist'
		);

		/**
		 * @filter `gravityview/search/searchable_fields/allowlist` Modifies the fields able to be searched using the Search Bar
		 *
		 * @since  2.14
		 *
		 * @param array    $searchable_fields Array of GravityView-formatted fields or only the field ID? Example: [ '1.2', 'created_by' ]
		 * @param \GV\View $view              Object of View being searched.
		 * @param bool     $with_full_field   Does $searchable_fields contain the full field array or just field ID? Default: false (just field ID)
		 */
		$searchable_fields = apply_filters(
			'gravityview/search/searchable_fields/allowlist',
			$searchable_fields,
			$view,
			$with_full_field
		);

		return $searchable_fields;
	}

	/**
	 * Normalize date from datepicker format to Y-m-d format.
	 *
	 * @since 2.42
	 *
	 * @param string $date_string The date string to normalize.
	 *
	 * @return string Normalized date string or empty string if invalid.
	 */
	private function normalize_date( string $date_string ): string {
		if ( empty( $date_string ) ) {
			return '';
		}

		$date = date_create_from_format( $this->get_datepicker_format( true ), $date_string );

		return $date ? $date->format( 'Y-m-d' ) : '';
	}

	/** --- Frontend --- */

	/**
	 * Calculate the search criteria to filter entries
	 *
	 * @param array $search_criteria       The search criteria
	 * @param int   $form_id               The form ID
	 * @param array $args                  Some args
	 *
	 * @param bool  $force_search_criteria Whether to suppress GF_Query filter, internally used in self::gf_query_filter
	 *
	 * @return array
	 */
	public function filter_entries( $search_criteria, $form_id = null, $args = [], $force_search_criteria = false ) {
		if ( ! $force_search_criteria && gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			/**
			 * If GF_Query is available, we can construct custom conditions with nested
			 * booleans on the query, giving up the old ways of flat search_criteria field_filters.
			 */
			add_action( 'gravityview/view/query', [ $this, 'gf_query_filter' ], 10, 3 );

			return $search_criteria; // Return the original criteria, GF_Query modification kicks in later
		}

		if ( 'post' === $this->search_method ) {
			$get = $_POST;
		} else {
			$get = $_GET;
		}

		$view    = \GV\View::by_id( \GV\Utils::get( $args, 'id' ) );
		$view_id = $view ? $view->ID : null;
		$form_id = $view ? $view->form->ID : null;

		gravityview()->log->debug(
			'Requested $_{method}: ',
			[
				'method' => $this->search_method,
				'data'   => $get,
			]
		);

		if ( empty( $get ) || ! is_array( $get ) ) {
			return $search_criteria;
		}

		$get = stripslashes_deep( $get );

		if ( ! is_null( $get ) ) {
			$get = gv_map_deep( $get, 'rawurldecode' );
		}

		// Make sure array key is set up
		$search_criteria['field_filters'] = \GV\Utils::get( $search_criteria, 'field_filters', [] );

		$searchable_fields        = $this->get_view_searchable_fields( $view );
		$searchable_field_objects = $this->get_view_searchable_fields( $view, true );

		/**
		 * @filter `gravityview/search-all-split-words` Search for each word separately or the whole phrase?
		 *
		 * @since  1.20.2
		 * @since  2.19.6 Added $view parameter
		 *
		 * @param bool     $split_words True: split a phrase into words; False: search whole word only [Default: true]
		 * @param \GV\View $view        The View being searched
		 */
		$split_words = apply_filters( 'gravityview/search-all-split-words', true, $view );

		/**
		 * @filter `gravityview/search-trim-input` Remove leading/trailing whitespaces from search value
		 *
		 * @since  2.9.3
		 * @since  2.19.6 Added $view parameter
		 *
		 * @param bool     $trim_search_value True: remove whitespace; False: keep as is [Default: true]
		 * @param \GV\View $view              The View being searched
		 */
		$trim_search_value = apply_filters( 'gravityview/search-trim-input', true, $view );

		// add free search
		if ( isset( $get['gv_search'] ) && '' !== $get['gv_search'] && in_array( 'search_all', $searchable_fields ) ) {
			$search_all_value = $trim_search_value ? trim( $get['gv_search'] ) : $get['gv_search'];

			$criteria = $this->get_criteria_from_query( $search_all_value, $split_words );

			$form = GFAPI::get_form( $form_id );

			$use_json_storage = false;

			foreach ( ( $form['fields'] ?? [] ) as $field ) {
				if ( 'json' === $field->storageType ) {
					$use_json_storage = true;

					break;
				}
			}

			foreach ( $criteria as $criterion ) {
				$params = array_merge(
					[ 'key' => null ],
					$criterion
				);

				$search_criteria['field_filters'][] = $params;

				// Certain form field meta values are stored as JSON, so we need to encode them before searching.
				// This replicates the behavior of GF_Query_JSON_Literal::sql().
				$original_value = $params['value'] ?? '';

				if ( $use_json_storage && $original_value && is_string( $original_value ) ) {
					$value = trim( json_encode( $original_value ), '"' );
					$value = str_replace( '\\', '\\\\', $value );

					if ( $value !== $original_value ) {
						$params['value']                    = $value;
						$search_criteria['field_filters'][] = $params;
					}
				}
			}
		}

		// start date & end date
		if ( in_array( 'entry_date', $searchable_fields ) ) {
			/**
			 * Get and normalize the dates according to the input format.
			 */
			$curr_start = $this->normalize_date($get['gv_start'] ?? '');

			// If gv_end is not explicitly set but gv_start is, use start date as end date.
			$curr_end = isset( $get['gv_end'] )
				? $this->normalize_date( $get['gv_end'] )
				: $curr_start;

			if ( $view ) {
				/**
				 * Override start and end dates if View is limited to some already.
				 */
				$start_date = $view->settings->get( 'start_date' );
				$start_timestamp = strtotime( $curr_start );
				if ( $start_date && $start_timestamp ) {
					$curr_start = $start_timestamp < strtotime( $start_date ) ? $start_date : $curr_start;
				}

				$end_date = $view->settings->get( 'end_date' );
				$end_timestamp = strtotime( $curr_end );
				if ( $end_date && $end_timestamp ) {
					$curr_end = $end_timestamp > strtotime( $end_date ) ? $end_date : $curr_end;
				}
			}

			/**
			 * Whether to adjust the timezone for entries. \n.
			 * `date_created` is stored in UTC format. Convert search date into UTC (also used on templates/fields/date_created.php). \n
			 * This is for backward compatibility before \GF_Query started to automatically apply the timezone offset.
			 *
			 * @since 1.12
			 *
			 * @param boolean $adjust_tz Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting. Default is `false`.
			 * @param string  $context   Where the filter is being called from. `search` in this case.
			 */
			$adjust_tz = apply_filters( 'gravityview_date_created_adjust_timezone', false, 'search' );

			/**
			 * Don't set $search_criteria['start_date'] if start_date is empty as it may lead to bad query results (GFAPI::get_entries)
			 */
			if ( ! empty( $curr_start ) ) {
				$curr_start                    = date( 'Y-m-d H:i:s', strtotime( $curr_start ) );
				$search_criteria['start_date'] = $adjust_tz ? get_gmt_from_date( $curr_start ) : $curr_start;
			}

			if ( ! empty( $curr_end ) ) {
				// Fast-forward 24 hour on the end time
				$curr_end                    = date( 'Y-m-d H:i:s', strtotime( $curr_end ) + DAY_IN_SECONDS );
				$search_criteria['end_date'] = $adjust_tz ? get_gmt_from_date( $curr_end ) : $curr_end;
				if ( strpos( $search_criteria['end_date'],
					'00:00:00' ) ) { // See https://github.com/gravityview/GravityView/issues/1056
					$search_criteria['end_date'] = date( 'Y-m-d H:i:s', strtotime( $search_criteria['end_date'] ) - 1 );
				}
			}
		}

		// search for a specific entry ID
		if ( ! empty( $get['gv_id'] ) && in_array( 'entry_id', $searchable_fields ) ) {
			$search_criteria['field_filters'][] = [
				'key'      => 'id',
				'value'    => absint( $get['gv_id'] ),
				'operator' => $this->get_operator( $get, 'gv_id', [ '=' ], '=' ),
			];
		}

		// search for a specific Created_by ID
		if ( ! empty( $get['gv_by'] ) && in_array( 'created_by', $searchable_fields ) ) {
			$search_criteria['field_filters'][] = [
				'key'      => 'created_by',
				'value'    => $get['gv_by'],
				'operator' => $this->get_operator( $get, 'gv_by', [ '=' ], '=' ),
			];
		}

		// Get search mode passed in URL
		$mode = isset( $get['mode'] ) && in_array( $get['mode'], [ 'any', 'all' ] ) ? $get['mode'] : 'any';

		// get the other search filters
		foreach ( $get as $key => $value ) {
			if ( 0 !== strpos( $key, 'filter_' ) && 0 !== strpos( $key, 'input_' ) ) {
				continue;
			}

			if ( false !== strpos( $key, '|op' ) ) {
				continue; // This is an operator
			}

			$filter_key = $this->convert_request_key_to_filter_key( $key );

			if ( $trim_search_value ) {
				$value = is_array( $value ) ? array_map( 'trim', $value ) : trim( $value );
			}

			if (
				gv_empty( $value, false, false )
				|| (
					is_array( $value ) && 1 === count( $value )
					&& gv_empty( $value[0], false, false )
				)
			) {
				/**
				 * Filter to control if empty field values should be ignored or strictly matched (default: true).
				 *
				 * @since  2.14.2.1
				 *
				 * @param bool     $ignore_empty_values
				 * @param int|null $filter_key
				 * @param int|null $view_id
				 * @param int|null $form_id
				 */
				$ignore_empty_values = apply_filters( 'gravityview/search/ignore-empty-values', true, $filter_key, $view_id, $form_id );

				if ( is_array( $value ) || $ignore_empty_values ) {
					continue;
				}

				$value = '';
			}

			if ( $form_id && '' === $value ) {
				$field = GFAPI::get_field( $form_id, $filter_key );

				// GF_Query casts Number field values to decimal, which may return unexpected result when the value is blank.
				if ( $field && 'number' === $field->type ) {
					$value = '-' . PHP_INT_MAX;
				}
			}

			if ( ! $filter = $this->prepare_field_filter( $filter_key, $value, $view, $searchable_field_objects, $get ) ) {
				continue;
			}

			if ( ! isset( $filter['operator'] ) ) {
				$filter['operator'] = $this->get_operator( $get, $key, [ 'contains' ], 'contains' );
			}

			if ( isset( $filter[0]['value'] ) ) {
				$filter[0]['value'] = $trim_search_value ? trim( $filter[0]['value'] ) : $filter[0]['value'];

				unset( $filter['operator'] );
				$search_criteria['field_filters'] = array_merge( $search_criteria['field_filters'], $filter );

				// if range type, set search mode to ALL
				if ( ! empty( $filter[0]['operator'] ) && in_array( $filter[0]['operator'],
						[ '>=', '<=', '>', '<' ] ) ) {
					$mode = 'all';
				}
			} elseif ( ! empty( $filter ) ) {
				$search_criteria['field_filters'][] = $filter;
			}
		}

		/**
		 * or `any`).
		 *
		 * @since 1.5.1
		 *
		 * @param string $mode Search mode (`any` vs `all`)
		 */
		$search_criteria['field_filters']['mode'] = apply_filters( 'gravityview/search/mode', $mode );

		gravityview()->log->debug( 'Returned Search Criteria: ', [ 'data' => $search_criteria ] );

		unset( $get );

		return $search_criteria;
	}

	/**
	 * Returns a list of quotation marks.
	 *
	 * @since 2.21.1
	 *
	 * @return array List of quotation marks with `opening` and `closing` keys.
	 */
	private function get_quotation_marks() {
		$quotations_marks = [
			'opening' => [ '"', "'", '“', '‘', '«', '‹', '「', '『', '【', '〖', '〝', '〟', '｢' ],
			'closing' => [ '"', "'", '”', '’', '»', '›', '」', '』', '】', '〗', '〞', '〟', '｣' ],
		];

		/**
		 * @filter `gk/gravityview/common/quotation-marks` Modify the quotation marks used to detect quoted searches.
		 *
		 * @since  2.22
		 *
		 * @param array $quotations_marks List of quotation marks with `opening` and `closing` keys.
		 */
		$quotations_marks = apply_filters( 'gk/gravityview/common/quotation-marks', $quotations_marks );

		return $quotations_marks;
	}

	/**
	 * Filters the \GF_Query with advanced logic.
	 *
	 * Drop-in for the legacy flat filters when \GF_Query is available.
	 *
	 * @param \GF_Query   $query   The current query object reference
	 * @param \GV\View    $this    The current view object
	 * @param \GV\Request $request The request object
	 */
	public function gf_query_filter( &$query, $view, $request ) {
		/**
		 * This is a shortcut to get all the needed search criteria.
		 * We feed these into an new GF_Query and tack them onto the current object.
		 */
		$search_criteria = $this->filter_entries( [], null, [ 'id' => $view->ID ], true /** force search_criteria */ );

		/**
		 * Call any userland filters that they might have.
		 */
		remove_filter( 'gravityview_fe_search_criteria', [ $this, 'filter_entries' ], 10, 3 );
		$search_criteria = apply_filters( 'gravityview_fe_search_criteria',
			$search_criteria,
			$view->form->ID,
			$view->settings->as_atts() );
		add_filter( 'gravityview_fe_search_criteria', [ $this, 'filter_entries' ], 10, 3 );

		$query_class = $view->get_query_class();

		if ( empty( $search_criteria['field_filters'] ) ) {
			return;
		}

		$include_global_search_words = $exclude_global_search_words = [];

		foreach ( $search_criteria['field_filters'] as $i => $criterion ) {
			if ( ! empty( $criterion['key'] ?? null ) ) {
				continue;
			}

			if ( 'not contains' === ( $criterion['operator'] ?? '' ) ) {
				$exclude_global_search_words[] = $criterion['value'];
				unset( $search_criteria['field_filters'][ $i ] );
			} elseif ( true === ( $criterion['required'] ?? false ) ) {
				$include_global_search_words[] = $criterion['value'];
				unset( $search_criteria['field_filters'][ $i ] );
			}
		}

		$widgets = $view->widgets->by_id( $this->widget_id );
		if ( $widgets->count() ) {
			/** @var GravityView_Widget_Search $widget */
			foreach ( $widgets->all() as $widget ) {
				$search_fields = $widget->get_search_fields( $view );

				foreach ( $search_fields as $search_field ) {
					if ( 'created_by' === $search_field['field'] && 'input_text' === $search_field['input'] ) {
						$created_by_text_mode = true;
						break 2;
					}
				}
			}
		}
		$extra_conditions = [];
		$mode             = 'any';

		foreach ( $search_criteria['field_filters'] as $key => &$filter ) {
			if ( ! is_array( $filter ) ) {
				if ( in_array( strtolower( $filter ), [ 'any', 'all' ] ) ) {
					$mode = $filter;
				}
				continue;
			}

			// Construct a manual query for unapproved statuses
			if (
				'is_approved' === $filter['key']
				&& in_array( \GravityView_Entry_Approval_Status::UNAPPROVED, (array) $filter['value'], false )
			) {
				$_tmp_query       = new $query_class(
					$view->form->ID,
					[
						'field_filters' => [
							[
								'operator' => 'in',
								'key'      => 'is_approved',
								'value'    => (array) $filter['value'],
							],
							[
								'operator' => 'is',
								'key'      => 'is_approved',
								'value'    => '',
							],
							'mode' => 'any',
						],
					]
				);
				$_tmp_query_parts = $_tmp_query->_introspect();

				$extra_conditions[] = $_tmp_query_parts['where'];

				$filter = false;
				continue;
			}

			// Construct manual query for text mode creator search
			if ( 'created_by' === $filter['key'] && ! empty( $created_by_text_mode ) ) {
				$extra_conditions[] = new GravityView_Widget_Search_Author_GF_Query_Condition( $filter, $view );
				$filter             = false;
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
			 *
			 * @since  2.0 Added $view parameter
			 *
			 * @param string   $operator Existing search operator
			 * @param array    $filter   array with `key`, `value`, `operator`, `type` keys
			 * @param \GV\View $view     The View we're operating on.
			 */
			$filter['operator'] = apply_filters( 'gravityview_search_operator', $filter['operator'], $filter, $view );

			if ( 'is' !== $filter['operator'] && '' === $filter['value'] ) {
				unset( $search_criteria['field_filters'][ $key ] );
			}
		}
		unset( $filter );

		if ( ! empty( $search_criteria['start_date'] ) || ! empty( $search_criteria['end_date'] ) ) {
			$date_criteria = [];

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

		$search_conditions = [];

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
				$_tmp_query       = new $query_class(
					$filter['form_id'],
					[
						'mode'          => 'any',
						'field_filters' => [ $filter ],
					]
				);
				$_tmp_query_parts = $_tmp_query->_introspect();

				/**
				 * @var GF_Query_Condition $search_condition
				 * */
				$search_condition = $_tmp_query_parts['where'];

				if ( empty( $filter['key'] ) && $search_condition->expressions ) {
					$search_conditions[] = $search_condition;
				} else {
					// If the left condition is empty, it is likely a multiple forms filter. In this case, we should retrieve the search condition from the main form.
					if ( ! $search_condition->left && $search_condition->expressions ) {
						$search_condition = $search_condition->expressions[0];
					}

					$left = $search_condition->left;

					// When casting a column value to a certain type (e.g., happens with the Number field), GF_Query_Column is wrapped in a GF_Query_Call class.
					if ( $left instanceof GF_Query_Call && $left->parameters ) {
						// Update columns to include the correct alias.
						$parameters = array_map( static function ( $parameter ) use ( $query ) {
							return $parameter instanceof GF_Query_Column
								? new GF_Query_Column(
									$parameter->field_id,
									$parameter->source,
									$query->_alias( $parameter->field_id,
										$parameter->source,
										$parameter->is_entry_column() ? 't' : 'm' )
								)
								: $parameter;
						}, $left->parameters );

						$left = new GF_Query_Call( $left->function_name, $parameters );
					} elseif ( $left ) {
						$alias = $query->_alias( $left->field_id, $left->source, $left->is_entry_column() ? 't' : 'm' );
						$left  = new GF_Query_Column( $left->field_id, $left->source, $alias );
					}

					if ( $this->is_product_field( $filter ) && ( $filter['is_numeric'] ?? false ) ) {
						$original_left = clone $left;
						$column        = $left instanceof GF_Query_Call ? $left->columns[0] ?? null : $left;
						$column_name   = sprintf( '`%s`.`%s`',
							$column->alias,
							$column->is_entry_column() ? $column->field_id : 'meta_value' );

						// Add the original join back.
						$search_conditions[] = new GF_Query_Condition( $column, null, $column );

						// Split product name for.
						$position = new GF_Query_Call( 'POSITION', [ sprintf( '"|" IN %s', $column_name ) ] );
						$left     = new GF_Query_Call( 'SUBSTR', [
							$column_name,
							sprintf( "%s + 1", $position->sql( $query ) ),
						] );

						// Remove currency symbol and format properly.
						$currency           = RGCurrency::get_currency( GFCommon::get_currency() );
						$symbol             = html_entity_decode( rgar( $currency, 'symbol_left' ) );
						$thousand_separator = rgar( $currency, 'thousand_separator' );
						$decimal_separator  = rgar( $currency, 'decimal_separator' );

						$replacements = [ $symbol => '', $thousand_separator => '' ];
						if ( ',' === $decimal_separator ) {
							$replacements[','] = '.';
						}

						foreach ( $replacements as $key => $value ) {
							$left = new GF_Query_Call( 'REPLACE', [
								$left->sql( $query ),
								'"' . $key . '"',
								'"' . $value . '"',
							] );
						}

						// Return original function call.
						if ( $original_left instanceof GF_Query_Call ) {
							$parameters    = $original_left->parameters;
							$function_name = $original_left->function_name;

							$parameters[0] = $left->sql( $query );
							if ( $function_name === 'CAST' ) {
								$function_name = ' ' . $function_name; // prevent regular `CAST` sql.
								if ( GF_Query::TYPE_DECIMAL === ( $parameters[1] ?? '' ) ) {
									$parameters[1] = 'DECIMAL(65,6)';
								}
								// CAST needs 'AND' as a separator.
								$parameters = [ implode( ' AS ', $parameters ) ];
							}

							$left = new GF_Query_Call( $function_name, $parameters );
						}
					}

					if ( $view->joins && GF_Query_Column::META == $left->field_id ) {
						foreach ( $view->joins as $_join ) {
							$on   = $_join->join_on;
							$join = $_join->join;

							$search_conditions[] = GF_Query_Condition::_or(
							// Join
								new GF_Query_Condition(
									new GF_Query_Column( GF_Query_Column::META,
										$join->ID,
										$query->_alias( GF_Query_Column::META, $join->ID, 'm' ) ),
									$search_condition->operator,
									$search_condition->right
								),
								// On
								new GF_Query_Condition(
									new GF_Query_Column( GF_Query_Column::META,
										$on->ID,
										$query->_alias( GF_Query_Column::META, $on->ID, 'm' ) ),
									$search_condition->operator,
									$search_condition->right
								)
							);
						}
					} else {
						$search_conditions[] = new GF_Query_Condition(
							$left,
							$search_condition->operator,
							$search_condition->right
						);
					}
				}
			}

			if ( $search_conditions ) {
				$search_conditions = 'all' === $mode
					? [ GF_Query_Condition::_and( ...$search_conditions ) ]
					: [ GF_Query_Condition::_or( ...$search_conditions ) ];
			}
		}

		/**
		 * Grab the current clauses. We'll be combining them shortly.
		 */
		$query_parts = $query->_introspect();

		if ( $include_global_search_words ) {
			global $wpdb;
			$extra_conditions[] = new GF_Query_Condition( new GF_Query_Call(
				'EXISTS',
				[
					sprintf(
						'SELECT 1 FROM `%s` WHERE `form_id` = %d AND `entry_id` = `%s`.`id` AND (%s)',
						GFFormsModel::get_entry_meta_table_name(),
						$view->form ? $view->form->ID : 0,
						$query->_alias( null, $view->form ? $view->form->ID : 0 ),
						implode( ' AND ', array_map( static function ( string $word ) use ( $wpdb ) {
							return $wpdb->prepare( '`meta_value` LIKE "%%%s%%"', $word );
						}, $include_global_search_words ) )
					),
				]
			) );
		}

		if ( $exclude_global_search_words ) {
			global $wpdb;
			$extra_conditions[] = new GF_Query_Condition( new GF_Query_Call(
				'NOT EXISTS',
				[
					sprintf(
						'SELECT 1 FROM `%s` WHERE `form_id` = %d AND `entry_id` = `%s`.`id` AND (%s)',
						GFFormsModel::get_entry_meta_table_name(),
						$view->form ? $view->form->ID : 0,
						$query->_alias( null, $view->form ? $view->form->ID : 0 ),
						implode( ' OR ', array_map( static function ( string $word ) use ( $wpdb ) {
							return $wpdb->prepare( '`meta_value` LIKE "%%%s%%"', $word );
						}, $exclude_global_search_words ) )
					),
				]
			) );
		}

		/**
		 * Combine the parts as a new WHERE clause.
		 */
		$where = \GF_Query_Condition::_and(
			...array_merge(
				[ $query_parts['where'] ],
				$search_conditions,
				$extra_conditions
			)
		);
		$query->where( $where );
	}

	/**
	 * Whether the field in the filter is a product field.
	 *
	 * @since 2.22
	 *
	 * @param array $filter The filter object.
	 *
	 * @return bool
	 */
	private function is_product_field( array $filter ): bool {
		$field = GFAPI::get_field( $filter['form_id'] ?? 0, $filter['key'] ?? 0 );

		return $field && \GFCommon::is_product_field( $field->type );
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
		$field_id = str_replace( [ 'filter_', 'input_' ], '', $key );

		// calculates field_id, removing 'filter_' and for '_' for advanced fields ( like name or checkbox )
		if ( preg_match( '/^[0-9_]+$/ism', $field_id ) ) {
			$field_id = str_replace( '_', '.', $field_id );
		}

		return $field_id;
	}

	/**
	 * Prepare the field filters to GFAPI
	 *
	 * The type post_category, multiselect and checkbox support multi-select search - each value needs to be separated
	 * in an independent filter so we could apply the ANY search mode.
	 *
	 * Format searched values
	 *
	 * @since 2.42
	 *
	 * @param string   $value             $_GET/$_POST search value
	 * @param \GV\View $view              The view we're looking at
	 * @param array[]  $searchable_fields The searchable fields as configured by the widget.
	 * @param string[] $get               The $_GET/$_POST array.
	 *
	 * @param string   $filter_key        ID of the field, or entry meta key
	 *
	 * @return array|false 1 or 2 deph levels, false if not allowed
	 * @todo  Set function as private.
	 *
	 */
	public function prepare_field_filter( $filter_key, $value, $view, $searchable_fields, $get = [] ) {
		$key        = $filter_key;
		$filter_key = explode( ':', $filter_key ); // field_id, form_id

		$form = null;

		if ( count( $filter_key ) > 1 ) {
			// form is specified
			[ $field_id, $form_id ] = $filter_key;

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
			$field_id          = reset( $filter_key );
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
		$form_field = is_numeric( $field_id )
			? \GV\GF_Field::by_id( $form, $field_id )
			: \GV\Internal_Field::by_id( $field_id );

		if ( ! $form_field ) {
			return false;
		}

		// default filter array
		$filter = [
			'key'     => $field_id,
			'value'   => $value,
			'form_id' => $form->ID,
		];

		switch ( $form_field->type ) {
			case 'select':
			case 'workflow_user':
			case 'radio':
				$filter['operator'] = $this->get_operator( $get, $key, [ 'is' ], 'is' );
				break;

			case 'post_category':
				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				// Reset filter variable
				$filter = [];

				foreach ( $value as $val ) {
					$cat      = get_term( $val, 'category' );
					$filter[] = [
						'key'      => $field_id,
						'value'    => esc_attr( $cat->name ) . ':' . $val,
						'operator' => $this->get_operator( $get, $key, [ 'is' ], 'is' ),
					];
				}

				break;

			case 'multiselect':
			case 'workflow_multi_user':
				if ( ! is_array( $value ) ) {
					break;
				}

				// Reset filter variable
				$filter = [];

				foreach ( $value as $val ) {
					$filter[] = [
						'key'   => $field_id,
						'value' => $val,
					];
				}

				break;

			case 'checkbox':
				// convert checkbox on/off into the correct search filter.
				// `empty` uses `__isset` on the field, which will return false; even if there are values.
				$inputs  = (array) $form_field->inputs;
				$choices = (array) $form_field->choices;
				if (
					false !== strpos( $field_id, '.' )
					&& ! empty( $inputs )
					&& ! empty( $choices )
				) {
					foreach ( $inputs as $k => $input ) {
						if ( $input['id'] === $field_id ) {
							$filter['value']    = $choices[ $k ]['value'];
							$filter['operator'] = $this->get_operator( $get, $key, [ 'is' ], 'is' );
							break;
						}
					}
				} elseif ( is_array( $value ) ) {
					// Reset filter variable
					$filter = [];

					foreach ( $value as $val ) {
						$filter[] = [
							'key'      => $field_id,
							'value'    => $val,
							'operator' => $this->get_operator( $get, $key, [ 'is' ], 'is' ),
						];
					}
				}

				break;

			case 'name':
			case 'address':
				if ( false === strpos( $field_id, '.' ) ) {
					$words = explode( ' ', $value );

					$filters = [];
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
						if ( $form_field->ID !== $searchable_field['field'] ) {
							continue;
						}

						// Only exact-match dropdowns, not text search
						if ( in_array( $searchable_field['input'], [ 'text', 'search' ], true ) ) {
							continue;
						}

						$input_id = gravityview_get_input_id_from_id( $form_field->ID );

						if ( 4 === $input_id ) {
							$filter['operator'] = $this->get_operator( $get, $key, [ 'is' ], 'is' );
						}
					}
				}

				break;

			case 'payment_date':
			case 'date':
				$date_format = $this->get_datepicker_format( true );

				if ( is_array( $value ) ) {
					// Reset filter variable
					$filter = [];

					foreach ( $value as $k => $date ) {
						if ( empty( $date ) ) {
							continue;
						}

						$operator = 'start' === $k ? '>=' : '<=';

						$filter[] = [
							'key'      => $field_id,
							'value'    => self::get_formatted_date( $date, 'Y-m-d', $date_format ),
							'operator' => $this->get_operator( $get, $key, [ $operator ], $operator ),
						];
					}
				} else {
					$date               = $value;
					$filter['value']    = self::get_formatted_date( $date, 'Y-m-d', $date_format );
					$filter['operator'] = $this->get_operator( $get, $key, [ 'is' ], 'is' );
				}

				if ( 'payment_date' === $key ) {
					$filter['operator'] = 'contains';
				}

				break;
			case 'number':
			case 'quantity':
			case 'product':
			case 'total':
				if ( is_array( $value ) ) {
					$filter = []; // Reset the filter.

					$min = $value['min'] ?? null; // Can't trust `rgar` here.
					$max = $value['max'] ?? null;

					if ( is_numeric( $min ) && is_numeric( $max ) && $min > $max ) {
						// Reverse the polarity!
						[ $min, $max ] = [ $max, $min ];
					}

					if ( is_numeric( $min ) ) {
						$filter[] = [ 'key' => $field_id, 'operator' => '>=', 'value' => $min, 'is_numeric' => true ];
					}
					if ( is_numeric( $max ) ) {
						$filter[] = [ 'key' => $field_id, 'operator' => '<=', 'value' => $max, 'is_numeric' => true ];
					}
				}
				break;
		} // switch field type

		return $filter;
	}

	/**
	 * Get the Field Format form GravityForms
	 *
	 * @since 1.10
	 *
	 * @param GF_Field_Date $field The field object
	 *
	 * @return string Format of the date in the database
	 */
	public static function get_date_field_format( GF_Field_Date $field ) {
		$format     = 'm/d/Y';
		$datepicker = [
			'mdy'       => 'm/d/Y',
			'dmy'       => 'd/m/Y',
			'dmy_dash'  => 'd-m-Y',
			'dmy_dot'   => 'd.m.Y',
			'ymd_slash' => 'Y/m/d',
			'ymd_dash'  => 'Y-m-d',
			'ymd_dot'   => 'Y.m.d',
		];

		if ( ! empty( $field->dateFormat ) && isset( $datepicker[ $field->dateFormat ] ) ) {
			$format = $datepicker[ $field->dateFormat ];
		}

		return $format;
	}

	/**
	 * Format a date value
	 *
	 * @since 2.1.2
	 *
	 * @param string $format       Wanted formatted date
	 *
	 * @param string $value        Date value input
	 * @param string $value_format The value format. Default: Y-m-d
	 *
	 * @return string
	 */
	public static function get_formatted_date( $value = '', $format = 'Y-m-d', $value_format = 'Y-m-d' ) {
		$date = date_create_from_format( $value_format, $value );

		if ( empty( $date ) ) {
			gravityview()->log->debug( 'Date format not valid: {value}', [ 'value' => $value ] );

			return '';
		}

		return $date->format( $format );
	}

	/**
	 * Include this extension templates path
	 *
	 * @param array $file_paths List of template paths ordered
	 */
	public function add_template_path( $file_paths ) {
		// Index 100 is the default GravityView template path.
		$file_paths[102] = self::$file . 'templates/';

		return $file_paths;
	}

	/**
	 * Retrieves the search fields based on the (legacy) configuration.
	 *
	 * @since 2.42
	 *
	 * @param array     $widget_args        The widget's configuration.
	 * @param View|null $view               The View.
	 * @param array     $additional_context Any additional context.
	 *
	 * @return Search_Field_Collection The Search Field Collection.
	 */
	private function get_search_field_collection(
		array $widget_args,
		?View $view,
		array $additional_context = []
	): Search_Field_Collection {
		if ( isset( $widget_args['search_fields_section'] ) ) {
			return Search_Field_Collection::from_configuration(
				(array) $widget_args['search_fields_section'],
				$view,
				$additional_context
			);
		}

		return Search_Field_Collection::from_legacy_configuration(
			$widget_args,
			$view,
			$additional_context
		);
	}
	/**
	 * Renders the Search Widget
	 *
	 * @param array                       $widget_args
	 * @param string                      $content
	 * @param string|\GV\Template_Context $context
	 *
	 * @return void
	 */
	public function render_frontend( $widget_args, $content = '', $context = '' ) {
		if ( $context instanceof \GV\Template_Context ) {
			$view_id = $context->view->ID;
			$view    = $context->view;
		} else {
			$view_id = \GV\Utils::get( $widget_args, 'view_id', 0 );
			$view    = \GV\View::by_id( $view_id );
		}

		$additional_context           = compact( 'context', 'widget_args' );
		$additional_context['widget'] = $this;

		if ( ! $view ) {
			gravityview()->log->error( 'View not found', [ 'data' => $widget_args ] );

			return;
		}

		$search_fields = $this->get_search_field_collection( $widget_args, $view, $additional_context );

		if ( ! $search_fields->count() ) {
			gravityview()->log->debug( 'No search fields configured for widget:', [ 'data' => $widget_args ] );
			return;
		}

		$submit_field = $search_fields->by_type( Search_Field_Submit::class )->first();
		$search_clear = $submit_field && ( $submit_field->to_configuration()['search_clear'] ?? false );

		$search_mode_field = $search_fields->by_type( Search_Field_Search_Mode::class )->first();
		$search_mode       = $search_mode_field && ( $search_mode_field->to_configuration()['mode'] ?? 'any' );

		// Before rendering, we want to make sure the submit and search mode field are added.
		$search_fields = $search_fields->ensure_required_search_fields();

		if ( $search_fields->has_date_field() ) {
			// enqueue datepicker stuff only if needed!
			$this->enqueue_datepicker();
		}

		$search_layout = ( ! empty( $widget_args['search_layout'] ) ? $widget_args['search_layout'] : 'rows' );
		$custom_class  = ! empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';

		$data = [
			'datepicker_class'            => $this->get_datepicker_class(),
			'search_method'               => $this->get_search_method(),
			'search_layout'               => $search_layout,
			'search_mode'                 => ( ! empty( $widget_args['search_mode'] ) ? $widget_args['search_mode'] : $search_mode ),
			'search_clear'                => ( ! empty( $widget_args['search_clear'] ) ? $widget_args['search_clear'] : $search_clear ),
			'view_id'                     => $view_id,
			'form_id'                     => $view->form ? $view->form->ID : 0,
			'search_class'                => self::get_search_class( $custom_class, $search_layout ),
			'permalink_fields'            => $this->add_no_permalink_fields( [], $this, $widget_args ),
			'search_form_action'          => self::get_search_form_action(),
			'search_fields'               => $search_fields,
			'search_rows_search-general'  => Grid::get_rows_from_collection( $search_fields, 'search-general' ),
			'search_rows_search-advanced' => Grid::get_rows_from_collection( $search_fields, 'search-advanced' ),
		];

		GravityView_View::getInstance()->render( 'widget', 'search', false, $data );
	}

	/**
	 * Get the search class for a search form.
	 *
	 * @since 1.5.4
	 * @since 2.42
	 *
	 * @param string $custom_class  Custom class to add to the search form
	 * @param string $search_layout Search layout ("horizontal" or "vertical"). Default: "horizontal".
	 *
	 * @return string Sanitized CSS class for the search form
	 */
	public static function get_search_class( $custom_class = '', $search_layout = 'horizontal' ) {
		$search_class = 'gv-search-' . $search_layout;

		if ( ! empty( $custom_class ) ) {
			$search_class .= ' ' . $custom_class;
		}

		/**
		 * Modify the CSS class for the search form.
		 *
		 * @param string $search_class The CSS class for the search form
		 */
		$search_class = apply_filters( 'gravityview_search_class', $search_class );

		// Is there an active search being performed? Used by fe-views.js
		$search_class .= gravityview()->request->is_search() || GravityView_frontend::getInstance()->isSearch() ? ' gv-is-search' : '';

		return gravityview_sanitize_html_class( $search_class );
	}

	/**
	 * Calculate the search form action
	 *
	 * @since 1.6
	 * @since 2.42
	 *
	 * @return string
	 */
	public static function get_search_form_action( $post_id = 0 ) {
		if ( empty( $post_id ) ) {
			$gravityview_view = GravityView_View::getInstance();

			$post_id = $gravityview_view->getPostId() ? $gravityview_view->getPostId() : $gravityview_view->getViewId();
		}

		$url = add_query_arg( [], get_permalink( $post_id ) );

		/**
		 * Override the search URL.
		 *
		 * @param string $action Where the form submits to.
		 *
		 * Further parameters will be added once adhoc context is added.
		 * Use gravityview()->request until then.
		 */
		return apply_filters( 'gravityview/widget/search/form/action', $url );
	}

	/**
	 * Output the Clear Search Results button
	 *
	 * @since 1.5.4
	 */
	public static function the_clear_search_button() {
		_deprecated_function( __METHOD__,
			'$ver$',
			'The button is now available in the templates as global $data[\'search_clear\']' );
	}

	/**
	 * Require the datepicker script for the frontend GV script
	 *
	 * @param array $js_dependencies Array of existing required scripts for the fe-views.js script
	 *
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
	 * @param array $view_data       View data array with View settings
	 *
	 * @return array
	 */
	public function add_datepicker_localization( $localizations = [], $view_data = [] ) {
		global $wp_locale;

		/**
		 * Modify the datepicker settings.
		 *
		 * @see http://api.jqueryui.com/datepicker/ Learn what settings are available
		 * @see http://www.renegadetechconsulting.com/tutorials/jquery-datepicker-and-wordpress-i18n Thanks for the helpful information on $wp_locale
		 *
		 * @param array $js_localization The data padded to the Javascript file
		 * @param array $view_data       View data array with View settings
		 */
		$datepicker_settings = apply_filters(
			'gravityview_datepicker_settings',
			[
				'yearRange'       => '-5:+5',
				'changeMonth'     => true,
				'changeYear'      => true,
				'closeText'       => esc_attr_x( 'Close', 'Close calendar', 'gk-gravityview' ),
				'prevText'        => esc_attr_x( 'Prev', 'Previous month in calendar', 'gk-gravityview' ),
				'nextText'        => esc_attr_x( 'Next', 'Next month in calendar', 'gk-gravityview' ),
				'currentText'     => esc_attr_x( 'Today', 'Today in calendar', 'gk-gravityview' ),
				'weekHeader'      => esc_attr_x( 'Week', 'Week in calendar', 'gk-gravityview' ),
				'monthStatus'     => __( 'Show a different month', 'gk-gravityview' ),
				'monthNames'      => array_values( $wp_locale->month ),
				'monthNamesShort' => array_values( $wp_locale->month_abbrev ),
				'dayNames'        => array_values( $wp_locale->weekday ),
				'dayNamesShort'   => array_values( $wp_locale->weekday_abbrev ),
				'dayNamesMin'     => array_values( $wp_locale->weekday_initial ),
				// get the start of week from WP general setting
				'firstDay'        => get_option( 'start_of_week' ),
				// is Right to left language? default is false
				'isRTL'           => is_rtl(),
			],
			$view_data
		);

		$localizations['datepicker'] = $datepicker_settings;

		return $localizations;
	}

	/**
	 * Enqueue the datepicker script
	 *
	 * @return void
	 * @todo Use own datepicker javascript instead of GF datepicker.js - that way, we can localize the settings and not
	 *       require the changeMonth and changeYear pickers.
	 */
	public function enqueue_datepicker() {
		$gravityview_view = GravityView_View::getInstance();

		wp_enqueue_script( 'jquery-ui-datepicker' );

		add_filter( 'gravityview_js_dependencies', [ $this, 'add_datepicker_js_dependency' ] );
		add_filter( 'gravityview_js_localization', [ $this, 'add_datepicker_localization' ], 10, 2 );

		$scheme = is_ssl() ? 'https://' : 'http://';
		wp_enqueue_style(
			'jquery-ui-datepicker',
			$scheme . 'ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css',
		);
	}

	private function get_datepicker_class() {
		/**
		 * @filter `gravityview_search_datepicker_class`
		 * Modify the CSS class for the datepicker, used by the CSS class is used by Gravity Forms' javascript to determine the format for the date picker. The `gv-datepicker` class is required by the GravityView datepicker javascript.
		 *
		 * @param string $css_class CSS class to use. Default: `gv-datepicker datepicker mdy` \n
		 *                          Options are:
		 *                          - `mdy` (mm/dd/yyyy)
		 *                          - `dmy` (dd/mm/yyyy)
		 *                          - `dmy_dash` (dd-mm-yyyy)
		 *                          - `dmy_dot` (dd.mm.yyyy)
		 *                          - `ymd_slash` (yyyy/mm/dd)
		 *                          - `ymd_dash` (yyyy-mm-dd)
		 *                          - `ymd_dot` (yyyy.mm.dd)
		 */
		$datepicker_class = apply_filters(
			'gravityview_search_datepicker_class',
			'gv-datepicker datepicker ' . $this->get_datepicker_format(),
		);

		return $datepicker_class;
	}

	/**
	 * Retrieve the datepicker format.
	 *
	 * @see https://docs.gravitykit.com/article/115-changing-the-format-of-the-search-widgets-date-picker
	 *
	 * @param bool $date_format Whether to return the PHP date format or the datpicker class name. Default: false.
	 *
	 * @return string The datepicker format placeholder, or the PHP date format.
	 */
	private function get_datepicker_format( $date_format = false ) {
		$default_format = 'mdy';

		/**
		 * @filter `gravityview/widgets/search/datepicker/format`
		 * @since  2.1.1
		 *
		 * @param string $format Default: mdy
		 *                       Options are:
		 *                       - `mdy` (mm/dd/yyyy)
		 *                       - `dmy` (dd/mm/yyyy)
		 *                       - `dmy_dash` (dd-mm-yyyy)
		 *                       - `dmy_dot` (dd.mm.yyyy)
		 *                       - `ymd_slash` (yyyy/mm/dd)
		 *                       - `ymd_dash` (yyyy-mm-dd)
		 *                       - `ymd_dot` (yyyy.mm.dd)
		 */
		$format = apply_filters( 'gravityview/widgets/search/datepicker/format', $default_format );

		$gf_date_formats = [
			'mdy' => 'm/d/Y',

			'dmy_dash' => 'd-m-Y',
			'dmy_dot'  => 'd.m.Y',
			'dmy'      => 'd/m/Y',

			'ymd_slash' => 'Y/m/d',
			'ymd_dash'  => 'Y-m-d',
			'ymd_dot'   => 'Y.m.d',
		];

		if ( ! $date_format ) {
			// If the format key isn't valid, return default format key
			return isset( $gf_date_formats[ $format ] ) ? $format : $default_format;
		}

		// If the format key isn't valid, return default format value
		return \GV\Utils::get( $gf_date_formats, $format, $gf_date_formats[ $default_format ] );
	}

	/**
	 * If previewing a View or page with embedded Views, make the search work properly by adding hidden fields with
	 * query vars
	 *
	 * @since 2.2.1
	 *
	 * @return void
	 */
	public function add_preview_inputs() {
		global $wp;

		if ( ! is_preview() || ! current_user_can( 'publish_gravityviews' ) ) {
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
	 * @param array  $allowed The allowed operators (allowlist).
	 * @param string $default The default operator.
	 *
	 * @return string The operator.
	 */
	private function get_operator( $get, $key, $allowed, $default ) {
		$operator = \GV\Utils::get( $get, "$key|op", $default );

		/**
		 * @depecated 2.14
		 */
		$allowed = apply_filters_deprecated(
			'gravityview/search/operator_whitelist',
			[ $allowed, $key ],
			'2.14',
			'gravityview/search/operator_allowlist'
		);

		/**
		 * An array of allowed operators for a field.
		 *
		 * @since 2.14
		 *
		 * @param string[] An allowlist of operators.
		 * @param string The filter name.
		 */
		$allowed = apply_filters( 'gravityview/search/operator_allowlist', $allowed, $key );

		if ( ! in_array( $operator, $allowed, true ) ) {
			$operator = $default;
		}

		return $operator;
	}

	/**
	 * Quotes values for a regex.
	 *
	 * @since 2.21.1
	 *
	 * @param array[] $words     The words to quote.
	 * @param string  $delimiter The delimiter.
	 *
	 * @return array[] The quoted words.
	 */
	private static function preg_quote( array $words, string $delimiter = '/' ): array {
		return array_map(
			static function ( string $mark ) use ( $delimiter ): string {
				return preg_quote( $mark, $delimiter );
			},
			$words
		);
	}

	/**
	 * Retrieves the words in with its operator for querying.
	 *
	 * @since 2.21.1
	 *
	 * @param string $query       The search query.
	 * @param bool   $split_words Whether to split the words.
	 *
	 * @return array The search words with their operator.
	 */
	private function get_criteria_from_query( string $query, bool $split_words ): array {
		$words           = [];
		$quotation_marks = $this->get_quotation_marks();

		$regex = sprintf(
			'/(?<match>(\+|\-))?(%s)(?<word>.*?)(%s)/m',
			implode( '|', self::preg_quote( $quotation_marks['opening'] ?? [] ) ),
			implode( '|', self::preg_quote( $quotation_marks['closing'] ?? [] ) )
		);

		if ( preg_match_all( $regex, $query, $matches ) ) {
			$query = str_replace( $matches[0], '', $query );
			foreach ( $matches['word'] as $i => $value ) {
				$operator = '-' === $matches['match'][ $i ] ? 'not contains' : 'contains';
				$required = '+' === $matches['match'][ $i ];
				$words[]  = array_filter( compact( 'operator', 'value', 'required' ) );
			}
		}

		$values = [];
		if ( $query ) {
			$values = $split_words
				? preg_split( '/\s+/', $query )
				: [ preg_replace( '/\s+/', ' ', $query ) ];
		}

		foreach ( $values as $value ) {
			$is_exclude = '-' === ( $value[0] ?? '' );
			$required   = '+' === ( $value[0] ?? '' );
			$words[]    = array_filter( [
				'operator' => $is_exclude ? 'not contains' : 'contains',
				'value'    => ( $is_exclude || $required ) ? substr( $value, 1 ) : $value,
				'required' => $required,
			] );
		}

		return array_filter( $words, static function ( array $word ) {
			return ! empty( $word['value'] ?? '' );
		} );
	}

	/**
	 * Adds search fields for a specific form.
	 *
	 * @since 2.42
	 *
	 * @param Search_Field[] $search_fields The fields.
	 * @param int            $form_id       The form ID.
	 *
	 * @return Search_Field[] The update fields.
	 */
	public function add_form_search_fields( array $search_fields, int $form_id ): array {
		if ( ! $form_id ) {
			return $search_fields;
		}

		$fields = gravityview_get_form_fields( $form_id, true, true );

		/**
		 * Modify the fields that are displayed as searchable in the Search Bar dropdown\n.
		 *
		 * @since 1.17
		 * @see   gravityview_get_form_fields() Used to fetch the fields
		 * @see   GravityView_Widget_Search::get_search_input_types See this method to modify the type of input types allowed for a field
		 *
		 * @param array $fields Array of searchable fields, as fetched by gravityview_get_form_fields()
		 * @param int   $form_id
		 */
		$fields = apply_filters( 'gravityview/search/searchable_fields', $fields, $form_id );

		$blocklist_field_types = apply_filters(
			'gravityview_blocklist_field_types',
			[ 'fileupload', 'post_image', 'post_id', 'section' ]
		);
		$blocklist_sub_fields  = apply_filters(
			'gravityview_blocklist_sub_fields',
			[ 'image_choice', 'multi_choice' ]
		);

		foreach ( $fields as $id => $field ) {
			if (
				in_array( $field['type'], $blocklist_field_types, true )
				|| ( in_array( $field['type'], $blocklist_sub_fields, true ) && null !== $field['parent'] )
			) {
				continue;
			}

			$field['id']      = $id;
			$field['form_id'] = $form_id;

			$field_instance = Search_Field_Gravity_Forms::from_field( $field );
			if ( ! $field_instance ) {
				continue;
			}

			$search_fields[] = $field_instance;
		}

		return $search_fields;
	}

	/**
	 * Returns all the searchable fields for a View in the legacy format.
	 *
	 * @since 2.42
	 *
	 * @param View $view The View.
	 *
	 * @return array{field: string, label:string, input_type:string}[] The searchable fields in the legacy format.
	 */
	private function get_search_fields( View $view ): array {
		$search_fields = [];
		$collection    = $this->get_search_field_collection( $this->configuration->all(), $view );

		foreach ( $collection->all() as $field ) {
			$search_fields[] = $field->to_legacy_format();
		}

		return $search_fields;
	}

	/**
	 * Add the settings for this field.
	 *
	 * @since 2.42
	 *
	 * @param array      $options     The original options.
	 * @param string     $template_id The template ID.
	 * @param string     $field_id    The field ID.
	 * @param string     $context     The area ID.
	 * @param string     $input_type  The (optional) input type.
	 * @param string|int $form_id     The form ID.
	 *
	 * @return array The updated options.
	 */
	final public function set_search_field_options(
		$options = [],
		$template_id = '',
		$field_id = '',
		$context = '',
		$input_type = '',
		$form_id = 0
	): array {
		$search_field = Search_Field_Collection::get_field_by_field_id( (int) $form_id, (string) $field_id );
		if ( ! $search_field ) {
			return $options;
		}

		return $search_field->merge_options( $options );
	}

	/**
	 * Renders the search areas in the settings field.
	 *
	 * @since 2.42
	 *
	 * @param array $field The field configuration.
	 *
	 * @return string
	 */
	private function get_search_sections( array $field ): string {
		global $post;

		$directory_entries_template = $this->search_fields_context['rendering']['template_id'] ?? 'default_table';

		// If no value is present, check if we have a legacy configuration.
		if ( null === ( $field['value'] ?? null ) ) {
			$search_fields = Search_Field_Collection::from_legacy_configuration(
				$this->search_fields_context['settings'] ?? [],
				View::from_post( $post )
			);

			if ( $search_fields->count() > 0 ) {
				// Set the legacy configuration on the field value as the Search Fields configuration.
				$field['value'] = $search_fields->to_configuration();
			}
		}

		ob_start();
		?>

		<div data-search-fields="<?php echo esc_attr( $field['name'] ?? '' ); ?>">
			<div class="gv-section">
				<h4><?php esc_html_e( 'Search fields shown', 'gk-gravityview' ); ?></h4>

				<div class="search-active-fields">
					<?php
					do_action(
						'gravityview_render_search_active_areas',
						$directory_entries_template,
						'search-general',
						$field
					);
					?>
				</div>

				<h4>
					<?php esc_html_e( 'Advanced Search fields shown', 'gk-gravityview' ); ?>
					<span>
						<?php
						esc_html_e(
							'If any Advanced Search fields exist, a link will show to toggle them.',
							'gk-gravityview'
						);
						?>
					</span>
				</h4>

				<div class="search-advanced-active-fields">
					<?php
					do_action(
						'gravityview_render_search_active_areas',
						$directory_entries_template,
						'search-advanced',
						$field
					);
					?>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the search areas.
	 *
	 * @since 2.42
	 *
	 * @param string                          $template_id The current slug of the selected View template.
	 * @param string                          $zone        Either 'search-general' or 'search-advanced'.
	 * @param array{name:string, value:mixed} $data        The search field data.
	 */
	public function render_search_active_areas( string $template_id, string $zone, array $data ): void {
		global $post;
		$admin_views = GravityView_Admin_Views::get_instance();

		$fields    = $data['value'] ?? null;
		$rows      = [ Grid::get_row_by_type( '100' ) ];
		$name      = $data['name'] ?? null;
		$has_value = null !== $fields;

		$is_new = 'auto-draft' === get_post_status( $post );

		if ( $has_value ) {
			$collection = Search_Field_Collection::from_configuration( $fields );
			$rows       = Grid::get_rows_from_collection( $collection, $zone );
		} elseif ( $is_new && 'search-general' === $zone ) {
			$area_key = key( $rows[0] );
			$zone_100 = $zone . '_' . ( $rows[0][ $area_key ][0]['areaid'] ?? 'top' );

			$fields = [
				$zone_100 => [
					Grid::uid() => ( new Search_Field_All() )->to_configuration(),
					Grid::uid() => ( new Search_Field_Submit() )->to_configuration(),
					Grid::uid() => ( new Search_Field_Search_Mode() )->to_configuration(),
				],
			];
		}
		?>

		<div data-grid-connect="search" data-grid-context="<?php echo esc_attr($zone); ?>" class="gv-grid gv-grid-pad gv-grid-border" id="search-<?php echo $zone; ?>-fields">
			<?php
			$type       = 'search';
			$is_dynamic = true;

			echo '<div class="gv-grid-rows-container">';
			ob_start();
			$admin_views->render_active_areas( $template_id, $type, $zone, $rows, $fields );
			$content = ob_get_clean();

			// replace input names.
			echo str_replace(
				[ sprintf( 'name="%ss[', $type ), 'name="areas[' ],
				[ sprintf( 'name="%s[', $name ), sprintf( 'name="%s[', $name ) ],
				$content
			);
			echo '</div>';

			/**
			 * Allows additional content after the zone was rendered.
			 *
			 * @filter `gk/gravityview/admin/view/after-zone`
			 *
			 * @param string $template_id Template ID.
			 * @param string $type        The zone type (field or widget).
			 * @param string $context     Current View context: `directory`, `single`, or `edit` (default: 'single')
			 * @param bool   $is_dynamic  Whether the zone is dynamic.
			 */
			do_action( 'gk/gravityview/admin-views/view/after-zone', $template_id, $type, $zone, $is_dynamic );
			?>
		</div>
		<?php
	}

	/**
	 * Render html for displaying available search fields.
	 *
	 * @since 2.42
	 */
	public function render_available_search_fields( ?int $form_id = 0, ?string $section = null ): void {
		global $post;

		if ( ! $form_id ) {
			$view = View::by_id( $post->ID ?? 0 );
			if ( ! $view instanceof View || ! $view->form instanceof GF_Form ) {
				return;
			}
			$form_id = $view->form->ID ?? 0;
		}

		$search_fields = Search_Field_Collection::available_fields( $form_id, $section );
		if ( ! $search_fields->count() ) {
			return;
		}

		foreach ( $search_fields as $search_field ) {
			echo $search_field;
		}
	}

	/**
	 * Records the rendering context of a search field about to be rendered.
	 *
	 * @since 2.42
	 *
	 * @param string $field_type Either 'widget', 'field' or 'search'.
	 * @param string $key        The key of the settings field.
	 * @param array  $option     The configuration of the settings field.
	 * @param array  $settings   All the values for the current item being rendered.
	 * @param array  $rendering  Extra rendering context added to the action.
	 */
	public function record_search_field_context( string $field_type, string $key, array $option, array $settings, array $rendering ): void {
		if (
			'widget' !== $field_type
			|| 'search_fields_section' !== $key
		) {
			return;
		}

		$this->search_fields_context = [
			'settings'  => $settings,
			'rendering' => $rendering,
		];
	}

	/**
	 * Resets the recorded areas for the next row.
	 *
	 * @since $ver$
	 *
	 * @param bool   $is_dynamic  Whether the area is dynamic.
	 * @param string $template_id The template ID.
	 * @param string $type        The object type (widget or field).
	 * @param string $zone        The render zone.
	 */
	public function reset_area_recording( $is_dynamic, $template_id, $type, $zone ): void {
		if ( 'search' !== $type ) {
			return;
		}

		$this->area_settings = [];
	}

	/**
	 * Renders a "Clear all fields" button in the View configuration.
	 *
	 * @since $ver$
	 *
	 * @param array  $area        The area.
	 * @param string $type        The type.
	 * @param array  $values      The values in the area.
	 * @param bool   $is_dynamic  Whether the zone is dynamic.
	 * @param string $template_id The template ID.
	 * @param string $zone        The zone.
	 */
	public function add_search_area_settings_button( $area, $type, $values, $is_dynamic, $template_id, $zone ): void {
		if ( 'search' !== $type ) {
			return;
		}

		$area['settings'] = $values[ $zone . '_' . $area['areaid'] ]['area_settings'] ?? [];
		// Record the area for rendering the settings after the row.
		$this->area_settings[] = $area;

		printf(
			'<a role="button" href="javascript:void(0);" class="gv-search-area-settings" data-areaid="%s" title="%s"><i class="dashicons dashicons-admin-generic"></i></a>',
			esc_attr( $zone . '_' . $area['areaid'] ),
			esc_attr__( 'Configure Area Settings', 'gk-gravityview' ),
		);
	}

	/**
	 * Registers the area settings for the search fields.
	 *
	 * @since $ver$
	 *
	 * @param array  $settings    The area settings.
	 * @param string $template_id The template ID.
	 * @param string $field_id    The Field ID.
	 *
	 * @return array
	 */
	public function add_search_area_settings( $settings, $template_id, $field_id ): array {
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		if ( 'area_settings' !== $field_id ) {
			return $settings;
		}

		$settings['layout'] = [
			'type'    => 'select',
			'label'   => __( 'Arrange Fields:', 'gk-gravityview' ),
			'choices' => [
				'column' => esc_html__( 'Stacked (vertical)', 'gk-gravityview' ),
				'row'    => esc_html__( 'Side by side (horizontal)', 'gk-gravityview' ),
			],
			'value'   => 'column',
		];

		return $settings;
	}

	/**
	 * Renders the settings for a search area.
	 *
	 * @since $ver$
	 *
	 * @param bool   $is_dynamic  Whether the area is dynamic.
	 * @param View   $view        The View.
	 * @param string $template_id The template ID.
	 * @param string $type        The object type (widget or field).
	 * @param string $zone        The render zone.
	 */
	public function render_area_settings( $is_dynamic, $view, $template_id, $type, $zone ): void {
		if ( 'search' !== $type || ! $this->area_settings ) {
			return;
		}

		$html = '';

		foreach ( $this->area_settings as $area ) {
			if ( ! isset( $area['areaid'] ) ) {
				continue;
			}

			$settings = GravityView_Render_Settings::render_field_options(
				0,
				'area',
				$template_id,
				'area_settings',
				esc_html__( 'Column', 'gk-gravityview' ),
				$zone . '_' . $area['areaid'],
				null,
				'area_settings',
				$area['settings'] ?? [],
				'area_settings',
				[
					'label' => esc_html__( 'Column Settings', 'gk-gravityview' ),
				]
			);

			// Remove no options indicator to avoid disabling the search widget settings icon.
			$settings = str_replace( GravityView_Render_Settings::NO_OPTIONS, '', $settings );

			$html .= sprintf(
				'<div class="area-settings-container" data-areaid="%s">%s</div>',
				$zone . '_' . $area['areaid'],
				$settings
			);
		}

		if ( $html ) {
			printf( '<div style="display:none;" class="area-settings-wrapper">%s</div>', $html );
		}

		// Reset areas for next rendering.
		$this->area_settings = [];
	}

} // end class

new GravityView_Widget_Search();

if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
	return;
}

/**
 * A GF_Query condition that allows user data searches.
 */
class GravityView_Widget_Search_Author_GF_Query_Condition extends \GF_Query_Condition {
	/**
	 * The View object.
	 *
	 * @since 2.2.2
	 *
	 * @var View
	 */
	private $view;

	/**
	 * The value to search.
	 *
	 * @since 2.2.2
	 *
	 * @var mixed
	 */
	private $value;

	public function __construct( $filter, $view ) {
		$this->value = $filter['value'];
		$this->view  = $view;
	}

	/**
	 * Serializes the object.
	 *
	 * @since 2.42
	 *
	 * @return array THe serialized data.
	 */
	public function __serialize(): array {
		return [
			'view_id' => $this->view->ID,
			'value'   => $this->value,
		];
	}

	/**
	 * Deserializes the object.
	 *
	 * @since 2.42
	 */
	public function __unserialize( array $data ): void {
		$this->value = $data['value'];
		$this->view  = View::by_id( $data['view_id'] ?? 0 );
	}

	public function sql( $query ) {
		global $wpdb;

		$user_meta_fields = [
			'nickname',
			'first_name',
			'last_name',
		];

		/**
		 * Filter the user meta fields to search.
		 *
		 * @param array The user meta fields.
		 * @param \GV\View $view The view.
		 */
		$user_meta_fields = apply_filters(
			'gravityview/widgets/search/created_by/user_meta_fields',
			$user_meta_fields,
			$this->view
		);

		$user_fields = [
			'user_nicename',
			'user_login',
			'display_name',
			'user_email',
		];

		/**
		 * Filter the user fields to search.
		 *
		 * @param array The user fields.
		 * @param \GV\View $view The view.
		 */
		$user_fields = apply_filters( 'gravityview/widgets/search/created_by/user_fields', $user_fields, $this->view );

		$conditions = [];

		foreach ( $user_fields as $user_field ) {
			$conditions[] = $wpdb->prepare( "`u`.`$user_field` LIKE %s", '%' . $wpdb->esc_like( $this->value ) . '%' );
		}

		foreach ( $user_meta_fields as $meta_field ) {
			$conditions[] = $wpdb->prepare(
				'(`um`.`meta_key` = %s AND `um`.`meta_value` LIKE %s)',
				$meta_field,
				'%' . $wpdb->esc_like( $this->value ) . '%'
			);
		}

		$conditions = '(' . implode( ' OR ', $conditions ) . ')';

		$alias = $query->_alias( null );

		return "(EXISTS (SELECT 1 FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id WHERE (u.ID = `$alias`.`created_by` AND $conditions)))";
	}
}
