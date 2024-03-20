<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The \GV\Join class.
 *
 * Contains a join between two Sources on two Fields.
 */
class Join {

	/**
	 * @var GF_Form|Source|Form
	 * @since 2.2 Made private property public
	 */
	public $join;

	/**
	 * @var GF_Form|Source|Form
	 * @since 2.2 Made private property public
	 */
	public $join_on;

	/**
	 * @var Field
	 */
	public $join_column;

	/**
	 * @var Field
	 */
	public $join_on_column;

	/**
	 * Construct a JOIN container.
	 *
	 * @param \GV\Source $join The form we're joining to.
	 * @param \GV\Field  $join_column Its column.
	 * @param \GV\Source $join_on The form we're joining on.
	 * @param \GV\Field  $join_on_column Its column.
	 */
	public function __construct( $join, $join_column, $join_on, $join_on_column ) {
		if ( $join instanceof \GV\Source ) {
			$this->join = $join;
		}

		if ( $join_on instanceof \GV\Source ) {
			$this->join_on = $join_on;
		}

		if ( $join_column instanceof \GV\Field ) {
			$this->join_column = $join_column;
		}

		if ( $join_on_column instanceof \GV\Field ) {
			$this->join_on_column = $join_on_column;
		}
	}

	/**
	 * Inject this join into the query.
	 *
	 * @param \GF_Query $query The \GF_Query instance.
	 *
	 * @return \GF_Query The $query
	 */
	public function as_query_join( $query ) {

		if ( ! gravityview()->plugin->supports( Plugin::FEATURE_JOINS ) ) {
			return null;
		}

		if ( ! $query instanceof \GF_Query ) {
			gravityview()->log->error( 'Query not instance of \GF_Query.' );
			return null;
		}

		$join_id    = intval( $this->join->ID );
		$join_on_id = intval( $this->join_on->ID );

		if ( empty( $join_id ) || empty( $join_on_id ) ) {
			gravityview()->log->error( 'Query join form not an integer.', array( 'data' => $this ) );
			return null;
		}

		return $query->join(
			new \GF_Query_Column( $this->join_on_column->ID, $join_on_id ),
			new \GF_Query_Column( $this->join_column->ID, $join_id )
		);
	}
}
