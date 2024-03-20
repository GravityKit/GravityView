<?php
/**
 * Add Code Snippet customizations
 *
 * @file      class-gravityview-plugin-hooks-code-snippets.php
 * @package   GravityView
 * @license   GPL2+
 * @author    GravityKit <hello@gravitykit.com>
 * @link      http://www.gravitykit.com
 * @copyright Copyright 2021, Katz Web Services, Inc.
 *
 * @since 2.13.2
 */

/**
 * @inheritDoc
 * @since 2.13.2
 */
class GravityView_Plugin_Hooks_Code_Snippets extends GravityView_Plugin_and_Theme_Hooks {

	/**
	 * @since 2.13.2
	 */
	protected $constant_name = 'CODE_SNIPPETS_FILE';

	/**
	 * @since 2.13.2
	 * @var array
	 */
	protected $style_handles = array( 'menu-icon-snippets' );
}

new GravityView_Plugin_Hooks_Code_Snippets();
