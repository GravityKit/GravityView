<?php
/**
 * Make some GFFormsModel public available.
 * @since 1.16.2
 */


class GravityView_GFFormsModel extends GFFormsModel {

    /**
     * Copied function from Gravity Forms plugin \GFFormsModel::media_handle_upload since the method is private.
     * @todo: Remove this as soon as the method becomes available
     * @param $url
     * @param $post_id
     * @param array $post_data
     * @return bool|int
     */
    public static function media_handle_upload( $url, $post_id, $post_data = array() ) {
        return parent::media_handle_upload( $url, $post_id, $post_data );
    }

}