<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group merge_tags
 * @since 1.15
 */
class GravityView_Merge_Tags_Test extends GV_UnitTestCase {

	/**
	 * @since 1.15.1
	 * @covers GravityView_Merge_Tags::replace_user_variables_created_by()
	 * @covers GravityView_Merge_Tags::replace_variables()
	 * @covers GravityView_Merge_Tags::replace_gv_merge_tags()
	 */
	function test_replace_user_variables_created_by() {
		$user = $this->factory->user->create_and_get();
		$form = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id' => $form['id'],
		) );

		$tests = array(
			'{created_by:ID}' => $user->ID,
		    '{created_by:roles}' => implode( ', ', $user->roles ),
			'{created_by:first_name}' => $user->user_firstname,
		);

		foreach ( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ) );
		}

	}

	/**
	 * @since 1.15
	 * @covers GravityView_Merge_Tags::replace_get_variables()
	 * @covers GravityView_Merge_Tags::replace_variables()
	 * @covers GravityView_Merge_Tags::replace_gv_merge_tags()
	 */
	function test_replace_get_variables() {

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
		remove_filter('gravityview/merge_tags/get/value/string', '__return_example' );

		### TEST gravityview/merge_tags/do_replace_variables FILTER
		add_filter( 'gravityview/merge_tags/do_replace_variables', '__return_false' );
		$this->assertEquals( '{get:string}', GravityView_Merge_Tags::replace_variables( '{get:string}' ) );
		remove_filter( 'gravityview/merge_tags/do_replace_variables', '__return_false' );
	}

	/**
	 * We want to make sure that GravityView doesn't mess with Texas
	 * @since 1.15.1
	 */
	function test_gf_merge_tags() {

		remove_all_filters( 'gform_pre_replace_merge_tags' );
		remove_all_filters( 'gform_merge_tag_filter' );

		global $post;

		$form = $this->factory->form->create_and_get();
		$post = $this->factory->post->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'post_id' => $post->ID, 'form_id' => $form['id'] ) );

		$tests = array(
			'{form_title}' => $form['title'],
			'{entry_id}' => $entry['id'],
			'{entry_url}' => get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=gf_entries&view=entry&id=' . $form['id'] . '&lid=' . rgar( $entry, 'id' ),
			'{admin_email}' => get_bloginfo( 'admin_email' ),
			'{post_id}' => $post->ID,
			'{embed_post:post_title}' => $post->post_title,
		);

		foreach( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ) );
			$this->assertEquals( urlencode( $expected ), GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, true ) );

			remove_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10 );
			$this->assertEquals( $expected, GFCommon::replace_variables( $merge_tag, $form, $entry ) );
			$this->assertEquals( urlencode( $expected ), GFCommon::replace_variables( $merge_tag, $form, $entry, true ) );
			add_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10, 7 );
		}

		wp_reset_postdata();
	}
}
