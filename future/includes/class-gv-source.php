<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Source class.
 *
 * Contains the source for \GV\Field values.
 *
 * For example, "gravityview" fields, like custom content, are sourced
 *  from the \GV\View and its \GV\Field configuration. While "gravityforms"
 *  fields are sourced from \GV\Entry instances.
 */
abstract class Source {
	
	/**
	 * @var string BACKEND_INTERNAL The backend identifier for special GravityView data sources
	 *  like custom content and the like. Not really a form, but a source nevertheless.
	 */
	const BACKEND_INTERNAL = 'internal';

	/**
	 * @var string BACKEND_GRAVITYFORMS The backend identifier for special GravityView data sources
	 *  like custom content and the like. Not really a form, but a source nevertheless.
	 */
	const BACKEND_GRAVITYFORMS = 'gravityforms';

	/**
	 * @var string The identifier of the backend used for this source.
	 *
	 * @see Constant backend identifiers above and \GV\Source subclasses.
	 *
	 * @api
	 * @since future
	 */
	public static $backend = null;
}
