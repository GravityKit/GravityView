<?php
/**
 * Make some GFFormsModel public available.
 * @since 1.16.2
 */


class GravityView_GFFormsModel extends GFFormsModel {

    /**
     * Copied function from Gravity Forms plugin \GFFormsModel::copy_post_image since the method is private.
     *
     * @since 1.16.2
     *
     * @param string $url URL of the post image to update
     * @param int $post_id ID of the post image to update
     * @return array|bool Array with `file`, `url` and `type` keys. False: failed to copy file to final directory path.
     */
    public static function copy_post_image( $url, $post_id ) {

        $reflection = new ReflectionMethod( 'GFFormsModel', 'copy_post_image' );

        /**
         * If the method changes to public, use Gravity Forms' method
         * @todo: If/when the method is public, remove the unneeded copied code.
         */
        if( $reflection->isPublic() ) {
            return parent::copy_post_image( $url, $post_id );
        }

        /**
         * Original Gravity Forms code below:
         * ==================================
         */

        $time = current_time( 'mysql' );

        if ( $post = get_post( $post_id ) ) {
            if ( substr( $post->post_date, 0, 4 ) > 0 ) {
                $time = $post->post_date;
            }
        }

        //making sure there is a valid upload folder
        if ( ! ( ( $upload_dir = wp_upload_dir( $time ) ) && false === $upload_dir['error'] ) ) {
            return false;
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
         * @todo: If/when the method is public, remove the unneeded copied code.
         */
        if( $reflection->isPublic() ) {
            return parent::get_post_fields( $form, $entry );
        }

        // It was private; let's make it public
        $reflection->setAccessible( true );

        return $reflection->invoke( new GFFormsModel, $form, $entry );
    }

    /**
     * Copied function from Gravity Forms plugin \GFFormsModel::copy_post_image since the method is private.
     *
     * @since 1.16.2
     *
     * @param string $url URL of the post image to update
     * @param int $post_id ID of the post image to update
     * @return array|bool Array with `file`, `url` and `type` keys. False: failed to copy file to final directory path.
     */
    public static function copy_post_image( $url, $post_id ) {

        $reflection = new ReflectionMethod( 'GFFormsModel', 'copy_post_image' );

        /**
         * If the method changes to public, use Gravity Forms' method
         * @todo: If/when the method is public, remove the unneeded copied code.
         */
        if( $reflection->isPublic() ) {
            return parent::copy_post_image( $url, $post_id );
        }

        // It was private; let's make it public
        $reflection->setAccessible( true );

        return $reflection->invoke( new GFFormsModel, $url, $post_id );
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
     * @param int $post_id ID of the post image to update
     * @param array $post_data Array of data for the eventual attachment post type that is created using {@see wp_insert_attachment}. Supports `post_mime_type`, `guid`, `post_parent`, `post_title`, `post_content` keys.
     * @return bool|int ID of attachment Post created. Returns false if file not created by copy_post_image
     */
    public static function media_handle_upload( $url, $post_id, $post_data = array() ) {

        $reflection = new ReflectionMethod( 'GFFormsModel', 'media_handle_upload' );

        /**
         * If the method changes to public, use Gravity Forms' method
         * @todo: If/when the method is public, remove the unneeded copied code.
         */
        if( $reflection->isPublic() ) {
            return parent::media_handle_upload( $url, $post_id, $post_data );
        }

        // It was private; let's make it public
        $reflection->setAccessible( true );

        return $reflection->invoke( new GFFormsModel, $url, $post_id, $post_data );
    }

}