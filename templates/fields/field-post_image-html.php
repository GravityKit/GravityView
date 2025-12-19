<?php
/**
 * The default post_image field output template.
 *
 * @global \GV\Template_Context $gravityview
 * @since 2.0
 */

if ( ! isset( $gravityview ) || empty( $gravityview->template ) ) {
	gravityview()->log->error( '{file} template loaded without context', array( 'file' => __FILE__ ) );
	return;
}

$field          = $gravityview->field->field;
$value          = $gravityview->value;
$entry          = $gravityview->entry->as_entry();
$field_settings = $gravityview->field->as_configuration();

/**
 * Parse the stored value of the post image
 *
 * The image is stored with `|:|` dividing the fields. Break it into its parts and see what's set.
 *
 * @see GFCommon::get_lead_field_display()
 * @var array
 */
$ary         = explode( '|:|', $value );
$url         = count( $ary ) > 0 ? $ary[0] : '';
$title       = count( $ary ) > 1 ? $ary[1] : '';
$caption     = count( $ary ) > 2 ? $ary[2] : '';
$description = count( $ary ) > 3 ? $ary[3] : '';

$link_atts = array();

/**
 * @since 1.5.4
 *
 * $field['postFeaturedImage'] - holds if the Post Image field is set as post featured image
 * $field_settings['dynamic_data'] - whether the field content should be fetched from the Post (dynamic data) or from the GF entry
 *
 * Dynamic data (get post featured image instead of GF entry field)
 */
if ( ! empty( $field['postFeaturedImage'] ) && ! empty( $field_settings['dynamic_data'] ) && ! empty( $entry['post_id'] ) && has_post_thumbnail( $entry['post_id'] ) ) {

	/**
	 * Modify what size is fetched for the post's Featured Image.
	 *
	 * @since 2.0
	 *
	 * @param string               $size    The size to be fetched using `wp_get_attachment_image_src()`. Default: 'large'.
	 * @param array                $entry   Gravity Forms entry array.
	 * @param \GV\Template_Context $context The context.
	 */
	$image_size = apply_filters( 'gravityview/fields/post_image/size', 'large', $entry, $gravityview );
	$image_url  = wp_get_attachment_image_src( get_post_thumbnail_id( $entry['post_id'] ), $image_size );

	if ( empty( $image_url[0] ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic featured image for post #' . $entry['post_id'] . ' doesnt exist (size: ' . $image_size . ').' );
	} else {
		$url = $image_url[0];
	}
}

//
// Get the link URL
//

// Link to the post created by the entry
if ( ! empty( $field_settings['link_to_post'] ) ) {
	$href = get_permalink( $entry['post_id'] );
}
// Link to the single entry
elseif ( ! empty( $field_settings['show_as_link'] ) ) {
	$href = gv_entry_link( $entry );
}
// Link to the file itself
else {

	$href = $url;

	// Only show the lightbox if linking to the file itself
	if ( $gravityview->view->settings->get( 'lightbox' ) ) {
		/**
		 * Modify the CSS class used to trigger lightbox functionality.
		 *
		 * @since 1.0.5-beta
		 *
		 * @param string $class The CSS class to trigger lightbox. Default: 'thickbox'.
		 */
		$link_atts['class'] = apply_filters( 'gravityview_lightbox_script', 'thickbox' );
	}
}


// Set the attributes for the link
$link_atts['href'] = $href;

// Add the title as the link title, if exists. This will appear as caption in the lightbox.
$link_atts['title'] = $title;


//
// Get the image
//
$image_atts = array(
	'src'          => $url,
	'alt'          => ( ! empty( $caption ) ? $caption : $title ),
	'validate_src' => false, // Already validated by GF
);

$image = new GravityView_Image( $image_atts );


/**
 * Modify the values used for the image meta.
 *
 * @since 1.2
 *
 * @see https://www.gravitykit.com/support/documentation/201606759 Read more about the filter.
 *
 * @param array $image_meta Associative array with `title`, `caption`, and `description` keys, each an array with `label`, `value`, `tag_label` and `tag_value` keys.
 */
$image_meta = apply_filters(
	'gravityview_post_image_meta',
	array(
		'title'       => array(
			'label'     => esc_attr_x( 'Title:', 'Post Image field title heading', 'gk-gravityview' ),
			'value'     => $title,
			'tag_label' => 'span',
			'tag_value' => 'div',
		),
		'caption'     => array(
			'label'     => esc_attr_x( 'Caption:', 'Post Image field caption heading', 'gk-gravityview' ),
			'value'     => $caption,
			'tag_label' => 'span',
			'tag_value' => 'figcaption',
		),
		'description' => array(
			'label'     => esc_attr_x( 'Description:', 'Post Image field description heading', 'gk-gravityview' ),
			'value'     => $description,
			'tag_label' => 'span',
			'tag_value' => 'div',
		),
	)
);

// If HTML5 output is enabled, support the `figure` and `figcaption` tags
$wrappertag = 'figure';

/**
 * Whether to show labels for the image meta.
 *
 * @since 1.2
 *
 * @see https://www.gravitykit.com/support/documentation/201606759 Read more about the filter.
 *
 * @param bool $showlabels True: Show labels; False: hide labels.
 */
$showlabels = apply_filters( 'gravityview_post_image_meta_show_labels', true );

// Wrapper tag
$output = '<' . $wrappertag . ' class="gv-image">';

// Image with link tag
$output .= gravityview_get_link( $href, $image, $link_atts );

foreach ( (array) $image_meta as $key => $meta ) {

	if ( ! empty( $meta['value'] ) ) {

		$output .= '<div class="gv-image-' . esc_attr( $key ) . '">';

		// Display the label if the label's not empty
		if ( ! empty( $showlabels ) && ! empty( $meta['label'] ) ) {
			$output .= '<' . esc_attr( $meta['tag_label'] ) . ' class="gv-image-label">';
			$output .= esc_html( $meta['label'] );
			$output .= '</' . esc_attr( $meta['tag_label'] ) . '> ';
		}

		// Display the value
		$output .= '<' . esc_attr( $meta['tag_value'] ) . ' class="gv-image-value">';
		$output .= esc_html( $meta['value'] );
		$output .= '</' . esc_attr( $meta['tag_value'] ) . '>';

		$output .= '</div>';
	}
}

$output .= '</' . $wrappertag . '>';

echo $output;
