<?php
/**
 * Make some GFFormsModel public available.
 *
 * @since 1.16.2
 */


class GravityView_GFFormsModel extends GFFormsModel {

	/**
	 * Make sure the method exists, regardless of GF version
	 *
	 * @since 1.22.2
	 *
	 * @return string|false False if not set, version string otherwise
	 */
	public static function get_database_version() {

		if ( is_callable( 'GFFormsModel::get_database_version' ) ) {
			return parent::get_database_version();
		}

		return get_option( 'gf_db_version' );
	}

	/**
	 * Determines if the field value matches the conditional logic rule value.
	 *
	 * @since 1.22.3
	 *
	 * @param mixed                $field_value  The field value to be checked.
	 * @param mixed                $target_value The conditional logic rule value.
	 * @param string               $operation    The conditional logic rule operator.
	 * @param null|GF_Field|string $source_field The field the rule is based on, or the entry meta
	 * @param null|array           $rule         The conditional logic rule properties.
	 * @param null|array           $form         The current form.
	 *
	 * @return bool
	 */
	public static function is_value_match( $field_value, $target_value, $operation = 'is', $source_field = null, $rule = null, $form = null ) {

		if ( in_array( $source_field, array( 'date_created', 'date_updated', 'payment_date' ), true ) ) {
			$field_value  = is_int( $field_value ) ? $field_value : strtotime( $field_value );
			$target_value = is_int( $target_value ) ? $target_value : strtotime( $target_value );
		}

		if ( $source_field instanceof GF_Field && 'date' == $source_field->type ) {
			$field_value  = is_int( $field_value ) ? $field_value : strtotime( $field_value );
			$target_value = is_int( $target_value ) ? $target_value : strtotime( $target_value );
		}

		if ( in_array( $_operation = str_replace( ' ', '_', trim( $operation ) ), array( 'in', 'not_in' ) ) ) {
			return GVCommon::matches_operation( (array) $field_value, (array) $target_value, $_operation );
		}

		return parent::is_value_match( $field_value, $target_value, $operation, $source_field, $rule, $form );
	}

	/**
	 * Given information provided in an entry, get array of media IDs
	 *
	 * This is necessary because GF doesn't expect to need to update post images, only to create them.
	 *
	 * @see GFFormsModel::create_post()
	 *
	 * @since 1.17
	 *
	 * @param array $form Gravity Forms form array
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array Array of "Field ID" => "Media IDs"
	 */
	public static function get_post_field_images( $form, $entry ) {

		$post_data = self::get_post_fields( $form, $entry );

		$media = get_attached_media( 'image', $entry['post_id'] );

		$post_images = array();

		foreach ( $media as $media_item ) {
			foreach ( (array) $post_data['images'] as $post_data_item ) {
				if (
					\GV\Utils::get( $post_data_item, 'title' ) === $media_item->post_title &&
					\GV\Utils::get( $post_data_item, 'description' ) === $media_item->post_content &&
					\GV\Utils::get( $post_data_item, 'caption' ) === $media_item->post_excerpt
				) {
					$post_images[ "{$post_data_item['field_id']}" ] = $media_item->ID;
				}
			}
		}

		return $post_images;
	}

	/**
	 * Alias of GFFormsModel::get_post_fields(); just making it public
	 *
	 * @see GFFormsModel::get_post_fields()
	 *
	 * @since 1.17
	 *
	 * @param array $form Gravity Forms form array
	 * @param array $entry Gravity Forms entry array
	 *
	 * @return array
	 */
	public static function get_post_fields( $form, $entry ) {

		$reflection = new ReflectionMethod( 'GFFormsModel', 'get_post_fields' );

		/**
		 * If the method changes to public, use Gravity Forms' method
		 *
		 * @todo: If/when the method is public, remove the unneeded copied code.
		 */
		if ( $reflection->isPublic() ) {
			return parent::get_post_fields( $form, $entry );
		}

		// It was private; let's make it public
		$reflection->setAccessible( true );

		return $reflection->invoke( new GFFormsModel(), $form, $entry );
	}

	/**
	 * Copied function from Gravity Forms plugin \GFFormsModel::copy_post_image since the method is private.
	 *
	 * @since 1.16.2
	 *
	 * @param string $url URL of the post image to update
	 * @param int    $post_id ID of the post image to update
	 * @return array|bool Array with `file`, `url` and `type` keys. False: failed to copy file to final directory path.
	 */
	public static function copy_post_image( $url, $post_id ) {

		$reflection = new ReflectionMethod( 'GFFormsModel', 'copy_post_image' );

		/**
		 * If the method changes to public, use Gravity Forms' method
		 *
		 * @todo: If/when the method is public, remove the unneeded copied code.
		 */
		if ( $reflection->isPublic() ) {
			return parent::copy_post_image( $url, $post_id );
		}

		// It was private; let's make it public
		$reflection->setAccessible( true );

		return $reflection->invoke( new GFFormsModel(), $url, $post_id );
	}

	/**
	 * Copied function from Gravity Forms plugin \GFFormsModel::media_handle_upload since the method is private.
	 *
	 * Note: The method became public in GF 1.9.17.7
	 *
	 * @see GFFormsModel::media_handle_upload
	 * @see GravityView_Edit_Entry_Render::maybe_update_post_fields
	 *
	 * @uses copy_post_image
	 * @uses wp_insert_attachment
	 * @uses wp_update_attachment_metadata
	 *
	 * @param string $url URL of the post image to update
	 * @param int    $post_id ID of the post image to update
	 * @param array  $post_data Array of data for the eventual attachment post type that is created using {@see wp_insert_attachment}. Supports `post_mime_type`, `guid`, `post_parent`, `post_title`, `post_content` keys.
	 * @return bool|int ID of attachment Post created. Returns false if file not created by copy_post_image
	 */
	public static function media_handle_upload( $url, $post_id, $post_data = array() ) {

		$reflection = new ReflectionMethod( 'GFFormsModel', 'media_handle_upload' );

		/**
		 * If the method changes to public, use Gravity Forms' method
		 *
		 * @todo: If/when the method is public, remove the unneeded copied code.
		 */
		if ( $reflection->isPublic() ) {
			return parent::media_handle_upload( $url, $post_id, $post_data );
		}

		// It was private; let's make it public
		$reflection->setAccessible( true );

		return $reflection->invoke( new GFFormsModel(), $url, $post_id, $post_data );
	}
}
