<?php

defined( 'DOING_GRAVITYVIEW_TESTS' ) || exit;

/**
 * @group merge_tags
 * @since 1.15
 */
class GravityView_Merge_Tags_Test extends GV_UnitTestCase {

	/**
	 * @since 1.15.1
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
	 * @since 1.21.4
	 * @covers GravityView_Merge_Tags::replace_current_post()
	 * @todo Needs test for archive page
	 */
	function test_replace_current_post() {
		global $post;

		$user  = $this->factory->user->create_and_get();
		$post  = $this->factory->post->create_and_get(array(
			'post_title' => 'HTML sanitize me! &<>'
		));
		$form  = $this->factory->form->create_and_get();
		$entry = $this->factory->entry->create_and_get( array(
			'created_by' => $user->ID,
			'form_id'    => $form['id'],
		) );

		$tests = array(
			'{current_post:ID}'         => $post->ID,
			'{current_post:post_title}' => $post->post_title,
			'{current_post:permalink}'  => get_permalink( $post ),
		);

		foreach ( $tests as $merge_tag => $expected ) {
			$expected = esc_html( $expected ); // By default, esc_html() is enabled
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, false, true ), 'Merge tag does not match: ' . $merge_tag );
		}

		// URL encoded
		foreach ( $tests as $merge_tag => $expected ) {
			$expected = urlencode( $expected );
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, true, false ), 'Merge tag does not match: ' . $merge_tag );
		}

		// HTML sanitization AND URL encoded
		foreach ( $tests as $merge_tag => $expected ) {
			$expected = esc_html( $expected );
			$expected = urlencode( $expected );
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, true, true ), 'Merge tag does not match: ' . $merge_tag );
		}

		// HTML sanitization turned off
		foreach ( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, false, false ), 'Merge tag does not match: ' . $merge_tag );
		}

		wp_reset_postdata();
	}

	/**
	 * @since 1.17
	 * @covers GravityView_Merge_Tags::process_modifiers()
	 */
	function test_process_modifiers() {

		$tests = array(
			array(
				'modifier' => 'maxwords:4',
				'raw' => 'The Earth was small, light blue, and so touchingly alone, our home that must be defended like a holy relic.',
				'expected' => 'The Earth was small,&hellip;',
			),
			// Test skipping {all_fields} merge tag
			array(
				'modifier' => 'maxwords:4',
				'merge_tag' => 'all_fields',
				'raw' => 'this should not be replaced; we are using all_fields merge tag.',
				'expected' => 'this should not be replaced; we are using all_fields merge tag.',
				'value' => 'this should not be replaced; we are using all_fields merge tag.',
			),
			// Test basic HTML
			array(
				'modifier' => 'maxwords:4',
				'raw' => '<p><strong>The Earth was small, light blue</strong>, and so touchingly alone, our home that must be defended like a holy relic.</p>',
				'expected' => '<p><strong>The Earth was small,&hellip;</strong></p>',
			),
			// Test HTML entities
			array(
				'modifier' => 'maxwords:5',
				'raw' => 'The Earth was small &amp; light blue.',
				'expected' => 'The Earth was small &amp;&hellip;',
			),

			// Test basic HTML with spacing
			// In this code, the <p> tag is considered its own word.
			array(
				'modifier' => 'maxwords:11',
				'raw' => '<p>
	<strong>The Earth was 
	small, light blue</strong>, and so touchingly <i>alone</i>, 
		our home that must be defended like a holy relic.</p>',
				'expected' => '<p> <strong>The Earth was small, light blue</strong>, and so touchingly <i>alone</i>,&hellip;</p>',
			),

			// Don't run maxwords on non-string
			array(
				'modifier' => 'maxwords',
				'value' => 'this should not be replaced; raw value is an array.',
				'expected' => 'this should not be replaced; raw value is an array.',
				'raw' => array(),
			),

			// Test wpautop
			array(
				'modifier' => 'wpautop',
				'raw' => 'The Earth was small &amp; light blue.',
				'expected' => '<p>The Earth was small &amp; light blue.</p>',
			),

			// Test wpautop line breaks
			array(
				'modifier' => 'wpautop',
				'raw' => 'The Earth was small 
				&amp; light blue.',
				'expected' => '<p>The Earth was small<br />
				&amp; light blue.</p>',
			),

			// Don't run wpautop on {all_fields}
			array(
				'modifier' => 'wpautop',
				'merge_tag' => 'all_fields',
				'value' => 'this should not be replaced; we are using all_fields merge tag.',
				'raw' => 'this should not be replaced; we are using all_fields merge tag.',
				'expected' => 'this should not be replaced; we are using all_fields merge tag.',
			),

			// Don't run wpautop on non-string
			array(
				'modifier' => 'wpautop',
				'value' => 'this should not be replaced; raw value is an array.',
				'expected' => 'this should not be replaced; raw value is an array.',
				'raw' => array(),
			),

			array(
				'modifier' => 'timestamp',
				'raw' => '02/03/2016', // February 3, 2016
				'expected' => 1454457600,
			),

			array(
				'modifier' => 'timestamp',
				'raw' => '2016-02-03', // February 3, 2016
				'expected' => 1454457600,
			),

			array(
				'modifier' => 'timestamp',
				'raw' => '03-02-2016', // February 3, 2016
				'expected' => 1454457600,
			),
		);

		foreach ( $tests as $test ) {
			$value = isset( $test['value'] ) ? $test['value'] : 'value should not be used';
			$merge_tag = isset( $test['merge_tag'] ) ? $test['merge_tag'] : 'merge tag not used';
			$value = GravityView_Merge_Tags::process_modifiers( $value, $merge_tag, $test['modifier'], 'field not used', $test['raw'] );
			$this->assertEquals( $test['expected'], $value, print_r( $test, true ) );
		}

	}

	/**
	 * @covers GravityView_Field_Date_Created::replace_merge_tag
	 * @group date_created
	 */
	function test_replace_date_created() {

		$form = $this->factory->form->create_and_get();

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
		) );

		$date_created = rgar( $entry, 'date_created' );

		/**
		 * adjusting date to local configured Time Zone
		 * @see GFCommon::format_date()
		 */
		$entry_gmt_time   = mysql2date( 'G', $date_created );
		$entry_local_time = GFCommon::get_local_timestamp( $entry_gmt_time );

		$tests = array(

			'{date_created:raw}' => $date_created,
			'{date_created:raw:timestamp}' => $date_created, // Raw logic is first, it wins
			'{date_created:raw:time}' => $date_created,
			'{date_created:raw:human}' => $date_created,
			'{date_created:raw:format:example}' => $date_created,

			'{date_created:timestamp:raw}' => $date_created, // Raw logic is first, it wins
			'{date_created:timestamp}' => $entry_local_time,
			'{date_created:timestamp:time}' => $entry_local_time,
			'{date_created:timestamp:human}' => $entry_local_time,
			'{date_created:timestamp:format:example}' => $entry_local_time,

			// Blog date format
			'{date_created}' => GFCommon::format_date( $date_created, false, '', false ),

			// Blog date format
			'{date_created:human}' => GFCommon::format_date( $date_created, true, '', false ),

			// Blog "date at time" format ("%s at %s")
			'{date_created:time}' => GFCommon::format_date( $date_created, false, '', true ),

			// 1 second ago
			'{date_created:diff}' => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			'{date_created:diff:format:%s is so long ago}' => sprintf( '%s is so long ago', human_time_diff( $entry_gmt_time ) ),

			// Relative should NOT process other modifiers
			'{date_created:diff:time}' => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			'{date_created:diff:human}' => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			'{date_created:human:diff}' => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),

			'{date_created:format:mdy}' => GFCommon::format_date( $date_created, false, 'mdy', false ),
			'{date_created:human:format:m/d/Y }' => GFCommon::format_date( $date_created, true, 'm/d/Y', false ),

			'{date_created:time:format:d}' => GFCommon::format_date( $date_created, false, 'd', true ),
			'{date_created:human:time:format:mdy}' => GFCommon::format_date( $date_created, true, 'mdy', true ),

			'{date_created:format:m/d/Y}' => date_i18n( 'm/d/Y', $entry_local_time, true ),
			'{date_created:format:m/d/Y\ \w\i\t\h\ \t\i\m\e\ h\:i\:s}' => date_i18n( 'm/d/Y\ \w\i\t\h\ \t\i\m\e\ h:i:s', $entry_local_time, true ),
		);

		foreach ( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
		}
	}

	/**
	 * @covers GravityView_Field::replace_merge_tag
	 * @covers GravityView_Field_Payment_Amount::replace_merge_tag
	 * @covers GravityView_Field_Payment_Status::replace_merge_tag
	 * @covers GravityView_Field_Payment_Method::replace_merge_tag
	 * @since 1.16
	 */
	function test_replace_field_custom_merge_tags() {

		$form = $this->factory->form->create_and_get();

		$entry_array = array(
			'form_id' => $form['id'],
			'currency' => 'USD',
			'payment_amount' => 200.39,
			'payment_status' => 'Paid',
			'payment_method' => 'Credit Card',
			'transaction_type' => 1,
			'is_fulfilled' => 1,
			'transaction_id' => 'apoaejt92983'
		);

		$entry = $this->factory->entry->create_and_get( $entry_array );

		$tests = array(
			'{payment_amount:raw}' => $entry_array['payment_amount'],
			'{payment_status}' => $entry_array['payment_status'],
			'{payment_method}' => $entry_array['payment_method'],
			'{transaction_id}' => $entry_array['transaction_id'],
			'{payment_amount}' => GravityView_Fields::get('payment_amount')->get_content( $entry_array['transaction_type'], $entry_array ),
			'{transaction_type}' => GravityView_Fields::get('transaction_type')->get_content( $entry_array['transaction_type'] ),
			'{is_fulfilled}' => GravityView_Fields::get('is_fulfilled')->get_content( $entry_array['is_fulfilled'] ),
		);

		foreach ( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
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
	 * We want to make sure that GravityView doesn't affect core Gravity Forms Merge Tags output
	 * @covers GravityView_Merge_Tags::replace_variables()
	 * @since 1.15.1
	 */
	function test_gf_merge_tags() {

		remove_all_filters( 'gform_pre_replace_merge_tags' );
		remove_all_filters( 'gform_merge_tag_filter' );
		
		$form = $this->factory->form->create_and_get();
		$post = $this->factory->post->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'post_id' => $post->ID, 'form_id' => $form['id'] ) );

		$tests = array(
			'{form_title}' => $form['title'],
			'{form_id}' => $form['id'],
			'{entry_id}' => $entry['id'],
			'{entry_url}' => esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=gf_entries&view=entry&id=' . $form['id'] . '&lid=' . rgar( $entry, 'id' ) ),
			'{admin_email}' => get_bloginfo( 'admin_email' ),
			'{post_id}' => $post->ID,
		);

		foreach( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
			$this->assertEquals( urlencode( $expected ), GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, true ), $merge_tag );

			remove_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10 );
			$this->assertEquals( $expected, GFCommon::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
			$this->assertEquals( urlencode( $expected ), GFCommon::replace_variables( $merge_tag, $form, $entry, true ), $merge_tag );
			add_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10, 7 );
		}

		wp_reset_postdata();
	}
}
