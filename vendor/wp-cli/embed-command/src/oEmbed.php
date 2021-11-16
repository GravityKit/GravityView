<?php

namespace WP_CLI\Embeds;

if ( ! class_exists( '\WP_oEmbed' ) ) {
	require_once ABSPATH . WPINC . '/class-oembed.php';
}

/**
 * Polyfill for older WP versions.
 */
class oEmbed extends \WP_oEmbed {

	/**
	 * Takes a URL and returns the corresponding oEmbed provider's URL, if there is one.
	 *
	 * @since 4.0.0
	 *
	 * @see WP_oEmbed::discover()
	 *
	 * @param string        $url  The URL to the content.
	 * @param string|array  $args Optional provider arguments.
	 * @return false|string False on failure, otherwise the oEmbed provider URL.
	 */
	public function get_provider( $url, $args = '' ) {
		if ( method_exists( 'WP_oEmbed', 'get_provider' ) ) {
			return parent::get_provider( $url, $args );
		}

		$args = wp_parse_args( $args );

		$provider = false;

		if ( ! isset( $args['discover'] ) ) {
			$args['discover'] = true;
		}

		foreach ( $this->providers as $matchmask => $data ) {
			list( $providerurl, $regex ) = $data;

			// Turn the asterisk-type provider URLs into regex
			if ( ! $regex ) {
				$matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $matchmask ), '#' ) ) . '#i';
				$matchmask = preg_replace( '|^#http\\\://|', '#https?\://', $matchmask );
			}

			if ( preg_match( $matchmask, $url ) ) {
				$provider = str_replace( '{format}', 'json', $providerurl ); // JSON is easier to deal with than XML
				break;
			}
		}

		if ( ! $provider && $args['discover'] ) {
			$provider = $this->discover( $url );
		}

		return $provider;
	}
}
