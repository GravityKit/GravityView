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
	 * @since 2.0
	 */
	public $position = '';

	/**
	 * @var string UID for this field.
	 *
	 * A unique relation identifier between this field and a view.
	 *
	 * @api
	 * @since 2.0
	 */
	public $UID = '';

	/**
	 * @var string The form field ID for this field.
	 * @api
	 * @since 2.0
	 */
	public $ID = '';

	/**
	 * @var string The form label for this field.
	 * @api
	 * @since 2.0
	 */
	public $label = '';

	/**
	 * @var string The custom label for this field.
	 * @api
	 * @since 2.0
	 */
	public $custom_label = '';

	/**
	 * @var bool Whether to show the label or not for this field.
	 * @api
	 * @since 2.0
	 */
	public $show_label = true;

	/**
	 * @var string The custom class for this field.
	 * @api
	 * @since 2.0
	 */
	public $custom_class = '';

	/**
	 * @var string The capability required to view this field.
	 *
	 * If empty, anyone can view it, including non-logged in users.
	 *
	 * @api
	 * @since 2.0
	 */
	public $cap = '';

	/**
	 * @var bool Show as a link to entry.
	 *
	 * @api
	 * @since 2.0
	 */
	public $show_as_link = false;

	/**
	 * @var bool Filter this field from searching.
	 *
	 * @api
	 * @since 2.0
	 */
	public $search_filter = false;

	/**
	 * Return an array of the old format as used by callers of `GVCommon:get_directory_fields()` for example.
	 *
	 *          'id' => string '9' (length=1)
	 *          'label' => string 'Screenshots' (length=11)
	 *          'show_label' => string '1' (length=1)
	 *          'custom_label' => string '' (length=0)
	 *          'custom_class' => string 'gv-gallery' (length=10)
	 *          'only_loggedin' => string '0' (length=1)
	 *          'only_loggedin_cap' => string 'read' (length=4)
	 *          'search_filter' => string '0'
	 *          'show_as_link' => string '0'
	 *
	 *          + whatever else specific field types may have
	 *
	 * @internal
	 * @since 2.0
	 *
	 * @return array
	 */
	public function as_configuration() {
		return array_merge(
			array(
				'id'                => $this->ID,
				'label'             => $this->label,
				'show_label'        => $this->show_label ? '1' : '0',
				'custom_label'      => $this->custom_label,
				'custom_class'      => $this->custom_class,
				'only_loggedin'     => $this->cap ? '1' : '0',
				'only_loggedin_cap' => $this->cap,
				'search_filter'     => $this->search_filter ? '1' : '0',
				'show_as_link'      => $this->show_as_link ? '1' : '0',
			),
			$this->configuration
		);
	}

	/**
	 * An alias for \GV\Source::get_field()
	 *
	 * @see \GV\Source::get_field()
	 * @param string $source A \GV\Source class as string this field is tied to.
	 * @param array  $args The arguments required for the backend to fetch the field (usually just the ID).
	 *
	 * @return \GV\Field|null A \GV\Field instance or null if not found.
	 */
	final public static function get( $source, $args ) {
		if ( ! is_string( $source ) || ! class_exists( $source ) ) {
			gravityview()->log->error( '{source} class not found', array( 'source' => $source ) );
			return null;
		}

		if ( ! method_exists( $source, 'get_field' ) ) {
			gravityview()->log->error( '{source} does not appear to be a valid \GV\Source subclass (get_field method missing)', array( 'source' => $source ) );
			return null;
		}

		return call_user_func_array( array( $source, 'get_field' ), is_array( $args ) ? $args : array( $args ) );
	}

	/**
	 * Create self from a configuration array.
	 *
	 * @param array $configuration The configuration array.
	 * @see \GV\Field::as_configuration()
	 * @internal
	 * @since 2.0
	 *
	 * @return \GV\Field The field implementation from configuration (\GV\GF_Field, \GV\Internal_Field).
	 */
	public static function from_configuration( $configuration ) {
		if ( empty( $configuration['id'] ) ) {
			$field = new self();
			gravityview()->log->error( 'Trying to get field from configuration without a field ID.', array( 'data' => $configuration ) );
			$field->update_configuration( $configuration );
			return $field;
		}

		/** Prevent infinte loops here from unimplemented children. */
		if ( version_compare( phpversion(), '5.4', '>=' ) ) {
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
		} else {
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		}
		$trace = $trace[1];
		if ( 'from_configuration' == $trace['function'] && __CLASS__ == $trace['class'] ) {
			$field = new self();
			gravityview()->log->error( 'Infinite loop protection tripped. Returning default class here.' );
			$field->update_configuration( $configuration );
			return $field;
		}

		/** @type \GV\GF_Field|\GV\Internal_Field $field_class Determine the field implementation to use, and try to use. */
		$field_class = is_numeric( $configuration['id'] ) ? '\GV\GF_Field' : '\GV\Internal_Field';

		/**
		 * Filter the field class about to be created from the configuration.
		 *
		 * @param string $field_class The field class about to be used.
		 * @param array $configuration The configuration as per \GV\Field::as_configuration()
		 */
		$field_class = apply_filters( 'gravityview/field/class', $field_class, $configuration );

		if ( ! class_exists( $field_class ) || ! method_exists( $field_class, 'from_configuration' ) ) {
			$field = new self();
			gravityview()->log->error(
				'Class {field_class}::from_configuration does not exist.',
				array(
					'field_class' => $field_class,
					'data'        => $configuration,
				)
			);
			$field->update_configuration( $configuration );
			return $field;
		}

		/** @type \GV\GF_Field|\GV\Internal_Field $field */
		$field = $field_class::from_configuration( $configuration );

		if ( ! $field ) {
			$field = new self();
			gravityview()->log->error(
				'Could not configure {field_class} with given configuration.',
				array(
					'field_class' => __CLASS__,
					'data'        => $configuration,
				)
			);
			$field->update_configuration( $configuration );
		}

		return $field;
	}

	/**
	 * Update configuration.
	 *
	 * @param array $configuration The configuration array.
	 * @see \GV\Field::as_configuration()
	 * @since 2.0
	 *
	 * @return void
	 */
	public function update_configuration( $configuration ) {
		$configuration = wp_parse_args( $configuration, $this->as_configuration() );

		if ( $this->ID != $configuration['id'] ) {
			/** Smelling trouble here... */
			gravityview()->log->warning( 'ID is being changed for {field_class} instance, but implementation is not. Use ::from_configuration instead', array( 'field_class', __CLASS__ ) );
		}

		$this->ID            = $configuration['id'];
		$this->label         = $configuration['label'];
		$this->show_label    = '1' == $configuration['show_label'];
		$this->custom_label  = $configuration['custom_label'];
		$this->custom_class  = $configuration['custom_class'];
		$this->cap           = '1' == $configuration['only_loggedin'] ? $configuration['only_loggedin_cap'] : '';
		$this->search_filter = '1' == $configuration['search_filter'];
		$this->show_as_link  = '1' == $configuration['show_as_link'];

		/** Shared among all field types (sort of). */
		$shared_configuration_keys = array(
			'id',
			'label',
			'show_label',
			'custom_label',
			'custom_class',
			'only_loggedin',
			'only_loggedin_cap',
			'search_filter',
			'show_as_link',
		);

		/** Everything else goes into the properties for now. @todo subclasses! */
		foreach ( $configuration as $key => $value ) {
			if ( ! in_array( $key, $shared_configuration_keys ) ) {
				$this->configuration[ $key ] = $value;

				if ( 'lightbox' === $key && 1 === (int) $value ) {
					$this->show_as_link = true;
				}
			}
		}
	}

	/**
	 * Retrieve the label for this field.
	 *
	 * @param \GV\View    $view The view for this context if applicable.
	 * @param \GV\Source  $source The source (form) for this context if applicable.
	 * @param \GV\Entry   $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return string The label for this field. Nothing here.
	 */
	public function get_label( View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {

		if ( ! $this->show_label ) {
			return '';
		}

		/** A custom label is available. */
		if ( ! empty( $this->custom_label ) ) {
			return \GravityView_API::replace_variables(
				$this->custom_label,
				$source ? $source->form ?? null : null,
				$entry ? $entry->as_entry() : null
			);
		}

		return '';
	}

	/**
	 * Retrieve the value for this field.
	 *
	 * Returns null in this implementation (or, rather, lack thereof).
	 *
	 * @param \GV\View    $view The view for this context if applicable.
	 * @param \GV\Source  $source The source (form) for this context if applicable.
	 * @param \GV\Entry   $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * @return mixed The value for this field.
	 */
	public function get_value( View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		return $this->get_value_filters( null, $view, $source, $entry, $request );
	}

	/**
	 * Apply all the required filters after get_value() was called.
	 *
	 * @param mixed       $value The value that will be filtered.
	 * @param \GV\View    $view The view for this context if applicable.
	 * @param \GV\Source  $source The source (form) for this context if applicable.
	 * @param \GV\Entry   $entry The entry for this context if applicable.
	 * @param \GV\Request $request The request for this context if applicable.
	 *
	 * This is in its own function since \GV\Field subclasses have to call it.
	 */
	protected function get_value_filters( $value, View $view = null, Source $source = null, Entry $entry = null, Request $request = null ) {
		if ( $this->type ) {
			/**
			 * Override the displayed value here.
			 *
			 * @param string $value The value.
			 * @param \GV\Field The field we're doing this for.
			 * @param \GV\View $view The view for this context if applicable.
			 * @param \GV\Source $source The source (form) for this context if applicable.
			 * @param \GV\Entry $entry The entry for this context if applicable.
			 * @param \GV\Request $request The request for this context if applicable.
			 */
			$value = apply_filters( "gravityview/field/{$this->type}/value", $value, $this, $view, $source, $entry, $request );
		}

		/**
		 * Override the displayed value here.
		 *
		 * @param string $value The value.
		 * @param \GV\Field The field we're doing this for.
		 * @param \GV\View $view The view for this context if applicable.
		 * @param \GV\Source $source The source (form) for this context if applicable.
		 * @param \GV\Entry $entry The entry for this context if applicable.
		 * @param \GV\Request $request The request for this context if applicable.
		 */
		return apply_filters( 'gravityview/field/value', $value, $this, $view, $source, $entry, $request );
	}

	/**
	 * Whether or not this field is visible.
	 *
	 * @param \GV\View|null Is visible where exactly?
	 * @since develop
	 *
	 * @return bool
	 */
	public function is_visible( $view = null ) {
		/**
		 * Should this field be visible?
		 *
		 * @param boolean $visible Visible or not, defaults to the set field capability requirement if defined.
		 * @param \GV\Field $field The field we're looking at.
		 * @param \GV\View|null A context view. Since @develop
		 */
		return apply_filters( 'gravityview/field/is_visible', ( ! $this->cap || \GVCommon::has_cap( $this->cap ) ), $this, $view );
	}

	/**
	 * Get one of the extra configuration keys via property accessors.
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed|null The value for the given configuration key, null if doesn't exist.
	 */
	public function __get( $key ) {
		switch ( $key ) {
			default:
				if ( isset( $this->configuration[ $key ] ) ) {
					return $this->configuration[ $key ];
				}
		}

		return null;
	}

	/**
	 * Is this set?
	 *
	 * @param string $key The key to get.
	 *
	 * @return boolean Whether this $key is set or not.
	 */
	public function __isset( $key ) {
		switch ( $key ) {
			default:
				return isset( $this->configuration[ $key ] );
		}
	}
}
