<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Entry sorting.
 */
class Entry_Sort {

	/**
	 * @var string An enum of sorts, sort direction identifier - ascending.
	 */
	const ASC = 'ASC';

	/**
	 * @var string An enum of sorts, sort direction identifier - descending.
	 */
	const DESC = 'DESC';

	/**
	 * @var \GV\Field The field that this sort is for.
	 */
	public $field;

	/**
	 * @var string The direction (see self::ASC, self::DESC).
	 */
	public $direction;

	/**
	 * Instantiate a sort for a field.
	 *
	 * @param \GV\Field $field The field we're sorting by.
	 * @param string $direction The direction of this sort (\GV\Entry_Sort::ASC, \GV\Entry_Sort::DESC).
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Sort An instance of this class, pass to \GV\Entry_Collection::sort()
	 */
	public function __construct( \GV\Field $field, $direction = self::ASC ) {
		$this->field = $field;
		$this->direction = $direction;
	}
}
