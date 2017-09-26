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
	 * Construct a JOIN container.
	 *
	 * @param \GV\Source $join The form we're joining to.
	 * @param \GV\Field $join_column Its column.
	 * @param \GV\Source $join_on The form we're joining on.
	 * @param \GV\Field $join_on_column Its column.
	 *
	 * @return \GV\Joins $this
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
	 * @param \GV\Query $query The \GV\Query instance.
	 *
	 * @return \GV\Query The $query
	 */
	public function as_query_join( $query ) {
		if ( ! class_exists( '\GV\Query' ) || ! $query instanceof Query ) {
				return null;
		}
		return $query->join(
			new Query\Column( $this->join_on_column, $this->join_on ),
			new Query\Column( $this->join_column, $this->join )
		);
	}
}
