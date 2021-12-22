<?php

namespace WP_CLI;

use WP_CLI;

class Completions {

	private $words;
	private $opts = [];

	public function __construct( $line ) {
		// TODO: properly parse single and double quotes
		$this->words = explode( ' ', $line );

		// First word is always `wp`.
		array_shift( $this->words );

		// Last word is either empty or an incomplete subcommand.
		$this->cur_word = end( $this->words );
		if ( '' !== $this->cur_word && ! preg_match( '/^\-/', $this->cur_word ) ) {
			array_pop( $this->words );
		}

		$is_alias = false;
		$is_help  = false;
		if ( ! empty( $this->words[0] ) && preg_match( '/^@/', $this->words[0] ) ) {
			array_shift( $this->words );
			// `wp @al` is false, but `wp @all ` is true.
			if ( count( $this->words ) ) {
				$is_alias = true;
			}
		} elseif ( ! empty( $this->words[0] ) && 'help' === $this->words[0] ) {
			array_shift( $this->words );
			$is_help = true;
		}

		$r = $this->get_command( $this->words );
		if ( ! is_array( $r ) ) {
			return;
		}

		list( $command, $args, $assoc_args ) = $r;

		$spec = SynopsisParser::parse( $command->get_synopsis() );

		foreach ( $spec as $arg ) {
			if ( 'positional' === $arg['type'] && 'file' === $arg['name'] ) {
				$this->add( '<file> ' );
				return;
			}
		}

		if ( $command->can_have_subcommands() ) {
			// Add completion when command is `wp` and alias isn't set.
			if ( 'wp' === $command->get_name() && false === $is_alias && false === $is_help ) {
				$aliases = WP_CLI::get_configurator()->get_aliases();
				foreach ( $aliases as $name => $_ ) {
					$this->add( "$name " );
				}
			}
			foreach ( $command->get_subcommands() as $name => $_ ) {
				$this->add( "$name " );
			}
		} else {
			foreach ( $spec as $arg ) {
				if ( in_array( $arg['type'], [ 'flag', 'assoc' ], true ) ) {
					if ( isset( $assoc_args[ $arg['name'] ] ) ) {
						continue;
					}

					$opt = "--{$arg['name']}";

					if ( 'flag' === $arg['type'] ) {
						$opt .= ' ';
					} elseif ( ! $arg['value']['optional'] ) {
						$opt .= '=';
					}

					$this->add( $opt );
				}
			}

			foreach ( $this->get_global_parameters() as $param => $runtime ) {
				if ( isset( $assoc_args[ $param ] ) ) {
					continue;
				}

				$opt = "--{$param}";

				if ( '' === $runtime || ! is_string( $runtime ) ) {
					$opt .= ' ';
				} else {
					$opt .= '=';
				}

				$this->add( $opt );
			}
		}

	}

	private function get_command( $words ) {
		$positional_args = [];
		$assoc_args      = [];

		foreach ( $words as $arg ) {
			if ( preg_match( '|^--([^=]+)=?|', $arg, $matches ) ) {
				$assoc_args[ $matches[1] ] = true;
			} else {
				$positional_args[] = $arg;
			}
		}

		$r = WP_CLI::get_runner()->find_command_to_run( $positional_args );
		if ( ! is_array( $r ) && array_pop( $positional_args ) === $this->cur_word ) {
			$r = WP_CLI::get_runner()->find_command_to_run( $positional_args );
		}

		if ( ! is_array( $r ) ) {
			return $r;
		}

		list( $command, $args ) = $r;

		return [ $command, $args, $assoc_args ];
	}

	private function get_global_parameters() {
		$params = [];
		foreach ( WP_CLI::get_configurator()->get_spec() as $key => $details ) {
			if ( false === $details['runtime'] ) {
				continue;
			}

			if ( isset( $details['deprecated'] ) ) {
				continue;
			}

			if ( isset( $details['hidden'] ) ) {
				continue;
			}
			$params[ $key ] = $details['runtime'];

			// Add additional option like `--[no-]color`.
			if ( true === $details['runtime'] ) {
				$params[ 'no-' . $key ] = '';
			}
		}

		return $params;
	}

	private function add( $opt ) {
		if ( '' !== $this->cur_word ) {
			if ( 0 !== strpos( $opt, $this->cur_word ) ) {
				return;
			}
		}

		$this->opts[] = $opt;
	}

	public function render() {
		foreach ( $this->opts as $opt ) {
			WP_CLI::line( $opt );
		}
	}
}
