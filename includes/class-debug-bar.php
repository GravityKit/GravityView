<?php

/**
 * Show debugging information in concert with the excellent {@link http://wordpress.org/plugins/debug-bar/ Debug Bar plugin}
 *
 * @file /includes/class.debug-bar.php
 * @since 2.2.4
 */
class GravityView_Debug_Bar extends Debug_Bar_Panel {

	var $warnings = NULL;

	var $notices = NULL;

	var $_visible = true;

	/**
	 * Only show if WP_DEBUG is running. Set the title of the panel.
	 * @return void
	 */
	function init() {

		$icon = is_admin() ? '<i class="icon gv-icon-astronaut-head"></i>&nbsp;' : NULL;
		$this->title( $icon . __('GravityView', 'gravityview') );
	}

	function get_warnings() {

		if( is_null( $this->warnings ) ) {
			$this->warnings = GravityView_Logging::get_errors();
		}

		return $this->warnings;
	}

	function get_notices() {

		if( is_null( $this->notices ) ) {
			$this->notices = GravityView_Logging::get_notices();
		}

		return $this->notices;
	}

	function prerender() {
		$this->set_visible();
	}

	/**
	 * Should the panel be shown? If there are notices or warnings, yes.
	 * @param boolean $visible {@internal Leave here for compatibility with the Debug_Bar_Panel parent class}
	 * @return void
	 */
	function set_visible( $visible = false ) {
		$this->_visible = ( count( $this->get_notices() ) || count( $this->get_warnings() ) );
	}

	/**
	 * Render the panel HTML
	 * @return string Panel output
	 */
	function render() {

		$output = "
		<style type='text/css'>
			#debug-bar-gravityview { padding:10px 2%; width: 96%; }
				#debug-bar-gravityview * { clear: none; }
				#debug-bar-gravityview h3,
				#debug-bar-gravityview .gravityview-debug-bar-title { font-weight: bold; font-size: 14px; line-height: 1.4; margin: 5px 0;  }
				#debug-bar-gravityview .alignright { float: right!important; margin-left: 10px!important; }
				#debug-bar-gravityview .ul-square, #debug-bar-gravityview .ul-square li { list-style: square; }
				#querylist #debug-bar-gravityview ol { font-family: \"Helvetica Neue\",sans-serif!important; }
				#debug-bar-gravityview hr { border:none;border-top:1px solid #ccc; margin-top:20px; }
				#debug-bar-gravityview h3 { margin-top:20px; }
				#debug-bar-gravityview pre {
					width:98%;
					background:#f0f0f0;
					border: 1px solid #ccc;
					overflow:auto;
					max-height:300px;
					margin-bottom: 10px;
					font: 12px Monaco,\"Courier New\",Courier,Fixed!important;
				}
		</style>
		<div id='debug-bar-gravityview'>";

		$output .= '<img src="'.plugins_url('assets/images/astronaut-200x263.png', GRAVITYVIEW_FILE ).'" class="alignright" alt="" width="100" height="132" />';


		$warnings = $this->get_warnings();
		$notices = $this->get_notices();

		if(count($warnings)) {
			$output .= '<h3><span>'.__('Warnings', 'gravityview').'</span></h3>';
			$output .= '<ol>';
			foreach ( $warnings as $key => $notice) {
				if(empty($notice['message'])) { continue; }
				$output .= '<li><a href="#'.sanitize_html_class( 'gv-warning-' . $key ).'">'.strip_tags($notice['message']).'</a></li>';
			}
			$output .= '</ol><hr />';
		}
		if(count($notices)) {
			$output .= '<h3><span>'.__('Logs', 'gravityview').'</span></h3>';
			$output .= '<ol>';
			foreach ( $notices as $key => $notice) {
				if(empty($notice['message'])) { continue; }
				$output .= '<li><a href="#'.sanitize_html_class( 'gv-notice-' . $key ).'">'.strip_tags($notice['message']).'</a></li>';
			}
			$output .= '</ol><hr />';
		}

		if ( count( $warnings ) ) {
			$output .= '<h3>Warnings</h3>';
			$output .= '<ol class="debug-bar-php-list">';
			foreach ( $warnings as $key => $notice) { $output .= $this->render_item( $notice, 'gv-warning-'  . $key ); }
			$output .= '</ol>';
		}

		if ( count( $notices ) ) {
			$output .= '<h3>Notices</h3>';
			$output .= '<ol class="debug-bar-php-list">';
			foreach ( $notices as $key => $notice) { $output .= $this->render_item( $notice, 'gv-notice-' . $key ); }
			$output .= '</ol>';
		}

		$output .= "</div>";

		echo $output;
	}

	/**
	 * Apply esc_html() to an array
	 * @param  string|array $item Unescaped
	 * @return string       Escaped HTML
	 */
	function esc_html_recursive($item) {
		if(is_object($item)) {
			foreach($item as $key => $value) {
				$item->{$key} = $this->esc_html_recursive($value);
			}
		} else if(is_array($item)) {
			foreach($item as $key => $value) {
				$item[$key] = $this->esc_html_recursive($value);
			}
		} else {
			$item = esc_html($item);
		}
		return $item;
	}


	/**
	 * Render each log item
	 * @param  array $notice `message`, `description`, `content`
	 * @param  string $anchor The anchor ID for the item
	 * @return string         HTML output
	 */
	function render_item( $notice, $anchor = '' ) {

		$output = '';

		if(!empty($notice['message'])) {
			$output .= '<a id="'.sanitize_html_class( $anchor ).'"></a>';
			$output .= "<li class='debug-bar-php-notice'>";
		}

		$output .= '<div class="clear"></div>';

		// Title
		$output .= '<div class="gravityview-debug-bar-title">'.esc_attr( $notice['message'] ).'</div>';

		// Debugging Output
		if( empty( $notice['data'] ) ) {
			if( !is_null( $notice['data'] ) ) {
				$output .= '<em>'._x('Empty', 'Debugging output data is empty.', 'gravityview' ).'</em>';
			}
		} else {
			$output .= sprintf( '<pre>%s</pre>', print_r($this->esc_html_recursive( $notice['data'] ), true) );
		}

		if(!empty($notice['message'])) {
			$output .= '</li>';
		}

		return $output;
	}
}