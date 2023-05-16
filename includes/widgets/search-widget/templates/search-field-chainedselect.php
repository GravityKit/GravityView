<?php
/**
 * Display the Chained Selects input field for the search widget.
 *
 * TODO:
 *
 * - [ ] When using Chained Select, it only works well when "All"
 *
 * @file class-search-widget.php See for usage
 */

$gravityview_view = GravityView_View::getInstance();
$view_id = $gravityview_view->getViewId();
$search_field = $gravityview_view->search_field;

if ( ! class_exists( 'GF_Chained_Field_Select' ) ) {
	gravityview()->log->error( 'The Gravity Forms Chained Select Add-On is not active.' );
	return;
}

// Make sure that there are choices to display
if( empty( $search_field['choices'] ) ) {
	gravityview()->log->debug( 'search-field-chainedselect.php - No choices for field' );
	return;
}

$form = \GV\GF_Form::from_form( $gravityview_view->getForm() );

$field = \GV\GF_Field::by_id( $form, $search_field['key'] );

/** @var GF_Chained_Field_Select $gf_field */
$gf_field = $field->field;

/**
 * Prevent Chained Select Search Bar input fields from outputting styles.
 * @since 2.14.4
 * @param GravityView_Widget_Search $this GravityView Widget instance
 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
 */
$alignment = apply_filters( 'gravityview/search/chained_selects/alignment', $gravityview_view->search_layout, $search_field );

/**
 * Choose whether to hide inactive dropdowns in the chain.
 * @since 2.14.4
 * @param bool $hide_inactive Whether to hide drop-downs that aren't available yet.
 * @param GravityView_Widget_Search $this GravityView Widget instance
 * @param array{key:string,label:string,value:string,type:string,choices:array} $search_field
 */
$hide_inactive = apply_filters( 'gravityview/search/chained_selects/hide_inactive', false, $search_field );

// Set horizontal/vertical alignment
$gf_field->chainedSelectsAlignment = $gravityview_view->search_layout;
?>
<div class="gv-search-box gv-search-field-chainedselect">
	<?php if( ! gv_empty( $search_field['label'], false, false ) ) { ?>
		<label for="search-box-<?php echo esc_attr( $search_field['name'] ); ?>"><?php echo esc_html( $search_field['label'] ); ?></label>
	<?php
	}

	echo strtr( '<div id="field_{form_id}_{field_id}">{input}</div>', array(
			'{form_id}'  => $form->ID,
			'{field_id}' => $field->ID,
			'{input}'    => $gf_field->get_field_input( $form->form, GravityView_Plugin_Hooks_Gravity_Forms_Chained_Selects::get_field_values( $gf_field ) ),
	) );
	?>
</div>
<script>
( function( $ ) {
	$( 'select', '.gv-search-field-chainedselect').on( 'change', function( e ) {
		window.gform.doAction( 'gform_input_change', e.target, <?php echo (int) $form->ID; ?>, <?php echo (int) $field->ID; ?> );
	});
<?php
	echo strtr( 'new GFChainedSelects( {form_id}, {field_id}, {hide_inactive}, "{search_layout}" );', array(
			'{form_id}'       => $form->ID,
			'{field_id}'      => $field->ID,
			'{hide_inactive}' => (int) $hide_inactive,
			'{search_layout}' => $gravityview_view->search_layout,
	) );
?>
} )( jQuery );
</script>
