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
	 * @var string An enum of sorts, sort direction identifier - random.
	 */
	const RAND = 'RAND';

	/**
	 * @var string Numeric sort mode.
	 */
	const NUMERIC = 'NUMERIC';

	/**
	 * @var string Alpabetic sort mode.
	 */
	const ALPHA = 'ALPHA';

	/**
	 * @var \GV\Field The field that this sort is for.
	 */
	public $field;

	/**
	 * @var string The direction (see self::ASC, self::DESC).
	 */
	public $direction;

	/**
	 * @var string The sort mode (see self::NUMERIC, self::ALPHA).
	 */
	public $mode;

	/**
	 * Instantiate a sort for a field.
	 *
	 * @param \GV\Field $field The field we're sorting by.
	 * @param string $direction The direction of this sort (\GV\Entry_Sort::ASC, \GV\Entry_Sort::DESC, etc.). Default: self::ASC.
	 * @param string $mode The sort mode (self::NUMERIC). Default: self::ALPHA.
	 *
	 * @api
	 * @since future
	 *
	 * @return \GV\Entry_Sort An instance of this class, pass to \GV\Entry_Collection::sort()
	 */
	public function __construct( \GV\Field $field, $direction = self::ASC, $mode = self::ALPHA ) {
		$this->field = $field;
		$this->direction = $direction;
		$this->mode = $mode;
	}
}
