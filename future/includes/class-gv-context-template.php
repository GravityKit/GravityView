<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A template Context class.
 *
 * This is provided to most template files as a global.
 */
class Template_Context extends Context {
	/**
	 * @var \GV\Template The template.
	 */
	public $template;

	/**
	 * @var \GV\View The view.
	 */
	public $view;

	/**
	 * @var \GV\Entry The entry. If single-entry view.
	 */
	public $entry;

	/**
	 * @var \GV\Entry_Collection The entries. If directory view.
	 */
	public $entries;

	/**
	 * @var \GV\Field_Collection The fields.
	 */
	public $fields;

	/**
	 * @var \GV\Field The field. When rendering a single field.
	 */
	public $field;

	/**
	 * @var \GV\Source The data source for a field.
	 */
	public $source;

	/**
	 * @var mixed The display value for a field.
	 */
	public $display_value;

	/**
	 * @var mixed The raw value for a field.
	 */
	public $value;

	/**
	 * @var \GV\Request The request.
	 */
	public $request;

	/**
	 * Create a context from a Template
	 *
	 * @param \GV\Template|array $template The template or array with values expected in a template
	 * @param array              $data Additional data not tied to the template object.
	 *
	 * @return \GV\Template_Context The context holder.
	 */
	public static function from_template( $template, $data = array() ) {
		$context = new self();

		$context->template = $template;

		/**
		 * Data.
		 */
		$context->display_value = Utils::get( $data, 'display_value' );
		$context->value         = Utils::get( $data, 'value' );

		/**
		 * Shortcuts.
		 */
		$context->view    = Utils::get( $template, 'view' );
		$context->source  = Utils::get( $template, 'source' );
		$context->field   = Utils::get( $template, 'field' ) ? : Utils::get( $data, 'field' );
		$context->entry   = Utils::get( $template, 'entry' ) ? : Utils::get( $data, 'entry' );
		$context->request = Utils::get( $template, 'request' );

		$context->entries = Utils::get( $template, 'entries' ) ? $template->entries : null;
		$context->fields  = $context->view ? $context->view->fields : null;

		return $context;
	}
}
