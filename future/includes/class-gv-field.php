<?php
namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * The default GravityView Field class.
 *
 * Houses all base Field functionality.
 */
class Field {

	/**
	 * @var array The custom View configuration for this field.
	 *
	 * Everything else is in the properties.
	 */
	private $configuration = array();

	/**
	 * @var string The field position in the view.
	 * @api
	 * @since future
	 */
	public $position = '';

	/**
	 * @var string UID for this field.
	 *
	 * A unique relation identifier between this field and a view.
	 *
	 * @api
	 * @since future
	 */
	public $UID = '';

	/**
	 * @var string The form field ID for this field.
	 * @api
	 * @since future
	 */
	public $ID = '';

	/**
	 * @var string The form label for this field.
	 * @api
	 * @since future
	 */
	public $label = '';

	/**
	 * @var string The custom label for this field.
	 * @api
	 * @since future
	 */
	public $custom_label = '';

	/**
	 * @var bool Whether to show the label or not for this field.
	 * @api
	 * @since future
	 */
	public $show_label = true;

	/**
	 * @var string The custom class for this field.
	 * @api
	 * @since future
	 */
	public $custom_class = '';

	/**
	 * @var string The capability required to view this field.
	 *
	 * If empty, anyone can view it, including non-logged in users.
	 *
	 * @api
	 * @since future
	 */
	public $cap = '';

	/**
	 * @var bool Show as a link to entry.
	 *
	 * @api
	 * @since future
	 */
	public $show_as_link = false;

	/**
	 * @var bool Filter this field from searching.
	 *
	 * @api
	 * @since future
	 */
	public $search_filter = false;

	/**
	 * Return an array of the old format as used by callers of `GVCommon:get_directory_fields()` for example.
	 *
	 *  		'id' => string '9' (length=1)
	 *  		'label' => string 'Screenshots' (length=11)
	 *			'show_label' => string '1' (length=1)
	 *			'custom_label' => string '' (length=0)
	 *			'custom_class' => string 'gv-gallery' (length=10)
	 * 			'only_loggedin' => string '0' (length=1)
	 *			'only_loggedin_cap' => string 'read' (length=4)
	 *			'search_filter' => string '0'
	 *			'show_as_link' => string '0'
	 *
	 *			+ whatever else specific field types may have
	 *
	 * @internal
	 * @since future
	 *
	 * @return array
	 */
	public function as_configuration() {
		return array_merge( array(
			'id' => $this->ID,
			'label' => $this->label,
			'show_label' => $this->show_label ? '1' : '0',
			'custom_label' => $this->custom_label,
			'custom_class' => $this->custom_class,
			'only_loggedin' => $this->cap ? '1' : '0',
			'only_loggedin_cap' => $this->cap,
			'search_filter' => $this->search_filter ? '1' : '0',
			'show_as_link' => $this->show_as_link ? '1' : '0',
		), $this->configuration );
	}

	/**
	 * Update self from a configuration array.
	 *
	 * @see \GV\Field::as_configuration()
	 * @internal
	 * @since future
	 *
	 * @return void
	 */
	public function from_configuration( $configuration ) {
		$configuration = wp_parse_args( $configuration, $this->as_configuration() );

		$this->ID = $configuration['id'];
		$this->label = $configuration['label'];
		$this->show_label = $configuration['show_label'] == '1';
		$this->custom_label = $configuration['custom_label'];
		$this->custom_class = $configuration['custom_class'];
		$this->cap = $configuration['only_loggedin'] == '1' ? $configuration['only_loggedin_cap'] : '';
		$this->search_filter = $configuration['search_filter'] == '1';
		$this->show_as_link = $configuration['show_as_link'] == '1';

		/** Shared among all field types (sort of). */
		$shared_configuration_keys = array(
			'id', 'label', 'show_label', 'custom_label', 'custom_class',
			'only_loggedin' ,'only_loggedin_cap', 'search_filter', 'show_as_link',
		);

		/** Everything else goes into the properties for now. @todo subclasses! */
		foreach ( $configuration as $key => $value ) {
			if ( ! in_array( $key, $shared_configuration_keys ) ) {
				$this->configuration[ $key ] = $value;
			}
		}
	}

	/**
	 * Get one of the extra configuration keys via property accessors.
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed|null The value for the given configuration key, null if doesn't exist.
	 */
	public function __get( $key ) {
		if ( isset( $this->configuration[ $key ] ) ) {
			return $this->configuration[ $key ];
		}
	}
}
