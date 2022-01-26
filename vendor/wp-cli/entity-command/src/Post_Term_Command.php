<?php

use WP_CLI\CommandWithTerms;
use WP_CLI\Fetchers\Post as PostFetcher;

/**
 * Adds, updates, removes, and lists post terms.
 *
 * ## EXAMPLES
 *
 *     # Set post terms
 *     $ wp post term set 123 test category
 *     Success: Set terms.
 */
class Post_Term_Command extends CommandWithTerms {
	protected $obj_type = 'post';

	public function __construct() {
		$this->fetcher = new PostFetcher();
	}

	protected function get_object_type() {
		$post = $this->fetcher->get_check( $this->get_obj_id() );

		return $post->post_type;
	}
}
