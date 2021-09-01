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

<div class="gv-view-template-notice notice inline error hidden">
    <p><!-- Contents will be replaced by JavaScript if there is an error --></p>
</div>

<?php // list all the available templates (type= fresh or custom ) ?>
<div class="gv-grid">
	<?php foreach( $templates as $id => $template ) {
		$selected     = ( $id == $current_template ) ? ' gv-selected' : '';
		$placeholder  = ! empty( $template['buy_source'] );
		$is_included  = ! empty( $template['included'] );
		$plugin_data  = GravityView_Admin_Installer::get_wp_plugins_data( \GV\Utils::get( $template, 'textdomain', '' ) );
		$button_text  = empty( $plugin_data ) ? esc_html__( 'Install Layout', 'gravityview' ) : esc_html__( 'Activate & Select Layout', 'gravityview' );
		$button_class = 'gv-layout-' . ( empty( $plugin_data ) ? 'install' : 'activate' );
		$template_path = isset($plugin_data['path']) ? $plugin_data['path'] : '';

		?>
		<div class="gv-grid-col-1-4">
			<div class="gv-view-types-module<?php echo $selected; if( $placeholder ) { echo ' gv-view-template-placeholder'; } ?>" data-filter="<?php echo esc_attr( $template['type'] ); ?>">
				<div class="gv-view-types-normal">
					<img src="<?php echo esc_url( $template['logo'] ); ?>" alt="<?php echo esc_attr( $template['label'] ); ?>">
					<h5><?php echo esc_html( $template['label'] ); ?></h5>
					<p class="description"><?php echo esc_html( $template['description'] ); ?></p>
				</div>
				<div class="gv-view-types-hover">
					<div>
						<?php
						if( $is_included ) {
						?>
							<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=gravityview&page=gv-admin-installer' ) ); ?>" class="button button-secondary button-hero <?php echo $button_class; ?>" rel="internal" data-template-path="<?php echo $template_path; ?>"><?php echo $button_text; ?></a></p>
							<?php if( !empty( $template['license'] ) ) { ?>
								<p class="gv-included-in"><?php echo sprintf( esc_html__( 'This layout is included in the %s license.', 'gravityview' ), esc_html( str_replace( ' ', '&nbsp;', $template['license'] ) ) ); ?></p>
							<?php } ?>
						<?php
						} elseif( $placeholder ) {
							$utm_string = '?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=' . urlencode( $template['license'] ) . '&utm_content=' . urlencode( $template['slug'] );
							?>
							<p><a href="<?php echo esc_url( $template['buy_source'] ); ?>" class="button button-primary button-hero" rel="noreferrer noopener external"><?php esc_html_e( 'Buy Now', 'gravityview'); ?></a></p>

							<?php if( !empty( $template['preview'] ) ) { ?>
								<p><a href="<?php echo esc_url( $template['preview'] ); ?>" rel="noreferrer noopener external" class="button button-secondary"><i class="dashicons dashicons-external" style="vertical-align: middle;" title="<?php esc_html_e( 'View a live demo of this layout', 'gravityview'); ?>"></i> <?php esc_html_e( 'Try a demo', 'gravityview' ); ?></a></p>
							<?php } ?>

							<?php if( ! empty( $template['license'] ) ) { ?>
								<p class="gv-included-in"><?php echo sprintf( esc_html__( 'This layout is included in the %s license.', 'gravityview' ), '<a href="https://gravityview.co/pricing/' . esc_attr( $utm_string ) . '" rel="noreferrer noopener external">' . esc_html( str_replace( ' ', '&nbsp;', $template['license'] ) ) . '</a>' ); ?></p>
							<?php } ?>
						<?php }

						if ($placeholder || $is_included) { ?> </div><div class="hidden"> <?php } ?>

                        <p><a href="#gv_select_template" role="button" class="gv_select_template button button-hero button-primary" data-templateid="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Select', 'gravityview'); ?></a></p>
                        <?php if( !empty( $template['preview'] ) ) { ?>
                            <a href="<?php echo esc_url( $template['preview'] ); ?>" rel="external" class="gv-site-preview"><i class="dashicons dashicons-welcome-view-site" title="<?php esc_html_e( 'View a live demo of this preset', 'gravityview'); ?>"></i></a>
                        <?php } ?>
					</div>
				</div>
			</div>
		</div>
	<?php }  ?>
</div>
