<?php
/**
 * @file class-gravityview-field-sequence.php
 * @package GravityView
 * @subpackage includes\fields
 */

/**
 * Add a sequence field.
 * @since develop
 */
class GravityView_Field_Sequence extends GravityView_Field {

	var $name = 'sequence';

	var $contexts = array( 'single', 'multiple' );

	/**
	 * @var bool
	 */
	var $is_sortable = false;

	/**
	 * @var bool
	 */
	var $is_searchable = false;

	/**
	 * @var bool
	 */
	var $is_numeric = true;

	var $_custom_merge_tag = 'sequence';

	var $group = 'gravityview';

	public function __construct() {

		$this->label = esc_html__( 'Result Number', 'gravityview' );

		add_filter( 'gravityview/metaboxes/tooltips', array( $this, 'field_tooltips') );

		parent::__construct();
	}

	/**
	 * Add tooltips
	 * @param  array $tooltips Existing tooltips
	 * @return array           Modified tooltips
	 */
	public function field_tooltips( $tooltips ) {

		$return = $tooltips;

		$return['reverse_sequence'] = array(
			'title' => __('Reverse the order of the result numbers', 'gravityview'),
			'value' => __('Output row numbers in descending order. If enabled, numbers will go from high to low.', 'gravityview'),
		);

		return $return;
	}

	public function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		unset ( $field_options['search_filter'] );

		$new_fields = array(
			'start' => array(
				'type' => 'number',
				'label' => __( 'First Row Number', 'gravityview' ),
				'value' => '1',
				'merge_tags' => false,
			),
			'reverse' => array(
				'type' => 'checkbox',
				'label' => __( 'Reverse the order of the result numbers', 'gravityview' ),
				'tooltip' => 'reverse_sequence',
				'value' => '',
			),
		);

		return $new_fields + $field_options;
	}

	/**
	 * Replace {sequence} Merge Tags inside Custom Content fields
	 *
	 * TODO:
	 * - Find a better way to infer current View data (without using legacy code)
	 * - Add tests
	 *
	 * @param array $matches
	 * @param string $text
	 * @param array $form
	 * @param array $entry
	 * @param bool $url_encode
	 * @param bool $esc_html
	 *
	 * @return string
	 */
	public function replace_merge_tag( $matches = array(), $text = '', $form = array(), $entry = array(), $url_encode = false, $esc_html = false ) {


		$view_data = gravityview_get_current_view_data(); // TODO: Don't use legacy code...

		if ( empty( $view_data ) ) {
			return '';
		}

		$return = $text;

		$context = new \GV\Template_Context();
		$context->view = \GV\View::by_id( $view_data['view_id'] );
		$context->entry = \GV\GF_Entry::from_entry( $entry );

		$gv_field = \GV\Internal_Field::by_id( 'sequence' );

		foreach ( $matches as $match ) {

			$full_tag = $match[0];
			$property = $match[1];

			$gv_field->reverse = false;
			$gv_field->start = 1;

			$modifiers = explode( ',', trim( $property ) );

			foreach ( $modifiers as $modifier ) {

				$modifier = trim( $modifier );

				if ( 'reverse' === $modifier ) {
					$gv_field->reverse = true;
				}

				$maybe_start = explode( ':', $modifier );

				if( 'start' === rgar( $maybe_start, 0 ) && is_numeric( rgar( $maybe_start, 1 ) ) ) {
					$gv_field->start = (int) rgar( $maybe_start, 1 );
				}
			}

			$context->field = $gv_field;

			$return = str_replace( $full_tag, $this->get_sequence( $context ), $return );
		}

		return $return;
	}

	/**
	 * Calculate the current sequence number for the context.
	 *
	 * @param  \GV\Template_Context $context The context.
	 *
	 * @return int The sequence number for the field/entry within the view results.
	 */
	public function get_sequence( $context ) {
		static $startlines = array();

		$context_key = md5( json_encode(
			array(
				$context->view->ID,
				\GV\Utils::get( $context, 'field/UID' ), //TODO: Generate UID when using Merge Tag
			)
		) );

		/**
		 * Figure out the starting number.
		 */
		if ( $context->request && $entry = $context->request->is_entry() ) {
			$sql_query = '';
			add_filter( 'gform_gf_query_sql', $callback = function( $sql ) use ( &$sql_query ) {
				$sql_query = $sql;
				return $sql;
			} );

			$total = $context->view->get_entries()->total();
			remove_filter( 'gform_gf_query_sql', $callback );

			unset( $sql_query['paginate'] );

			global $wpdb;

			foreach ( $wpdb->get_results( implode( ' ', $sql_query ), ARRAY_A ) as $n => $result ) {
				if ( in_array( $entry->ID, $result ) ) {
					return $context->field->reverse ? ( $total - $n ) : ( $n + 1 );
				}
			}

			return 0;
		} elseif ( ! isset( $startlines[ $context_key ] ) ) {
			$pagenum  = max( 0, \GV\Utils::_GET( 'pagenum', 1 ) - 1 );
			$pagesize = $context->view->settings->get( 'page_size', 25 );

			if ( $context->field->reverse ) {
				$startlines[ $context_key ] = $context->view->get_entries()->total() - ( $pagenum * $pagesize );
				$startlines[ $context_key ] += $context->field->start - 1;
			} else {
				$startlines[ $context_key ] = ( $pagenum * $pagesize ) + $context->field->start;
			}
		}

		return $context->field->reverse ? $startlines[ $context_key ]-- : $startlines[ $context_key ]++;
	}
}

new GravityView_Field_Sequence;
