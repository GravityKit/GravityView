<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * Entry filtering settings.
 *
 * A proposed long-term vision for this would be an API
 *  similar to the new-school ORMs out there.
 *
 * new Entry_Filter(
 *	Field::by_id( 3 )->eq( 99 )->and(
 *		Field::by_id( 4 )->neq( null )->or( Field::by_id( 4 )->lte( 0 ) )
 *	)->and(
 *		Field::by_id( 5 )->like( "%search%" )->and( Field::by_id( 6 )->between( $t, $f ) )
 *	)
 * );
 *
 * Very flexible in code, but unserialization could be a pain in the neck.
 *
 * For now we use the Gravity Forms backend for this, since developing an ORM
 *  will take us another year :)
 */
abstract class Entry_Filter {
}

/** Load implementations. */
require gravityview()->plugin->dir( 'future/includes/class-gv-collection-entry-filter-gravityforms.php' );
