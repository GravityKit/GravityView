<?php namespace WP_CLI\Maintenance;

use WP_CLI;

final class Release_Date_Command {

	/**
	 * Retrieves the date a given release for a repository was published at.
	 *
	 * ## OPTIONS
	 *
	 * <repo>
	 * : Name of the repository to fetch the release notes for. If no user/org
	 * was provided, 'wp-cli' org is assumed.
	 *
	 * <release>
	 * : Name of the release to fetch the release notes for.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		list( $repo, $milestone_name ) = $args;

		if ( false === strpos( $repo, '/' ) ) {
			$repo = "wp-cli/{$repo}";
		}

		$release = GitHub::get_release_by_tag(
			$repo,
			strpos( $milestone_name, 'v' ) === 0
				? $milestone_name
				: "v{$milestone_name}",
			[ 'state' => 'all' ]
		);

		WP_CLI::log( $release->published_at );
	}
}
