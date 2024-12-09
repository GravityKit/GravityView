<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

require_once 'trait-gv-field-renderer.php';

/**
 * The single Entry template.
 *
 * @since $ver$
 */
final class Entry_Layout_Builder_Template extends Entry_Template {
	use Field_Renderer_Trait;
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	public static $slug = \GravityView_Layout_Builder::ID;
}
