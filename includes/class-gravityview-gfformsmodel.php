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

        $form_id = get_post_meta( $post_id, '_gform-form-id', true );

        /**
         * Filter the media upload location.
         *
         * @param array $upload_dir The current upload directory’s path and url.
         * @param int $form_id The ID of the form currently being processed.
         * @param int $post_id The ID of the post created from the entry currently being processed.
         */
        $upload_dir = gf_apply_filters( 'gform_media_upload_path', $form_id, $upload_dir, $form_id, $post_id );

        if ( ! file_exists( $upload_dir['path'] ) ) {
            if ( ! wp_mkdir_p( $upload_dir['path'] ) ) {
                return false;
            }
        }

        $name     = basename( $url );
        $filename = wp_unique_filename( $upload_dir['path'], $name );

        // the destination path
        $new_file = $upload_dir['path'] . "/$filename";

        // the source path
        $y                = substr( $time, 0, 4 );
        $m                = substr( $time, 5, 2 );
        $target_root      = RGFormsModel::get_upload_path( $form_id ) . "/$y/$m/";
        $target_root_url  = RGFormsModel::get_upload_url( $form_id ) . "/$y/$m/";
        $upload_root_info = array( 'path' => $target_root, 'url' => $target_root_url );
        $upload_root_info = gf_apply_filters( 'gform_upload_path', $form_id, $upload_root_info, $form_id );
        $path             = str_replace( $upload_root_info['url'], $upload_root_info['path'], $url );

        // copy the file to the destination path
        if ( ! copy( $path, $new_file ) ) {
            return false;
        }

        // Set correct file permissions
        $stat  = stat( dirname( $new_file ) );
        $perms = $stat['mode'] & 0000666;
        @ chmod( $new_file, $perms );

        // Compute the URL
        $url = $upload_dir['url'] . "/$filename";

        if ( is_multisite() ) {
            delete_transient( 'dirsize_cache' );
        }

        $type = wp_check_filetype( $new_file );

        return array( 'file' => $new_file, 'url' => $url, 'type' => $type['type'] );

    }

    /**
     * Copied function from Gravity Forms plugin \GFFormsModel::media_handle_upload since the method is private.
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

        /**
         * Original Gravity Forms code below:
         * ==================================
         */

        //WordPress Administration API required for the media_handle_upload() function
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $name = basename( $url );

        $file = self::copy_post_image( $url, $post_id );

        if ( ! $file ) {
            return false;
        }

        $name_parts = pathinfo( $name );
        $name       = trim( substr( $name, 0, - ( 1 + strlen( $name_parts['extension'] ) ) ) );

        $url     = $file['url'];
        $type    = $file['type'];
        $file    = $file['file'];
        $title   = $name;
        $content = '';

        // use image exif/iptc data for title and caption defaults if possible
        if ( $image_meta = @wp_read_image_metadata( $file ) ) {
            if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
                $title = $image_meta['title'];
            }
            if ( trim( $image_meta['caption'] ) ) {
                $content = $image_meta['caption'];
            }
        }

        // Construct the attachment array
        $attachment = array_merge(
            array(
                'post_mime_type' => $type,
                'guid'           => $url,
                'post_parent'    => $post_id,
                'post_title'     => $title,
                'post_content'   => $content,
            ), $post_data
        );

        // Save the data
        $id = wp_insert_attachment( $attachment, $file, $post_id );
        if ( ! is_wp_error( $id ) ) {
            wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
        }

        return $id;
    }


}