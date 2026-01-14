<?php

use GV\Template_Context;

/**
 * GravityView Image Resizer.
 *
 * @since TODO
 */
class GravityView_Image_Resizer {
	/**
	 * Entry meta key for resized images.
	 *
	 * @since TODO
	 */
	const META_KEY = '_gv_thumbnails';

	/**
	 * Lock TTL in seconds.
	 *
	 * @since TODO
	 */
	const LOCK_TTL = 60;

	/**
	 * Failure TTL in seconds.
	 *
	 * @since TODO
	 */
	const FAIL_TTL = 300;

	/**
	 * Register filters and actions.
	 *
	 * @since TODO
	 */
	public function __construct() {
		add_filter( 'gravityview/fields/fileupload/image_atts', array( $this, 'filter_image_atts' ), 20, 5 );
		add_action( 'gform_delete_entry', array( $this, 'cleanup_entry_thumbnails' ), 10, 1 );
	}

	/**
	 * Replace image src with a resized URL when possible.
	 *
	 * @since TODO
	 *
	 * @param array                 $image_atts   Image attributes.
	 * @param array|null            $field_compat Legacy field data array.
	 * @param Template_Context|null $context      Template context.
	 * @param array|null            $file_info    File info from the File Upload field.
	 * @param int|null              $index        File index for multi-file fields.
	 *
	 * @return array
	 */
	public function filter_image_atts( $image_atts = array(), $field_compat = null, $context = null, $file_info = null, $index = null ) {
		if ( ! $context instanceof Template_Context ) {
			return $image_atts;
		}

		$field_settings = $context->field->as_configuration();
		$entry          = $context->entry->as_entry();

		$enabled = apply_filters( 'gravityview/image-resize/enabled', true, $context, $field_settings, $entry );
		if ( ! $enabled ) {
			return $image_atts;
		}

		$width = isset( $image_atts['width'] ) ? (int) $image_atts['width'] : 0;
		if ( $width <= 0 ) {
			return $image_atts;
		}

		$should_resize = apply_filters( 'gravityview/image-resize/should-resize', true, $entry, $field_settings, $context );
		if ( ! $should_resize ) {
			return $image_atts;
		}

		$entry_id = (int) rgar( $entry, 'id' );
		if ( $entry_id <= 0 ) {
			return $image_atts;
		}

		$field_id = (int) $context->field->ID;
		$form_id  = (int) rgar( $entry, 'form_id' );

		$bypass_secure = ! empty( $field_settings['bypass_secure_download'] );
		$bypass_secure = apply_filters( 'gravityview/image-resize/bypass-secure', $bypass_secure, $entry, $field_settings, $context );

		if ( is_array( $file_info ) && ! empty( $file_info['is_secure'] ) && ! $bypass_secure ) {
			return $image_atts;
		}

		$source_url = $this->get_source_url( $image_atts, $file_info, $bypass_secure );
		if ( empty( $source_url ) ) {
			return $image_atts;
		}

		$local_path = $this->resolve_local_path( $source_url, $form_id );
		if ( empty( $local_path ) ) {
			return $image_atts;
		}

		$image_stats = $this->get_image_stats( $local_path );
		if ( empty( $image_stats ) ) {
			return $image_atts;
		}

		if ( ! $this->is_allowed_mime( $image_stats['mime'], $entry, $field_settings, $context ) ) {
			return $image_atts;
		}

		if ( $image_stats['width'] <= $width ) {
			return $image_atts;
		}

		if ( ! $this->is_resize_safe( $image_stats['width'], $image_stats['height'], $entry, $field_settings, $context ) ) {
			return $image_atts;
		}

		$source_sig = $this->get_source_signature( $local_path );
		if ( empty( $source_sig ) ) {
			return $image_atts;
		}

		$file_key = $this->get_file_key( $index );
		$size_key = 'w' . $width;

		$cached = $this->get_cached_size( $entry_id, $field_id, $file_key, $size_key, $source_sig );
		if ( ! empty( $cached ) ) {
			gravityview()->log->debug( 'Image resize cache hit', array( 'entry_id' => $entry_id, 'field_id' => $field_id, 'width' => $width ) );
			$image_atts['src'] = $cached['url'];
			if ( ! empty( $cached['height'] ) ) {
				$image_atts['height'] = (int) $cached['height'];
			}
			if ( ! empty( $cached['width'] ) ) {
				$image_atts['width'] = (int) $cached['width'];
			}
			return $image_atts;
		}

		if ( $this->has_recent_failure( $entry_id, $field_id, $file_key, $width, $source_sig ) ) {
			gravityview()->log->debug(
				'Image resize skipped due to recent failure',
				array(
					'entry_id' => $entry_id,
					'field_id' => $field_id,
					'width'    => $width,
				)
			);
			return $image_atts;
		}

		if ( ! $this->acquire_lock( $entry_id, $field_id, $file_key, $width ) ) {
			return $image_atts;
		}

		try {
			$resized = $this->resize_image( $local_path, $entry_id, $field_id, $width, $entry, $source_sig );
		} finally {
			$this->release_lock( $entry_id, $field_id, $file_key, $width );
		}

		if ( is_wp_error( $resized ) || empty( $resized['url'] ) ) {
			if ( is_wp_error( $resized ) ) {
				gravityview()->log->error( 'Image resize failed: {message}', array( 'message' => $resized->get_error_message() ) );
				$this->set_failure( $entry_id, $field_id, $file_key, $width, $source_sig, $resized->get_error_message() );
			} else {
				$this->set_failure( $entry_id, $field_id, $file_key, $width, $source_sig, 'Image resize failed.' );
			}
			return $image_atts;
		}

		gravityview()->log->debug( 'Image resize created', array( 'entry_id' => $entry_id, 'field_id' => $field_id, 'width' => $width ) );

		$this->update_meta_for_size(
			$entry_id,
			$field_id,
			$file_key,
			$size_key,
			$source_url,
			$source_sig,
			array(
				'url'     => $resized['url'],
				'path'    => $resized['path'],
				'width'   => $resized['width'],
				'height'  => $resized['height'],
				'created' => time(),
			)
		);

		$image_atts['src'] = $resized['url'];
		if ( ! empty( $resized['height'] ) ) {
			$image_atts['height'] = (int) $resized['height'];
		}
		if ( ! empty( $resized['width'] ) ) {
			$image_atts['width'] = (int) $resized['width'];
		}

		return $image_atts;
	}

	/**
	 * Determine the most appropriate source URL for resizing.
	 *
	 * @since TODO
	 *
	 * @param array      $image_atts    Image attributes array.
	 * @param array|null $file_info     File info from the File Upload field.
	 * @param bool       $bypass_secure Whether direct URLs are allowed.
	 *
	 * @return string
	 */
	private function get_source_url( $image_atts, $file_info, $bypass_secure ) {
		if ( is_array( $file_info ) ) {
			if ( $bypass_secure && ! empty( $file_info['insecure_file_path'] ) ) {
				return $file_info['insecure_file_path'];
			}
			if ( ! empty( $file_info['file_path'] ) ) {
				return $file_info['file_path'];
			}
		}

		return isset( $image_atts['src'] ) ? $image_atts['src'] : '';
	}

	/**
	 * Resolve a URL to a local filesystem path.
	 *
	 * @since TODO
	 *
	 * @param string $url     File URL.
	 * @param int    $form_id Gravity Forms form ID.
	 *
	 * @return string|null
	 */
	private function resolve_local_path( $url, $form_id ) {
		$clean_url = strtok( $url, '?#' );
		$clean_url = rawurldecode( $clean_url );

		$upload_dir = wp_upload_dir();
		$baseurl    = set_url_scheme( $upload_dir['baseurl'], 'http' );
		$basedir    = $upload_dir['basedir'];

		$clean_url_http = set_url_scheme( $clean_url, 'http' );

		if ( class_exists( 'GFFormsModel' ) && $form_id ) {
			$gf_url = set_url_scheme( GFFormsModel::get_upload_url( $form_id ), 'http' );

			if ( 0 === strpos( $clean_url_http, $gf_url ) ) {
				$relative = substr( $clean_url_http, strlen( $gf_url ) );
				$candidate = untrailingslashit( GFFormsModel::get_upload_path( $form_id ) ) . $relative;

				return $this->validate_local_path( $candidate, $basedir );
			}
		}

		if ( 0 === strpos( $clean_url_http, $baseurl ) ) {
			$relative  = substr( $clean_url_http, strlen( $baseurl ) );
			$candidate = untrailingslashit( $basedir ) . $relative;

			return $this->validate_local_path( $candidate, $basedir );
		}

		return null;
	}

	/**
	 * Validate that the candidate path is a local file within uploads.
	 *
	 * @since TODO
	 *
	 * @param string $candidate       Candidate filesystem path.
	 * @param string $uploads_basedir Uploads base directory.
	 *
	 * @return string|null
	 */
	private function validate_local_path( $candidate, $uploads_basedir ) {
		$candidate = wp_normalize_path( $candidate );

		if ( ! is_file( $candidate ) ) {
			return null;
		}

		$real_path = realpath( $candidate );
		$real_base = realpath( $uploads_basedir );

		if ( ! $real_path || ! $real_base ) {
			return null;
		}

		if ( 0 !== strpos( $real_path, $real_base ) ) {
			return null;
		}

		return $real_path;
	}

	/**
	 * Get image metadata without loading the full file.
	 *
	 * @since TODO
	 *
	 * @param string $path Local image path.
	 *
	 * @return array|null
	 */
	private function get_image_stats( $path ) {
		$info = @getimagesize( $path );
		if ( empty( $info ) || empty( $info[0] ) || empty( $info[1] ) ) {
			return null;
		}

		return array(
			'width'  => (int) $info[0],
			'height' => (int) $info[1],
			'mime'   => isset( $info['mime'] ) ? $info['mime'] : '',
		);
	}

	/**
	 * Check whether the MIME type is allowed for resizing.
	 *
	 * @since TODO
	 *
	 * @param string           $mime           MIME type.
	 * @param array            $entry          Gravity Forms entry.
	 * @param array            $field_settings Field settings.
	 * @param Template_Context $context        Template context.
	 *
	 * @return bool
	 */
	private function is_allowed_mime( $mime, $entry, $field_settings, $context ) {
		$allowed = apply_filters(
			'gravityview/image-resize/mime-allowlist',
			array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ),
			$entry,
			$field_settings,
			$context
		);

		return in_array( $mime, (array) $allowed, true );
	}

	/**
	 * Check guardrails for resizing safety.
	 *
	 * @since TODO
	 *
	 * @param int              $width          Source width.
	 * @param int              $height         Source height.
	 * @param array            $entry          Gravity Forms entry.
	 * @param array            $field_settings Field settings.
	 * @param Template_Context $context        Template context.
	 *
	 * @return bool
	 */
	private function is_resize_safe( $width, $height, $entry, $field_settings, $context ) {
		$max_dimension = (int) apply_filters( 'gravityview/image-resize/max-dimension', 5000, $entry, $field_settings, $context );
		if ( $width > $max_dimension || $height > $max_dimension ) {
			return false;
		}

		$memory_limit = $this->get_memory_limit_bytes();
		if ( $memory_limit <= 0 ) {
			return true;
		}

		$threshold = (float) apply_filters( 'gravityview/image-resize/memory-threshold', 0.75, $entry, $field_settings, $context );
		$threshold = max( 0.1, min( 1, $threshold ) );

		$estimated = $width * $height * 4 * 1.5;

		return ( $estimated <= ( $memory_limit * $threshold ) );
	}

	/**
	 * Convert PHP memory_limit to bytes.
	 *
	 * @since TODO
	 *
	 * @return int
	 */
	private function get_memory_limit_bytes() {
		$limit = ini_get( 'memory_limit' );
		if ( $limit === false || $limit === '' ) {
			return 0;
		}

		$limit = trim( $limit );
		if ( $limit === '-1' ) {
			return 0;
		}

		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return (int) wp_convert_hr_to_bytes( $limit );
		}

		$last  = strtolower( $limit[ strlen( $limit ) - 1 ] );
		$limit = (int) $limit;

		switch ( $last ) {
			case 'g':
				$limit *= 1024;
				// no break
			case 'm':
				$limit *= 1024;
				// no break
			case 'k':
				$limit *= 1024;
		}

		return (int) $limit;
	}

	/**
	 * Get a source signature for cache invalidation.
	 *
	 * @since TODO
	 *
	 * @param string $path Local file path.
	 *
	 * @return string|null
	 */
	private function get_source_signature( $path ) {
		$mtime = @filemtime( $path );
		$size  = @filesize( $path );

		if ( ! $mtime || ! $size ) {
			return null;
		}

		return $mtime . ':' . $size;
	}

	/**
	 * Fetch cached resize metadata for a file/size combo.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id   Entry ID.
	 * @param int    $field_id   Field ID.
	 * @param string $file_key   File index key.
	 * @param string $size_key   Size key.
	 * @param string $source_sig Source signature.
	 *
	 * @return array|null
	 */
	private function get_cached_size( $entry_id, $field_id, $file_key, $size_key, $source_sig ) {
		$meta = gform_get_meta( $entry_id, self::META_KEY );
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			return null;
		}

		$field_key = 'field_' . $field_id;
		if ( empty( $meta[ $field_key ]['files'][ $file_key ] ) ) {
			return null;
		}

		$file_meta = $meta[ $field_key ]['files'][ $file_key ];
		if ( empty( $file_meta['source_sig'] ) || $file_meta['source_sig'] !== $source_sig ) {
			return null;
		}

		$size_meta = isset( $file_meta['sizes'][ $size_key ] ) ? $file_meta['sizes'][ $size_key ] : null;
		if ( empty( $size_meta['url'] ) ) {
			return null;
		}

		$path = isset( $size_meta['path'] ) ? $size_meta['path'] : $this->resolve_storage_path( $size_meta['url'] );
		if ( empty( $path ) || ! is_file( $path ) ) {
			return null;
		}

		$size_meta['path'] = $path;

		return $size_meta;
	}

	/**
	 * Resolve a stored URL to a local path inside uploads.
	 *
	 * @since TODO
	 *
	 * @param string $url Stored URL.
	 *
	 * @return string|null
	 */
	private function resolve_storage_path( $url ) {
		$upload_dir = wp_upload_dir();
		$baseurl    = set_url_scheme( $upload_dir['baseurl'], 'http' );
		$basedir    = $upload_dir['basedir'];

		$clean_url = strtok( $url, '?#' );
		$clean_url = rawurldecode( $clean_url );
		$clean_url = set_url_scheme( $clean_url, 'http' );

		if ( 0 !== strpos( $clean_url, $baseurl ) ) {
			return null;
		}

		$relative  = substr( $clean_url, strlen( $baseurl ) );
		$candidate = untrailingslashit( $basedir ) . $relative;

		return wp_normalize_path( $candidate );
	}

	/**
	 * Update entry meta with resized image info.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id   Entry ID.
	 * @param int    $field_id   Field ID.
	 * @param string $file_key   File index key.
	 * @param string $size_key   Size key.
	 * @param string $source_url Source URL.
	 * @param string $source_sig Source signature.
	 * @param array  $size_data  Size metadata.
	 *
	 * @return void
	 */
	private function update_meta_for_size( $entry_id, $field_id, $file_key, $size_key, $source_url, $source_sig, $size_data ) {
		$meta = gform_get_meta( $entry_id, self::META_KEY );
		if ( empty( $meta ) || ! is_array( $meta ) ) {
			$meta = array();
		}

		$field_key = 'field_' . $field_id;

		if ( empty( $meta[ $field_key ]['files'][ $file_key ] ) || $meta[ $field_key ]['files'][ $file_key ]['source_sig'] !== $source_sig ) {
			$meta[ $field_key ]['files'][ $file_key ] = array(
				'source_url' => $source_url,
				'source_sig' => $source_sig,
				'sizes'      => array(),
			);
		}

		$meta[ $field_key ]['files'][ $file_key ]['sizes'][ $size_key ] = $size_data;

		gform_update_meta( $entry_id, self::META_KEY, $meta );
	}

	/**
	 * Resize an image and return the cached file info.
	 *
	 * @since TODO
	 *
	 * @param string $source_path Source file path.
	 * @param int    $entry_id    Entry ID.
	 * @param int    $field_id    Field ID.
	 * @param int    $width       Target width.
	 * @param array  $entry       Entry data.
	 * @param string $source_sig  Source signature.
	 *
	 * @return array|\WP_Error
	 */
	private function resize_image( $source_path, $entry_id, $field_id, $width, $entry, $source_sig ) {
		$storage = $this->get_storage_base( $entry, $field_id );
		$upload_dir = wp_upload_dir();

		if ( ! $this->is_storage_in_uploads( $storage['dir'], $upload_dir['basedir'] ) ) {
			return new WP_Error( 'gv_resize_dir', 'Storage directory must be within uploads.' );
		}

		$dir     = trailingslashit( $storage['dir'] ) . $entry_id . '/' . $field_id . '/' . $width;
		$urlbase = trailingslashit( $storage['url'] ) . $entry_id . '/' . $field_id . '/' . $width;

		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'gv_resize_dir', 'Unable to create resize directory.' );
		}

		$base      = sanitize_file_name( pathinfo( $source_path, PATHINFO_FILENAME ) );
		$extension = strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) );
		$hash      = substr( md5( $source_path . '|' . $source_sig . '|' . $width ), 0, 12 );
		$filename  = $base . '-' . $hash . '.' . $extension;
		$dest_path = trailingslashit( $dir ) . $filename;
		$dest_url  = trailingslashit( $urlbase ) . $filename;

		if ( is_file( $dest_path ) ) {
			$size = $this->get_image_stats( $dest_path );

			return array(
				'url'    => $dest_url,
				'path'   => $dest_path,
				'width'  => isset( $size['width'] ) ? $size['width'] : $width,
				'height' => isset( $size['height'] ) ? $size['height'] : null,
			);
		}

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'image' );
		}

		$editor = wp_get_image_editor( $source_path );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$quality = (int) apply_filters( 'gravityview/image-resize/quality', 82, $entry, $field_id );
		if ( $quality > 0 && method_exists( $editor, 'set_quality' ) ) {
			$editor->set_quality( $quality );
		}

		$result = $editor->resize( $width, null, false );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$saved = $editor->save( $dest_path );
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$size = $editor->get_size();

		return array(
			'url'    => $dest_url,
			'path'   => $dest_path,
			'width'  => isset( $size['width'] ) ? $size['width'] : $width,
			'height' => isset( $size['height'] ) ? $size['height'] : null,
		);
	}

	/**
	 * Get storage base paths, applying filters.
	 *
	 * @since TODO
	 *
	 * @param array $entry    Entry data.
	 * @param int   $field_id Field ID.
	 *
	 * @return array{dir:string,url:string}
	 */
	private function get_storage_base( $entry, $field_id ) {
		$upload_dir = wp_upload_dir();
		$default_dir = trailingslashit( $upload_dir['basedir'] ) . 'gravityview/thumbnails';
		$default_url = trailingslashit( $upload_dir['baseurl'] ) . 'gravityview/thumbnails';

		$dir = apply_filters( 'gravityview/image-resize/storage_dir', $default_dir, $entry, $field_id );
		$url = apply_filters( 'gravityview/image-resize/storage_url', $default_url, $entry, $field_id );

		return array(
			'dir' => untrailingslashit( $dir ),
			'url' => untrailingslashit( $url ),
		);
	}

	/**
	 * Check if a recent resize failure was recorded.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id   Entry ID.
	 * @param int    $field_id   Field ID.
	 * @param string $file_key   File key.
	 * @param int    $width      Width.
	 * @param string $source_sig Source signature.
	 *
	 * @return bool
	 */
	private function has_recent_failure( $entry_id, $field_id, $file_key, $width, $source_sig ) {
		return (bool) get_transient( $this->get_failure_key( $entry_id, $field_id, $file_key, $width, $source_sig ) );
	}

	/**
	 * Record a resize failure to avoid repeated attempts.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id   Entry ID.
	 * @param int    $field_id   Field ID.
	 * @param string $file_key   File key.
	 * @param int    $width      Width.
	 * @param string $source_sig Source signature.
	 * @param string $message    Error message.
	 *
	 * @return void
	 */
	private function set_failure( $entry_id, $field_id, $file_key, $width, $source_sig, $message ) {
		set_transient(
			$this->get_failure_key( $entry_id, $field_id, $file_key, $width, $source_sig ),
			array(
				'message' => $message,
				'time'    => time(),
			),
			self::FAIL_TTL
		);
	}

	/**
	 * Build a failure transient key.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id   Entry ID.
	 * @param int    $field_id   Field ID.
	 * @param string $file_key   File key.
	 * @param int    $width      Width.
	 * @param string $source_sig Source signature.
	 *
	 * @return string
	 */
	private function get_failure_key( $entry_id, $field_id, $file_key, $width, $source_sig ) {
		return 'gv_resize_fail_' . md5( $entry_id . '|' . $field_id . '|' . $file_key . '|' . $width . '|' . $source_sig );
	}

	/**
	 * Validate storage is inside uploads.
	 *
	 * @since TODO
	 *
	 * @param string $storage_dir     Storage directory.
	 * @param string $uploads_basedir Uploads base directory.
	 *
	 * @return bool
	 */
	private function is_storage_in_uploads( $storage_dir, $uploads_basedir ) {
		$real_base    = realpath( $uploads_basedir );
		$real_storage = realpath( $storage_dir );

		if ( $real_base && $real_storage ) {
			$real_base    = trailingslashit( wp_normalize_path( $real_base ) );
			$real_storage = trailingslashit( wp_normalize_path( $real_storage ) );

			return 0 === strpos( $real_storage, $real_base );
		}

		$normalized_base    = trailingslashit( wp_normalize_path( untrailingslashit( $uploads_basedir ) ) );
		$normalized_storage = trailingslashit( wp_normalize_path( $storage_dir ) );

		return 0 === strpos( $normalized_storage, $normalized_base );
	}

	/**
	 * Build a file key for multi-file fields.
	 *
	 * @since TODO
	 *
	 * @param int|null $index File index.
	 *
	 * @return string
	 */
	private function get_file_key( $index ) {
		$index = is_null( $index ) ? 0 : (int) $index;

		return 'idx_' . $index;
	}

	/**
	 * Acquire a resize lock.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $field_id Field ID.
	 * @param string $file_key File key.
	 * @param int    $width    Width.
	 *
	 * @return bool
	 */
	private function acquire_lock( $entry_id, $field_id, $file_key, $width ) {
		$lock_key = $this->get_lock_key( $entry_id, $field_id, $file_key, $width );
		if ( get_transient( $lock_key ) ) {
			return false;
		}

		set_transient( $lock_key, 1, self::LOCK_TTL );

		return true;
	}

	/**
	 * Release a resize lock.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $field_id Field ID.
	 * @param string $file_key File key.
	 * @param int    $width    Width.
	 *
	 * @return void
	 */
	private function release_lock( $entry_id, $field_id, $file_key, $width ) {
		delete_transient( $this->get_lock_key( $entry_id, $field_id, $file_key, $width ) );
	}

	/**
	 * Build the lock transient key.
	 *
	 * @since TODO
	 *
	 * @param int    $entry_id Entry ID.
	 * @param int    $field_id Field ID.
	 * @param string $file_key File key.
	 * @param int    $width    Width.
	 *
	 * @return string
	 */
	private function get_lock_key( $entry_id, $field_id, $file_key, $width ) {
		return 'gv_resize_' . md5( $entry_id . '|' . $field_id . '|' . $file_key . '|' . $width );
	}

	/**
	 * Cleanup resized files and meta when an entry is deleted.
	 *
	 * @since TODO
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return void
	 */
	public function cleanup_entry_thumbnails( $entry_id ) {
		$entry_id = (int) $entry_id;
		if ( $entry_id <= 0 ) {
			return;
		}

		$entry = array();
		if ( class_exists( 'GFAPI' ) ) {
			$maybe_entry = GFAPI::get_entry( $entry_id );
			if ( ! is_wp_error( $maybe_entry ) ) {
				$entry = $maybe_entry;
			}
		}

		$storage    = $this->get_storage_base( $entry, 0 );
		$target_dir = trailingslashit( $storage['dir'] ) . $entry_id;
		$upload_dir = wp_upload_dir();

		$this->delete_directory( $target_dir, $upload_dir['basedir'] );
		gform_delete_meta( $entry_id, self::META_KEY );
	}

	/**
	 * Recursively delete a directory inside uploads.
	 *
	 * @since TODO
	 *
	 * @param string $dir             Directory to delete.
	 * @param string $uploads_basedir Uploads base directory.
	 *
	 * @return void
	 */
	private function delete_directory( $dir, $uploads_basedir ) {
		$dir = wp_normalize_path( $dir );
		$base = realpath( $uploads_basedir );
		$real = realpath( $dir );

		if ( ! $real || ! $base || 0 !== strpos( $real, $base ) ) {
			return;
		}

		if ( ! is_dir( $real ) ) {
			return;
		}

		$items = scandir( $real );
		if ( false === $items ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}
			$path = $real . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				$this->delete_directory( $path, $uploads_basedir );
				continue;
			}
			@unlink( $path );
		}

		@rmdir( $real );
	}
}

new GravityView_Image_Resizer();
