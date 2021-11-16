<?php namespace WP_CLI\Maintenance;

use WP_CLI;
use WP_CLI\Utils;

final class Release_Notes_Command {

	/**
	 * Packages excluded from release notes generation.
	 *
	 * @var array
	 */
	private $excluded_packages = [
		'wp-cli/wp-cli-tests',
		'wp-cli/regenerate-readme',
		'wp-cli/autoload-splitter',
		'wp-cli/wp-config-transformer',
		'wp-cli/php-cli-tools',
		'wp-cli/spyc',
	];

	/**
	 * Higher-level packages that represent the principal project building
	 * blocks.
	 *
	 * @var array
	 */
	private $higher_level_packages = [
		'wp-cli/wp-cli-bundle',
		'wp-cli/wp-cli',
		'wp-cli/handbook',
		'wp-cli/wp-cli.github.com',
	];

	/**
	 * Gets the release notes for one or more milestones of a repository.
	 *
	 * ## OPTIONS
	 *
	 * [<repo>]
	 * : Name of the repository to fetch the release notes for. If no user/org
	 * was provided, 'wp-cli' org is assumed. If no repo is passed, release
	 * notes for the entire org state since the last bundle release are fetched.
	 *
	 * [<milestone>...]
	 * : Name of one or more milestones to fetch the release notes for. If none
	 * are passed, the current open one is assumed.
	 *
	 * [--source=<source>]
	 * : Choose source from where to copy content.
	 * ---
	 * default: release
	 * options:
	 *   - release
	 *   - pull-request
	 *
	 * [--format=<format>]
	 * : Render output in a specific format.
	 * ---
	 * default: markdown
	 * options:
	 *   - markdown
	 *   - html
	 * ---
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		$repo            = null;
		$milestone_names = null;

		if ( count( $args ) > 0 ) {
			$repo = array_shift( $args );
		}

		$milestone_names = $args;

		$source = Utils\get_flag_value( $assoc_args, 'source', 'release' );
		$format = Utils\get_flag_value( $assoc_args, 'format', 'markdown' );

		if ( $repo ) {
			$this->get_repo_release_notes(
				$repo,
				$milestone_names,
				$source,
				$format
			);

			return;
		}

		$this->get_bundle_release_notes( $source, $format );
	}

	private function get_bundle_release_notes( $source, $format ) {
		// Get the release notes for the current open large project milestones.
		foreach ( $this->higher_level_packages as $repo ) {
			$milestones = GitHub::get_project_milestones( $repo );
			// Cheap way to get the latest milestone
			$milestone = array_shift( $milestones );
			if ( ! $milestone ) {
				continue;
			}

			WP_CLI::log( $this->repo_heading( $repo, $format ) );

			$this->get_repo_release_notes(
				$repo,
				$milestone->title,
				$source,
				$format
			);
		}

		// Identify all command dependencies and their release notes

		$bundle = 'wp-cli/wp-cli-bundle';

		$milestones = GitHub::get_project_milestones(
			'wp-cli/wp-cli',
			[ 'state' => 'closed' ]
		);
		// Cheap way to get the latest closed milestone
		$milestone = array_shift( $milestones );
		$tag       = is_object( $milestone ) ? "v{$milestone->title}" : 'master';

		$composer_lock_url = sprintf(
			'https://raw.githubusercontent.com/%s/%s/composer.lock',
			$bundle,
			$tag
		);
		$response          = Utils\http_request( 'GET', $composer_lock_url );
		if ( 200 !== $response->status_code ) {
			WP_CLI::error(
				sprintf(
					'Could not fetch composer.json (HTTP code %d)',
					$response->status_code
				)
			);
		}
		$composer_json = json_decode( $response->body, true );

		usort(
			$composer_json['packages'],
			function ( $a, $b ) {
				return $a['name'] < $b['name'] ? - 1 : 1;
			}
		);

		foreach ( $composer_json['packages'] as $package ) {
			$package_name       = $package['name'];
			$version_constraint = str_replace( 'v', '', $package['version'] );
			if ( ! preg_match( '#^wp-cli/.+-command$#', $package_name )
				&& ! in_array( $package_name, $this->excluded_packages, true ) ) {
				continue;
			}

			WP_CLI::log( $this->repo_heading( $package_name, $format ) );

			// Closed milestones denote a tagged release
			$milestones = GitHub::get_project_milestones(
				$package_name,
				[ 'state' => 'closed' ]
			);
			foreach ( $milestones as $milestone ) {
				if ( ! version_compare(
					$milestone->title,
					$version_constraint,
					'>'
				) ) {
					continue;
				}

				$this->get_repo_release_notes(
					$package_name,
					$milestone->title,
					$source,
					$format
				);
			}
		}
	}

	private function get_repo_release_notes(
		$repo,
		$milestone_names,
		$source,
		$format
	) {
		if ( false === strpos( $repo, '/' ) ) {
			$repo = "wp-cli/{$repo}";
		}

		$milestone_names = (array) $milestone_names;

		$potential_milestones = GitHub::get_project_milestones(
			$repo,
			[ 'state' => 'all' ]
		);

		$milestones = [];
		foreach ( $potential_milestones as $potential_milestone ) {
			if ( in_array(
				$potential_milestone->title,
				$milestone_names,
				true
			) ) {
				$milestones[] = $potential_milestone;
				$index        = array_search(
					$potential_milestone->title,
					$milestone_names,
					true
				);
				unset( $milestone_names[ $index ] );
			}
		}

		if ( ! empty( $milestone_names ) ) {
			WP_CLI::warning(
				sprintf(
					"Couldn't find the requested milestone(s) '%s' in repository '%s'.",
					implode( "', '", $milestone_names ),
					$repo
				)
			);
		}

		$entries = [];
		foreach ( $milestones as $milestone ) {
			switch ( $source ) {
				case 'release':
					$tag = 0 === strpos( $milestone->title, 'v' )
						? $milestone->title
						: "v{$milestone->title}";

					$release = GitHub::get_release_by_tag(
						$repo,
						$tag,
						[ 'throw_errors' => false ]
					);

					if ( $release ) {
						WP_CLI::log( $release->body );
						break;
					}

					WP_CLI::warning( "Release notes not found for {$repo}@{$tag}, falling back to pull-request source" );
					// Fall-through is intentional here.
				case 'pull-request':
					$pull_requests = GitHub::get_project_milestone_pull_requests(
						$repo,
						$milestone->number
					);

					foreach ( $pull_requests as $pull_request ) {
						$entries[] = $this->get_pull_request_reference(
							$pull_request,
							$format
						);
					}
					break;
				default:
					WP_CLI::error( "Unknown --source: {$source}" );
			}
		}

		$template = 'html' === $format ? '<ul>%s</ul>' : '%s';

		WP_CLI::log( sprintf( $template, implode( '', $entries ) ) );
	}

	private function get_pull_request_reference(
		$pull_request,
		$format
	) {
		$template = 'html' === $format ?
			'<li>%1$s [<a href="%3$s">#%2$d</a>]</li>' :
			'- %1$s [[#%2$d](%3$s)]' . PHP_EOL;

		return sprintf(
			$template,
			$this->format_title( $pull_request->title, $format ),
			$pull_request->number,
			$pull_request->url
		);
	}

	private function format_title( $title, $format ) {
		if ( 'html' === $format ) {
			$title = preg_replace( '/`(.*?)`/', '<code>$1</code>', $title );
		}

		return trim( $title );
	}

	private function repo_heading( $repo, $format ) {
		return sprintf(
			'html' === $format
				? '<h4><a href="%2$s">%1$s</a></h4>' . PHP_EOL
				: '#### [%1$s](%2$s)' . PHP_EOL,
			$repo,
			"https://github.com/{$repo}/"
		);
	}
}
