<?php namespace WP_CLI\Maintenance;

use WP_CLI;
use WP_CLI\Utils;

class GitHub {

	const API_ROOT = 'https://api.github.com/';

	/**
	 * Gets the milestones for a given project.
	 *
	 * @param string $project
	 *
	 * @return array
	 */
	public static function get_project_milestones(
		$project,
		$args = []
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/milestones',
			$project
		);

		$args['per_page'] = 100;

		list( $body, $headers ) = self::request( $request_url, $args );

		return $body;
	}

	/**
	 * Gets a release for a given project by its tag name.
	 *
	 * @param string $project
	 * @param string $tag
	 * @param array  $args
	 *
	 * @return array|false
	 */
	public static function get_release_by_tag(
		$project,
		$tag,
		$args = []
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/releases/tags/%s',
			$project,
			$tag
		);

		$args['per_page'] = 100;

		list( $body, $headers ) = self::request( $request_url, $args );

		return $body;
	}

	/**
	 * Gets the issues that are labeled with a given label.
	 *
	 * @param string $project
	 * @param string $label
	 * @param array  $args
	 *
	 * @return array|false
	 */
	public static function get_issues_by_label(
		$project,
		$label,
		$args = []
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/issues',
			$project
		);

		$args['per_page'] = 100;
		$args['labels']   = $label;

		list( $body, $headers ) = self::request( $request_url, $args );

		return $body;
	}

	/**
	 * Removes a label from an issue.
	 *
	 * @param string $project
	 * @param string $issue
	 * @param string $label
	 * @param array  $args
	 *
	 * @return array|false
	 */
	public static function remove_label(
		$project,
		$issue,
		$label,
		$args = []
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/issues/%s/labels/%s',
			$project,
			$issue,
			$label
		);

		$headers['http_verb'] = 'DELETE';

		list( $body, $headers ) = self::request( $request_url, $args, $headers );

		return $body;
	}

	/**
	 * Adds a label to an issue.
	 *
	 * @param string $project
	 * @param string $issue
	 * @param string $label
	 * @param array  $args
	 *
	 * @return array|false
	 */
	public static function add_label(
		$project,
		$issue,
		$label,
		$args = []
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/issues/%s/labels',
			$project,
			$issue,
			$label
		);

		$headers['http_verb'] = 'POST';

		$args = [ $label ];

		list( $body, $headers ) = self::request( $request_url, $args, $headers );

		return $body;
	}

	/**
	 * Delete a label from a repository.
	 *
	 * @param string $project
	 * @param string $label
	 * @param array  $args
	 *
	 * @return array|false
	 */
	public static function delete_label(
		$project,
		$label,
		$args = []
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/labels/%s',
			$project,
			$label
		);

		$headers['http_verb'] = 'DELETE';

		list( $body, $headers ) = self::request( $request_url, $args, $headers );

		return $body;
	}

	/**
	 * Gets the pull requests assigned to a milestone of a given project.
	 *
	 * @param string  $project
	 * @param integer $milestone_id
	 *
	 * @return array
	 */
	public static function get_project_milestone_pull_requests(
		$project,
		$milestone_id
	) {
		$request_url = sprintf(
			self::API_ROOT . 'repos/%s/issues',
			$project
		);

		$args = [
			'per_page'  => 100,
			'milestone' => $milestone_id,
			'state'     => 'all',
		];

		$pull_requests = [];
		do {
			list( $body, $headers ) = self::request( $request_url, $args );
			foreach ( $body as $issue ) {
				if ( ! empty( $issue->pull_request ) ) {
					$pull_requests[] = $issue;
				}
			}
			$args        = [];
			$request_url = false;
			// Set $request_url to 'rel="next" if present'
			if ( ! empty( $headers['Link'] ) ) {
				$bits = explode( ',', $headers['Link'] );
				foreach ( $bits as $bit ) {
					if ( false !== stripos( $bit, 'rel="next"' ) ) {
						$hrefandrel  = explode( '; ', $bit );
						$request_url = trim( trim( $hrefandrel[0] ), '<>' );
						break;
					}
				}
			}
		} while ( $request_url );

		return $pull_requests;
	}

	/**
	 * Parses the contributors from pull request objects.
	 *
	 * @param array $pull_requests
	 *
	 * @return array
	 */
	public static function parse_contributors_from_pull_requests(
		$pull_requests
	) {
		$contributors = [];
		foreach ( $pull_requests as $pull_request ) {
			if ( ! empty( $pull_request->user ) ) {
				$contributors[ $pull_request->user->html_url ] = $pull_request->user->login;
			}
		}

		return $contributors;
	}

	/**
	 * Makes a request to the GitHub API.
	 *
	 * @param string $url
	 * @param array  $args
	 * @param array  $headers
	 *
	 * @return array|false
	 */
	public static function request(
		$url,
		$args = [],
		$headers = []
	) {
		$headers = array_merge(
			$headers,
			[
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WP-CLI',
			]
		);

		$token = getenv( 'GITHUB_TOKEN' );
		if ( $token ) {
			$headers['Authorization'] = 'token ' . $token;
		}

		$verb = 'GET';
		if ( isset( $headers['http_verb'] ) ) {
			$verb = $headers['http_verb'];
			unset( $headers['http_verb'] );
		}

		if ( 'POST' === $verb ) {
			$args = json_encode( $args );
		}

		$response = Utils\http_request( $verb, $url, $args, $headers );

		if ( 20 !== (int) substr( $response->status_code, 0, 2 ) ) {
			if ( isset( $args['throw_errors'] ) && false === $args['throw_errors'] ) {
				return false;
			}

			WP_CLI::error(
				sprintf(
					"Failed request to $url\nGitHub API returned: %s (HTTP code %d)",
					$response->body,
					$response->status_code
				)
			);
		}

		return [ json_decode( $response->body ), $response->headers ];
	}
}
