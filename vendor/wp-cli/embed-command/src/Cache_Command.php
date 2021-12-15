<?php

namespace WP_CLI\Embeds;

use WP_CLI;
use WP_CLI\Process;
use WP_CLI\Utils;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Finds, triggers, and deletes oEmbed caches.
 */
class Cache_Command extends WP_CLI_Command {

	/**
	 * Deletes all oEmbed caches for a given post.
	 *
	 * oEmbed caches for a post are stored in the post's metadata.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of the post to clear the cache for.
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear cache for a post
	 *     $ wp embed cache clear 123
	 *     Success: Cleared oEmbed cache.
	 */
	public function clear( $args, $assoc_args ) {
		/** @var \WP_Embed $wp_embed */
		global $wp_embed;

		$post_id = $args[0];

		$post_metas = get_post_custom_keys( $post_id );

		if ( $post_metas ) {
			$post_metas = array_filter(
				$post_metas,
				function ( $v ) {
					return '_oembed_' === substr( $v, 0, 8 );
				}
			);
		}

		if ( empty( $post_metas ) ) {
			WP_CLI::error( 'No cache to clear!' );
		}

		$wp_embed->delete_oembed_caches( $post_id );

		WP_CLI::success( 'Cleared oEmbed cache.' );
	}

	/**
	 * Finds an oEmbed cache post ID for a given URL.
	 *
	 * Starting with WordPress 4.9, embeds that aren't associated with a specific post will be cached in
	 * a new oembed_cache post type. There can be more than one such entry for a url depending on attributes and context.
	 *
	 * Not to be confused with oEmbed caches for a given post which are stored in the post's metadata.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : URL to retrieve oEmbed data for.
	 *
	 * [--width=<width>]
	 * : Width of the embed in pixels. Part of cache key so must match. Defaults to `content_width` if set else 500px, so is theme and context dependent.
	 *
	 * [--height=<height>]
	 * : Height of the embed in pixels. Part of cache key so must match. Defaults to 1.5 * default width (`content_width` or 500px), to a maximum of 1000px.
	 *
	 * [--discover]
	 * : Whether to search with the discover attribute set or not. Part of cache key so must match. If not given, will search with attribute: unset, '1', '0', returning first.
	 *
	 * ## EXAMPLES
	 *
	 *     # Find cache post ID for a given URL.
	 *     $ wp embed cache find https://www.youtube.com/watch?v=dQw4w9WgXcQ --width=500
	 *     123
	 */
	public function find( $args, $assoc_args ) {
		if ( Utils\wp_version_compare( '4.9', '<' ) ) {
			WP_CLI::error( 'Requires WordPress 4.9 or greater.' );
		}

		/** @var \WP_Embed $wp_embed */
		global $wp_embed;

		$url      = $args[0];
		$width    = Utils\get_flag_value( $assoc_args, 'width' );
		$height   = Utils\get_flag_value( $assoc_args, 'height' );
		$discover = Utils\get_flag_value( $assoc_args, 'discover' );

		// The `$key_suffix` used for caching is part based on serializing the attributes array without normalizing it first so need to try to replicate that.
		$oembed_args = array();

		if ( null !== $width ) {
			$oembed_args['width'] = $width; // Keep as string as if from a shortcode attribute.
		}
		if ( null !== $height ) {
			$oembed_args['height'] = $height; // Keep as string as if from a shortcode attribute.
		}
		if ( null !== $discover ) {
			$discovers = array( ( $discover ) ? '1' : '0' );
		} else {
			$discovers = array( null, '1', '0' );
		}

		$attr = wp_parse_args( $oembed_args, wp_embed_defaults( $url ) );

		foreach ( $discovers as $discover ) {
			if ( null !== $discover ) {
				$attr['discover'] = $discover;
			}
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- needed to mimic WP Core behavior. See: \WP_Embed::shortcode.
			$key_suffix = md5( $url . serialize( $attr ) );

			$cached_post_id = $wp_embed->find_oembed_post_id( $key_suffix );

			if ( $cached_post_id ) {
				WP_CLI::line( $cached_post_id );

				return;
			}
		}

		WP_CLI::error( 'No cache post ID found!' );
	}

	/**
	 * Triggers the caching of all oEmbed results for a given post.
	 *
	 * oEmbed caches for a post are stored in the post's metadata.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of the post to do the caching for.
	 *
	 * ## EXAMPLES
	 *
	 *     # Triggers cache for a post
	 *     $ wp embed cache trigger 456
	 *     Success: Caching triggered!
	 */
	public function trigger( $args, $assoc_args ) {
		/** @var \WP_Embed $wp_embed */
		global $wp_embed;

		$post_id    = $args[0];
		$post       = get_post( $post_id );
		$post_types = get_post_types( array( 'show_ui' => true ) );

		if ( empty( $post->ID ) ) {
			WP_CLI::warning( sprintf( "Post id '%s' not found.", $post_id ) );

			return;
		}

		/** This filter is documented in wp-includes/class-wp-embed.php */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Using WP Core hook.
		if ( ! in_array( $post->post_type, apply_filters( 'embed_cache_oembed_types', $post_types ), true ) ) {
			WP_CLI::warning( sprintf( "Cannot cache oEmbed results for '%s' post type", $post->post_type ) );

			return;
		}

		$wp_embed->cache_oembed( $post_id );

		WP_CLI::success( 'Caching triggered!' );
	}
}
