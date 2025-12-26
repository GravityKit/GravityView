<?php
/**
 * @file class-gravityview-field-other-entries.php
 * @package GravityView
 * @subpackage includes\fields
 * @since 1.7.2
 */

/**
 * A field that displays other entries by the entry_creator for the same View in a list format
 *
 * @since 1.7.2
 */
class GravityView_Field_Other_Entries extends GravityView_Field {

	var $name = 'other_entries';

	var $is_searchable = false;

	var $contexts = array( 'multiple', 'single' );

	var $group = 'gravityview';

	var $icon = 'dashicons-admin-page';

	private $context;

	public function __construct() {
		$this->label       = esc_html__( 'Other Entries', 'gk-gravityview' );
		$this->description = esc_html__( 'Display other entries created by the entry creator.', 'gk-gravityview' );
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 * @since 1.7.2
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		if ( 'edit' === $context ) {
			return $field_options;
		}

		// No "Link to single entry"; all the items will be links to entries!
		unset( $field_options['show_as_link'] );

		$new_options = array();

		$new_options['link_format'] = array(
			'type'       => 'text',
			'label'      => __( 'Entry link text (required)', 'gk-gravityview' ),
			'value'      => __( 'Entry #{entry_id}', 'gk-gravityview' ),
			'merge_tags' => 'force',
			'group'      => 'field',
		);

		$new_options['after_link'] = array(
			'type'       => 'textarea',
			'label'      => __( 'Text or HTML to display after the link (optional)', 'gk-gravityview' ),
			'desc'       => __( 'This content will be displayed below each entry link.', 'gk-gravityview' ),
			'value'      => '',
			'merge_tags' => 'force',
			'class'      => 'widefat code',
			'group'      => 'field',
		);

		$new_options['page_size'] = array(
			'type'       => 'number',
			'label'      => __( 'Entries to Display', 'gk-gravityview' ),
			'desc'       => __( 'What is the maximum number of entries that should be shown?', 'gk-gravityview' ) . ' ' . sprintf( _x( 'Set to %s for no maximum.', '%s replaced with a formatted 0', 'gk-gravityview' ), '<code>0</code>' ),
			'value'      => '10',
			'merge_tags' => false,
			'min'        => 0,
			'group'      => 'field',
		);

		$new_options['no_entries_hide'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Hide if no entries', 'gk-gravityview' ),
			'desc'  => __( 'Don\'t display this field if the entry creator has no other entries', 'gk-gravityview' ),
			'value' => false,
			'group' => 'visibility',
		);

		$new_options['no_entries_text'] = array(
			'type'     => 'text',
			'label'    => __( 'No Entries Text', 'gk-gravityview' ),
			'desc'     => __( 'The text that is shown if the entry creator has no other entries (and "Hide if no entries" is disabled).', 'gk-gravityview' ),
			'value'    => __( 'This user has no other entries.', 'gk-gravityview' ),
			'class'    => 'widefat',
			'requires' => 'no_entries_hide',
			'group'    => 'visibility',
		);

		return $new_options + $field_options;
	}

	/**
	 * Retrieve the other entries based on the current View and entry.
	 *
	 * @param \GV\Template_Context $context The context that contains the View and the Entry.
	 *
	 * @return \GV\Entry[] The entries.
	 */
	public function get_entries( $context ) {
		add_action( 'gravityview/view/query', array( $this, 'gf_query_filter' ), 10, 3 );
		add_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ), 10, 3 );

		// Exclude widiget modifiers altogether
		global $wp_filter;
		$filters = array(
			'gravityview_fe_search_criteria',
			'gravityview_search_criteria',
			'gravityview/view/query',
		);
		$removed = $remove = array();
		foreach ( $filters as $filter ) {
			foreach ( $wp_filter[ $filter ] as $priority => $callbacks ) {
				foreach ( $callbacks as $id => $callback ) {
					if ( ! is_array( $callback['function'] ) ) {
						continue;
					}
					if ( $callback['function'][0] instanceof \GV\Widget ) {
						$remove[] = array( $filter, $priority, $id );
					}
				}
			}
		}

		foreach ( $remove as $r ) {
			list( $filter, $priority, $id ) = $r;
			$removed[]                      = array( $filter, $priority, $id, $wp_filter[ $filter ]->callbacks[ $priority ][ $id ] );
			unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $id ] );
		}

		$this->context = $context;

		$entries = $context->view->get_entries()->all();

		foreach ( $removed as $r ) {
			list( $filter, $priority, $id, $function )           = $r;
			$wp_filter[ $filter ]->callbacks[ $priority ][ $id ] = $function;
		}

		remove_action( 'gravityview/view/query', array( $this, 'gf_query_filter' ) );
		remove_filter( 'gravityview_fe_search_criteria', array( $this, 'filter_entries' ) );

		$this->context = null;

		return $entries;
	}

	public function filter_entries( $search_criteria, $form_id = null, $args = array(), $force_search_criteria = false ) {
		$context = $this->context;

		$created_by = $context->entry['created_by'];

		/** Filter entries by approved and created_by. */
		$search_criteria['field_filters'][] = array(
			'key'      => 'created_by',
			'value'    => $created_by,
			'operator' => 'is',
		);

		/**
		 * Modify the search parameters before the entries are fetched.
		 *
		 * @since 1.11
		 * @since 2.0 Added $gravityview parameter.
		 *
		 * @param array                $criteria      Gravity Forms search criteria array, as used by GVCommon::get_entries().
		 * @param array                $view_settings Associative array of settings with plugin defaults used if not set by the View.
		 * @param int                  $form_id       The Gravity Forms ID.
		 * @param \GV\Template_Context $gravityview   The context.
		 */
		$criteria = apply_filters( 'gravityview/field/other_entries/criteria', $search_criteria, $context->view->settings->as_atts(), $context->view->form->ID, $context );

		/** Force mode all and filter out our own entry. */
		$search_criteria['field_filters']['mode'] = 'all';
		$search_criteria['field_filters'][]       = array(
			'key'      => 'id',
			'value'    => $context->entry->ID,
			'operator' => 'isnot',
		);

		$search_criteria['paging']['page_size'] = $context->field->page_size ? : 10;

		return $search_criteria;
	}

	public function gf_query_filter( &$query, $view, $request ) {
		// @todo One day, we can implement in GF_Query as well...
		// this would allow us to keep on using nested conditionals and not force 'all'
	}
}

new GravityView_Field_Other_Entries();
