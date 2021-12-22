<?php namespace WP_CLI\Maintenance;

use WP_CLI;

final class Replace_Label_Command {

	/**
	 * Replaces a label with a different one, and optionally deletes the old
	 * label.
	 *
	 * ## OPTIONS
	 *
	 * <repo>
	 * : Name of the repository you want to replace a label for.
	 *
	 * <old-label>
	 * : Old label to replace on all issues.
	 *
	 * <new-label>
	 * : New label to replace it with.
	 *
	 * [--delete]
	 * : Delete the old label after the operation is complete.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args ) {

		list( $repo, $old_label, $new_label ) = $args;

		if ( false === strpos( $repo, '/' ) ) {
			$repo = "wp-cli/{$repo}";
		}

		$delete = WP_CLI\Utils\get_flag_value( $assoc_args, 'delete', false );

		foreach ( GitHub::get_issues_by_label( $repo, $old_label ) as $issue ) {
			GitHub::remove_label( $repo, $issue->number, $old_label );
			GitHub::add_label( $repo, $issue->number, $new_label );
		}

		if ( $delete ) {
			GitHub::delete_label( $repo, $old_label );
		}

		WP_CLI::success( "Label '{$old_label}' was replaced with '{$new_label}' in the '{$repo}' repository." );
	}
}
