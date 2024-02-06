<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */

global $post;

$view   = \GV\View::from_post( $post );
$secret = $view->get_validation_secret();
$atts   = [ sprintf( "id='%d'", $post->ID ) ];
if ( $secret ) {
	$atts[] = sprintf( "secret='%s'", $secret );
}

$shortcode = sprintf( '[gravityview %s]', implode( ' ', $atts ) );
?>
<div class="misc-pub-section gv-shortcode misc-pub-section-last">
	<i class="dashicons dashicons-editor-code"></i>
	<span><?php esc_html_e( 'Embed Shortcode', 'gk-gravityview' ); ?></span>
	<div>
		<input type="text" readonly="readonly" value="<?php echo esc_attr($shortcode) ?>" class="code widefat" />
		<span class="howto"><?php esc_html_e( 'Add this shortcode to a post or page to embed this view.', 'gk-gravityview' ); ?></span>
		<span class="copied"><?php esc_html_e( 'Copied!', 'gk-gravityview' ); ?></span>
	</div>
</div>
