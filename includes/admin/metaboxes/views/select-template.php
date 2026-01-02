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

// current value
$directory_template = gravityview_get_directory_entries_template_id( $post->ID );

$templates = gravityview_get_registered_templates();

$wp_plugins = array();

foreach ( GravityKitFoundation::helpers()->core->get_plugins() as $path => $plugin ) {
	if ( empty( $plugin['TextDomain'] ) ) {
		continue;
	}

	$wp_plugins[ $plugin['TextDomain'] ] = array(
		'path'      => $path,
		'version'   => $plugin['Version'],
		'activated' => is_plugin_active( $path ),
	);
}

// current input
?>
<div class="gv-view-template-notice notice inline error hidden">
	<p><!-- Contents will be replaced by JavaScript if there is an error --></p>
</div>

<?php // list all the available templates (type= fresh or custom ) ?>
<div class="gv-grid">
	<div class="gv-grid-row">
	<?php
	// Retrieve the base templates.
	$base_template_mapping = array_reduce(
		array_keys( $templates ),
		static function ( array $mapping, string $key ) use ( $templates ) {
			$template    = $templates[ $key ];
			$placeholder = ! empty( $template['buy_source'] );
			$is_included = ! empty( $template['included'] );
			$type        = $template['type'] ?? '';

			if ( 'custom' !== $type || $placeholder || $is_included ) {
				return $mapping;
			}

			$mapping[ $template['slug'] ] = $key;

			return $mapping;
		},
		[]
	);

	foreach ( $templates as $id => $template ) {
		$selected           = ( $id == $directory_template ) ? ' gv-selected' : '';
		$placeholder        = ! empty( $template['buy_source'] );
		$is_included        = ! empty( $template['included'] );
		$plugin_data        = GravityKit\GravityView\Foundation\Helpers\Core::get_installed_plugin_by_text_domain( $template['textdomain'] ?? '' ) ?? [];
		$plugin_text_domain = $plugin_data['text_domain'] ?? $template['textdomain'] ?? '';
		$button_text        = empty( $plugin_data ) ? esc_html__( 'Install', 'gk-gravityview' ) : esc_html__( 'Activate & Select', 'gk-gravityview' );
		$button_class       = 'gv-layout-' . ( empty( $plugin_data ) ? 'install' : 'activate' );
		$base_type          = $base_template_mapping[ $template['slug'] ?? 'table' ] ?? 'default_table';
		$template_path      = \GV\Utils::get( $plugin_data, 'path', '' );
		$template_id        = \GV\Utils::get( $template, 'template_id', '' );
		$download_id        = \GV\Utils::get( $template, 'download_id', '' );
		$type               = \GV\Utils::get( $template, 'type', '' );
		$logo               = \GV\Utils::get( $template, 'logo', '' );
		$label              = \GV\Utils::get( $template, 'label', '' );
		$description        = \GV\Utils::get( $template, 'description', '' );
		?>
		<div class="gv-grid-col-1-6">
			<div class="gv-view-types-module
			<?php
			echo $selected;
			if ( $placeholder ) {
				echo ' gv-view-template-placeholder'; }
			?>
			" data-filter="<?php echo esc_attr( $type ); ?>">
				<div class="gv-view-types-normal">
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $label ); ?>">
					<h5><?php echo esc_html( $label ); ?></h5>
					<p class="description"><?php echo esc_html( $description ); ?></p>
				</div>
				<div class="gv-view-types-hover">
					<div>
						<?php
						if ( $is_included ) {
							?>
							<p><button class="button button-secondary button-hero <?php echo esc_attr( $button_class ); ?>" rel="internal" data-template-text-domain="<?php echo esc_attr( $plugin_text_domain ); ?>" data-templateid="<?php echo esc_attr( $template_id ); ?>" data-download-id="<?php echo esc_attr( $download_id ); ?>"><?php echo $button_text; ?></button></p>
							<?php if ( ! empty( $template['license'] ) ) { ?>
								<p class="gv-included-in"><?php printf( esc_html__( 'This layout is included in the %s license.', 'gk-gravityview' ), esc_html( str_replace( ' ', '&nbsp;', $template['license'] ) ) ); ?></p>
							<?php } ?>
							<?php
						} elseif ( $placeholder ) {
							$utm_string = '?utm_source=plugin&utm_medium=buy_now&utm_campaign=view_type&utm_term=' . urlencode( $template['license'] ) . '&utm_content=' . urlencode( $template['slug'] );
							?>
							<p><a href="<?php echo esc_url( $template['buy_source'] ); ?>" class="button button-primary button-hero" rel="noreferrer noopener external"><?php esc_html_e( 'Buy Now', 'gk-gravityview' ); ?></a></p>

							<?php if ( ! empty( $template['preview'] ) ) { ?>
								<p><a href="<?php echo esc_url( $template['preview'] ); ?>" rel="noreferrer noopener external" class="button button-secondary"><i class="dashicons dashicons-external" style="vertical-align: middle;" title="<?php esc_html_e( 'View a live demo of this layout', 'gk-gravityview' ); ?>"></i> <?php esc_html_e( 'Try a demo', 'gk-gravityview' ); ?></a></p>
							<?php } ?>

							<?php if ( ! empty( $template['license'] ) ) { ?>
								<p class="gv-included-in"><?php printf( esc_html__( 'This layout is included in the %s license.', 'gk-gravityview' ), '<a href="https://www.gravitykit.com/pricing/' . esc_attr( $utm_string ) . '" rel="noreferrer noopener external">' . esc_html( str_replace( ' ', '&nbsp;', $template['license'] ) ) . '</a>' ); ?></p>
							<?php } ?>
							<?php
						}

						if ( $placeholder || $is_included ) {

							?>
						</div><div class="hidden"> <?php } ?>

						<p><a href="#gv_select_template" role="button" class="gv_select_template button button-hero button-primary" data-templateid="<?php echo esc_attr( $id ); ?>" data-base-template="<?php echo esc_attr( $base_type) ; ?>" data-testid="select-<?php echo esc_attr( sanitize_title( $label ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select %s', 'gk-gravityview' ), $label ) ); ?>"><?php esc_html_e( 'Select', 'gk-gravityview' ); ?></a></p>
						<?php if ( ! empty( $template['preview'] ) ) { ?>
							<a href="<?php echo esc_url( $template['preview'] ); ?>" rel="external" class="gv-site-preview"><i class="dashicons dashicons-welcome-view-site" title="<?php esc_html_e( 'View a live demo of this preset', 'gk-gravityview' ); ?>"></i></a>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
	</div>
</div>
