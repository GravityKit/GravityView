<?php
/**
 * Generic class for generating image tag
 */
class GravityView_Image {

	var $alt;
	var $src;
	var $title;
	var $width;
	var $height;

	/**
	 * HTML class attribute.
	 * @var string
	 */
	var $class = NULL;

	/**
	 * HTML style attribute
	 * @var string
	 */
	var $style = NULL;

	/**
 	 * String representing size of image - Choose from "full", "medium", "thumb", "tiny"
	 * @var string
	 */
	var $size = NULL;

	/**
	 * Use PHP getimagesize function to auto-calculate image size?
	 * @var boolean
	 */
	var $getimagesize = false;

	/**
	 * Check the src to make sure it looks like a valid image URL
	 * @var boolean
	 */
	var $validate_src = true;

	/**
	 * Handle being treated as a string by returning the HTML
	 * @return string HTML of image
	 */
	function __toString() {
		return $this->html();
	}


	function __construct( $atts = array() ) {

		$defaults = array(
			'width' => $this->width,
			'height' => $this->height,
			'alt' => $this->alt,
			'title' => $this->title,
			'size' => $this->size,
			'src' => $this->src,
			'class' => $this->class,
			'getimagesize' => false,
			'validate_src' => true
		);

		$atts = wp_parse_args( $atts, $defaults );

		foreach( $atts as $key => $val ) {
			$this->{$key} = $val;
		}

		$this->class = !empty( $this->class ) ? esc_attr( implode( ' ', (array)$this->class ) ) : $this->class;

		$this->set_image_size();

	}

	/**
	 * Verify that the src URL matches image patterns.
	 *
	 * Yes, images can not have extensions, but this is a basic check. To disable this,
	 * set `validate_src` to false when instantiating the object.
	 *
	 * @return boolean     True: matches pattern; False: does not match pattern.
	 */
	function validate_image_src() {

		if ( !$this->validate_src ) { return true; }

		$info = pathinfo( $this->src );

		/**
		 * @filter `gravityview_image_extensions` Extensions that GravityView recognizes as valid images to be shown in an `img` tag
		 * @param array $image_exts Default: `['jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico']`
		 */
		$image_exts = apply_filters( 'gravityview_image_extensions', array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico' ));

		return isset( $info['extension'] ) && in_array( strtolower( $info['extension'] ), $image_exts);
	}

	/**
	 * Get default widths and heights for image size.
	 *
	 * @return void
	 */
	public function set_image_size( $string = NULL, $width = NULL, $height = NULL ) {


		// If there is no width or height passed
		if ( empty( $width ) || empty( $height ) ) {

			// And there is no string size passed
			// 		And we want to get the image size using PHP
			if ( empty( $string ) && !empty( $this->getimagesize ) ) {

				$image_size = @getimagesize( $this->src );

				// If it didn't return a response, it may be a HTTPS/SSL error
				if ( empty( $image_size[0] ) ) {
					$image_size = @getimagesize( set_url_scheme( $this->src, 'http' ) );
				}

				if ( !empty( $image_size ) ) {
					list( $width, $height ) = $image_size;
				}

			}
			// Otherwise, we calculate based on the string size value
			else {

				/**
				 * @filter `gravityview_image_sizes` Modify the image size presets used by GravityView_Image class
				 * @param array $image_sizes Array of image sizes with the key being the size slug, and the value being an array with `width` and `height` defined, in pixels
				 */
				$image_sizes = apply_filters( 'gravityview_image_sizes', array(
					'tiny' => array('width' => 40, 'height' => 30),
					'small' => array('width' => 100, 'height' => 75),
					'medium' => array('width' => 250, 'height' => 188),
					'large' => array('width' => 448, 'height' => 336),
				) );

				switch( $this->size ) {
					case 'tiny':
						extract($image_sizes['tiny']);
						break;
					case 'small':
					case 's':
					case 'thumb':
						extract($image_sizes['small']);
						break;
					case 'm':
					case 'medium':
						extract($image_sizes['medium']);
						break;
					case 'large':
					case 'l':
						extract($image_sizes['large']);
						break;
					default:
						// Verify that the passed sizes are integers.
						$width = !empty( $width ) ? intval( $width ) : intval( $this->width );
						$height = !empty( $height ) ? intval( $height ) : intval( $this->height );
				}

			}

		}

		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Return the HTML tag for the image
	 * @return string HTML of the image
	 */
	public function html() {

		$html = '';

		if ( $this->validate_image_src() && ! empty( $this->src ) ) {
			$atts = '';
			foreach ( array( 'width', 'height', 'alt', 'title', 'class' ) as $attr ) {

				if ( empty( $this->{$attr} ) ) { continue; }

				$atts .= sprintf( ' %s="%s"', $attr, esc_attr( $this->{$attr} ) );
			}

			$html = sprintf( '<img src="%s"%s />', esc_url_raw( $this->src ), $atts );
		}

		/**
		 * @filter `gravityview_image_html` Filter the HTML image output
		 * @param string $html the generated image html
		 * @param GravityView_Image $this The current image object
		 */
		return apply_filters( 'gravityview_image_html', $html, $this );
	}
}
