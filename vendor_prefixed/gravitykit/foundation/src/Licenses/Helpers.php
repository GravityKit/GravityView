<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Licenses;

use Exception;

class Helpers {
	/**
	 * Performs remote call to GravityKit's EDD API.
	 *
	 * @sice 1.0
	 *
	 * @param string $url
	 * @param array  $args
	 *
	 * @throws Exception
	 *
	 * @return array|null Response body.
	 */
	static function query_api( $url, array $args = [] ) {
		$request_parameters = [
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $args,
		];

		$response = wp_remote_post(
			$url,
			$request_parameters
		);

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $body ) ) {
			throw new Exception( $response->get_error_message() );
		}

		try {
			$response = json_decode( $body, true );
		} catch ( Exception $e ) {
			throw new Exception( esc_html__( 'Unable to process remote request. Invalid response body.', 'gk-gravityview' ) );
		}

		return $response;
	}
}
