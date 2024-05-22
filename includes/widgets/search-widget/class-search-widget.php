<?php
/**
 * The GravityView New Search widget
 *
 * @package   GravityView-DataTables-Ext
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class GravityView_Widget_Search extends \GV\Widget {

	public $icon = 'dashicons-search';

	public static $file;
	public static $instance;

	private $search_filters = array();

	/**
	 * whether search method is GET or POST ( default: GET )
	 *
	 * @since 1.16.4
	 * @var string $search_method
	 */
	private $search_method = 'get';

	public function __construct() {

		$this->widget_id          = 'search_bar';
		$this->widget_description = esc_html__( 'Search form for searching entries.', 'gk-gravityview' );
		$this->widget_subtitle = '';

		self::$instance = &$this;

		self::$file = plugin_dir_path( __FILE__ );

		$default_values = array(
			'header' => 0,
			'footer' => 0,
		);

		$settings = array(
			'search_fields_section' => array(
				'type' => 'html',
				'id'   => 'search_fields_section',
				'desc' => '<!-- Search fields will be added here! 🔎 -->',
			),
			'search_fields' => array(
				'type' => 'hidden',
				'label' => '',
				'class' => 'gv-search-fields-value',
				'value' => '[{"field":"search_all","input":"input_text"}]', // Default: Search Everything text box
			),
			'search_settings_divider' => array(
				'type' => 'html',
				'id'   => 'search_fields_section',
				'desc' => sprintf( '<h3>%s</h3>', esc_html__( 'Search settings', 'gk-gravityview' ) ),
			),
			'search_layout' => array(
				'type'       => 'radio',
				'full_width' => true,
				'label'      => esc_html__( 'Search Layout', 'gk-gravityview' ),
				'value'      => 'horizontal',
				'options'    => array(
					'horizontal' => esc_html__( 'Horizontal', 'gk-gravityview' ),
					'vertical'   => esc_html__( 'Vertical', 'gk-gravityview' ),
				),
			),
			'search_clear'  => array(
				'type'  => 'checkbox',
				'label' => __( 'Show Clear button', 'gk-gravityview' ),
				'desc'  => __( 'When a search is performed, display a button that removes all search values.', 'gk-gravityview' ),
				'value' => true,
			),
			'search_mode'   => array(
				'type'       => 'radio',
				'full_width' => true,
				'label'      => esc_html__( 'Search Mode', 'gk-gravityview' ),
				'desc'       => __( 'Should search results match all search fields, or any?', 'gk-gravityview' ),
				'value'      => 'any',
				'class'      => 'hide-if-js',
				'options'    => array(
					'any' => esc_html__( 'Match Any Fields', 'gk-gravityview' ),
					'all' => esc_html__( 'Match All Fields', 'gk-gravityview' ),
				),
			),
			'sieve_choices' => array(
				'type'       => 'radio',
				'full_width' => true,
				'label'      => esc_html__( 'Pre-Filter Choices', 'gk-gravityview' ),
				// translators: Do not translate [b], [/b], [link], or [/link]; they are placeholders for HTML and links to documentation.
				'desc'       => strtr(
					esc_html__( 'For fields with choices: Instead of showing all choices for each field, show only field choices that exist in submitted form entries.', 'gk-gravityview' ) .
					'<p><strong>⚠️ ' . esc_html__( 'This setting affects security.', 'gk-gravityview' ) . '</strong> ' . esc_html__( '[link]Learn about the Pre-Filter Choices setting[/link] before enabling it.', 'gk-gravityview' ) . '</p>',
					array(
						'[b]'     => '<strong>',
						'[/b]'    => '</strong>',
						'[link]'  => '<a href="https://docs.gravitykit.com/article/701-s" target="_blank" rel="external noopener nofollower" title="' . esc_attr__( 'This link opens in a new window.', 'gk-gravityview' ) . '">',
						'[/link]' => '</a>',
					)
				),
				'value'      => '0',
				'class'      => 'hide-if-js',
				'options'    => array(
					'0' => esc_html__( 'Show all field choices', 'gk-gravityview' ),
					'1' => esc_html__( 'Only show choices that exist in form entries', 'gk-gravityview' ),
				),
			),
		);

		if ( ! $this->is_registered() ) {
			// frontend - filter entries
			add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 3 );

			// frontend - add template path
			add_filter( 'gravityview_template_paths', array( $this, 'add_template_path' ) );

			// admin - add scripts - run at 1100 to make sure GravityView_Admin_Views::add_scripts_and_styles() runs first at 999
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 1100 );
			add_filter( 'gravityview_noconflict_scripts', array( $this, 'register_no_conflict' ) );

			// ajax - get the searchable fields
			add_action( 'wp_ajax_gv_searchable_fields', array( 'GravityView_Widget_Search', 'get_searchable_fields' ) );

			add_action( 'gravityview_search_widget_fields_after', array( $this, 'add_preview_inputs' ) );

			add_filter( 'gravityview/api/reserved_query_args', array( $this, 'add_reserved_args' ) );

			// All other GravityView-added hooks for this filter run at the default 10.
			add_filter( 'gravityview_widget_search_filters', array( $this, 'maybe_sieve_filter_choices' ), 1000, 4 );
		}

		parent::__construct( esc_html__( 'Search Bar', 'gk-gravityview' ), null, $default_values, $settings );

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
		$input_types = array(
			'text'         => array( 'input_text' ),
			'address'      => array( 'input_text' ),
			'number'       => array( 'input_text', 'number_range' ),
			'date'         => array( 'date', 'date_range' ),
			'entry_date'   => array( 'date_range' ),
			'boolean'      => array( 'single_checkbox' ),
			'select'       => array( 'select', 'radio', 'link' ),
			'multi'        => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),
			'multiselect' => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),
			'checkbox' => array( 'select', 'multiselect', 'radio', 'checkbox', 'link' ),

			// hybrids
			'created_by'   => array( 'select', 'radio', 'checkbox', 'multiselect', 'link', 'input_text' ),
			'multi_text'   => array( 'select', 'radio', 'checkbox', 'multiselect', 'link', 'input_text' ),
			'product'      => array( 'select', 'radio', 'link', 'input_text', 'number_range' ),
		);

		/**
		 * Change the types of search fields available to a field type.
		 *
		 * @see GravityView_Widget_Search::get_search_input_labels() for the available input types
		 * @param array $input_types Associative array: key is field `name`, value is array of GravityView input types (note: use `input_text` for `text`)
		 */
		$input_types = apply_filters( 'gravityview/search/input_types', $input_types );

		return $input_types;
	}

	public static function get_input_types_by_gf_field( $gf_field ) {

		if( ! $gf_field instanceof GF_Field ) {
			return ['input_text'];
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
		$input_labels = array(
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
		);

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
	 * @param  mixed $hook
	 */
	public function add_scripts_and_styles( $hook ) {
		global $pagenow;

		// Don't process any scripts below here if it's not a GravityView page or the widgets screen
		if ( ! gravityview()->request->is_admin( $hook, 'single' ) && ( 'widgets.php' !== $pagenow ) ) {
			return;
		}

		$script_min    = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$script_source = empty( $script_min ) ? '/source' : '';

		wp_enqueue_script( 'gravityview_searchwidget_admin', plugins_url( 'assets/js' . $script_source . '/admin-search-widget' . $script_min . '.js', __FILE__ ), array( 'jquery', 'gravityview_views_scripts' ), \GV\Plugin::$version );

		wp_localize_script(
			'gravityview_searchwidget_admin',
			'gvSearchVar',
			array(
				'nonce'             => wp_create_nonce( 'gravityview_ajaxsearchwidget' ),
				'label_nofields'    => esc_html__( 'No search fields configured yet.', 'gk-gravityview' ),
				'label_addfield'    => esc_html__( 'Add Search Field', 'gk-gravityview' ),
				'label_label'       => esc_html__( 'Label', 'gk-gravityview' ),
				'label_searchfield' => esc_html__( 'Search Field', 'gk-gravityview' ),
				'label_inputtype'   => esc_html__( 'Input Type', 'gk-gravityview' ),
				'label_ajaxerror'   => esc_html__( 'There was an error loading searchable fields. Save the View or refresh the page to fix this issue.', 'gk-gravityview' ),
				'input_labels'      => json_encode( self::get_search_input_labels() ),
				'input_types'       => json_encode( self::get_input_types_by_field_type() ),
			)
		);
	}

	/**
	 * Add admin script to the no-conflict scripts allowlist
	 *
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
	 * @param  int    $form_id
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
				'text' => esc_html__( 'Search Everything', 'gk-gravityview' ),
				'type' => 'text',
			),
			'entry_date' => array(
				'text' => esc_html__( 'Entry Date', 'gk-gravityview' ),
				'type' => 'entry_date',
			),
			'entry_id'   => array(
				'text' => esc_html__( 'Entry ID', 'gk-gravityview' ),
				'type' => 'text',
			),
			'created_by' => array(
				'text' => esc_html__( 'Entry Creator', 'gk-gravityview' ),
				'type' => 'created_by',
			),
			'is_starred' => array(
				'text' => esc_html__( 'Is Starred', 'gk-gravityview' ),
				'type' => 'boolean',
			),
			'is_read'    => array(
				'text'    => esc_html__( 'Is Read', 'gk-gravityview' ),
				'type'    => 'select',
				'choices' => array(
					array(
						'text'  => __( 'Read', 'gk-gravityview' ),
						'value' => '1',
					),
					array(
						'text'  => __( 'Unread', 'gk-gravityview' ),
						'value' => '0',
					),
				),
			),
		);

		if ( gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
			$custom_fields['is_approved'] = array(
				'text' => esc_html__( 'Approval Status', 'gk-gravityview' ),
				'type' => 'multi',
			);
		}

		foreach ( $custom_fields as $custom_field_key => $custom_field ) {
			$output .= sprintf( '<option value="%s" %s data-inputtypes="%s" data-placeholder="%s">%s</option>', $custom_field_key, selected( $custom_field_key, $current, false ), $custom_field['type'], self::get_field_label( array( 'field' => $custom_field_key ) ), $custom_field['text'] );
		}

		// Get fields with sub-inputs and no parent
		$fields = gravityview_get_form_fields( $form_id, true, true );

		/**
		 * Modify the fields that are displayed as searchable in the Search Bar dropdown\n.
		 *
		 * @since 1.17
		 * @see gravityview_get_form_fields() Used to fetch the fields
		 * @see GravityView_Widget_Search::get_search_input_types See this method to modify the type of input types allowed for a field
		 * @param array $fields Array of searchable fields, as fetched by gravityview_get_form_fields()
		 * @param  int $form_id
		 */
		$fields = apply_filters( 'gravityview/search/searchable_fields', $fields, $form_id );

		if ( ! empty( $fields ) ) {

			$blocklist_field_types = apply_filters( 'gravityview_blocklist_field_types', array( 'fileupload', 'post_image', 'post_id', 'section' ), null );

			$blocklist_sub_fields = apply_filters( 'gravityview_blocklist_sub_fields', array( 'image_choice', 'multi_choice' ), null );

			foreach ( $fields as $id => $field ) {

				if ( in_array( $field['type'], $blocklist_field_types ) ) {
					continue;
				}

				if ( in_array( $field['type'], $blocklist_sub_fields ) && NULL !== $field['parent'] ) {
					continue;
				}

				$types = self::get_search_input_types( $id, $field['type'] );

				$output .= '<option value="' . $id . '" ' . selected( $id, $current, false ) . 'data-inputtypes="' . esc_attr( $types ) . '" data-placeholder="'.esc_html( $field['label'] ).'">' . esc_html( $field['text'] ?? $field['label'] ) . '</option>';
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
	 * @param string           $field_type Gravity Forms field type (also the `name` parameter of GravityView_Field classes)
	 *
	 * @return string GV field search input type ('multi', 'boolean', 'select', 'date', 'text')
	 */
	public static function get_search_input_types( $field_id = '', $field_type = null ) {

		// @todo - This needs to be improved - many fields have . including products and addresses
		if ( false !== strpos( (string) $field_id, '.' ) && in_array( $field_type, array( 'checkbox' ) ) || in_array( $field_id, array( 'is_fulfilled' ) ) ) {
			$input_type = 'boolean'; // on/off checkbox
		} elseif ( in_array( $field_type, array( 'checkbox', 'post_category', 'multiselect', 'image_choice','multi_choice' ) ) ) {
			$input_type = 'multi'; // multiselect
		} elseif ( in_array( $field_id, array( 'payment_status' ) ) ) {
			$input_type = 'multi_text';
		} elseif ( in_array( $field_type, array( 'select', 'radio' ) ) ) {
			$input_type = 'select';
		} elseif ( in_array( $field_type, array( 'date' ) ) || in_array( $field_id, array( 'payment_date' ) ) ) {
			$input_type = 'date';
		} elseif ( in_array( $field_type, array( 'number', 'quantity', 'total' ) ) || in_array( $field_id, array( 'payment_amount' ) ) ) {
			$input_type = 'number';
		} elseif ( in_array( $field_type, array( 'product' ) ) ) {
			$input_type = 'product';
		} else {
			$input_type = 'text';
		}

		/**
		 * Modify the search form input type based on field type.
		 *
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
			elseif ( ! empty( $widget_args['view_id'] ) ) {
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
	 * @param bool          $with_full_field Return full field array, or just field ID? Default: false (just field ID)
	 *
	 *          TODO: Move to \GV\View, perhaps? And return a Field_Collection
	 *          TODO: Use in gravityview()->request->is_search() to calculate whether a valid search
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
			if ( $_fields = json_decode( $widget->configuration->get( 'search_fields' ), true ) ) {
				foreach ( $_fields as $field ) {
					if ( empty( $field['form_id'] ) ) {
						$field['form_id'] = $view->form ? $view->form->ID : 0;
					}
					$searchable_fields[] = $with_full_field ? $field : $field['field'];
				}
			}
		}

		/**
		 * @since 2.5.1
		 * @depecated 2.14
		 */
		$searchable_fields = apply_filters_deprecated( 'gravityview/search/searchable_fields/whitelist', array( $searchable_fields, $view, $with_full_field ), '2.14', 'gravityview/search/searchable_fields/allowlist' );

		/**
		 * @filter `gravityview/search/searchable_fields/allowlist` Modifies the fields able to be searched using the Search Bar
		 *
		 * @since 2.14
		 *
		 * @param array $searchable_fields Array of GravityView-formatted fields or only the field ID? Example: [ '1.2', 'created_by' ]
		 * @param \GV\View $view Object of View being searched.
		 * @param bool $with_full_field Does $searchable_fields contain the full field array or just field ID? Default: false (just field ID)
		 */
		$searchable_fields = apply_filters( 'gravityview/search/searchable_fields/allowlist', $searchable_fields, $view, $with_full_field );

		return $searchable_fields;
	}

	/** --- Frontend --- */

	/**
	 * Calculate the search criteria to filter entries
	 *
	 * @param array $search_criteria The search criteria
	 * @param int   $form_id The form ID
	 * @param array $args Some args
	 *
	 * @param bool  $force_search_criteria Whether to suppress GF_Query filter, internally used in self::gf_query_filter
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
			array(
				'method' => $this->search_method,
				'data'   => $get,
			)
		);

		if ( empty( $get ) || ! is_array( $get ) ) {
			return $search_criteria;
		}

		$get = stripslashes_deep( $get );

		if ( ! is_null( $get ) ) {
			$get = gv_map_deep( $get, 'rawurldecode' );
		}

		// Make sure array key is set up
		$search_criteria['field_filters'] = \GV\Utils::get( $search_criteria, 'field_filters', array() );

		$searchable_fields        = $this->get_view_searchable_fields( $view );
		$searchable_field_objects = $this->get_view_searchable_fields( $view, true );

		/**
		 * @filter `gravityview/search-all-split-words` Search for each word separately or the whole phrase?
		 *
		 * @since 1.20.2
		 * @since 2.19.6 Added $view parameter
		 *
		 * @param bool $split_words True: split a phrase into words; False: search whole word only [Default: true]
		 * @param \GV\View $view The View being searched
		 */
		$split_words = apply_filters( 'gravityview/search-all-split-words', true, $view );

		/**
		 * @filter `gravityview/search-trim-input` Remove leading/trailing whitespaces from search value
		 *
		 * @since 2.9.3
		 * @since 2.19.6 Added $view parameter
		 *
		 * @param bool $trim_search_value True: remove whitespace; False: keep as is [Default: true]
		 * @param \GV\View $view The View being searched
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
				$value = $params['value'] ?? '';

				if ( $use_json_storage && $value && is_string( $value ) ) {
					$value = trim( json_encode( $value ), '"' );
					$value = str_replace( '\\', '\\\\', $value );

					$search_criteria['field_filters'][] = array_merge(
						$params,
						[ 'value' => $value ]
					);
				}
			}
		}

		// start date & end date
		if ( in_array( 'entry_date', $searchable_fields ) ) {
			/**
			 * Get and normalize the dates according to the input format.
			 */
			if ( $curr_start = ! empty( $get['gv_start'] ) ? $get['gv_start'] : '' ) {
				if ( $curr_start_date = date_create_from_format( $this->get_datepicker_format( true ), $curr_start ) ) {
					$curr_start = $curr_start_date->format( 'Y-m-d' );
				}
			}

			if ( $curr_end = ! empty( $get['gv_start'] ) ? ( ! empty( $get['gv_end'] ) ? $get['gv_end'] : '' ) : '' ) {
				if ( $curr_end_date = date_create_from_format( $this->get_datepicker_format( true ), $curr_end ) ) {
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
			 * Whether to adjust the timezone for entries. \n.
			 * `date_created` is stored in UTC format. Convert search date into UTC (also used on templates/fields/date_created.php). \n
			 * This is for backward compatibility before \GF_Query started to automatically apply the timezone offset.
			 *
			 * @since 1.12
			 * @param boolean $adjust_tz  Use timezone-adjusted datetime? If true, adjusts date based on blog's timezone setting. If false, uses UTC setting. Default is `false`.
			 * @param string $context Where the filter is being called from. `search` in this case.
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
				if ( strpos( $search_criteria['end_date'], '00:00:00' ) ) { // See https://github.com/gravityview/GravityView/issues/1056
					$search_criteria['end_date'] = date( 'Y-m-d H:i:s', strtotime( $search_criteria['end_date'] ) - 1 );
				}
			}
		}

		// search for a specific entry ID
		if ( ! empty( $get['gv_id'] ) && in_array( 'entry_id', $searchable_fields ) ) {
			$search_criteria['field_filters'][] = array(
				'key'      => 'id',
				'value'    => absint( $get['gv_id'] ),
				'operator' => $this->get_operator( $get, 'gv_id', array( '=' ), '=' ),
			);
		}

		// search for a specific Created_by ID
		if ( ! empty( $get['gv_by'] ) && in_array( 'created_by', $searchable_fields ) ) {
			$search_criteria['field_filters'][] = array(
				'key'      => 'created_by',
				'value'    => $get['gv_by'],
				'operator' => $this->get_operator( $get, 'gv_by', array( '=' ), '=' ),
			);
		}

		// Get search mode passed in URL
		$mode = isset( $get['mode'] ) && in_array( $get['mode'], array( 'any', 'all' ) ) ? $get['mode'] : 'any';

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

			if ( gv_empty( $value, false, false ) || ( is_array( $value ) && 1 === count( $value ) && gv_empty( $value[0], false, false ) ) ) {
				/**
				 * Filter to control if empty field values should be ignored or strictly matched (default: true).
				 * @since  2.14.2.1
				 * @param bool $ignore_empty_values
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
				$filter['operator'] = $this->get_operator( $get, $key, array( 'contains' ), 'contains' );
			}

			if ( isset( $filter[0]['value'] ) ) {
				$filter[0]['value'] = $trim_search_value ? trim( $filter[0]['value'] ) : $filter[0]['value'];

				unset($filter['operator']);
				$search_criteria['field_filters'] = array_merge( $search_criteria['field_filters'], $filter );

				// if range type, set search mode to ALL
				if ( ! empty( $filter[0]['operator'] ) && in_array( $filter[0]['operator'], array( '>=', '<=', '>', '<' ) ) ) {
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
		 * @param string $mode Search mode (`any` vs `all`)
		 */
		$search_criteria['field_filters']['mode'] = apply_filters( 'gravityview/search/mode', $mode );

		gravityview()->log->debug( 'Returned Search Criteria: ', array( 'data' => $search_criteria ) );

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
		 * @since 2.22
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
	 * @param \GF_Query   $query The current query object reference
	 * @param \GV\View    $this The current view object
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
		$mode             = 'any';

		foreach ( $search_criteria['field_filters'] as $key => &$filter ) {
			if ( ! is_array( $filter ) ) {
				if ( in_array( strtolower( $filter ), array( 'any', 'all' ) ) ) {
					$mode = $filter;
				}
				continue;
			}

			// Construct a manual query for unapproved statuses
			if ( 'is_approved' === $filter['key'] && in_array( \GravityView_Entry_Approval_Status::UNAPPROVED, (array) $filter['value'] ) ) {
				$_tmp_query       = new $query_class(
					$view->form->ID,
					array(
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
						'mode' => 'any',
						),
					)
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
			 * @since 2.0 Added $view parameter
			 *
			 * @param string $operator Existing search operator
			 * @param array $filter array with `key`, `value`, `operator`, `type` keys
			 * @param \GV\View $view The View we're operating on.
			 */
			$filter['operator'] = apply_filters( 'gravityview_search_operator', $filter['operator'], $filter, $view );

			if ( 'is' !== $filter['operator'] && '' === $filter['value'] ) {
				unset( $search_criteria['field_filters'][ $key ] );
			}
		}
		unset( $filter );

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
				$_tmp_query       = new $query_class(
					$filter['form_id'],
					array(
						'mode'          => 'any',
						'field_filters' => array( $filter ),
					)
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
									$query->_alias( $parameter->field_id, $parameter->source, $parameter->is_entry_column() ? 't' : 'm' )
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
						$column_name   = sprintf( '`%s`.`%s`', $column->alias, $column->is_entry_column() ? $column->field_id : 'meta_value' );

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
									new GF_Query_Column( GF_Query_Column::META, $join->ID, $query->_alias( GF_Query_Column::META, $join->ID, 'm' ) ),
									$search_condition->operator,
									$search_condition->right
								),
								// On
								new GF_Query_Condition(
									new GF_Query_Column( GF_Query_Column::META, $on->ID, $query->_alias( GF_Query_Column::META, $on->ID, 'm' ) ),
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
					)
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
					)
				]
			) );
		}


		/**
		 * Combine the parts as a new WHERE clause.
		 */
		$where = \GF_Query_Condition::_and(
			...array_merge(
				[$query_parts['where']],
				$search_conditions,
				$extra_conditions
			)
		);
		$query->where( $where );
	}

	/**
	 * Whether the field in the filter is a product field.
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

		$field_id = str_replace( array( 'filter_', 'input_' ), '', $key );

		// calculates field_id, removing 'filter_' and for '_' for advanced fields ( like name or checkbox )
		if ( preg_match( '/^[0-9_]+$/ism', $field_id ) ) {
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
	 * @param  string   $filter_key ID of the field, or entry meta key
	 * @param  string   $value $_GET/$_POST search value
	 * @param  \GV\View $view The view we're looking at
	 * @param array[]  $searchable_fields The searchable fields as configured by the widget.
	 * @param string[] $get The $_GET/$_POST array.
	 *
	 * @since develop Added 5th $get parameter for operator overrides.
	 * @todo Set function as private.
	 *
	 * @return array|false 1 or 2 deph levels, false if not allowed
	 */
	public function prepare_field_filter( $filter_key, $value, $view, $searchable_fields, $get = array() ) {
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
		$form_field = is_numeric( $field_id ) ? \GV\GF_Field::by_id( $form, $field_id ) : \GV\Internal_Field::by_id( $field_id );

		if ( ! $form_field ) {
			return false;
		}

		// default filter array
		$filter = array(
			'key'     => $field_id,
			'value'   => $value,
			'form_id' => $form->ID,
		);

		switch ( $form_field->type ) {

			case 'select':
			case 'workflow_user':
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
					$cat      = get_term( $val, 'category' );
					$filter[] = array(
						'key'      => $field_id,
						'value'    => esc_attr( $cat->name ) . ':' . $val,
						'operator' => $this->get_operator( $get, $key, array( 'is' ), 'is' ),
					);
				}

				break;

			case 'multiselect':
			case 'workflow_multi_user':
				if ( ! is_array( $value ) ) {
					break;
				}

				// Reset filter variable
				$filter = array();

				foreach ( $value as $val ) {
					$filter[] = array(
						'key'   => $field_id,
						'value' => $val,
					);
				}

				break;

			case 'checkbox':
				// convert checkbox on/off into the correct search filter
				if ( false !== strpos( $field_id, '.' ) && ! empty( $form_field->inputs ) && ! empty( $form_field->choices ) ) {
					foreach ( $form_field->inputs as $k => $input ) {
						if ( $input['id'] == $field_id ) {
							$filter['value']    = $form_field->choices[ $k ]['value'];
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

						if ( $form_field->ID !== $searchable_field['field'] ) {
							continue;
						}

						// Only exact-match dropdowns, not text search
						if ( in_array( $searchable_field['input'], array( 'text', 'search' ), true ) ) {
							continue;
						}

						$input_id = gravityview_get_input_id_from_id( $form_field->ID );

						if ( 4 === $input_id ) {
							$filter['operator'] = $this->get_operator( $get, $key, array( 'is' ), 'is' );
						}
					}
				}

				break;

			case 'payment_date':
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

						$filter[] = array(
							'key'      => $field_id,
							'value'    => self::get_formatted_date( $date, 'Y-m-d', $date_format ),
							'operator' => $this->get_operator( $get, $key, array( $operator ), $operator ),
						);
					}
				} else {
					$date               = $value;
					$filter['value']    = self::get_formatted_date( $date, 'Y-m-d', $date_format );
					$filter['operator'] = $this->get_operator( $get, $key, array( 'is' ), 'is' );
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

					if ( is_numeric( $min ) && is_numeric( $max ) && $min > $max) {
						// Reverse the polarity!
						[$min, $max] = [$max, $min];
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
	 * @param GF_Field_Date $field The field object
	 * @since 1.10
	 *
	 * @return string Format of the date in the database
	 */
	public static function get_date_field_format( GF_Field_Date $field ) {
		$format     = 'm/d/Y';
		$datepicker = array(
			'mdy'       => 'm/d/Y',
			'dmy'       => 'd/m/Y',
			'dmy_dash'  => 'd-m-Y',
			'dmy_dot'   => 'd.m.Y',
			'ymd_slash' => 'Y/m/d',
			'ymd_dash'  => 'Y-m-d',
			'ymd_dot'   => 'Y.m.d',
		);

		if ( ! empty( $field->dateFormat ) && isset( $datepicker[ $field->dateFormat ] ) ) {
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
	 *
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

		foreach ( $search_fields as $k => $field ) {
			if ( in_array( $field['input'], array( 'date', 'date_range', 'entry_date' ), true ) ) {
				return true;
			}
		}

		return false;
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
		} else {
			$view_id = \GV\Utils::get( $widget_args, 'view_id', 0 );
		}

		$search_fields = $this->set_search_fields( $widget_args, $context );

		if ( $this->has_date_field( $search_fields ) ) {
			// enqueue datepicker stuff only if needed!
			$this->enqueue_datepicker();
		}

		$search_layout = ( ! empty( $widget_args['search_layout'] ) ? $widget_args['search_layout'] : 'horizontal' );
		$custom_class = ! empty( $widget_args['custom_class'] ) ? $widget_args['custom_class'] : '';

		$data = [
			'datepicker_class' => $this->get_datepicker_class(),
			'search_fields' => $search_fields,
			'search_method' => $this->get_search_method(),
			'search_layout' => $search_layout,
			'search_mode' => ( ! empty( $widget_args['search_mode'] ) ? $widget_args['search_mode'] : 'any' ),
			'search_clear' => ( ! empty( $widget_args['search_clear'] ) ? $widget_args['search_clear'] : false ),
			'view_id' => $view_id,
			'search_class' => self::get_search_class( $custom_class, $search_layout ),
			'permalink_fields' => $this->add_no_permalink_fields( array(), $this, $widget_args ),
			'search_form_action' => self::get_search_form_action(),
		];

		GravityView_View::getInstance()->render( 'widget', 'search', false, $data );
	}

	/**
	 * @param string|\GV\Template_Context $context
	 *
	 * @return array|mixed|null
	 */
	public function set_search_fields( $widget_args, $context ) {

		if ( $context instanceof \GV\Template_Context ) {
			$view = $context->view;
		} else {
			$view_id = \GV\Utils::get( $widget_args, 'view_id', 0 );
			$view = \GV\View::by_id( $view_id );
		}

		if ( ! $view instanceof \GV\View ) {
			gravityview()->log->error( 'View not found', array( 'data' => $widget_args ) );
			return [];
		}

		$search_fields = $view->fields->by_position( 'search_*' )->as_configuration();

		if ( empty( $search_fields ) || ! is_array( $search_fields ) ) {
			gravityview()->log->debug( 'No search fields configured for widget:', array( 'data' => $widget_args ) );
			return [];
		}

		// prepare fields
		foreach ( $search_fields as $group => $fields ) {
			foreach ( $fields as $k => $field ) {

				$updated_field = $field;

				$updated_field = $this->get_search_filter_details( $updated_field, $context, $widget_args );

				$updated_field['label'] = self::get_field_label( $field );

				switch ( $field['field'] ) {

					case 'search_all':
						$updated_field['key']   = 'search_all';
						$updated_field['input'] = 'search_all';
						$updated_field['value'] = $this->rgget_or_rgpost( 'gv_search' );
						break;

					case 'entry_date':
						$updated_field['key']   = 'entry_date';
						$updated_field['input'] = 'entry_date';
						$updated_field['value'] = array(
							'start' => $this->rgget_or_rgpost( 'gv_start' ),
							'end'   => $this->rgget_or_rgpost( 'gv_end' ),
						);
						break;

					case 'entry_id':
						$updated_field['key']   = 'entry_id';
						$updated_field['input'] = 'entry_id';
						$updated_field['value'] = $this->rgget_or_rgpost( 'gv_id' );
						break;

					case 'created_by':
						$updated_field['key']   = 'created_by';
						$updated_field['name']  = 'gv_by';
						$updated_field['value'] = $this->rgget_or_rgpost( 'gv_by' );
						break;

					case 'is_approved':
						$updated_field['key']     = 'is_approved';
						$updated_field['value']   = $this->rgget_or_rgpost( 'filter_is_approved' );
						$updated_field['choices'] = self::get_is_approved_choices();
						break;

				case 'is_read':
					$updated_field['key']     = 'is_read';
					$updated_field['value']   = $this->rgget_or_rgpost( 'filter_is_read' );
					$updated_field['choices'] = array(
						array(
							'text'  => __( 'Unread', 'gk-gravityview' ),
							'value' => 0,
						),
						array(
							'text'  => __( 'Read', 'gk-gravityview' ),
							'value' => 1,
						),
					);
					break;
				}

				$search_fields[ $group ][ $k ] = $updated_field;
			}
		}

		gravityview()->log->debug( 'Calculated Search Fields: ', array( 'data' => $search_fields ) );

		/**
		 * Modify what fields are shown. The order of the fields in the $search_filters array controls the order as displayed in the search bar widget.
		 *
		 * @param array $search_fields Array of search filters with `key`, `label`, `value`, `type`, `choices` keys
		 * @param GravityView_Widget_Search $this Current widget object
		 * @param array $widget_args Args passed to this method. {@since 1.8}
		 * @param \GV\Template_Context $context {@since 2.0}
		 * @type array
		 */
		$search_fields = apply_filters( 'gravityview_widget_search_filters', $search_fields, $this, $widget_args, $context );

		return $search_fields;
	}

	/**
	 * Get the search class for a search form.
	 *
	 * @since 1.5.4
	 * @since TODO Added $search_layout parameter.
	 *
	 * @param string $custom_class Custom class to add to the search form
	 * @param string $search_layout Search layout ("horizontal" or "vertical"). Default: "horizontal".
	 *
	 * @return string Sanitized CSS class for the search form
	 */
	public static function get_search_class( $custom_class = '', $search_layout = 'horizontal' ) {

		$search_class = 'gv-search-'. $search_layout;

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
	 * @since TODO Added $post_id parameter.
	 *
	 * @return string
	 */
	public static function get_search_form_action( $post_id = 0 ) {

		if ( empty( $post_id ) ) {
			$gravityview_view = GravityView_View::getInstance();

			$post_id = $gravityview_view->getPostId() ? $gravityview_view->getPostId() : $gravityview_view->getViewId();
		}

		$url = add_query_arg( array(), get_permalink( $post_id ) );

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
	 * Get the label for a search form field
	 *
	 * @param  array $field      Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys
	 * @param  array $form_field Form field data, as fetched by `gravityview_get_field()`
	 * @return string             Label for the search form
	 */
	private static function get_field_label( $field, $form_field = array() ) {

		// TODO: Convert from "Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys" to use the current structure.

		$label = \GV\Utils::get( $field, 'custom_label', '' );

		if ( '' === $label ) {
			$label = \GV\Utils::_GET( 'label', \GV\Utils::get( $field, 'label' ) );
		}

		if ( ! $label ) {

			$label = isset( $form_field['label'] ) ? $form_field['label'] : '';

			switch ( $field['field'] ) {
				case 'search_all':
					$label = __( 'Search Entries:', 'gk-gravityview' );
					break;
				case 'entry_date':
					$label = __( 'Filter by date:', 'gk-gravityview' );
					break;
				case 'entry_id':
					$label = __( 'Entry ID:', 'gk-gravityview' );
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
		 * Modify the label for a search field. Supports returning HTML.
		 *
		 * @since 1.17.3 Added $field parameter
		 * @param string $label Existing label text, sanitized.
		 * @param array $form_field Gravity Forms field array, as returned by `GFFormsModel::get_field()`
		 * @param array $field Field setting as sent by the GV configuration - has `field`, `input` (input type), and `label` keys
		 */
		$label = apply_filters( 'gravityview_search_field_label', esc_attr( $label ), $form_field, $field );

		return $label;
	}

	/**
	 * Prepare search fields to frontend render with other details (label, field type, searched values)
	 *
	 * @since 2.16 Added $widget_args parameter.
	 *
	 * @param array       $field
	 * @param \GV\Template_Context $context
	 * @param array       $widget_args
	 *
	 * @return array
	 */
	private function get_search_filter_details( $field, $context, $widget_args ) {

		$form = GFAPI::get_form( $field['form_id'] );

		// for advanced field ids (eg, first name / last name )
		$name = 'filter_' . $field['id'];

		// get searched value from $_GET/$_POST (string or array)
		$value = $this->rgget_or_rgpost( $name );

		// get form field details
		$form_field = GFAPI::get_field( $form, $field['id'] );

		$form_field_type = \GV\Utils::get( $form_field, 'type' );

		$filter = array(
			'key' => \GV\Utils::get( $field, 'id' ),
			'name'  => $name,
			'label' => \GV\Utils::get( $field, 'label' ),
			'input' => self::get_input_types_by_gf_field( $form_field )[0],
			'value' => $value,
			'type'  => $form_field_type,
			'form_id' => \GV\Utils::get( $field, 'form_id' ),
		);

		// collect choices
		if ( 'post_category' === $form_field_type && ! empty( $form_field['displayAllCategories'] ) && empty( $form_field['choices'] ) ) {
			$filter['choices'] = gravityview_get_terms_choices();
		} elseif ( ! empty( $form_field->choices ) ) {
			$filter['choices'] = $form_field->choices;
		}

		if ( 'date_range' === $field['input'] && empty( $value ) ) {
			$filter['value'] = array(
				'start' => '',
				'end'   => '',
			);
		}

		if ( 'number_range' === $field['input'] && empty( $value ) ) {
			$filter['value'] = array(
				'min' => '',
				'max' => '',
			);
		}

		if ( 'created_by' === $field['field'] ) {
			$filter['choices'] = self::get_created_by_choices( ( isset( $context->view ) ? $context->view : null ) );
			$filter['type']    = 'created_by';
		}

		if( 'payment_status' === $field['field'] ) {
			$filter['type']    = 'entry_meta';
			$filter['choices'] = GFCommon::get_entry_payment_statuses_as_choices();
		}

		if ( 'payment_status' === $field['field'] ) {
			$filter['type']    = 'entry_meta';
			$filter['choices'] = GFCommon::get_entry_payment_statuses_as_choices();
		}

		/**
		 * Filter the output filter details for the Search widget.
		 *
		 * @since 2.5
		 * @param array $filter The filter details
		 * @param array $field The search field configuration
		 * @param \GV\Context The context
		 */
		return apply_filters( 'gravityview/search/filter_details', $filter, $field, $context );
	}

	/**
	 * If sieve choices is enabled, run it for each of the fields with choices.
	 *
	 * @since 2.16.6
	 *
	 * @uses sieve_filter_choices
	 *
	 * @param array                     $search_fields Array of search filters with `key`, `label`, `value`, `type` keys
	 * @param GravityView_Widget_Search $widget Current widget object
	 * @param array                     $widget_args Args passed to this method. {@since 1.8}
	 * @param \GV\Template_Context      $context
	 *
	 * @return array If the search field GF Field type is `address`, and there are choices to add, adds them and changes the input type. Otherwise, sets the input to text.
	 */
	public function maybe_sieve_filter_choices( $search_fields, $widget, $widget_args, $context ) {

		$sieve_choices = \GV\Utils::get( $widget_args, 'sieve_choices', false );

		if ( ! $sieve_choices ) {
			return $search_fields;
		}

		foreach ( $search_fields as &$filter ) {
			if ( empty( $filter['choices'] ) ) {
				continue;
			}

			$field = gravityview_get_field( $context->view->form->form, $filter['key'] );  // @todo Support multiple forms (joins)

			/**
			 * Only output used choices for this field.
			 *
			 * @since 2.16 Modified default value to the `sieve_choices` widget setting and added $widget_args parameter.
			 *
			 * @param bool $sieve_choices True: Yes, filter choices based on whether the value exists in entries. False: show all choices in the original field. Default: false.
			 * @param array $field The field configuration.
			 * @param \GV\Template_Context The context.
			 */
			if ( apply_filters( 'gravityview/search/sieve_choices', $sieve_choices, $field, $context, $widget_args ) ) {
				$filter['choices'] = $this->sieve_filter_choices( $filter, $context );
			}
		}

		return $search_fields;
	}

	/**
	 * Sieve filter choices to only ones that are used.
	 *
	 * @param array       $filter The filter configuration.
	 * @param \GV\Context $context The context
	 *
	 * @since 2.5
	 * @internal
	 *
	 * @return array The filter choices.
	 */
	private function sieve_filter_choices( $filter, $context ) {
		if ( empty( $filter['key'] ) || empty( $filter['choices'] ) ) {
			return $filter; // @todo Populate plugins might give us empty choices
		}

		// Allow only specific entry meta and field-ids to be sieved.
		if ( ! in_array( $filter['key'], array( 'created_by', 'payment_status' ), true ) && ! is_numeric( $filter['key'] ) ) {
			return $filter;
		}

		$form_id = $context->view->form->ID; // @todo Support multiple forms (joins)

		$cache = new GravityView_Cache( $form_id, array( 'sieve', $filter['key'], $context->view->ID ) );

		$filter_choices = $cache->get();

		if ( $filter_choices ) {
			return $filter_choices;
		}

		global $wpdb;

		$entry_table_name      = GFFormsModel::get_entry_table_name();
		$entry_meta_table_name = GFFormsModel::get_entry_meta_table_name();

		$key_like    = $wpdb->esc_like( $filter['key'] ) . '.%';
		$filter_type = \GV\Utils::get( $filter, 'type' );

		switch ( $filter_type ) {
			case 'post_category':
				$choices = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT SUBSTRING_INDEX( `meta_value`, ':', 1) FROM $entry_meta_table_name WHERE ( `meta_key` LIKE %s OR `meta_key` = %d) AND `form_id` = %d",
						$key_like,
						$filter['key'],
						$form_id
					)
				);
				break;
			case 'created_by':
			case 'entry_meta':
				$choices = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT `{$filter['key']}` FROM $entry_table_name WHERE `form_id` = %d",
						$form_id
					)
				);
				break;
			default:
				$sql = $wpdb->prepare(
					"SELECT DISTINCT `meta_value` FROM $entry_meta_table_name WHERE ( `meta_key` LIKE %s OR `meta_key` = %s ) AND `form_id` = %d",
					$key_like,
					$filter['key'],
					$form_id
				);

				$choices = $wpdb->get_col( $sql );

				$field = gravityview_get_field( $context->view->form->form, $filter['key'] );

				if ( $field && 'json' === $field->storageType ) {
					$choices        = array_map( 'json_decode', $choices );
					$_choices_array = array();
					foreach ( $choices as $choice ) {
						if ( is_array( $choice ) ) {
							$_choices_array = array_merge( $_choices_array, $choice );
						} else {
							$_choices_array [] = $choice;
						}
					}
					$choices = array_unique( $_choices_array );
				}

				break;
		}

		$filter_choices = array();
		foreach ( $filter['choices'] as $choice ) {
			if ( in_array( $choice['text'], $choices, true ) || in_array( $choice['value'], $choices, true ) ) {
				$filter_choices[] = $choice;
			}
		}

		$cache->set( $filter_choices, 'sieve_filter_choices', WEEK_IN_SECONDS );

		return $filter_choices;
	}

	/**
	 * Calculate the search choices for the users
	 *
	 * @param \GV\View|null $view The View, if set.
	 *
	 * @since 1.8
	 * @since 2.3 Added $view parameter.
	 *
	 * @return array Array of user choices (value = ID, text = display name)
	 */
	private static function get_created_by_choices( $view ) {

		/**
		 * filter gravityview/get_users/search_widget
		 *
		 * @see \GVCommon::get_users
		 */
		$users = GVCommon::get_users( 'search_widget', array( 'fields' => array( 'ID', 'display_name' ) ) );

		$choices = array();
		foreach ( $users as $user ) {
			/**
			 * Filter the display text in created by search choices.
			 *
			 * @since 2.3
			 * @param string[in,out] The text. Default: $user->display_name
			 * @param \WP_User $user The user.
			 * @param \GV\View|null $view The view.
			 */
			$text      = apply_filters( 'gravityview/search/created_by/text', $user->display_name, $user, $view );
			$choices[] = array(
				'value' => $user->ID,
				'text'  => $text,
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
				'text'  => $status['label'],
			);
		}

		return $choices;
	}

	/**
	 * Output the Clear Search Results button
	 *
	 * @since 1.5.4
	 */
	public static function the_clear_search_button() {
		_deprecated_function( __METHOD__, 'TODO', 'The button is now available in the templates as global $data[\'search_clear\']' );
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

		if ( ! is_null( $value ) ) {
			$value = gv_map_deep( $value, 'rawurldecode' );
		}

		$value = gv_map_deep( $value, '_wp_specialchars' );

		return $value;
	}


	/**
	 * Require the datepicker script for the frontend GV script
	 *
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
		 * Modify the datepicker settings.
		 *
		 * @see http://api.jqueryui.com/datepicker/ Learn what settings are available
		 * @see http://www.renegadetechconsulting.com/tutorials/jquery-datepicker-and-wordpress-i18n Thanks for the helpful information on $wp_locale
		 * @param array $js_localization The data padded to the Javascript file
		 * @param array $view_data View data array with View settings
		 */
		$datepicker_settings = apply_filters(
			'gravityview_datepicker_settings',
			array(
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
			),
			$view_data
		);

		$localizations['datepicker'] = $datepicker_settings;

		return $localizations;
	}

	/**
	 * Enqueue the datepicker script
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
		wp_enqueue_style( 'jquery-ui-datepicker', $scheme . 'ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
	}

	private function get_datepicker_class() {
		/**
		 * @filter `gravityview_search_datepicker_class`
		 * Modify the CSS class for the datepicker, used by the CSS class is used by Gravity Forms' javascript to determine the format for the date picker. The `gv-datepicker` class is required by the GravityView datepicker javascript.
		 *
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
		$datepicker_class = apply_filters( 'gravityview_search_datepicker_class', 'gv-datepicker datepicker ' . $this->get_datepicker_format() );

		return $datepicker_class;
	}

	/**
	 * Retrieve the datepicker format.
	 *
	 * @param bool $date_format Whether to return the PHP date format or the datpicker class name. Default: false.
	 *
	 * @see https://docs.gravitykit.com/article/115-changing-the-format-of-the-search-widgets-date-picker
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
			'mdy'       => 'm/d/Y',

			'dmy_dash'  => 'd-m-Y',
			'dmy_dot'   => 'd.m.Y',
			'dmy'       => 'd/m/Y',

			'ymd_slash' => 'Y/m/d',
			'ymd_dash'  => 'Y-m-d',
			'ymd_dot'   => 'Y.m.d',
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
		$allowed = apply_filters_deprecated( 'gravityview/search/operator_whitelist', array( $allowed, $key ), '2.14', 'gravityview/search/operator_allowlist' );

		/**
		 * An array of allowed operators for a field.
		 *
		 * @since 2.14
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
	 * @param array[] $words The words to quote.
	 * @param string   $delimiter The delimiter.
	 *
	 * @return array[] The quoted words.
	 */
	private static function preg_quote( array $words, string $delimiter = '/' ): array {
		return array_map( static function ( string $mark ) use ( $delimiter ): string {
			return preg_quote( $mark, $delimiter );
		}, $words );
	}

	/**
	 * Retrieves the words in with its operator for querying.
	 *
	 * @since v2.21.1
	 *
	 * @param string $query The search query.
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
} // end class

new GravityView_Widget_Search();

if ( ! gravityview()->plugin->supports( \GV\Plugin::FEATURE_GFQUERY ) ) {
	return;
}

/**
 * A GF_Query condition that allows user data searches.
 */
class GravityView_Widget_Search_Author_GF_Query_Condition extends \GF_Query_Condition {
	public function __construct( $filter, $view ) {
		$this->value = $filter['value'];
		$this->view  = $view;
	}

	public function sql( $query ) {
		global $wpdb;

		$user_meta_fields = array(
			'nickname',
			'first_name',
			'last_name',
		);

		/**
		 * Filter the user meta fields to search.
		 *
		 * @param array The user meta fields.
		 * @param \GV\View $view The view.
		 */
		$user_meta_fields = apply_filters( 'gravityview/widgets/search/created_by/user_meta_fields', $user_meta_fields, $this->view );

		$user_fields = array(
			'user_nicename',
			'user_login',
			'display_name',
			'user_email',
		);

		/**
		 * Filter the user fields to search.
		 *
		 * @param array The user fields.
		 * @param \GV\View $view The view.
		 */
		$user_fields = apply_filters( 'gravityview/widgets/search/created_by/user_fields', $user_fields, $this->view );

		$conditions = array();

		foreach ( $user_fields as $user_field ) {
			$conditions[] = $wpdb->prepare( "`u`.`$user_field` LIKE %s", '%' . $wpdb->esc_like( $this->value ) . '%' );
		}

		foreach ( $user_meta_fields as $meta_field ) {
			$conditions[] = $wpdb->prepare( '(`um`.`meta_key` = %s AND `um`.`meta_value` LIKE %s)', $meta_field, '%' . $wpdb->esc_like( $this->value ) . '%' );
		}

		$conditions = '(' . implode( ' OR ', $conditions ) . ')';

		$alias = $query->_alias( null );

		return "(EXISTS (SELECT 1 FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id WHERE (u.ID = `$alias`.`created_by` AND $conditions)))";
	}
}
