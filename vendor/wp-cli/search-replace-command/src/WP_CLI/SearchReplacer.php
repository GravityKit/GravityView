<?php

namespace WP_CLI;

use ArrayObject;
use Exception;

class SearchReplacer {

	private $from;
	private $to;
	private $recurse_objects;
	private $regex;
	private $regex_flags;
	private $regex_delimiter;
	private $regex_limit;
	private $logging;
	private $log_data;
	private $max_recursion;

	/**
	 * @param string  $from            String we're looking to replace.
	 * @param string  $to              What we want it to be replaced with.
	 * @param bool    $recurse_objects Should objects be recursively replaced?
	 * @param bool    $regex           Whether `$from` is a regular expression.
	 * @param string  $regex_flags     Flags for regular expression.
	 * @param string  $regex_delimiter Delimiter for regular expression.
	 * @param bool    $logging         Whether logging.
	 * @param integer $regex_limit     The maximum possible replacements for each pattern in each subject string.
	 */
	public function __construct( $from, $to, $recurse_objects = false, $regex = false, $regex_flags = '', $regex_delimiter = '/', $logging = false, $regex_limit = -1 ) {
		$this->from            = $from;
		$this->to              = $to;
		$this->recurse_objects = $recurse_objects;
		$this->regex           = $regex;
		$this->regex_flags     = $regex_flags;
		$this->regex_delimiter = $regex_delimiter;
		$this->regex_limit     = $regex_limit;
		$this->logging         = $logging;
		$this->clear_log_data();

		// Get the XDebug nesting level. Will be zero (no limit) if no value is set
		$this->max_recursion = intval( ini_get( 'xdebug.max_nesting_level' ) );
	}

	/**
	 * Take a serialised array and unserialise it replacing elements as needed and
	 * unserialising any subordinate arrays and performing the replace on those too.
	 * Ignores any serialized objects unless $recurse_objects is set to true.
	 *
	 * @param array|string $data            The data to operate on.
	 * @param bool         $serialised      Does the value of $data need to be unserialized?
	 *
	 * @return array       The original array with all elements replaced as needed.
	 */
	public function run( $data, $serialised = false ) {
		return $this->run_recursively( $data, $serialised );
	}

	/**
	 * @param int          $recursion_level Current recursion depth within the original data.
	 * @param array        $visited_data    Data that has been seen in previous recursion iterations.
	 */
	private function run_recursively( $data, $serialised, $recursion_level = 0, $visited_data = array() ) {

		// some unseriliased data cannot be re-serialised eg. SimpleXMLElements
		try {

			if ( $this->recurse_objects ) {

				// If we've reached the maximum recursion level, short circuit
				if ( 0 !== $this->max_recursion && $recursion_level >= $this->max_recursion ) {
					return $data;
				}

				if ( is_array( $data ) || is_object( $data ) ) {
					// If we've seen this exact object or array before, short circuit
					if ( in_array( $data, $visited_data, true ) ) {
						return $data; // Avoid infinite loops when there's a cycle
					}
					// Add this data to the list of
					$visited_data[] = $data;
				}
			}

			$unserialized = is_string( $data ) ? @unserialize( $data ) : false;
			if ( false !== $unserialized ) {
				$data = $this->run_recursively( $unserialized, true, $recursion_level + 1 );
			} elseif ( is_array( $data ) ) {
				$keys = array_keys( $data );
				foreach ( $keys as $key ) {
					$data[ $key ] = $this->run_recursively( $data[ $key ], false, $recursion_level + 1, $visited_data );
				}
			} elseif ( $this->recurse_objects && ( is_object( $data ) || $data instanceof \__PHP_Incomplete_Class ) ) {
				if ( $data instanceof \__PHP_Incomplete_Class ) {
					$array = new ArrayObject( $data );
					\WP_CLI::warning(
						sprintf(
							'Skipping an uninitialized class "%s", replacements might not be complete.',
							$array['__PHP_Incomplete_Class_Name']
						)
					);
				} else {
					foreach ( $data as $key => $value ) {
						$data->$key = $this->run_recursively( $value, false, $recursion_level + 1, $visited_data );
					}
				}
			} elseif ( is_string( $data ) ) {
				if ( $this->logging ) {
					$old_data = $data;
				}
				if ( $this->regex ) {
					$search_regex  = $this->regex_delimiter;
					$search_regex .= $this->from;
					$search_regex .= $this->regex_delimiter;
					$search_regex .= $this->regex_flags;

					$result = preg_replace( $search_regex, $this->to, $data, $this->regex_limit );
					if ( null === $result || PREG_NO_ERROR !== preg_last_error() ) {
						\WP_CLI::warning(
							sprintf(
								'The provided regular expression threw a PCRE error - %s',
								$this->preg_error_message( $result )
							)
						);
					}
					$data = $result;
				} else {
					$data = str_replace( $this->from, $this->to, $data );
				}
				if ( $this->logging && $old_data !== $data ) {
					$this->log_data[] = $old_data;
				}
			}

			if ( $serialised ) {
				return serialize( $data );
			}
		} catch ( Exception $error ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Deliberally empty.

		}

		return $data;
	}

	/**
	 * Gets existing data saved for this run when logging.
	 * @return array Array of data strings, prior to replacements.
	 */
	public function get_log_data() {
		return $this->log_data;
	}

	/**
	 * Clears data stored for logging.
	 */
	public function clear_log_data() {
		$this->log_data = array();
	}

	/**
	 * Get the PCRE error constant name from an error value.
	 *
	 * @param  integer $error Error code.
	 * @return string         Error constant name.
	 */
	private function preg_error_message( $error ) {
		static $error_names = null;

		if ( null === $error_names ) {
			$definitions    = get_defined_constants( true );
			$pcre_constants = array_key_exists( 'pcre', $definitions )
				? $definitions['pcre']
				: array();
			$error_names    = array_flip( $pcre_constants );
		}

		return isset( $error_names[ $error ] )
			? $error_names[ $error ]
			: '<unknown error>';
	}
}
