<?php
namespace GV\Mocks;

/**
 * Time merge calls.
 *
 * Might be in GF_Query soon.
 */
class GF_Query_Call_TIMESORT extends \GF_Query_Call {
	public function timesort_sql( $query ) {
		global $wpdb;

		list( $column, $sql ) = $this->parameters;
		$meta_table           = \GFFormsModel::get_entry_meta_table_name();

		$alias = $query->_alias( $column->field_id, $column->source, 'm' );

		/*
		SELECT v,
		IF(
		POSITION('pm' IN v) > 0,

		(
		SUBSTRING_INDEX(v, ':', 1)
		+ IF(SUBSTRING_INDEX(v, ':', 1) < 12, 12, 0)
		) * 60,

		SUBSTRING_INDEX(v, ':', 1) * 60
		) +
		RIGHT(IF(
		POSITION('m' IN v) > 0,
		SUBSTRING_INDEX(v, ' ', 1),
		v
		),2) t1

		FROM meta;
		*/

		// Detect if 'pm' is in the time field
		$pm_exists = "POSITION('pm' IN $alias.`meta_value`)";

		// Transform a pm time into minutes ((hour + (12 if hour > 12 else 0)) * 60)
		$minutes_12 = "(SUBSTRING_INDEX($alias.`meta_value`, ':', 1) + IF(SUBSTRING_INDEX($alias.`meta_value`, ':', 1) < 12, 12, 0)) * 60";

		// Transform a 24-hour time into minutes (hour * 60), maybe compensate 12 am = 0
		$minutes_24 = "(SUBSTRING_INDEX($alias.`meta_value`, ':', 1) - IF(POSITION('am' IN $alias.`meta_value`) AND SUBSTRING_INDEX($alias.`meta_value`, ':', 1) = '12', 12, 0)) * 60";

		// Minutes
		$minutes = "RIGHT(IF(POSITION('m' IN $alias.`meta_value`), SUBSTRING_INDEX($alias.`meta_value`, ' ', 1), $alias.`meta_value`), 2)";

		// Combine the insanity :)
		$condition = "IF($pm_exists, $minutes_12, $minutes_24) + $minutes";

		return "(SELECT $condition)";
	}
}
