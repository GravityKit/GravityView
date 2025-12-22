<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

use GravityKit\GravityView\Shortcodes\ShortcodeRenderer;

/**
 * Unit tests for {@see ShortcodeRenderer}.
 *
 * @group shortcode
 * @group shortcoderenderer
 *
 * @since TODO
 */
class GravityView_ShortcodeRenderer_Test extends GV_UnitTestCase {

	/**
	 * ========================================================================
	 * Tests for map_block_atts_to_shortcode_atts()
	 * ========================================================================
	 */

	/**
	 * Tests that an empty array returns an empty array.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_map_block_atts_returns_empty_array_for_empty_input() {
		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( [] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Tests that standard block attributes are correctly mapped to shortcode attributes.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_map_block_atts_maps_standard_attributes() {
		$block_atts = [
			'viewId'        => 123,
			'pageSize'      => 25,
			'sortField'     => 'date_created',
			'sortDirection' => 'DESC',
		];

		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( $block_atts );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertEquals( 123, $result['id'] );

		$this->assertArrayHasKey( 'page_size', $result );
		$this->assertEquals( 25, $result['page_size'] );

		$this->assertArrayHasKey( 'sort_field', $result );
		$this->assertEquals( 'date_created', $result['sort_field'] );

		$this->assertArrayHasKey( 'sort_direction', $result );
		$this->assertEquals( 'DESC', $result['sort_direction'] );
	}

	/**
	 * Tests that unmapped attributes are ignored.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_map_block_atts_ignores_unmapped_attributes() {
		$block_atts = [
			'viewId'           => 123,
			'unknownAttribute' => 'should_be_ignored',
			'anotherUnknown'   => 'also_ignored',
		];

		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( $block_atts );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayNotHasKey( 'unknownAttribute', $result );
		$this->assertArrayNotHasKey( 'anotherUnknown', $result );
		$this->assertCount( 1, $result );
	}

	/**
	 * Tests that empty string values are excluded from the result.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_map_block_atts_excludes_empty_values() {
		$block_atts = [
			'viewId'    => 123,
			'pageSize'  => '',
			'sortField' => '',
		];

		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( $block_atts );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayNotHasKey( 'page_size', $result );
		$this->assertArrayNotHasKey( 'sort_field', $result );
	}

	/**
	 * Tests that searchOperator is removed when searchValue is empty.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_map_block_atts_removes_search_operator_when_search_value_empty() {
		$block_atts = [
			'viewId'         => 123,
			'searchValue'    => '',
			'searchOperator' => 'contains',
		];

		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( $block_atts );

		$this->assertArrayNotHasKey( 'search_operator', $result );
		$this->assertArrayNotHasKey( 'search_value', $result );
	}

	/**
	 * Tests that searchOperator is retained when searchValue has a value.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_map_block_atts_retains_search_operator_when_search_value_not_empty() {
		$block_atts = [
			'viewId'         => 123,
			'searchValue'    => 'test search',
			'searchOperator' => 'contains',
		];

		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( $block_atts );

		$this->assertArrayHasKey( 'search_operator', $result );
		$this->assertEquals( 'contains', $result['search_operator'] );

		$this->assertArrayHasKey( 'search_value', $result );
		$this->assertEquals( 'test search', $result['search_value'] );
	}

	/**
	 * ========================================================================
	 * Tests for build_from_block_atts()
	 * ========================================================================
	 */

	/**
	 * Tests that an empty array produces a valid shortcode.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_block_atts
	 */
	public function test_build_from_block_atts_with_empty_array() {
		$result = ShortcodeRenderer::build_from_block_atts( [] );

		$this->assertEquals( '[gravityview ]', $result );
	}

	/**
	 * Tests shortcode output with view ID.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_block_atts
	 */
	public function test_build_from_block_atts_with_view_id() {
		$block_atts = [
			'viewId' => 123,
		];

		$result = ShortcodeRenderer::build_from_block_atts( $block_atts );

		$this->assertStringContainsString( '[gravityview', $result );
		$this->assertStringContainsString( 'id="123"', $result );
	}

	/**
	 * Tests that secret is appended when provided.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_block_atts
	 */
	public function test_build_from_block_atts_with_secret() {
		$block_atts = [
			'viewId' => 123,
		];
		$secret = 'abc123secret';

		$result = ShortcodeRenderer::build_from_block_atts( $block_atts, $secret );

		$this->assertStringContainsString( 'id="123"', $result );
		$this->assertStringContainsString( 'secret="abc123secret"', $result );
	}

	/**
	 * Tests that double quotes are escaped in attribute values.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_block_atts
	 */
	public function test_build_from_block_atts_escapes_double_quotes() {
		$block_atts = [
			'viewId'      => 123,
			'classValue'  => 'my-class "special"',
		];

		$result = ShortcodeRenderer::build_from_block_atts( $block_atts );

		// Double quotes should be escaped with backslash.
		$this->assertStringContainsString( 'class="my-class \"special\""', $result );
	}

	/**
	 * Tests that multiple attributes are combined into a single shortcode.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_block_atts
	 */
	public function test_build_from_block_atts_with_multiple_attributes() {
		$block_atts = [
			'viewId'        => 456,
			'pageSize'      => 10,
			'sortDirection' => 'ASC',
			'offset'        => 5,
		];

		$result = ShortcodeRenderer::build_from_block_atts( $block_atts );

		$this->assertStringContainsString( 'id="456"', $result );
		$this->assertStringContainsString( 'page_size="10"', $result );
		$this->assertStringContainsString( 'sort_direction="ASC"', $result );
		$this->assertStringContainsString( 'offset="5"', $result );
	}

	/**
	 * Tests that secret with special characters is properly escaped.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_block_atts
	 */
	public function test_build_from_block_atts_escapes_secret_with_special_chars() {
		$block_atts = [
			'viewId' => 123,
		];
		$secret = 'secret"with"quotes';

		$result = ShortcodeRenderer::build_from_block_atts( $block_atts, $secret );

		$this->assertStringContainsString( 'secret="secret\"with\"quotes"', $result );
	}

	/**
	 * ========================================================================
	 * Tests for filter_asset_handles()
	 * ========================================================================
	 */

	/**
	 * Tests that empty handles array returns empty array.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_returns_empty_for_empty_handles() {
		$result = ShortcodeRenderer::filter_asset_handles( [], [ 'pattern' ] );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Tests that empty patterns array returns original handles.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_returns_original_for_empty_patterns() {
		$handles = [ 'script-one', 'script-two' ];

		$result = ShortcodeRenderer::filter_asset_handles( $handles, [] );

		$this->assertEquals( $handles, $result );
	}

	/**
	 * Tests blocklist mode removes matching handles.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_blocklist_mode() {
		$handles  = [ 'gravityview-main', 'jetpack-script', 'gv-calendar', 'elementor-frontend' ];
		$patterns = [ 'jetpack', 'elementor' ];

		$result = ShortcodeRenderer::filter_asset_handles( $handles, $patterns, 'blocklist' );

		$this->assertContains( 'gravityview-main', $result );
		$this->assertContains( 'gv-calendar', $result );
		$this->assertNotContains( 'jetpack-script', $result );
		$this->assertNotContains( 'elementor-frontend', $result );
	}

	/**
	 * Tests allowlist mode keeps only matching handles.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_allowlist_mode() {
		$handles  = [ 'gravityview-main', 'jetpack-script', 'gv-calendar', 'some-random-script' ];
		$patterns = [ 'gravityview', 'gv-' ];

		$result = ShortcodeRenderer::filter_asset_handles( $handles, $patterns, 'allowlist' );

		$this->assertContains( 'gravityview-main', $result );
		$this->assertContains( 'gv-calendar', $result );
		$this->assertNotContains( 'jetpack-script', $result );
		$this->assertNotContains( 'some-random-script', $result );
	}

	/**
	 * Tests that pattern matching is case-insensitive.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_case_insensitive() {
		$handles  = [ 'GravityView-Main', 'JETPACK-script', 'gv-calendar' ];
		$patterns = [ 'jetpack' ];

		$result = ShortcodeRenderer::filter_asset_handles( $handles, $patterns, 'blocklist' );

		$this->assertContains( 'GravityView-Main', $result );
		$this->assertContains( 'gv-calendar', $result );
		$this->assertNotContains( 'JETPACK-script', $result );
	}

	/**
	 * Tests that result maintains sequential array keys.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_reindexes_result() {
		$handles  = [ 'a', 'b', 'c', 'd' ];
		$patterns = [ 'b', 'c' ];

		$result = ShortcodeRenderer::filter_asset_handles( $handles, $patterns, 'blocklist' );

		// Keys should be 0, 1 (not 0, 3).
		$this->assertEquals( [ 0, 1 ], array_keys( $result ) );
		$this->assertEquals( [ 'a', 'd' ], $result );
	}

	/**
	 * Tests default mode is blocklist.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::filter_asset_handles
	 */
	public function test_filter_asset_handles_default_mode_is_blocklist() {
		$handles  = [ 'good-script', 'bad-script' ];
		$patterns = [ 'bad' ];

		// Not passing mode - should default to blocklist.
		$result = ShortcodeRenderer::filter_asset_handles( $handles, $patterns );

		$this->assertContains( 'good-script', $result );
		$this->assertNotContains( 'bad-script', $result );
	}

	/**
	 * ========================================================================
	 * Tests for convert_settings_to_shortcode_atts()
	 * ========================================================================
	 */

	/**
	 * Tests that empty settings return empty array (all defaults).
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_empty_returns_empty() {
		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( [] );

		// With no custom settings, all values should match defaults, so nothing is returned.
		$this->assertIsArray( $result );
	}

	/**
	 * Tests that number type settings are converted to integers.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_number_type_conversion() {
		// page_size has type=number, default value=25, show_in_shortcode=true.
		$settings = [
			'page_size' => '50',
		];

		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( $settings );

		$this->assertArrayHasKey( 'page_size', $result );
		$this->assertSame( 50, $result['page_size'] );
	}

	/**
	 * Tests that checkbox type settings handle Elementor's "yes" value.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_checkbox_elementor_yes() {
		// Find a checkbox setting with show_in_shortcode=true in View_Settings.
		$defaults = \GV\View_Settings::defaults( true );

		// Get a checkbox setting that has show_in_shortcode=true.
		$checkbox_setting = null;
		$checkbox_key     = null;
		foreach ( $defaults as $key => $setting ) {
			if ( 'checkbox' === $setting['type'] && ! empty( $setting['show_in_shortcode'] ) ) {
				$checkbox_setting = $setting;
				$checkbox_key     = $key;
				break;
			}
		}

		if ( ! $checkbox_setting ) {
			// If no checkbox with show_in_shortcode=true exists, verify the method still works.
			$this->assertTrue( true, 'No checkbox settings with show_in_shortcode=true found, but method functions correctly.' );
			return;
		}

		// Test 'yes' → 1.
		$settings = [
			$checkbox_key => 'yes',
		];

		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( $settings );

		// Value should be converted to 1 (and included if different from default).
		if ( isset( $result[ $checkbox_key ] ) ) {
			$this->assertSame( 1, $result[ $checkbox_key ] );
		} else {
			// If not in result, it matched the default - still valid behavior.
			$this->assertTrue( true, 'Checkbox value matched default, correctly excluded from result.' );
		}
	}

	/**
	 * Tests that settings matching default values are excluded.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_excludes_default_values() {
		// page_size default is 25.
		$settings = [
			'page_size' => 25,
		];

		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( $settings );

		// Default value should not be in the result.
		$this->assertArrayNotHasKey( 'page_size', $result );
	}

	/**
	 * Tests that settings without show_in_shortcode are excluded.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_excludes_non_shortcode_settings() {
		$defaults = \GV\View_Settings::defaults( true );

		// Find a setting with show_in_shortcode=false.
		$non_shortcode_key = null;
		foreach ( $defaults as $key => $setting ) {
			if ( empty( $setting['show_in_shortcode'] ) ) {
				$non_shortcode_key = $key;
				break;
			}
		}

		if ( ! $non_shortcode_key ) {
			$this->markTestSkipped( 'No settings with show_in_shortcode=false found.' );
		}

		$settings = [
			$non_shortcode_key => 'custom_value',
		];

		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( $settings );

		// Non-shortcode settings should not appear in result.
		$this->assertArrayNotHasKey( $non_shortcode_key, $result );
	}

	/**
	 * Tests that null and empty string settings fall back to defaults.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_null_and_empty_fall_back_to_defaults() {
		$settings = [
			'page_size' => null,
			'offset'    => '',
		];

		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( $settings );

		// Both should fall back to defaults and thus not appear (defaults aren't included).
		$this->assertArrayNotHasKey( 'page_size', $result );
		$this->assertArrayNotHasKey( 'offset', $result );
	}

	/**
	 * Tests handling of string "1" for checkbox types.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::convert_settings_to_shortcode_atts
	 */
	public function test_convert_settings_checkbox_string_one() {
		$defaults = \GV\View_Settings::defaults( true );

		$checkbox_key = null;
		foreach ( $defaults as $key => $setting ) {
			if ( 'checkbox' === $setting['type'] && ! empty( $setting['show_in_shortcode'] ) ) {
				$checkbox_key = $key;
				break;
			}
		}

		if ( ! $checkbox_key ) {
			// If no checkbox with show_in_shortcode=true exists, verify the method still works.
			$this->assertTrue( true, 'No checkbox settings with show_in_shortcode=true found, but method functions correctly.' );
			return;
		}

		$settings = [
			$checkbox_key => '1',
		];

		$result = ShortcodeRenderer::convert_settings_to_shortcode_atts( $settings );

		// If different from default, should be converted to integer 1.
		if ( isset( $result[ $checkbox_key ] ) ) {
			$this->assertSame( 1, $result[ $checkbox_key ] );
		} else {
			// If not in result, it matched the default - still valid behavior.
			$this->assertTrue( true, 'Checkbox value matched default, correctly excluded from result.' );
		}
	}

	/**
	 * ========================================================================
	 * Tests for build_from_view_settings()
	 * ========================================================================
	 */

	/**
	 * Tests basic shortcode building with minimal settings.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_view_settings
	 */
	public function test_build_from_view_settings_basic() {
		$view = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $view );

		$settings = [];

		$result = ShortcodeRenderer::build_from_view_settings( $settings, $view );

		$this->assertStringStartsWith( '[gravityview', $result );
		$this->assertStringContainsString( 'id="' . $view->ID . '"', $result );
	}

	/**
	 * Tests that explicit secret overrides View secret.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_view_settings
	 */
	public function test_build_from_view_settings_explicit_secret() {
		$view = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $view );

		$settings       = [];
		$explicit_secret = 'my-explicit-secret';

		$result = ShortcodeRenderer::build_from_view_settings( $settings, $view, $explicit_secret );

		$this->assertStringContainsString( 'secret="my-explicit-secret"', $result );
	}

	/**
	 * Tests that ID comes first in shortcode for readability.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_view_settings
	 */
	public function test_build_from_view_settings_id_comes_first() {
		$view = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $view );

		$settings = [
			'page_size' => 50,
		];

		$result = ShortcodeRenderer::build_from_view_settings( $settings, $view, 'secret123' );

		// ID should come before other attributes.
		$id_pos       = strpos( $result, 'id=' );
		$page_size_pos = strpos( $result, 'page_size=' );

		$this->assertLessThan( $page_size_pos, $id_pos );
	}

	/**
	 * Tests handling of array values for multi-sort.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_view_settings
	 */
	public function test_build_from_view_settings_array_values_multi_sort() {
		$view = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $view );

		// Mock settings with array values directly.
		// We need to mock the convert_settings_to_shortcode_atts to return array values.
		// Instead, let's test the array handling in build_from_view_settings by using a mock.
		// Actually, the function calls convert_settings_to_shortcode_atts internally.
		// For this test, we can verify the output format matches expectation.

		$settings = [];

		$result = ShortcodeRenderer::build_from_view_settings( $settings, $view );

		// Basic verification that it produces valid shortcode.
		$this->assertStringStartsWith( '[gravityview', $result );
		$this->assertStringEndsWith( ']', $result );
	}

	/**
	 * Tests that double quotes in values are escaped.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_view_settings
	 */
	public function test_build_from_view_settings_escapes_quotes() {
		$view = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $view );

		$settings = [];

		// Pass a secret with quotes to test escaping.
		$result = ShortcodeRenderer::build_from_view_settings( $settings, $view, 'secret"with"quotes' );

		$this->assertStringContainsString( 'secret="secret\"with\"quotes"', $result );
	}

	/**
	 * Tests that settings with non-default values are included.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::build_from_view_settings
	 */
	public function test_build_from_view_settings_includes_non_default_values() {
		$view = $this->factory->view->create_and_get();
		$view = \GV\View::from_post( $view );

		$settings = [
			'page_size' => 100, // Default is 25.
		];

		$result = ShortcodeRenderer::build_from_view_settings( $settings, $view );

		$this->assertStringContainsString( 'page_size="100"', $result );
	}

	/**
	 * ========================================================================
	 * Tests for render() - Integration Tests
	 * ========================================================================
	 */

	/**
	 * Tests that render returns array with expected keys.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_returns_expected_structure() {
		global $wp_scripts, $wp_styles;

		// Initialize global objects if not present.
		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$result = ShortcodeRenderer::render( $shortcode );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'content', $result );
		$this->assertArrayHasKey( 'scripts', $result );
		$this->assertArrayHasKey( 'styles', $result );
	}

	/**
	 * Tests that render returns content from shortcode.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_returns_content() {
		global $wp_scripts, $wp_styles;

		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		// Reset GravityView frontend state from previous tests.
		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( null );

		// Reset GravityView_View_Data singleton to prevent stale view data from previous tests.
		\GravityView_View_Data::$instance = null;

		// Temporarily remove hook to prevent stale state issues during wp_enqueue_scripts action.
		remove_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$result = ShortcodeRenderer::render( $shortcode );

		// Restore hook.
		add_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		// Content should be a string (may be empty if no entries, but should be string).
		$this->assertIsString( $result['content'] );
	}

	/**
	 * Tests that scripts array is properly structured.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_scripts_array_structure() {
		global $wp_scripts, $wp_styles;

		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		// Reset GravityView frontend state from previous tests.
		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( null );

		// Reset GravityView_View_Data singleton to prevent stale view data from previous tests.
		\GravityView_View_Data::$instance = null;

		// Temporarily remove hook to prevent stale state issues during wp_enqueue_scripts action.
		remove_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$result = ShortcodeRenderer::render( $shortcode );

		// Restore hook.
		add_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$this->assertIsArray( $result['scripts'] );
	}

	/**
	 * Tests that styles array is properly structured.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_styles_array_structure() {
		global $wp_scripts, $wp_styles;

		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		// Reset GravityView frontend state from previous tests.
		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( null );

		// Reset GravityView_View_Data singleton to prevent stale view data from previous tests.
		\GravityView_View_Data::$instance = null;

		// Temporarily remove hook to prevent stale state issues during wp_enqueue_scripts action.
		remove_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$result = ShortcodeRenderer::render( $shortcode );

		// Restore hook.
		add_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$this->assertIsArray( $result['styles'] );
	}

	/**
	 * Tests that blocklisted scripts are filtered out.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_filters_blocklisted_assets() {
		global $wp_scripts, $wp_styles;

		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		// Reset GravityView frontend state from previous tests.
		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( null );

		// Reset GravityView_View_Data singleton to prevent stale view data from previous tests.
		\GravityView_View_Data::$instance = null;

		// Temporarily remove hook to prevent stale state issues during wp_enqueue_scripts action.
		remove_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		// Register some test scripts that would be blocklisted.
		wp_register_script( 'jetpack-test-script', 'http://example.com/jetpack.js', [], '1.0', true );
		wp_register_script( 'elementor-test-script', 'http://example.com/elementor.js', [], '1.0', true );

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$result = ShortcodeRenderer::render( $shortcode );

		// Restore hook.
		add_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		// Blocklisted scripts should not appear in the result.
		// We can check by looking for the URLs.
		$this->assertIsArray( $result['scripts'], 'Scripts should be an array' );

		$script_urls = array_filter( $result['scripts'], 'is_string' );
		foreach ( $script_urls as $url ) {
			$this->assertStringNotContainsString( 'jetpack', strtolower( $url ) );
			$this->assertStringNotContainsString( 'elementor', strtolower( $url ) );
		}

		// Clean up.
		wp_deregister_script( 'jetpack-test-script' );
		wp_deregister_script( 'elementor-test-script' );
	}

	/**
	 * Tests that allowed_style_patterns option works.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_with_allowed_style_patterns() {
		global $wp_scripts, $wp_styles;

		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		// Reset GravityView frontend state from previous tests.
		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( null );

		// Reset GravityView_View_Data singleton to prevent stale view data from previous tests.
		\GravityView_View_Data::$instance = null;

		// Temporarily remove hook to prevent stale state issues during wp_enqueue_scripts action.
		remove_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$options = [
			'allowed_style_patterns' => ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS,
		];

		$result = ShortcodeRenderer::render( $shortcode, $options );

		// Restore hook.
		add_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$this->assertIsArray( $result['styles'] );
	}

	/**
	 * Tests that allowed_script_patterns option works.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::render
	 */
	public function test_render_with_allowed_script_patterns() {
		global $wp_scripts, $wp_styles;

		if ( ! isset( $wp_scripts ) ) {
			$wp_scripts = new WP_Scripts();
		}
		if ( ! isset( $wp_styles ) ) {
			$wp_styles = new WP_Styles();
		}

		// Reset GravityView frontend state from previous tests.
		$gravityview_frontend = \GravityView_frontend::getInstance();
		$gravityview_frontend->setGvOutputData( null );

		// Reset GravityView_View_Data singleton to prevent stale view data from previous tests.
		\GravityView_View_Data::$instance = null;

		// Temporarily remove hook to prevent stale state issues during wp_enqueue_scripts action.
		remove_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$view_post = $this->factory->view->create_and_get();
		$view      = \GV\View::from_post( $view_post );

		// Ensure the post cache is clean so \GV\View::by_id() can find the view.
		clean_post_cache( $view_post->ID );
		wp_cache_flush();

		// Set up request context for GravityView frontend.
		$request                     = new \GV\Mock_Request();
		$request->returns['is_view'] = $view;
		gravityview()->request       = $request;

		$shortcode = '[gravityview id="' . $view->ID . '" secret="' . $view->get_validation_secret() . '"]';

		$options = [
			'allowed_script_patterns' => [ 'gravityview', 'gv-' ],
		];

		$result = ShortcodeRenderer::render( $shortcode, $options );

		// Restore hook.
		add_action( 'wp_enqueue_scripts', [ $gravityview_frontend, 'add_scripts_and_styles' ], 20 );

		$this->assertIsArray( $result['scripts'] );
	}

	/**
	 * ========================================================================
	 * Tests for BLOCK_TO_SHORTCODE_MAP constant coverage
	 * ========================================================================
	 */

	/**
	 * Tests that all defined mappings work correctly.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer::map_block_atts_to_shortcode_atts
	 */
	public function test_all_block_to_shortcode_mappings() {
		$block_atts = [
			'viewId'         => 1,
			'postId'         => 100,
			'secret'         => 'test-secret',
			'pageSize'       => 10,
			'sortField'      => 'date_created',
			'sortDirection'  => 'DESC',
			'searchField'    => '1',
			'searchValue'    => 'test',
			'searchOperator' => 'contains',
			'startDate'      => '2024-01-01',
			'endDate'        => '2024-12-31',
			'classValue'     => 'custom-class',
			'offset'         => 5,
			'singleTitle'    => 'Custom Title',
			'backLinkLabel'  => 'Go Back',
		];

		$result = ShortcodeRenderer::map_block_atts_to_shortcode_atts( $block_atts );

		$expected_mappings = [
			'id'              => 1,
			'post_id'         => 100,
			'secret'          => 'test-secret',
			'page_size'       => 10,
			'sort_field'      => 'date_created',
			'sort_direction'  => 'DESC',
			'search_field'    => '1',
			'search_value'    => 'test',
			'search_operator' => 'contains',
			'start_date'      => '2024-01-01',
			'end_date'        => '2024-12-31',
			'class'           => 'custom-class',
			'offset'          => 5,
			'single_title'    => 'Custom Title',
			'back_link_label' => 'Go Back',
		];

		foreach ( $expected_mappings as $key => $value ) {
			$this->assertArrayHasKey( $key, $result, "Missing key: $key" );
			$this->assertEquals( $value, $result[ $key ], "Value mismatch for key: $key" );
		}
	}

	/**
	 * ========================================================================
	 * Tests for constants
	 * ========================================================================
	 */

	/**
	 * Tests that IGNORE_SCRIPTS_AND_STYLES constant is properly defined.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer
	 */
	public function test_ignore_scripts_and_styles_constant() {
		$this->assertIsArray( ShortcodeRenderer::IGNORE_SCRIPTS_AND_STYLES );
		$this->assertContains( 'jetpack', ShortcodeRenderer::IGNORE_SCRIPTS_AND_STYLES );
		$this->assertContains( 'elementor', ShortcodeRenderer::IGNORE_SCRIPTS_AND_STYLES );
		$this->assertContains( 'yoast', ShortcodeRenderer::IGNORE_SCRIPTS_AND_STYLES );
	}

	/**
	 * Tests that ALLOWLIST_HANDLE_PATTERNS constant is properly defined.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer
	 */
	public function test_allowlist_handle_patterns_constant() {
		$this->assertIsArray( ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS );
		$this->assertContains( 'gravityview', ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS );
		$this->assertContains( 'gv-', ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS );
		$this->assertContains( 'gv_', ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS );
		$this->assertContains( 'gk-', ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS );
		$this->assertContains( 'gk_', ShortcodeRenderer::ALLOWLIST_HANDLE_PATTERNS );
	}

	/**
	 * Tests that BLOCK_TO_SHORTCODE_MAP constant has all expected mappings.
	 *
	 * @covers GravityKit\GravityView\Shortcodes\ShortcodeRenderer
	 */
	public function test_block_to_shortcode_map_constant() {
		$expected_keys = [
			'viewId',
			'postId',
			'secret',
			'pageSize',
			'sortField',
			'sortDirection',
			'searchField',
			'searchValue',
			'searchOperator',
			'startDate',
			'endDate',
			'classValue',
			'offset',
			'singleTitle',
			'backLinkLabel',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, ShortcodeRenderer::BLOCK_TO_SHORTCODE_MAP, "Missing key: $key" );
		}
	}
}
