<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

require_once 'trait-gv-field-renderer.php';

/**
 * The View template.
 *
 * @since $ver$
 */
final class View_Layout_Builder_Template extends View_Template {
	use Field_Renderer_Trait;

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public static $slug = \GravityView_Layout_Builder::ID;

	/**
	 * Modifies the entry class for this template.
	 *
	 * @since  2.46.2
	 *
	 * @filter `gravityview_entry_class`.
	 * @filter `gravityview/template/layout-builder/entry/class`.
	 *
	 * @param string    $class The class.
	 * @param \GV\Entry $entry The entry.
	 * @param \GV\Template_Context The context.
	 *
	 * @return string The classes.
	 */
    public static function entry_class( string $class, Entry $entry, Template_Context $context ): string {
        /**
         * Modify the class applied to the entry row.
         *
         * @param string $class Existing class.
         * @param array $entry Current entry being displayed
         * @param \GravityView_View $this Current GravityView_View object
         * @deprecated Use `gravityview/template/table/entry/class`
         * @return string The modified class.
         */
        $class = apply_filters( 'gravityview_entry_class', $class, $entry->as_entry(), \GravityView_View::getInstance() );

        /**
         * Modify the class applied to the entry row.
         *
         * @param string $class The existing class.
         * @param \GV\Template_Context $context The context.
         * @return string The modified class.
         */
        return apply_filters( 'gravityview/template/layout-builder/entry/class', $class, Template_Context::from_template( $context->template, compact( 'entry' ) ) );
    }
}
