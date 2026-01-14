<?php

/**
 * @group image
 * @group image-resizer
 */
class GravityView_Image_Resizer_Test extends GV_UnitTestCase {
	/**
	 * @var int
	 */
	private $form_id;

	/**
	 * @var array
	 */
	private $form;

	/**
	 * @var array
	 */
	private $entry;

	/**
	 * @var string
	 */
	private $image_path;

	/**
	 * @var string
	 */
	private $image_url;

	/**
	 * @var \GV\Template_Context
	 */
	private $context;

	/**
	 * @var \GV\GF_Field
	 */
	private $gv_field;

	/**
	 * @var GravityView_Image_Resizer
	 */
	private $resizer;

	/**
	 * @var array
	 */
	private $file_info;

	/**
	 * @var array
	 */
	private $filters = [];

	/**
	 * Sets up test data.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'GravityView_Image_Resizer', false ) ) {
			require_once GRAVITYVIEW_DIR . 'includes/class-gravityview-image-resizer.php';
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) || ! function_exists( 'imagepng' ) ) {
			$this->markTestSkipped( 'GD functions not available.' );
		}

		$this->form = [
			'title'  => 'Image Resizer Form',
			'fields' => [
				[
					'id'    => 1,
					'type'  => 'fileupload',
					'label' => 'File Upload Field',
				],
			],
		];

		$this->form_id = GFAPI::add_form( $this->form );

		$this->create_test_image( 800, 600 );

		$editor = wp_get_image_editor( $this->image_path );
		if ( is_wp_error( $editor ) ) {
			$this->cleanup_test_image();
			$this->markTestSkipped( 'No image editor available.' );
		}

		$this->entry = $this->factory->entry->create_and_get( [
			'form_id' => $this->form_id,
			'1'       => $this->image_url,
		] );

		$view_post = $this->factory->view->create_and_get( [ 'form_id' => $this->form_id ] );
		$view      = \GV\View::from_post( $view_post );

		$gv_form  = \GV\GF_Form::by_id( $this->form_id );
		$gv_entry = \GV\GF_Entry::from_entry( $this->entry );

		$this->gv_field = \GV\GF_Field::by_id( $gv_form, 1 );
		$this->gv_field->update_configuration( [
			'id'                     => 1,
			'label'                  => 'File Upload Field',
			'image_width'            => 250,
			'bypass_secure_download' => true,
		] );

		$this->context = \GV\Template_Context::from_template( [
			'view'    => $view,
			'entry'   => $gv_entry,
			'field'   => $this->gv_field,
			'request' => new \GV\Frontend_Request(),
		], [
			'value'         => $this->image_url,
			'display_value' => $this->image_url,
		] );

		$this->file_info = [
			'file_path'          => $this->image_url,
			'insecure_file_path' => $this->image_url,
			'is_secure'          => false,
		];

		$this->resizer = $this->get_resizer_instance();
	}

	/**
	 * Cleans up test data.
	 */
	public function tearDown(): void {
		foreach ( $this->filters as $filter ) {
			remove_filter( $filter['tag'], $filter['callback'], $filter['priority'] );
		}

		$this->filters = [];

		if ( ! empty( $this->entry['id'] ) && $this->resizer instanceof GravityView_Image_Resizer ) {
			$this->resizer->cleanup_entry_thumbnails( $this->entry['id'] );
		}

		if ( ! empty( $this->entry['id'] ) ) {
			GFAPI::delete_entry( $this->entry['id'] );
		}

		if ( ! empty( $this->form_id ) ) {
			GFAPI::delete_form( $this->form_id );
		}

		$this->cleanup_test_image();

		parent::tearDown();
	}

	/**
	 * Tests resizing and meta updates.
	 */
	public function test_resizes_image_and_updates_meta() {
		$image_atts = $this->get_image_atts( 250 );

		$result = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$this->assertNotEquals( $image_atts['src'], $result['src'] );
		$this->assertEquals( 250, $result['width'] );
		$this->assertEquals( 188, $result['height'] );

		$path = $this->path_from_url( $result['src'] );
		$this->assertNotEmpty( $path );
		$this->assertFileExists( $path );

		$meta = gform_get_meta( $this->entry['id'], GravityView_Image_Resizer::META_KEY );
		$this->assertIsArray( $meta );
		$this->assertArrayHasKey( 'field_1', $meta );
		$this->assertArrayHasKey( 'files', $meta['field_1'] );
		$this->assertArrayHasKey( 'idx_0', $meta['field_1']['files'] );
		$this->assertArrayHasKey( 'sizes', $meta['field_1']['files']['idx_0'] );
		$this->assertArrayHasKey( 'w500', $meta['field_1']['files']['idx_0']['sizes'] );
		$this->assertEquals( $result['src'], $meta['field_1']['files']['idx_0']['sizes']['w500']['url'] );
	}

	/**
	 * Tests cache usage on repeated renders.
	 */
	public function test_resize_uses_cache_for_second_call() {
		$image_atts = $this->get_image_atts( 250 );

		$first = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );
		$second = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$this->assertEquals( $first['src'], $second['src'] );
		$this->assertNotEmpty( $second['src'] );
	}

	/**
	 * Tests secure URL bypass behavior.
	 */
	public function test_resize_skips_when_secure_and_not_bypassed() {
		$this->gv_field->update_configuration( [ 'bypass_secure_download' => false ] );

		$file_info = $this->file_info;
		$file_info['is_secure'] = true;

		$image_atts = $this->get_image_atts( 250 );

		$result = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $file_info, 0 );

		$this->assertEquals( $image_atts['src'], $result['src'] );
		$this->assertEmpty( gform_get_meta( $this->entry['id'], GravityView_Image_Resizer::META_KEY ) );
	}

	/**
	 * Tests skipping when width is not provided.
	 */
	public function test_resize_skips_when_width_not_set() {
		$image_atts = $this->get_image_atts( 0 );

		$result = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$this->assertEquals( $image_atts['src'], $result['src'] );
		$this->assertEmpty( gform_get_meta( $this->entry['id'], GravityView_Image_Resizer::META_KEY ) );
	}

	/**
	 * Tests cleanup of resized files and meta.
	 */
	public function test_cleanup_deletes_files_and_meta() {
		$image_atts = $this->get_image_atts( 250 );
		$result     = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$path = $this->path_from_url( $result['src'] );
		$this->assertFileExists( $path );

		$this->resizer->cleanup_entry_thumbnails( $this->entry['id'] );

		$this->assertFileDoesNotExist( $path );
		$this->assertEmpty( gform_get_meta( $this->entry['id'], GravityView_Image_Resizer::META_KEY ) );
	}

	/**
	 * Tests rejecting storage directories outside uploads.
	 */
	public function test_storage_outside_uploads_is_rejected() {
		$upload_dir  = wp_upload_dir();
		$outside_dir = trailingslashit( dirname( $upload_dir['basedir'] ) ) . 'gv-outside';

		$this->add_filter_with_cleanup( 'gk/gravityview/image-resize/storage-dir', function() use ( $outside_dir ) {
			return $outside_dir;
		} );

		$this->add_filter_with_cleanup( 'gk/gravityview/image-resize/storage-url', function() {
			return 'http://example.com/gv-outside';
		} );

		$image_atts = $this->get_image_atts( 250 );

		$result = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$this->assertEquals( $image_atts['src'], $result['src'] );
		$this->assertFalse( is_dir( $outside_dir ) );
		$this->assertEmpty( gform_get_meta( $this->entry['id'], GravityView_Image_Resizer::META_KEY ) );
	}

	/**
	 * Tests skipping when a recent failure is recorded.
	 */
	public function test_resize_skips_when_recent_failure_exists() {
		$image_atts = $this->get_image_atts( 250 );

		$source_sig = $this->call_private_method( 'get_source_signature', [ $this->image_path ] );
		$failure_key = $this->call_private_method(
			'get_failure_key',
			[ $this->entry['id'], 1, 'idx_0', 500, $source_sig ]
		);

		set_transient( $failure_key, [ 'message' => 'failure' ], GravityView_Image_Resizer::FAIL_TTL );

		$result = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$this->assertEquals( $image_atts['src'], $result['src'] );

		delete_transient( $failure_key );
	}

	/**
	 * Tests retina disabling via filter.
	 */
	public function test_retina_can_be_disabled() {
		$this->add_filter_with_cleanup( 'gk/gravityview/image-resize/retina', '__return_false' );

		$image_atts = $this->get_image_atts( 250 );

		$result = $this->resizer->filter_image_atts( $image_atts, [], $this->context, $this->file_info, 0 );

		$meta = gform_get_meta( $this->entry['id'], GravityView_Image_Resizer::META_KEY );
		$this->assertArrayHasKey( 'w250', $meta['field_1']['files']['idx_0']['sizes'] );
		$this->assertEquals( 250, $result['width'] );
	}

	/**
	 * Builds a resizer instance without invoking hooks.
	 */
	private function get_resizer_instance() {
		$reflection = new ReflectionClass( GravityView_Image_Resizer::class );

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Builds image attributes for rendering.
	 */
	private function get_image_atts( $width ) {
		$atts = [
			'src'   => $this->image_url,
			'class' => 'gv-image',
			'alt'   => 'Test Image',
		];

		if ( $width > 0 ) {
			$atts['width'] = $width;
		} else {
			$atts['width'] = 0;
		}

		return $atts;
	}

	/**
	 * Creates a temporary test image.
	 */
	private function create_test_image( $width, $height ) {
		$upload_path = GFFormsModel::get_upload_path( $this->form_id );
		$upload_url  = GFFormsModel::get_upload_url( $this->form_id );

		wp_mkdir_p( $upload_path );

		$this->image_path = trailingslashit( $upload_path ) . 'gv-resizer-test.png';
		$this->image_url  = trailingslashit( $upload_url ) . 'gv-resizer-test.png';

		$image = imagecreatetruecolor( $width, $height );
		$white = imagecolorallocate( $image, 255, 255, 255 );
		imagefill( $image, 0, 0, $white );
		imagepng( $image, $this->image_path );
		imagedestroy( $image );
	}

	/**
	 * Removes the temporary test image.
	 */
	private function cleanup_test_image() {
		if ( $this->image_path && is_file( $this->image_path ) ) {
			unlink( $this->image_path );
		}
	}

	/**
	 * Converts an uploads URL to a local path.
	 */
	private function path_from_url( $url ) {
		$upload_dir = wp_upload_dir();

		if ( empty( $upload_dir['baseurl'] ) || empty( $upload_dir['basedir'] ) ) {
			return '';
		}

		return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
	}

	/**
	 * Adds a filter and tracks it for cleanup.
	 */
	private function add_filter_with_cleanup( $tag, $callback, $priority = 10 ) {
		add_filter( $tag, $callback, $priority );
		$this->filters[] = [
			'tag'      => $tag,
			'callback' => $callback,
			'priority' => $priority,
		];
	}

	/**
	 * Invokes a private method using reflection.
	 */
	private function call_private_method( $method, array $args = [] ) {
		$reflection = new ReflectionMethod( GravityView_Image_Resizer::class, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $this->resizer, $args );
	}
}
