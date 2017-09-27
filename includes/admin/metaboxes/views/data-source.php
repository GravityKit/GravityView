<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/views
 * @since 1.8
 * @global WP_Post $post
 */


// Use nonce for verification
wp_nonce_field( 'gravityview_select_form', 'gravityview_select_form_nonce' );

//current value
$current_form = (int) rgar( (array) $_GET, 'form_id', gravityview_get_form_id( $post->ID ) );

// If form is in trash or not existing, show error
GravityView_Admin::connected_form_warning( $current_form );

// check for available gravity forms
$forms = gravityview_get_forms('any');
?>
<label for="gravityview_form_id" ><?php esc_html_e( 'Where would you like the data to come from for this View?', 'gravityview' ); ?></label>

<p>
	<?php

	if ( empty( $current_form ) && GVCommon::has_cap( 'gravityforms_create_form' ) ) {
		?>
		<a class="button button-primary" href="#gv_start_fresh" title="<?php esc_attr_e( 'Use a Form Preset', 'gravityview' ); ?>"><?php esc_html_e( 'Use a Form Preset', 'gravityview' ); ?></a>

		<?php if( !empty( $forms ) ) { ?>
			<span>&nbsp;<?php esc_html_e( 'or use an existing form', 'gravityview' ); ?>&nbsp;</span>
		<?php }
	}

	// If there are no forms to select, show no forms.
	if( !empty( $forms ) ) { ?>
		<select name="gravityview_form_id" id="gravityview_form_id">
			<option value="" <?php selected( '', $current_form, true ); ?>>&mdash; <?php esc_html_e( 'list of forms', 'gravityview' ); ?> &mdash;</option>
			<?php foreach( $forms as $form ) { ?>
				<option value="<?php echo $form['id']; ?>" <?php selected( $form['id'], $current_form, true ); ?>><?php echo esc_html( $form['title'] ); ?></option>
			<?php } ?>
		</select>

		<?php

		    $view = \GV\View::from_post( $post );

			// Join forms but only if there are more of them to join on.
			if ( $view && $view->form ) {
				?>
					<p>Use joins to combine data from several forms into one longer entry. Join on quote ID fields, custom ID fields, anything you like!</p>
				<?php

				/**
				 * Joins can only be done on existing ones.
				 *
				 * Add the source form as the original one.
				 * No join column and on column since it's not joined to anything.
				 */
				$existing_joins = $view->joins;

				/**
				 * Joins that have been listed out in clauses. Cannot be reused.
				 */
				$known_joins = array( $view->form->ID );

				/**
				 * List out the current joins and let them be edited.
				 * + add an empty one for additions.
				 */
				foreach ( array_merge( $existing_joins, array( null /** the create a new join line */ ) ) as $join ) {
					if ( $join && $join->join ) {
						/** Joiner and joinee. This is getting confusing. */
						$known_joins = array_merge( $known_joins, array( $join->join->ID, $join->join_on->ID ) );
					}
					?>
						<div>
							<span>Join</span>
							<select name="gravityview_form_join[]" class="gravityview_form_join">
								<option>(no join)</option>
								<?php foreach ( $forms as $form ) {
									if ( in_array( $form['id'], $known_joins ) ) {
										printf( '<option value="%d" %s>%s</option>', $form['id'], selected( $form['id'], $join ? $join->join->ID : -1, false ), esc_html( $form['title'] ) );
									}
								} ?>
							</select>
							<select name="gravityview_form_join_column[]" class="gravityview_form_join_column" data-selected="<?php echo esc_attr( $join ? $join->join_column->ID : null ); ?>">
								<!-- Loaded dynamically -->
							</select>
							<span>with</span>
							<select name="gravityview_form_join_on[]" class="gravityview_form_join_on">
								<option>(no join)</option>
								<?php foreach ( $forms as $form ) {
									if ( ! in_array( $form['id'], $known_joins ) ) {
										printf( '<option value="%d" %s>%s</option>', $form['id'], selected( $form['id'], $join ? $join->join_on->ID : -1, false ), esc_html( $form['title'] ) );
									}
								} ?>
							</select>
							<select name="gravityview_form_join_on_column[]" class="gravityview_form_join_on_column" data-selected="<?php echo esc_attr( $join ? $join->join_on_column->ID : null ); ?>">
								<!-- Loaded dynamically -->
							</select>
						</div>
					<?php
				}

				?>
					<script type="text/javascript">
						var fields = <?php
							$fields = array();

							foreach ( $forms as $form ) {
								if ( $_form = \GV\GF_Form::by_id( $form['id'] ) ) {
									$fields[ $_form->ID ] = array();

									/** @var \GV\Field $field */
									foreach ( $_form->get_fields() as $field ) {

										if ( ! $field ) {
                                            continue;
										}

										$fields[ $_form->ID ] []= array(
											'id' => $field->ID,
											'label' => $field->get_label( $view, $_form ),
										);
									}
								}
							}

							/**
							 * A map of form_id -> fields_array
							 * (where fields_array -> id, label)
							 */
							echo json_encode( $fields );
						?>;

						jQuery( '.gravityview_form_join' ).on( 'change', function( e ) {
							var t = jQuery( e.currentTarget );
							var form_id = t.val();

							// Reset the field options for this form
							t.parent().find( '.gravityview_form_join_column option' ).remove();
							if ( fields[ form_id ] ) {
								var f = t.parent().find( '.gravityview_form_join_column' );
								// Add an empty one
								f.append( '<option></option>' );
								// Add every field, selecting if this is a saved join
								jQuery( fields[ form_id ] ).each( function( id, field ) {
									var field_node = jQuery( '<option></option>' );
									field_node.attr( 'value', field.id );
									field_node.html( field.label + ' (' + field.id + ')' );
									if ( field.id == f.attr( 'data-selected' ) ) {
										field_node.attr( 'selected', 'selected' );
									}
									t.parent().find( '.gravityview_form_join_column' ).append( field_node );
								} );
							}
						} ).trigger( 'change' );

						jQuery( '.gravityview_form_join_on' ).on( 'change', function( e ) {
							var t = jQuery( e.currentTarget );
							var form_id = t.val();

							// Reset the field options for this form
							t.parent().find( '.gravityview_form_join_on_column option' ).remove();
							if ( fields[ form_id ] ) {
								var f = t.parent().find( '.gravityview_form_join_on_column' );
								// Add an empty one
								f.append( '<option></option>' );
								// Add every field, selecting if this is a saved join
								jQuery( fields[ form_id ] ).each( function( id, field ) {
									var field_node = jQuery( '<option></option>' );
									field_node.attr( 'value', field.id );
									field_node.html( field.label + ' (' + field.id + ')' );
									if ( field.id == f.attr( 'data-selected' ) ) {
										field_node.attr( 'selected', 'selected' );
									}
									t.parent().find( '.gravityview_form_join_on_column' ).append( field_node );
								} );
							}
						} ).trigger( 'change' );
					</script>
					<hr />
				<?php
			}
		?>

	<?php } else { ?>
		<select name="gravityview_form_id" id="gravityview_form_id" class="hidden"><option selected="selected" value=""></option></select>
	<?php } ?>

	&nbsp;<a class="button button-primary" <?php if( empty( $current_form ) ) { echo 'style="display:none;"'; } ?> id="gv_switch_view_button" href="#gv_switch_view" title="<?php esc_attr_e( 'Switch View', 'gravityview' ); ?>"><?php esc_html_e( 'Switch View Type', 'gravityview' ); ?></a>
</p>

<?php // confirm dialog box ?>
<div id="gravityview_form_id_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gravityview' ); ?>">
	<p><?php esc_html_e( 'Changing the form will reset your field configuration. Changes will be permanent once you save the View.', 'gravityview' ); ?></p>
</div>

<?php // confirm template dialog box ?>
<div id="gravityview_switch_template_dialog" class="gv-dialog-options gv-dialog-warning" title="<?php esc_attr_e( 'Attention', 'gravityview' ); ?>">
	<p><?php esc_html_e( 'Changing the View Type will reset your field configuration. Changes will be permanent once you save the View.', 'gravityview' ); ?></p>
</div>

<?php // no js notice ?>
<div class="error hide-if-js">
	<p><?php esc_html_e( 'GravityView requires Javascript to be enabled.', 'gravityview' ); ?></p>
</div>

<?php
// hidden field to keep track of start fresh state ?>
<input type="hidden" id="gravityview_form_id_start_fresh" name="gravityview_form_id_start_fresh" value="0" />
