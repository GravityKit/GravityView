<?php
namespace GV\Shortcodes;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The [gventry] shortcode.
 */
class gventry extends \GV\Shortcode {
	/**
	 * {@inheritDoc}
	 */
	public $name = 'gventry';

	/**
	 * Process and output the [gventry] shortcode.
	 *
	 * @param array $atts The attributes passed.
	 * @param string $content The content inside the shortcode.
	 *
	 * @return string|null The output.
	 */
	public function callback( $atts, $content = null ) {

		$request = gravityview()->request;

		if ( $request->is_admin() ) {
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$atts = wp_parse_args( $atts, array(
			'id'        => 0,
			'entry_id'  => 0,
			'view_id'   => 0,
		) );

		/**
		 * @filter `gravityview/shortcodes/gventry/atts` Filter the [gventry] shortcode attributes.
		 * @param array $atts The initial attributes.
		 * @since 2.0
		 */
		$atts = apply_filters( 'gravityview/shortcodes/gventry/atts', $atts );

		$entry_id = ! empty( $atts['entry_id'] ) ? $atts['entry_id'] : $atts['id'];

		$view = \GV\View::by_id( $atts['view_id'] );

		if ( ! $view ) {
			gravityview()->log->error( 'View does not exist #{view_id}', array( 'view_id' => $atts['view_id'] ) );
			return apply_filters( 'gravityview/shortcodes/gventry/output', '', null, null, $atts );
		}

		$entry = $this->get_entry_or_message( $entry_id, $view, $atts );

		// Entry not found; return string message
		if ( ! $entry instanceof \GV\Entry ) {
			return $entry;
		}

		$restrict_access = $this->restrict_access( $view, $entry, $atts );

		// Something's amiss; you shall not pass
		if( null !== $restrict_access ) {
			return $restrict_access;
		}

		/** Remove the back link. */
		add_filter( 'gravityview/template/links/back/url', '__return_false' );

		$renderer = new \GV\Entry_Renderer();

		$request = new \GV\Mock_Request();
		$request->returns['is_entry'] = $entry;

		$output = $renderer->render( $entry, $view, $request );

		remove_filter( 'gravityview/template/links/back/url', '__return_false' );

		/**
		 * @filter `gravityview/shortcodes/gventry/output` Filter the [gventry] output.
		 * @param string $output The output.
		 * @param \GV\View|null $view The View detected or null.
		 * @param \GV\Entry|null $entry The Entry or null.
		 *
		 * @since 2.0
		 */
		return apply_filters( 'gravityview/shortcodes/gventry/output', $output, $view, $entry, $atts );
	}
}
