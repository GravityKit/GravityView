<?php

use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Manages taxonomy terms and term meta, with create, delete, and list commands.
 *
 * See reference for [taxonomies and their terms](https://codex.wordpress.org/Taxonomies).
 *
 * ## EXAMPLES
 *
 *     # Create a new term.
 *     $ wp term create category Apple --description="A type of fruit"
 *     Success: Created category 199.
 *
 *     # Get details about a term.
 *     $ wp term get category 199 --format=json --fields=term_id,name,slug,count
 *     {"term_id":199,"name":"Apple","slug":"apple","count":1}
 *
 *     # Update an existing term.
 *     $ wp term update category 15 --name=Apple
 *     Success: Term updated.
 *
 *     # Get the term's URL.
 *     $ wp term list post_tag --include=123 --field=url
 *     http://example.com/tag/tips-and-tricks
 *
 *     # Delete post category
 *     $ wp term delete category 15
 *     Success: Deleted category 15.
 *
 *     # Recount posts assigned to each categories and tags
 *     $ wp term recount category post_tag
 *     Success: Updated category term count
 *     Success: Updated post_tag term count
 *
 * @package wp-cli
 */
class Term_Command extends WP_CLI_Command {

	private $fields = [
		'term_id',
		'term_taxonomy_id',
		'name',
		'slug',
		'description',
		'parent',
		'count',
	];

	/**
	 * Lists terms in a taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>...
	 * : List terms of one or more taxonomies
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see get_terms() $args parameter for a list of fields).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each term.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each term:
	 *
	 * * term_id
	 * * term_taxonomy_id
	 * * name
	 * * slug
	 * * description
	 * * parent
	 * * count
	 *
	 * These fields are optionally available:
	 *
	 * * url
	 *
	 * ## EXAMPLES
	 *
	 *     # List post categories
	 *     $ wp term list category --format=csv
	 *     term_id,term_taxonomy_id,name,slug,description,parent,count
	 *     2,2,aciform,aciform,,0,1
	 *     3,3,antiquarianism,antiquarianism,,0,1
	 *     4,4,arrangement,arrangement,,0,1
	 *     5,5,asmodeus,asmodeus,,0,1
	 *
	 *     # List post tags
	 *     $ wp term list post_tag --fields=name,slug
	 *     +-----------+-------------+
	 *     | name      | slug        |
	 *     +-----------+-------------+
	 *     | 8BIT      | 8bit        |
	 *     | alignment | alignment-2 |
	 *     | Articles  | articles    |
	 *     | aside     | aside       |
	 *     +-----------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		foreach ( $args as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				WP_CLI::error( "Taxonomy {$taxonomy} doesn't exist." );
			}
		}

		$formatter = $this->get_formatter( $assoc_args );

		$defaults   = [
			'hide_empty' => false,
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		if ( ! empty( $assoc_args['term_id'] ) ) {
			$term  = get_term_by( 'id', $assoc_args['term_id'], $args[0] );
			$terms = [ $term ];
		} elseif ( ! empty( $assoc_args['include'] )
			&& ! empty( $assoc_args['orderby'] )
			&& 'include' === $assoc_args['orderby']
			&& Utils\wp_version_compare( '4.7', '<' ) ) {
			$terms    = [];
			$term_ids = explode( ',', $assoc_args['include'] );
			foreach ( $term_ids as $term_id ) {
				$term = get_term_by( 'id', $term_id, $args[0] );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				} else {
					WP_CLI::warning( "Invalid term {$term_id}." );
				}
			}
		} else {
			$terms = get_terms( $args, $assoc_args );
		}

		$terms = array_map(
			function( $term ) {
					$term->count  = (int) $term->count;
					$term->parent = (int) $term->parent;
					$term->url    = get_term_link( $term );
					return $term;
			},
			$terms
		);

		if ( 'ids' === $formatter->format ) {
			$terms = wp_list_pluck( $terms, 'term_id' );
			echo implode( ' ', $terms );
		} else {
			$formatter->display_items( $terms );
		}
	}

	/**
	 * Creates a new term.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : Taxonomy for the new term.
	 *
	 * <term>
	 * : A name for the new term.
	 *
	 * [--slug=<slug>]
	 * : A unique slug for the new term. Defaults to sanitized version of name.
	 *
	 * [--description=<description>]
	 * : A description for the new term.
	 *
	 * [--parent=<term-id>]
	 * : A parent for the new term.
	 *
	 * [--porcelain]
	 * : Output just the new term id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a new category "Apple" with a description.
	 *     $ wp term create category Apple --description="A type of fruit"
	 *     Success: Created category 199.
	 */
	public function create( $args, $assoc_args ) {

		list( $taxonomy, $term ) = $args;

		$defaults   = [
			'slug'        => sanitize_title( $term ),
			'description' => '',
			'parent'      => 0,
		];
		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		$porcelain = Utils\get_flag_value( $assoc_args, 'porcelain' );
		unset( $assoc_args['porcelain'] );

		// Compatibility for < WP 4.0
		if ( $assoc_args['parent'] > 0 && ! term_exists( (int) $assoc_args['parent'] ) ) {
			WP_CLI::error( 'Parent term does not exist.' );
		}

		$assoc_args = wp_slash( $assoc_args );
		$term       = wp_slash( $term );
		$result     = wp_insert_term( $term, $taxonomy, $assoc_args );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		} else {
			if ( $porcelain ) {
				WP_CLI::line( $result['term_id'] );
			} else {
				WP_CLI::success( "Created {$taxonomy} {$result['term_id']}." );
			}
		}
	}

	/**
	 * Gets details about a term.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : Taxonomy of the term to get
	 *
	 * <term>
	 * : ID or slug of the term to get
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: id
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole term, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details about a category with id 199.
	 *     $ wp term get category 199 --format=json
	 *     {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}
	 *
	 *     # Get details about a category with slug apple.
	 *     $ wp term get category apple --by=slug --format=json
	 *     {"term_id":199,"name":"Apple","slug":"apple","term_group":0,"term_taxonomy_id":199,"taxonomy":"category","description":"A type of fruit","parent":0,"count":0,"filter":"raw"}
	 */
	public function get( $args, $assoc_args ) {

		list( $taxonomy, $term ) = $args;

		$term = get_term_by( Utils\get_flag_value( $assoc_args, 'by' ), $term, $taxonomy );

		if ( ! $term ) {
			WP_CLI::error( "Term doesn't exist." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$term_array           = get_object_vars( $term );
			$assoc_args['fields'] = array_keys( $term_array );
		}

		$term->count  = (int) $term->count;
		$term->parent = (int) $term->parent;

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $term );
	}

	/**
	 * Updates an existing term.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : Taxonomy of the term to update.
	 *
	 * <term>
	 * : ID or slug for the term to update.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: id
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * [--name=<name>]
	 * : A new name for the term.
	 *
	 * [--slug=<slug>]
	 * : A new slug for the term.
	 *
	 * [--description=<description>]
	 * : A new description for the term.
	 *
	 * [--parent=<term-id>]
	 * : A new parent for the term.
	 *
	 * ## EXAMPLES
	 *
	 *     # Change category with id 15 to use the name "Apple"
	 *     $ wp term update category 15 --name=Apple
	 *     Success: Term updated.
	 *
	 *     # Change category with slug apple to use the name "Apple"
	 *     $ wp term update category apple --by=slug --name=Apple
	 *     Success: Term updated.
	 */
	public function update( $args, $assoc_args ) {

		list( $taxonomy, $term ) = $args;

		$defaults   = [
			'name'        => null,
			'slug'        => null,
			'description' => null,
			'parent'      => null,
		];
		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		foreach ( $assoc_args as $key => $value ) {
			if ( null === $value ) {
				unset( $assoc_args[ $key ] );
			}
		}

		$assoc_args = wp_slash( $assoc_args );

		$term = get_term_by( Utils\get_flag_value( $assoc_args, 'by' ), $term, $taxonomy );

		if ( ! $term ) {
			WP_CLI::error( "Term doesn't exist." );
		}

		// Update term.
		$result = wp_update_term( $term->term_id, $taxonomy, $assoc_args );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		} else {
			WP_CLI::success( 'Term updated.' );
		}
	}

	/**
	 * Deletes an existing term.
	 *
	 * Errors if the term doesn't exist, or there was a problem in deleting it.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : Taxonomy of the term to delete.
	 *
	 * <term>...
	 * : One or more IDs or slugs of terms to delete.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: id
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete post category by id
	 *     $ wp term delete category 15
	 *     Deleted category 15.
	 *     Success: Deleted 1 of 1 terms.
	 *
	 *     # Delete post category by slug
	 *     $ wp term delete category apple --by=slug
	 *     Deleted category 15.
	 *     Success: Deleted 1 of 1 terms.
	 *
	 *     # Delete all post tags
	 *     $ wp term list post_tag --field=term_id | xargs wp term delete post_tag
	 *     Deleted post_tag 159.
	 *     Deleted post_tag 160.
	 *     Deleted post_tag 161.
	 *     Success: Deleted 3 of 3 terms.
	 */
	public function delete( $args, $assoc_args ) {
		$taxonomy = array_shift( $args );

		$successes = 0;
		$errors    = 0;
		foreach ( $args as $term_id ) {

			$term = $term_id;

			// Get term by specified argument otherwise get term by id.
			$field = Utils\get_flag_value( $assoc_args, 'by' );
			if ( 'slug' === $field ) {

				// Get term by slug.
				$term = get_term_by( $field, $term, $taxonomy );

				// If term not found, then show error message and skip the iteration.
				if ( ! $term ) {
					WP_CLI::warning( "{$taxonomy} {$term_id} doesn't exist." );
					continue;
				}

				// Get the term id;
				$term_id = $term->term_id;

			}

			$result = wp_delete_term( $term_id, $taxonomy );

			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( $result );
				$errors++;
			} elseif ( $result ) {
				WP_CLI::log( "Deleted {$taxonomy} {$term_id}." );
				$successes++;
			} else {
				WP_CLI::warning( "{$taxonomy} {$term_id} doesn't exist." );
			}
		}
		Utils\report_batch_operation_results( 'term', 'delete', count( $args ), $successes, $errors );
	}

	/**
	 * Generates some terms.
	 *
	 * Creates a specified number of new terms with dummy data.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : The taxonomy for the generated terms.
	 *
	 * [--count=<number>]
	 * : How many terms to generate?
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--max_depth=<number>]
	 * : Generate child terms down to a certain depth.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *   - progress
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate post categories.
	 *     $ wp term generate category --count=10
	 *     Generating terms  100% [=========] 0:02 / 0:02
	 *
	 *     # Add meta to every generated term.
	 *     $ wp term generate category --format=ids --count=3 | xargs -d ' ' -I % wp term meta add % foo bar
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 */
	public function generate( $args, $assoc_args ) {
		global $wpdb;

		list ( $taxonomy ) = $args;

		$defaults = [
			'count'     => 100,
			'max_depth' => 1,
		];

		$final_args = array_merge( $defaults, $assoc_args );
		$count      = $final_args['count'];
		$max_depth  = $final_args['max_depth'];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			WP_CLI::error( "'{$taxonomy}' is not a registered taxonomy." );
		}

		$label = get_taxonomy( $taxonomy )->labels->singular_name;
		$slug  = sanitize_title_with_dashes( $label );

		$hierarchical = get_taxonomy( $taxonomy )->hierarchical;

		$format = Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = Utils\make_progress_bar( 'Generating terms', $count );
		}

		$previous_term_id = 0;
		$current_parent   = 0;
		$current_depth    = 1;

		$max_id = (int) $wpdb->get_var( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} ORDER BY term_taxonomy_id DESC LIMIT 1" );

		$suspend_cache_invalidation = wp_suspend_cache_invalidation( true );
		$created                    = [];

		for ( $index = $max_id + 1; $index <= $max_id + $count; $index++ ) {

			if ( $hierarchical ) {

				if ( $previous_term_id && $this->maybe_make_child() && $current_depth < $max_depth ) {

					$current_parent = $previous_term_id;
					$current_depth++;

				} elseif ( $this->maybe_reset_depth() ) {

					$current_parent = 0;
					$current_depth  = 1;

				}
			}

			$args = [
				'parent' => $current_parent,
				'slug'   => $slug . "-{$index}",
			];

			$name = "{$label} {$index}";
			$term = wp_insert_term( $name, $taxonomy, $args );
			if ( is_wp_error( $term ) ) {
				WP_CLI::warning( $term );
			} else {
				$created[]        = $term['term_id'];
				$previous_term_id = $term['term_id'];
				if ( 'ids' === $format ) {
					echo $term['term_id'];
					if ( $index < $max_id + $count ) {
						echo ' ';
					}
				}
			}

			if ( 'progress' === $format ) {
				$notify->tick();
			}
		}

		wp_suspend_cache_invalidation( $suspend_cache_invalidation );
		clean_term_cache( $created, $taxonomy );

		if ( 'progress' === $format ) {
			$notify->finish();
		}
	}

	/**
	 * Recalculates number of posts assigned to each term.
	 *
	 * In instances where manual updates are made to the terms assigned to
	 * posts in the database, the number of posts associated with a term
	 * can become out-of-sync with the actual number of posts.
	 *
	 * This command runs wp_update_term_count() on the taxonomy's terms
	 * to bring the count back to the correct value.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>...
	 * : One or more taxonomies to recalculate.
	 *
	 * ## EXAMPLES
	 *
	 *     # Recount posts assigned to each categories and tags
	 *     $ wp term recount category post_tag
	 *     Success: Updated category term count.
	 *     Success: Updated post_tag term count.
	 *
	 *     # Recount all listed taxonomies
	 *     $ wp taxonomy list --field=name | xargs wp term recount
	 *     Success: Updated category term count.
	 *     Success: Updated post_tag term count.
	 *     Success: Updated nav_menu term count.
	 *     Success: Updated link_category term count.
	 *     Success: Updated post_format term count.
	 */
	public function recount( $args ) {
		foreach ( $args as $taxonomy ) {

			if ( ! taxonomy_exists( $taxonomy ) ) {
				WP_CLI::warning( "Taxonomy {$taxonomy} does not exist." );
			} else {

				$terms             = get_terms( $taxonomy, [ 'hide_empty' => false ] );
				$term_taxonomy_ids = wp_list_pluck( $terms, 'term_taxonomy_id' );

				wp_update_term_count( $term_taxonomy_ids, $taxonomy );

				WP_CLI::success( "Updated {$taxonomy} term count." );
			}
		}
	}

	/**
	 * Migrate a term of a taxonomy to another taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * <term>
	 * : Slug or ID of the term to migrate.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: id
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * [--from=<taxonomy>]
	 * : Taxonomy slug of the term to migrate.
	 *
	 * [--to=<taxonomy>]
	 * : Taxonomy slug to migrate to.
	 *
	 * ## EXAMPLES
	 *
	 *     # Migrate a category's term (video) to tag taxonomy.
	 *     $ wp term migrate 9190 --from=category --to=post_tag
	 *     Term '9190' migrated!
	 *     Old instance of term '9190' removed from its original taxonomy.
	 *     Success: Migrated the term '9190' from taxonomy 'category' to taxonomy 'post_tag' for 1 posts
	 */
	public function migrate( $args, $assoc_args ) {
		$term_reference       = $args[0];
		$original_taxonomy    = Utils\get_flag_value( $assoc_args, 'from' );
		$destination_taxonomy = Utils\get_flag_value( $assoc_args, 'to' );

		$term = get_term_by( Utils\get_flag_value( $assoc_args, 'by' ), $term_reference, $original_taxonomy );

		if ( ! $term ) {
			WP_CLI::error( "Taxonomy term '{$term_reference}' for taxonomy '{$original_taxonomy}' doesn't exist." );
		}

		$original_taxonomy = get_taxonomy( $original_taxonomy );

		$id = wp_insert_term(
			$term->name,
			$destination_taxonomy,
			[
				'slug'        => $term->slug,
				'parent'      => 0,
				'description' => $term->description,
			]
		);

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id->get_error_message() );
		}

		$post_ids = get_objects_in_term( $term->term_id, $original_taxonomy->name );

		foreach ( $post_ids as $post_id ) {
			$type = get_post_type( $post_id );
			if ( in_array( $type, $original_taxonomy->object_type, true ) ) {
				$term_taxonomy_id = wp_set_object_terms( $post_id, $id['term_id'], $destination_taxonomy, true );

				if ( is_wp_error( $term_taxonomy_id ) ) {
					WP_CLI::error( "Failed to assign the term '{$term->slug}' to the post {$post_id}. Reason: " . $term_taxonomy_id->get_error_message() );
				}

				WP_CLI::log( "Term '{$term->slug}' assigned to post {$post_id}." );
			}

			clean_post_cache( $post_id );
		}

		clean_term_cache( $term->term_id );

		WP_CLI::log( "Term '{$term->slug}' migrated." );

		$del = wp_delete_term( $term->term_id, $original_taxonomy->name );

		if ( is_wp_error( $del ) ) {
			WP_CLI::error( "Failed to delete the term '{$term->slug}'. Reason: " . $del->get_error_message() );
		}

		WP_CLI::log( "Old instance of term '{$term->slug}' removed from its original taxonomy." );
		$post_count  = count( $post_ids );
		$post_plural = Utils\pluralize( 'post', $post_count );
		WP_CLI::success( "Migrated the term '{$term->slug}' from taxonomy '{$original_taxonomy->name}' to taxonomy '{$destination_taxonomy}' for {$post_count} {$post_plural}." );
	}

	private function maybe_make_child() {
		// 50% chance of making child term.
		return ( wp_rand( 1, 2 ) === 1 );
	}

	private function maybe_reset_depth() {
		// 10% chance of reseting to root depth.
		return ( wp_rand( 1, 10 ) === 7 );
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'term' );
	}
}
