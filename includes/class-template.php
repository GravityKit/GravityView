<?php
/**
 * GravityView templating engine class
 *
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2014, Katz Web Services, Inc.
 *
 * @since 1.0.0
 */

/** If this file is called directly, abort. */

use GV\Grid;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( '\GV\Gamajo_Template_Loader' ) ) {
	require GRAVITYVIEW_DIR . 'future/lib/class-gamajo-template-loader.php';
}

class GravityView_View extends \GV\Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @var string $filter_prefix
	 */
	protected $filter_prefix = 'gravityview';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @var string $theme_template_directory
	 */
	protected $theme_template_directory = 'gravityview';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @var string $plugin_directory
	 */
	protected $plugin_directory = GRAVITYVIEW_DIR;

	/**
	 * Store templates locations that have already been located
	 *
	 * @var array $located_templates
	 */
	protected $located_templates = array();

	/**
	 * The name of the template, like "list", "table", or "datatables"
	 *
	 * @var string $template_part_slug
	 */
	protected $template_part_slug = '';

	/**
	 * The name of the file part, like "body" or "single"
	 *
	 * @var string $template_part_name
	 */
	protected $template_part_name = '';

	/**
	 * @var int $form_id Gravity Forms form ID
	 */
	protected $form_id = null;

	/**
	 * @var int $view_id View ID
	 * @todo: this needs to be public until extensions support 1.7+
	 */
	public $view_id = null;

	/**
	 * @var array $fields Fields for the form
	 */
	protected $fields = array();

	/**
	 * @var string $context Current screen. Defaults to "directory" or "single"
	 */
	protected $context = 'directory';

	/**
	 * @var int|null $post_id If in embedded post or page, the ID of it
	 */
	protected $post_id = null;

	/**
	 * @var array $form Gravity Forms form array at ID $form_id
	 */
	protected $form = null;

	/**
	 * @var array $atts Configuration for the View
	 */
	protected $atts = array();

	/**
	 * @var array $entries Entries for the current result. Single item in array for single entry View
	 */
	protected $entries = array();

	/**
	 * @var int $total_entries Total entries count for the current result.
	 */
	protected $total_entries = 0;

	/**
	 * @var string $back_link_label The label to display back links
	 */
	protected $back_link_label = '';

	/**
	 * @var array $paging Array with `offset` and `page_size` keys
	 */
	protected $paging = array();

	/**
	 * @var array $sorting Array with `sort_field` and `sort_direction` keys
	 */
	protected $sorting = array();

	/**
	 * @var bool $hide_until_searched Whether to hide the results until a search is performed
	 * @since 1.5.4
	 */
	protected $hide_until_searched = false;

	/**
	 * Current entry in the loop
	 *
	 * @var array $_current_entry
	 */
	protected $_current_entry = array();

	/**
	 * @var array $_current_field
	 */
	protected $_current_field = array();

	/**
	 * @var GravityView_View $instance
	 */
	static $instance = null;

	/**
	 * The current field data.
	 *
	 * @deprecated 1.6.2
	 */
	public $field_data;

	public $search_fields;

	public $search_field;

	public $permalink_fields;

	public $search_layout;

	public $search_mode;

	public $search_class;

	public $search_clear;

	public $updated;

	/**
	 * Construct the view object
	 *
	 * @param  array $atts Associative array to set the data of
	 */
	function __construct( $atts = array() ) {

		$atts = wp_parse_args(
			$atts,
			array(
				'form_id' => null,
				'view_id' => null,
				'fields'  => null,
				'context' => null,
				'post_id' => null,
				'form'    => null,
				'atts'    => null,
			)
		);

		foreach ( $atts as $key => $value ) {
			if ( is_null( $value ) ) {
				continue;
			}
			$this->{$key} = $value;
		}

		// Add granular overrides
		add_filter( $this->filter_prefix . '_get_template_part', array( $this, 'add_id_specific_templates' ), 10, 3 );

		// widget logic
		add_action( 'gravityview/template/before', array( $this, 'render_widget_hooks' ) );
		add_action( 'gravityview/template/after', array( $this, 'render_widget_hooks' ) );

		/**
		 * Clear the current entry after the loop is done
		 *
		 * @since 1.7.3
		 */
		add_action( 'gravityview_footer', array( $this, 'clearCurrentEntry' ), 500 );

		self::$instance = &$this;
	}

	/**
	 * @param null $passed_post
	 *
	 * @return GravityView_View
	 */
	static function getInstance( $passed_post = null ) {

		if ( empty( self::$instance ) ) {
			self::$instance = new self( $passed_post );
		}

		return self::$instance;
	}

	/**
	 * @param string|null $key The key to a specific attribute of the current field
	 * @return array|mixed|null If $key is set and attribute exists at $key, return that. If not set, return NULL. Otherwise, return current field array
	 */
	public function getCurrentField( $key = null ) {

		if ( ! empty( $key ) ) {
			if ( isset( $this->_current_field[ $key ] ) ) {
				return $this->_current_field[ $key ];
			}
			return null;
		}

		return $this->_current_field;
	}

	public function setCurrentFieldSetting( $key, $value ) {

		if ( ! empty( $this->_current_field ) ) {
			$this->_current_field['field_settings'][ $key ] = $value;
		}
	}

	public function getCurrentFieldSetting( $key ) {
		$settings = $this->getCurrentField( 'field_settings' );

		if ( $settings && ! empty( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		return null;
	}

	/**
	 * @param array $passed_field
	 */
	public function setCurrentField( $passed_field ) {

		$existing_field = $this->getCurrentField();

		$set_field = wp_parse_args( $passed_field, $existing_field );

		$this->_current_field = $set_field;

		/**
		 * Backward compatibility
		 *
		 * @deprecated 1.6.2
		 */
		$this->field_data = $set_field;
	}

	/**
	 * @param string|null $key The key to a specific field in the fields array
	 * @return array|mixed|null If $key is set and field exists at $key, return that. If not set, return NULL. Otherwise, return array of fields.
	 */
	public function getAtts( $key = null ) {

		if ( ! empty( $key ) ) {
			if ( isset( $this->atts[ $key ] ) ) {
				return $this->atts[ $key ];
			}
			return null;
		}

		return $this->atts;
	}

	/**
	 * @param array $atts
	 */
	public function setAtts( $atts ) {
		$this->atts = $atts;
	}

	/**
	 * @return array
	 */
	public function getForm() {
		return $this->form;
	}

	/**
	 * @param array $form
	 */
	public function setForm( $form ) {
		$this->form = $form;
	}

	/**
	 * @return int|null
	 */
	public function getPostId() {
		return $this->post_id;
	}

	/**
	 * @param int|null $post_id
	 */
	public function setPostId( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * @return string
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * @param string $context
	 */
	public function setContext( $context ) {
		$this->context = $context;
	}

	/**
	 * @param string|null $key The key to a specific field in the fields array
	 * @return array|mixed|null If $key is set and field exists at $key, return that. If not set, return NULL. Otherwise, return array of fields.
	 */
	public function getFields( $key = null ) {

		$fields = empty( $this->fields ) ? null : $this->fields;

		if ( $fields && ! empty( $key ) ) {
			$fields = isset( $fields[ $key ] ) ? $fields[ $key ] : null;
		}

		return $fields;
	}

	/**
	 * Get the fields for a specific context
	 *
	 * @since 1.19.2
	 *
	 * @param string $context [Optional] "directory", "single", or "edit"
	 *
	 * @return array Array of GravityView field layout configurations
	 */
	public function getContextFields( $context = '' ) {

		if ( '' === $context ) {
			$context = $this->getContext();
		}

		$fields = $this->getFields();

		foreach ( (array) $fields as $key => $context_fields ) {

			// Formatted as `{context}_{template id}-{zone name}`, so we want just the $context to match against
			$matches = explode( '_', $key );

			if ( isset( $matches[0] ) && $matches[0] === $context ) {
				return $context_fields;
			}
		}

		return array();
	}

	/**
	 * @param array $fields
	 */
	public function setFields( $fields ) {
		$this->fields = $fields;
	}

	/**
	 * @param string $key The key to a specific field in the fields array
	 * @return array|mixed|null If $key is set and field exists at $key, return that. If not set, return NULL. Otherwise, return array of fields.
	 */
	public function getField( $key ) {

		if ( ! empty( $key ) ) {
			if ( isset( $this->fields[ $key ] ) ) {
				return $this->fields[ $key ];
			}
		}

		return null;
	}

	/**
	 * @param string $key The key to a specific field in the fields array
	 * @param mixed  $value The value to set for the field
	 */
	public function setField( $key, $value ) {
		$this->fields[ $key ] = $value;
	}

	/**
	 * @return int
	 */
	public function getViewId() {
		return absint( $this->view_id );
	}

	/**
	 * @param int $view_id
	 */
	public function setViewId( $view_id ) {
		$this->view_id = intval( $view_id );
	}

	/**
	 * @return int
	 */
	public function getFormId() {
		return $this->form_id;
	}

	/**
	 * @param int $form_id
	 */
	public function setFormId( $form_id ) {
		$this->form_id = $form_id;
	}

	/**
	 * @return array
	 */
	public function getEntries() {
		return $this->entries;
	}

	/**
	 * @param array $entries
	 */
	public function setEntries( $entries ) {
		$this->entries = $entries;
	}

	/**
	 * @return int
	 */
	public function getTotalEntries() {
		return (int) $this->total_entries;
	}

	/**
	 * @param int $total_entries
	 */
	public function setTotalEntries( $total_entries ) {
		$this->total_entries = intval( $total_entries );
	}

	/**
	 * @return array
	 */
	public function getPaging() {

		$default_params = array(
			'offset'    => 0,
			'page_size' => 20,
		);

		return wp_parse_args( $this->paging, $default_params );
	}

	/**
	 * @param array $paging
	 */
	public function setPaging( $paging ) {
		$this->paging = $paging;
	}

	/**
	 * Get an array with pagination information
	 *
	 * @since 1.13
	 *
	 * @return array {
	 *  @type int $first The starting entry number (counter, not ID)
	 *  @type int $last The last displayed entry number (counter, not ID)
	 *  @type int $total The total number of entries
	 * }
	 */
	public function getPaginationCounts() {

		$paging    = $this->getPaging();
		$offset    = $paging['offset'];
		$page_size = $paging['page_size'];
		$total     = $this->getTotalEntries();

		if ( empty( $total ) ) {
			gravityview()->log->debug( 'No entries. Returning empty array.' );

			return array();
		}

		$first = empty( $offset ) ? 1 : $offset + 1;

		// If the page size + starting entry is larger than total, the total is the max.
		$last = ( $offset + $page_size > $total ) ? $total : $offset + $page_size;

		/**
		 * Modify the displayed pagination numbers.
		 *
		 * @since 1.13
		 * @param array $counts Array with $first, $last, $total numbers in that order
		 */
		[ $first, $last, $total ] = apply_filters( 'gravityview_pagination_counts', array( $first, $last, $total ) );

		return array(
			'first' => (int) $first,
			'last'  => (int) $last,
			'total' => (int) $total,
		);
	}

	/**
	 * @return array
	 */
	public function getSorting() {

		$defaults_params = array(
			'sort_field'     => 'date_created',
			'sort_direction' => 'ASC',
			'is_numeric'     => false,
		);

		return wp_parse_args( $this->sorting, $defaults_params );
	}

	/**
	 * @param array $sorting
	 */
	public function setSorting( $sorting ) {
		$this->sorting = $sorting;
	}

	/**
	 * @param boolean $do_replace Perform merge tag and shortcode processing on the label. Default: true.
	 * @since 2.0
	 *
	 * @deprecated Use $template->get_back_label();
	 *
	 * @return string
	 */
	public function getBackLinkLabel( $do_replace = true ) {
		if ( $do_replace ) {
			$back_link_label = GravityView_API::replace_variables( $this->back_link_label, $this->getForm(), $this->getCurrentEntry() );
			return do_shortcode( $back_link_label );
		}

		return $this->back_link_label;
	}

	/**
	 * @param string $back_link_label
	 */
	public function setBackLinkLabel( $back_link_label ) {
		$this->back_link_label = $back_link_label;
	}

	/**
	 * @return boolean
	 */
	public function isHideUntilSearched() {
		return $this->hide_until_searched;
	}

	/**
	 * @param boolean $hide_until_searched
	 */
	public function setHideUntilSearched( $hide_until_searched ) {
		$this->hide_until_searched = $hide_until_searched;
	}

	/**
	 * @return string
	 */
	public function getTemplatePartSlug() {
		return $this->template_part_slug;
	}

	/**
	 * @param string $template_part_slug
	 */
	public function setTemplatePartSlug( $template_part_slug ) {
		$this->template_part_slug = $template_part_slug;
	}

	/**
	 * @return string
	 */
	public function getTemplatePartName() {
		return $this->template_part_name;
	}

	/**
	 * @param string $template_part_name
	 */
	public function setTemplatePartName( $template_part_name ) {
		$this->template_part_name = $template_part_name;
	}

	/**
	 * Return the current entry. If in the loop, the current entry. If single entry, the currently viewed entry.
	 *
	 * @return array
	 */
	public function getCurrentEntry() {

		if ( in_array( $this->getContext(), array( 'edit', 'single' ) ) ) {
			$entries = $this->getEntries();
			$entry   = $entries[0];
		} else {
			$entry = $this->_current_entry;
		}

		/** @since 1.16 Fixes DataTables empty entry issue */
		if ( empty( $entry ) && ! empty( $this->_current_field['entry'] ) ) {
			$entry = $this->_current_field['entry'];
		}

		return $entry;
	}

	/**
	 * @param array $current_entry
	 * @return void
	 */
	public function setCurrentEntry( $current_entry ) {
		$this->_current_entry = $current_entry;
	}

	/**
	 * Clear the current entry after all entries in the loop have been displayed.
	 *
	 * @since 1.7.3
	 * @return void
	 */
	public function clearCurrentEntry() {
		$this->_current_entry = null;
	}

	/**
	 * Render an output zone, as configured in the Admin
	 *
	 * @since 1.16.4 Added $echo parameter
	 *
	 * @param string $zone The zone name, like 'footer-left'
	 * @param array  $atts
	 * @param bool   $echo Whether to print the output
	 *
	 * @deprecated This will never get called in new templates.
	 *
	 * @return string|null
	 */
	public function renderZone( $zone = '', $atts = array(), $echo = true ) {

		if ( empty( $zone ) ) {
			gravityview()->log->error( 'No zone defined.' );
			return null;
		}

		$defaults = array(
			'slug'       => $this->getTemplatePartSlug(),
			'context'    => $this->getContext(),
			'entry'      => $this->getCurrentEntry(),
			'form'       => $this->getForm(),
			'hide_empty' => $this->getAtts( 'hide_empty' ),
		);

		$final_atts = wp_parse_args( $atts, $defaults );

		$output = '';

		$final_atts['zone_id'] = "{$final_atts['context']}_{$final_atts['slug']}-{$zone}";

		$fields = $this->getField( $final_atts['zone_id'] );

		// Backward compatibility
		if ( 'table' === $this->getTemplatePartSlug() ) {
			/**
			 * Modify the fields displayed in a table.
			 *
			 * @param array $fields
			 * @param \GravityView_View $this
			 * @deprecated Use `gravityview/template/table/fields`
			 */
			$fields = apply_filters( 'gravityview_table_cells', $fields, $this );
		}

		if ( empty( $fields ) ) {

			gravityview()->log->warning( 'Empty zone configuration for {zone_id}.', array( 'zone_id' => $final_atts['zone_id'] ) );

			return null;
		}

		$field_output = '';
		foreach ( $fields as $field ) {
			$final_atts['field'] = $field;

			$field_output .= gravityview_field_output( $final_atts );
		}

		/**
		 * If a zone has no field output, choose whether to show wrapper
		 * False by default to keep backward compatibility
		 *
		 * @since 1.7.6
		 * @param boolean $hide_empty_zone Default: false
		 * @since 2.0
		 * @param \GV\Template_Context $context The context. Null here. Since this path is deprecated.
		 */
		if ( empty( $field_output ) && apply_filters( 'gravityview/render/hide-empty-zone', false, null ) ) {
			return null;
		}

		if ( ! empty( $final_atts['wrapper_class'] ) ) {
			$output .= '<div class="' . gravityview_sanitize_html_class( $final_atts['wrapper_class'] ) . '">';
		}

		$output .= $field_output;

		if ( ! empty( $final_atts['wrapper_class'] ) ) {
			$output .= '</div>';
		}

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	/**
	 * In order to improve lookup times, we store located templates in a local array.
	 *
	 * This improves performance by up to 1/2 second on a 250 entry View with 7 columns showing
	 *
	 * @inheritdoc
	 * @see Gamajo_Template_Loader::locate_template()
	 * @return null|string NULL: Template not found; String: path to template
	 */
	function locate_template( $template_names, $load = false, $require_once = true ) {

		if ( is_string( $template_names ) && isset( $this->located_templates[ $template_names ] ) ) {

			$located = $this->located_templates[ $template_names ];

		} else {

			// Set $load to always false so we handle it here.
			$located = parent::locate_template( $template_names, false, $require_once );

			if ( is_string( $template_names ) ) {
				$this->located_templates[ $template_names ] = $located;
			}
		}

		if ( $load && $located ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

	/**
	 * Magic Method: Instead of throwing an error when a variable isn't set, return null.
	 *
	 * @param  string $name Key for the data retrieval.
	 * @return mixed|null    The stored data.
	 */
	public function __get( $name ) {
		if ( isset( $this->{$name} ) ) {
			return $this->{$name};
		} else {
			return null;
		}
	}

	/**
	 * Enable overrides of GravityView templates on a granular basis
	 *
	 * The loading order is:
	 *
	 * - view-[View ID]-table-footer.php
	 * - form-[Form ID]-table-footer.php
	 * - page-[ID of post or page where view is embedded]-table-footer.php
	 * - table-footer.php
	 *
	 * @see  Gamajo_Template_Loader::get_template_file_names() Where the filter is
	 * @param array  $templates Existing list of templates.
	 * @param string $slug      Name of the template base, example: `table`, `list`, `datatables`, `map`
	 * @param string $name      Name of the template part, example: `body`, `footer`, `head`, `single`
	 *
	 * @return array $templates Modified template array, merged with existing $templates values
	 */
	function add_id_specific_templates( $templates, $slug, $name ) {

		$additional = array();

		// form-19-table-body.php
		$additional[] = sprintf( 'form-%d-%s-%s.php', $this->getFormId(), $slug, $name );

		if ( $view_id = $this->getViewId() ) {
			// view-3-table-body.php
			$additional[] = sprintf( 'view-%d-%s-%s.php', $view_id, $slug, $name );
		}

		if ( $this->getPostId() ) {

			// page-19-table-body.php
			$additional[] = sprintf( 'page-%d-%s-%s.php', $this->getPostId(), $slug, $name );
		}

		// Combine with existing table-body.php and table.php
		$templates = array_merge( $additional, $templates );

		gravityview()->log->debug( 'List of Template Files', array( 'data' => $templates ) );

		return $templates;
	}

	/**
	 * Render a template part
	 *
	 * @since 2.42
	 *
	 * @param string $slug The template slug (like "table").
	 * @param string $name The template part name (like "body").
	 * @param bool $require_once Whether to require_once the template file. Default: true.
	 * @param array $data Data to pass to the template, available to the template as a $data global. Default: array().
	 */
	public function render( $slug, $name, $require_once = true, $data = [] ) {

		$this->setTemplatePartSlug( $slug );

		$this->setTemplatePartName( $name );

		// Set the data to be available in the template as a $data global.
		$this->set_template_data( $data );

		$template_file = $this->get_template_part( $slug, $name, false );

		gravityview()->log->debug( 'Rendering Template File: {path}', array( 'path' => $template_file ) );

		if ( ! empty( $template_file ) ) {

			if ( $require_once ) {
				require_once $template_file;
			} else {
				require $template_file;
			}
		}

		// Clear the data after rendering.
		$this->unset_template_data();
	}

	/**
	 * Output the widgets on before/after hooks.
	 *
	 * @param int|\GV\Template_Context $view_id_or_context The View ID or the context.
	 *
	 * @return void
	 */
	public function render_widget_hooks( $view_id_or_context ) {

		/**
		 * @deprecated Numeric argument is deprecated. Pass a \GV\Template_Context instead.
		 */
		if ( is_numeric( $view_id_or_context ) ) {
			$view          = \GV\View::by_id( $view_id_or_context );
			$is_single     = 'single' == gravityview_get_context();
			$total_entries = self::getInstance()->getTotalEntries();

			/**
			 * Fake new context for legacy template code.
			 */
			$view_id_or_context = \GV\Template_Context::from_template(
				array(
					'view' => $view,
				)
			);

		} elseif ( $view_id_or_context instanceof \GV\Template_Context ) {
			$view          = $view_id_or_context->view;
			$is_single     = (bool) $view_id_or_context->request->is_entry();
			$total_entries = $view_id_or_context->entries ? $view_id_or_context->entries->count() : 0;

		} else {
			gravityview()->log->error( 'No View ID or template context provided to render_widget_hooks' );
			return;
		}

		if ( $is_single ) {
			gravityview()->log->debug( 'Not rendering widgets; single entry' );
			return;
		}

		switch ( current_filter() ) {
			default:
			case 'gravityview/template/before':
			case 'gravityview_before':
				$zone = 'header';
				break;
			case 'gravityview/template/after':
			case 'gravityview_after':
				$zone = 'footer';
				break;
		}

		$widgets = $view->widgets->by_position( "$zone*" );

		/**
		 * Prevent output if no widgets to show.
		 *
		 * @since 1.16
		 */
		if ( ! $widgets->count() ) {
			gravityview()->log->debug(
				'No widgets for View #{view_id} in zone {zone}',
				array(
					'view_id' => $view->ID,
					'zone'    => $zone,
				)
			);
			return;
		}

		// Prevent being called twice
		if ( did_action( "gravityview/widgets/$zone/{$view->ID}/rendered" ) ) {
			gravityview()->log->debug( 'Not rendering {zone}; already rendered', array( 'zone' => $zone . '_' . $view->ID . '_widgets' ) );
			return;
		}

		$rows = Grid::get_rows_from_collection( $widgets, $zone );

		// TODO: Move to sep. method, use an action instead
		wp_enqueue_style( 'gravityview_default_style' );

		$default_css_class = 'gv-grid gv-widgets-' . $zone;

		if ( ! $total_entries ) {
			$default_css_class .= ' gv-widgets-no-results';
		}

		if ( ! $total_entries && ! gravityview()->request->is_search() && 3 === (int) $view->settings->get( 'no_entries_options', '0' ) ) {
			$default_css_class .= ' gv-hidden';
		}

		/**
		 * The CSS class applied to the widget container div.
		 *
		 * @since 1.16.2
		 * @param string $css_class Default: `gv-grid gv-widgets-{zone}` where `{zone}` is replaced by the current `$zone` value. If the View has no results, adds ` gv-widgets-no-results`
		 * @param string $zone Current widget zone, either `header` or `footer`
		 * @param array $widgets Array of widget configurations for the current zone, as set by `gravityview_get_current_view_data()['widgets']`
		 */
		$css_class = apply_filters( 'gravityview/widgets/wrapper_css_class', $default_css_class, $zone, $widgets->as_configuration() );

		$css_class = gravityview_sanitize_html_class( $css_class );

		// TODO Convert to partials
		?>
		<div class="<?php echo $css_class; ?>">
			<?php foreach ( $rows as $row ) { ?>
				<div class="gv-grid-row">
				<?php
				foreach ( $row as $col => $areas ) {
					$is_right = ( '2-2' === $col || strpos( $col, ' right' ) !== false );
					$column   = $col . ' gv-' . ( $is_right ? 'right' : 'left' );
					?>
					<div class="gv-grid-col-<?php echo esc_attr( $column ); ?>">
						<?php
						if ( ! empty( $areas ) ) {
							foreach ( $areas as $area ) {
								foreach ( $widgets->by_position( $zone . '_' . $area['areaid'] )->all() as $widget ) {
									do_action( sprintf( 'gravityview/widgets/%s/render', $widget->get_widget_id() ), $widget->configuration->all(), null, $view_id_or_context );
								}
							}
						}
						?>
					</div>
				<?php } // $row ?>
				</div>
			<?php } // $rows ?>
		</div>

		<?php

		/**
		 * Prevent widgets from being called twice.
		 * Checking for loop_start prevents themes and plugins that pre-process shortcodes from triggering the action before displaying. Like, ahem, the Divi theme and WordPress SEO plugin
		 */
		if ( did_action( 'wp_head' ) ) {
			do_action( "gravityview/widgets/$zone/{$view->ID}/rendered" );
		}
	}

	/**
	 * Include a file inside this context.
	 *
	 * @param string $path A path to the legacy template to include.
	 *
	 * @return void
	 */
	public function _include( $path ) {
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}
