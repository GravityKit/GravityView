<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * A collection of \GV\Widget objects.
 * @implements Collection<\GV\Widget>
 */
class Widget_Collection extends Collection {
	/**
	 * Add a \GV\Widet to this collection.
	 *
	 * @param \GV\Widget $widget The widget to add to the internal array.
	 *
	 * @api
	 * @since 2.0
	 * @return void
	 */
	public function add( $widget ) {
		if ( ! $widget instanceof Widget ) {
			gravityview()->log->error( 'Widget_Collections can only contain objects of type \GV\Widget.' );
			return;
		}
		parent::add( $widget );
	}

	/**
	 * Get a \GV\Widget from this list by UID.
	 *
	 * @param int $widget_uid The UID of the widget in the collection to get.
	 *
	 * @api
	 * @since 2.0
	 *
	 * @return \GV\Widget|null The \GV\Widget with the $widget_uid as the UID, or null if not found.
	 */
	public function get( $widget_uid ) {
		foreach ( $this->all() as $widget ) {
			if ( $widget->UID == $widget_uid ) {
				return $widget;
			}
		}
		return null;
	}

	/**
	 * Get a copy of this \GV\Widget_Collection filtered by position.
	 *
	 * @param string $position The position to get the widgets for.
	 *  Can be a wildcard *
	 *
	 * @api
	 * @since
	 *
	 * @return \GV\Widget_Collection A filtered collection of \GV\Widget, filtered by position.
	 */
	public function by_position( $position ) {
		$widgets = new self();

		$search = implode( '.*', array_map( 'preg_quote', explode( '*', $position ) ) );

		foreach ( $this->all() as $widget ) {
			if ( preg_match( "#^{$search}$#", $widget->position ) ) {
				$widgets->add( $widget );
			}
		}
		return $widgets;
	}

	/**
	 * Get a copy of this \GV\Widget_Collection filtered by ID.
	 *
	 * @param string $id The IDs to get the widgets for.
	 *
	 * @api
	 * @since
	 *
	 * @return \GV\Widget_Collection A filtered collection of \GV\Widget, filtered by ID.
	 */
	public function by_id( $id ) {
		$widgets = new self();

		foreach ( $this->all() as $widget ) {
			if ( $id == $widget->get_widget_id() ) {
				$widgets->add( $widget );
			}
		}
		return $widgets;
	}

	/**
	 * Parse a configuration array into a Widget_Collection.
	 *
	 * @param array $configuration The configuration, structured like so:
	 *
	 * array(
	 *
	 *  [other zones]
	 *
	 *  'footer_right' => array(
	 *
	 *      [other widgets]
	 *
	 *      '5372653f25d44' => array(
	 *          @see \GV\Widget::as_configuration() for structure
	 *      )
	 *
	 *      [other widgets]
	 *  )
	 *
	 *  [other zones]
	 * )
	 *
	 * @return \GV\Widget_Collection A collection of widgets.
	 */
	public static function from_configuration( $configuration ) {
		$widgets = new self();
		foreach ( $configuration as $position => $_widgets ) {

			if ( empty( $_widgets ) || ! is_array( $_widgets ) ) {
				continue;
			}

			foreach ( $_widgets as $uid => $_configuration ) {
				if ( ! $widget = Widget::from_configuration( $_configuration ) ) {
					continue;
				}

				$widget->UID      = $uid;
				$widget->position = $position;

				$widgets->add( $widget );
			}
		}
		return $widgets;
	}

	/**
	 * Return a configuration array for this widget collection.
	 *
	 * @return array See \GV\Widget_Collection::from_configuration() for structure.
	 */
	public function as_configuration() {
		$configuration = array();

		/**
		 * @var \GV\Widget $widget
		 */
		foreach ( $this->all() as $widget ) {
			if ( empty( $configuration[ $widget->position ] ) ) {
				$configuration[ $widget->position ] = array();
			}

			$configuration[ $widget->position ][ $widget->UID ] = $widget->as_configuration();
		}
		return $configuration;
	}
}
