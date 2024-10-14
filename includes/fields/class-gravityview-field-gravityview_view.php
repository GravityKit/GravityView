<?php
/**
 * @file class-gravityview-field-view.php
 * @since 2.19
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Field to display a GravityView View.
 *
 * @since 2.19
 */
class GravityView_Field_GravityView_View extends GravityView_Field {

	public $name = 'gravityview_view';

	public $contexts = array( 'single' );

	public $group = 'gravityview';

	public $is_searchable = false;

	public $is_sortable = false;

	public $icon = 'data:image/svg+xml,%3Csvg%20fill=%22none%22%20height=%2280%22%20viewBox=%220%200%2080%2080%22%20width=%2280%22%20xmlns=%22http://www.w3.org/2000/svg%22%20xmlns:xlink=%22http://www.w3.org/1999/xlink%22%3E%3Cpath%20clip-rule=%22evenodd%22%20d=%22m70.6842%2063.9999h-9.2135v3c0%204.9706-4.1537%209-9.2134%209h-24.5683c-5.1266%200-9.2135-4.0294-9.2135-9v-44.9999h-9.21341c-1.73812%200-3.07072%201.343-3.07072%202.9999v30c0%201.657%201.3326%203.0001%203.07072%203.0001h1.53601c.8068%200%201.5347.6715%201.5347%201.5v3c0%20.8284-.7279%201.4999-1.5347%201.4999h-1.53601c-5.13114%200-9.213445-4.0294-9.213445-9v-30c0-4.9705%204.082305-9%209.213445-9h9.21341v-2.9999c0-4.97062%204.0869-9%209.2135-9h24.5683c5.0597%200%209.2134%204.02938%209.2134%209v45h9.2135c1.6711%200%203.0707-1.3431%203.0707-3.0001v-30c0-1.6569-1.3996-2.9999-3.0707-2.9999h-1.536c-.8736%200-1.536-.6716-1.536-1.5v-3.0001c0-.8284.6624-1.5%201.536-1.5h1.536c5.0642%200%209.2121%204.0295%209.2121%209v30c0%204.9706-4.1479%209-9.2121%209zm-15.3562-50.9999c0-1.657-1.404-3-3.0707-3h-24.5683c-1.7335%200-3.0708%201.343-3.0708%203v53.9999c0%201.6568%201.3373%203.0001%203.0708%203.0001h24.5683c1.6667%200%203.0707-1.3433%203.0707-3.0001z%22%20fill=%22%2340464d%22%20fill-rule=%22evenodd%22/%3E%3C/svg%3E%0A';

	function __construct() {

		$this->label       = __( 'GravityView View', 'gk-gravityview' );
		$this->description = __( 'Embed a View inside a View!', 'gk-gravityview' );

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function field_options( $field_options, $template_id, $field_id, $context, $input_type, $form_id ) {

		unset( $field_options['search_filter'], $field_options['show_as_link'], $field_options['new_window'] );

		$view_cpts = GVCommon::get_all_views(
			array(
				'orderby' => 'post_title',
				'order'   => 'ASC',
				'exclude' => array( get_the_ID() ),
			)
		);

		$formatted_views = array(
			0 => esc_html__( 'Select a View', 'gk-gravityview' ),
		);

		foreach ( $view_cpts as $view_cpt ) {
			$formatted_views[ $view_cpt->ID ] = sprintf(
				'%s (#%d)',
				$view_cpt->post_title ?: esc_html__( 'View', 'gk-gravityview' ),
				$view_cpt->ID
			);
		}

		$new_fields = array(
			'view_id'         => array(
				'type'    => 'select',
				'label'   => __( 'View to embed', 'gk-gravityview' ),
				'value'   => '',
				'options' => $formatted_views,
			),
			'search_field'    => array(
				'type'        => 'text',
				'label'       => __( 'Search field', 'gk-gravityview' ),
				'value'       => '',
				'desc'        => strtr(
					__( 'Accepts a Field ID or entry meta name. [link]Learn more about pre-filtering Views.[/link]', 'gk-gravityview' ),
					array(
						'[link]'  => '<a href="https://docs.gravitykit.com/article/73-using-the-shortcode#advanced-use-cases" target="_blank">',
						'[/link]' => '<span class="screen-reader-text">(' . esc_attr__( 'This link opens in a new window.', 'gk-gravityview' ) . ')</span></a>',
					)
				),
				'class'       => 'widefat',
				'placeholder' => esc_html__( 'Example: created_by or 1.3', 'gk-gravityview' ),
				'group'       => 'advanced',
			),
			'search_operator' => array(
				'type'    => 'select',
				'label'   => __( 'Search operator', 'gk-gravityview' ),
				'value'   => 'is',
				'options' => array(
					'is'          => __( 'is', 'gk-gravityview' ),
					'contains'    => __( 'contains', 'gk-gravityview' ),
					'isnot'       => __( 'is not', 'gk-gravityview' ),
					'like'        => __( 'like', 'gk-gravityview' ),
					'in'          => __( 'in', 'gk-gravityview' ),
					'starts_with' => __( 'starts with', 'gk-gravityview' ),
					'ends_with'   => __( 'ends with', 'gk-gravityview' ),
				),
				'group'   => 'advanced',
			),
			'search_value'    => array(
				'type'        => 'text',
				'class'       => 'code widefat',
				'label'       => __( 'Search value', 'gk-gravityview' ),
				'value'       => '',
				'desc'        => esc_html__( 'Pre-filter a View by the values of an entry.', 'gk-gravityview' ),
				'placeholder' => __( 'Example: {user:ID}', 'gk-gravityview' ),
				'merge_tags'  => 'force',
				'group'       => 'advanced',
			),
			'start_date'      => array(
				'type'        => 'text',
				'label'       => __( 'Override start date', 'gk-gravityview' ),
				'value'       => '',
				'merge_tags'  => 'force',
				'group'       => 'advanced',
				'placeholder' => esc_html__( 'Example: -1 week', 'gk-gravityview' ),
			),
			'end_date'        => array(
				'type'        => 'text',
				'label'       => __( 'Override end date', 'gk-gravityview' ),
				'value'       => '',
				'merge_tags'  => 'force',
				'group'       => 'advanced',
				'placeholder' => esc_html__( 'Example: tomorrow', 'gk-gravityview' ),
			),
			'page_size'       => array(
				'type'    => 'select',
				'label'   => __( 'Override page size', 'gk-gravityview' ),
				'value'   => 'default',
				'group'   => 'advanced',
				'options' => array(
					'default' => esc_html__( 'Use View setting', 'gk-gravityview' ),
					10        => 10,
					25        => 25,
					50        => 50,
					100       => 100,
				),
			),
		);

		return $new_fields + $field_options;
	}


	/**
	 * Outputs the View based on the configured field settings.
	 *
	 * @since 2.19
	 *
	 * @used-by ../../templates/fields/field-gravityview_view-html.php
	 *
	 * @param array                $field_settings
	 * @param \GV\Template_Context $context
	 *
	 * @return void
	 */
	public static function render_frontend( $field_settings, $context ) {
		global $post;

		$view_id = $field_settings['view_id'] ?? null;
		$form    = $context->views->form->form ?? GFAPI::get_form( $field_settings['form_id'] ?? 0 );

		if ( ! $view_id || ! $form ) {
			return;
		}

		$attributes = [];

		if ( ! empty( $post->ID ) ) {
			$attributes = [ 'post_id' => $post->ID ];
		}

		$page_size_value = \GV\Utils::get( $field_settings, 'page_size', 'default' );
		if ( 'default' !== $page_size_value ) {
			$attributes['page_size'] = (int) $page_size_value;
		}

		// Prepare search field.
		$search_field = \GV\Utils::get( $field_settings, 'search_field' );
		if ( ! is_null( $search_field ) ) {
			$attributes['search_field'] = esc_attr( $search_field );
		}

		$search_value = \GV\Utils::get( $field_settings, 'search_value' );
		if ( ! is_null( $search_value ) ) {
			$search_value               = GFCommon::replace_variables( $search_value, $form, $context->entry->as_entry() );
			$attributes['search_value'] = esc_attr( $search_value );
		}

		// Prepare search operator.
		$search_operator = \GV\Utils::get( $field_settings, 'search_operator' );
		if ( ! is_null( $search_operator ) ) {
			$attributes['search_operator'] = esc_attr( $search_operator );
		}

		// Start date
		$start_date = \GV\Utils::get( $field_settings, 'start_date' );
		if ( ! empty( $start_date ) ) {
			$start_date               = GFCommon::replace_variables( $start_date, $form, $context->entry->as_entry() );
			$attributes['start_date'] = esc_attr( $start_date );
		}

		// End date
		$end_date = \GV\Utils::get( $field_settings, 'end_date' );
		if ( ! empty( $end_date ) ) {
			$end_date               = GFCommon::replace_variables( $end_date, $form, $context->entry->as_entry() );
			$attributes['end_date'] = esc_attr( $end_date );
		}

		$view      = \GV\View::by_id( $view_id );

		if ( ! $view ) {
			return;
		}

		$shortcode = $view->get_shortcode( $attributes );

		echo do_shortcode( $shortcode );
	}
}

new GravityView_Field_GravityView_View();
