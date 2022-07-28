<?php

namespace WP_CLI\Embeds;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI_Command;

/**
 * Retrieves oEmbed providers.
 */
class Provider_Command extends WP_CLI_Command {
	protected $default_fields = array(
		'format',
		'endpoint',
	);

	/**
	 * Lists all available oEmbed providers.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Display the value of a single field
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * [--force-regex]
	 * : Turn the asterisk-type provider URLs into regexes.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each provider:
	 *
	 * * format
	 * * endpoint
	 *
	 * This field is optionally available:
	 *
	 * * regex
	 *
	 * ## EXAMPLES
	 *
	 *     # List format,endpoint fields of available providers.
	 *     $ wp embed provider list --fields=format,endpoint
	 *     +------------------------------+-----------------------------------------+
	 *     | format                       | endpoint                                |
	 *     +------------------------------+-----------------------------------------+
	 *     | #https?://youtu\.be/.*#i     | https://www.youtube.com/oembed          |
	 *     | #https?://flic\.kr/.*#i      | https://www.flickr.com/services/oembed/ |
	 *     | #https?://wordpress\.tv/.*#i | https://wordpress.tv/oembed/            |
	 *
	 * @subcommand list
	 */
	public function list_providers( $args, $assoc_args ) {

		$oembed = new oEmbed();

		$force_regex = Utils\get_flag_value( $assoc_args, 'force-regex' );

		$providers = array();

		foreach ( (array) $oembed->providers as $matchmask => $data ) {
			list( $providerurl, $regex ) = $data;

			// Turn the asterisk-type provider URLs into regex
			if ( $force_regex && ! $regex ) {
				$matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $matchmask ), '#' ) ) . '#i';
				$matchmask = preg_replace( '|^#http\\\://|', '#https?\://', $matchmask );
			}

			$providers[] = array(
				'format'   => $matchmask,
				'endpoint' => $providerurl,
				'regex'    => $regex ? '1' : '0',
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $providers );
	}

	/**
	 * Gets the matching provider for a given URL.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : URL to retrieve provider for.
	 *
	 * [--discover]
	 * : Whether to use oEmbed discovery or not. Defaults to true.
	 *
	 * [--limit-response-size=<size>]
	 * : Limit the size of the resulting HTML when using discovery. Default 150 KB (the standard WordPress limit). Not compatible with 'no-discover'.
	 *
	 * [--link-type=<json|xml>]
	 * : Whether to accept only a certain link type when using discovery. Defaults to any (json or xml), preferring json. Not compatible with 'no-discover'.
	 * ---
	 * options:
	 *   - json
	 *   - xml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the matching provider for the URL.
	 *     $ wp embed provider match https://www.youtube.com/watch?v=dQw4w9WgXcQ
	 *     https://www.youtube.com/oembed
	 *
	 * @subcommand match
	 */
	public function match_provider( $args, $assoc_args ) {
		$oembed = new oEmbed();

		$url                 = $args[0];
		$discover            = \WP_CLI\Utils\get_flag_value( $assoc_args, 'discover', true );
		$response_size_limit = \WP_CLI\Utils\get_flag_value( $assoc_args, 'limit-response-size' );
		$link_type           = \WP_CLI\Utils\get_flag_value( $assoc_args, 'link-type' );

		if ( ! $discover && ( null !== $response_size_limit || null !== $link_type ) ) {
			if ( null !== $response_size_limit && null !== $link_type ) {
				$msg = "The 'limit-response-size' and 'link-type' options can only be used with discovery.";
			} elseif ( null !== $response_size_limit ) {
				$msg = "The 'limit-response-size' option can only be used with discovery.";
			} else {
				$msg = "The 'link-type' option can only be used with discovery.";
			}
			WP_CLI::error( $msg );
		}

		if ( $response_size_limit ) {
			if ( Utils\wp_version_compare( '4.0', '<' ) ) {
				WP_CLI::warning( "The 'limit-response-size' option only works for WordPress 4.0 onwards." );
				// Fall through anyway...
			}
			add_filter(
				'oembed_remote_get_args',
				function ( $args ) use ( $response_size_limit ) {
					$args['limit_response_size'] = $response_size_limit;
					return $args;
				}
			);
		}

		if ( $link_type ) {
			// Filter discovery response.
			add_filter(
				'oembed_linktypes',
				function ( $linktypes ) use ( $link_type ) {
					foreach ( $linktypes as $mime_type => $linktype_format ) {
						if ( $link_type !== $linktype_format ) {
							unset( $linktypes[ $mime_type ] );
						}
					}
					return $linktypes;
				}
			);
		}

		$oembed_args = array(
			'discover' => $discover,
		);

		$provider = $oembed->get_provider( $url, $oembed_args );

		if ( ! $provider ) {
			if ( ! $discover ) {
				WP_CLI::error( 'No oEmbed provider found for given URL. Maybe try discovery?' );
			} else {
				WP_CLI::error( 'No oEmbed provider found for given URL.' );
			}
		}

		WP_CLI::line( $provider );
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 * @return \WP_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->default_fields );
	}
}
