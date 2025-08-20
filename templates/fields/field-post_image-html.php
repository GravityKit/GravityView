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
$post_id        = GVCommon::get_post_id_from_entry( $entry );

/**
 * Parse the stored value of the post image
 *
 * The image is stored with `|:|` dividing the fields. Break it into its parts and see what's set.
 *
 * @see GFCommon::get_lead_field_display()
 * @var array
 */
$image_data  = GravityView_Field_Post_Image::explode_value( $value );
$url         = $image_data['url'];
$title       = $image_data['title'];
$caption     = $image_data['caption'];
$description = $image_data['description'];

$link_atts = array();

/**
 * @since 1.5.4
 *
 * $field->postFeaturedImage - holds if the Post Image field is set as post featured image
 * $field_settings['dynamic_data'] - whether the field content should be fetched from the Post (dynamic data) or from the GF entry
 *
 * Dynamic data (get post featured image instead of GF entry field)
 */
if ( ! empty( $field->postFeaturedImage ) && ! empty( $field_settings['dynamic_data'] ) && ! empty( $post_id ) && has_post_thumbnail( $post_id ) ) {

	/**
	 * Modify what size is fetched for the post's Featured Image
	 *
	 * @param string $size The size to be fetched using `wp_get_attachment_image_src()` (default: 'large')
	 * @param array $entry Gravity Forms entry array
	 * @since 2.0
	 * @param \GV\Template_Context $context The context
	 */
	$image_size = apply_filters( 'gravityview/fields/post_image/size', 'large', $entry, $gravityview );
	$image_url  = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), $image_size );

	if ( empty( $image_url[0] ) ) {
		do_action( 'gravityview_log_debug', 'Dynamic featured image for post #' . $post_id . ' doesnt exist (size: ' . $image_size . ').' );
	} else {
		$url = $image_url[0];
	}
}

//
// Get the link URL
//

// Link to the post created by the entry
if ( ! empty( $field_settings['link_to_post'] ) && ! empty( $post_id ) ) {
	$href = get_permalink( $post_id );
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
 * @see https://www.gravitykit.com/support/documentation/201606759 Read more about the filter
 * @var array $image_meta Associative array with `title`, `caption`, and `description` keys, each an array with `label`, `value`, `tag_label` and `tag_value` keys
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
 * @see https://www.gravitykit.com/support/documentation/201606759 Read more about the filter
 * @var boolean $showlabels True: Show labels; False: hide labels
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
