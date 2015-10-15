<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group merge_tags
 * @since 1.15
 */
class GravityView_Merge_Tags_Test extends GV_UnitTestCase {

	/**
	 * @since 1.15
	 * @covers GravityView_Merge_Tags::replace_variables()
	 */
	function test_replace_variables() {

		$basic_string = 'basic string';
		$_GET['string'] = $basic_string;
		$this->assertEquals( $basic_string, GravityView_Merge_Tags::replace_variables( '{get:string}' ) );

		$basic_string = 'basic string, with commas';
		$_GET['string'] = $basic_string;
		$this->assertEquals( $basic_string, GravityView_Merge_Tags::replace_variables( '{get:string}' ) );

		$esc_html_string = '& < > \' " <script>tag</script>';
		$_GET['string'] = $esc_html_string;

		## DEFAULT: esc_html ESCAPED
		$this->assertEquals( esc_html( $esc_html_string ), GravityView_Merge_Tags::replace_variables( '{get:string}' ) );

		## TEST merge_tags/get/esc_html FILTER
		add_filter( 'gravityview/merge_tags/get/esc_html/string', '__return_false' );
		$this->assertEquals( $esc_html_string, GravityView_Merge_Tags::replace_variables( '{get:string}' ) );
		remove_filter( 'gravityview/merge_tags/get/esc_html/string', '__return_false' );

		## TEST merge_tags/get/value/string FILTER
		function __return_example() { return 'example'; }
		add_filter('gravityview/merge_tags/get/value/string', '__return_example' );
		$this->assertEquals( 'example', GravityView_Merge_Tags::replace_variables( '{get:string}' ) );
	}
}
