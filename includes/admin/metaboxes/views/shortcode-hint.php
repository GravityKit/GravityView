<?php
/**
 * @package GravityView
 * @subpackage Gravityview/admin/metaboxes/partials
 * @global $post
 */

global $post;
?>
<div class="misc-pub-section gv-shortcode misc-pub-section-last">
	<i class="dashicons dashicons-editor-code"></i>
	<span><?php esc_html_e( 'Embed Shortcode', 'gravityview' ); ?></span>
	<div>
		<input type="text" readonly="readonly" value="[gravityview id='<?php echo $post->ID; ?>']" class="code widefat" />
		<span class="howto"><?php esc_html_e( 'Add this shortcode to a post or page to embed this view.', 'gravityview' ); ?></span>
	</div>
</div>