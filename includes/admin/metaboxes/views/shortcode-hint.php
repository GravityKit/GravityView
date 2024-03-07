<?php
/**
 * @package    GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */

global $post;

$view      = \GV\View::from_post( $post );
$shortcode = $view->get_shortcode();
$secret    = $view->get_validation_secret( true );
?>
<div class="misc-pub-section gv-shortcode misc-pub-section-last">
	<i class="dashicons dashicons-editor-code"></i>
	<span id="gv-embed-shortcode-label"><?php esc_html_e( 'Embed Shortcode', 'gk-gravityview' ); ?></span>
	<div>
		<input id="gv-embed-shortcode" aria-labelledby="gv-embed-shortcode-label" type="text" readonly="readonly" value="<?php echo esc_attr( $shortcode ); ?>" class="code widefat" data-secret="<?php echo esc_attr( $secret ); ?>" />
		<span class="howto"><?php echo esc_html__( 'Add this shortcode to a post or page to embed this view.', 'gk-gravityview' ) . ' ' . esc_html__( 'Click to copy', 'gk-gravityview' ); ?>.</span>
		<span class="copied"><?php esc_html_e( 'Copied!', 'gk-gravityview' ); ?></span>
	</div>
</div>
