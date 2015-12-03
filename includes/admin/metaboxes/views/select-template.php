<?php
/**
 * @file select-template.php
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global WP_Post $post
 */

global $post;

// Use nonce for verification
wp_nonce_field( 'gravityview_select_template', 'gravityview_select_template_nonce' );

//current value
$current_template = gravityview_get_template_id( $post->ID );

$templates = gravityview_get_registered_templates();

// current input
?>
<input type="hidden" id="gravityview_directory_template" name="gravityview_directory_template" value="<?php echo esc_attr( $current_template ); ?>" />

<?php // list all the available templates (type= fresh or custom ) ?>
<div class="gv-grid">
	<?php foreach( $templates as $id => $template ) {
		$selected = ( $id == $current_template ) ? ' gv-selected' : ''; ?>

		<div class="gv-grid-col-1-3">
			<div class="gv-view-types-module<?php echo $selected; ?>" data-filter="<?php echo esc_attr( $template['type'] ); ?>">
				<div class="gv-view-types-hover">
					<div>
						<?php if( !empty( $template['buy_source'] ) ) { ?>
							<p><a href="<?php echo esc_url( $template['buy_source'] ); ?>" class="button-primary button-buy-now"><?php esc_html_e( 'Buy Now', 'gravityview'); ?></a></p>
						<?php } else { ?>
							<p><a href="#gv_select_template" class="button button-large button-primary" data-templateid="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Select', 'gravityview'); ?></a></p>
							<?php if( !empty( $template['preview'] ) ) { ?>
								<a href="<?php echo esc_url( $template['preview'] ); ?>" rel="external" class="gv-site-preview"><i class="dashicons dashicons-admin-links" title="<?php esc_html_e( 'View a live demo of this preset', 'gravityview'); ?>"></i></a>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
				<div class="gv-view-types-normal">
					<img src="<?php echo esc_url( $template['logo'] ); ?>" alt="<?php echo esc_attr( $template['label'] ); ?>">
					<h5><?php echo esc_attr( $template['label'] ); ?></h5>
					<p class="description"><?php echo esc_attr( $template['description'] ); ?></p>
				</div>
			</div>
		</div>
	<?php }  ?>
</div>