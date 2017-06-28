<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gravityview] shortcode.
 */
class gravityview extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gravityview';

	/**
	 * Process and output the [gravityview] shortcode.
	 *
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 *
	 * @return string The output.
	 */
	public function callback( $atts, $content = null ) {

		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return;
		}

		$atts = wp_parse_args( $atts, array(
			'id' => 0,
			'view_id' => 0,
			'detail' => null,
			'page_size' => 20,
		) );

		$view = \GV\View::by_id( $atts['id'] ? : $atts['view_id'] );

		if ( ! $view ) {
			return;
		}

		$view->settings->update( $atts );
		$entries = $view->get_entries( $request );

		if ( $atts['detail'] ) {
			return $this->detail( $view, $entries, $atts );
		}

		$renderer = new \GV\View_Renderer();
		return $renderer->render( $view, $request );
	}

	/**
	 * Output view details.
	 *
	 * @param \GV\View $view The View.
	 * @param \GV\Entry_Collection $entries The calculated entries.
	 * @param array $atts The shortcode attributes (with defaults).
	 * @param array $view_atts A quirky compatibility parameter where we get the unaltered view atts.
	 *
	 * @return string The output.
	 */
	private function detail( $view, $entries, $atts ) {
		$output = '';

		switch ( $key = $atts['detail'] ):
			case 'total_entries':
				$output = number_format_i18n( $entries->total() );
				break;
			case 'first_entry':
				$output = number_format_i18n( min( $entries->total(), $view->settings->get( 'offset' ) + 1 ) );
				break;
			case 'last_entry':
				$output = number_format_i18n( $view->settings->get( 'page_size' ) );
				break;
			case 'page_size':
				$output = number_format_i18n( $view->settings->get( $key ) );
				break;
		endswitch;

		return $output;
	}
}
