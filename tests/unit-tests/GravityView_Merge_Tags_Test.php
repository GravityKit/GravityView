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

			array(
				'modifier' => 'esc_html',
				'raw' => '<example>',
				'expected' => '&lt;example&gt;',
			),
			array(
				'modifier' => 'sanitize_title',
				'raw' => '<example>',
				'expected' => '',
			),
			array(
				'modifier' => 'sanitize_html_class',
				'raw' => '<example>',
				'expected' => 'example',
			),
			array(
				'modifier' => 'sanitize_html_class',
				'raw' => '***',
				'expected' => '',
			),
			array(
				'modifier' => 'esc_html',
				'raw' => '["Apple", "Orange", "Pear"]',
				'expected' => '[&quot;Apple&quot;, &quot;Orange&quot;, &quot;Pear&quot;]',
			),
			array(
				'modifier' => 'sanitize_title',
				'raw' => '["Apple", "Orange", "Pear"]',
				'expected' => 'apple-orange-pear',
			),
			array(
				'modifier' => 'sanitize_html_class',
				'raw' => '["Apple", "Orange", "Pear"]',
				'expected' => 'Apple Orange Pear',
			),

			array(
				'modifier' => 'format:F-j-y',
				'merge_tag' => 'Date',
				'value' => '06/02/2024',
				'raw' => '06/02/2024',
				'expected' => 'June-2-24',
			),

			array(
				'modifier' => 'format:g:i..a',
				'merge_tag' => 'Time',
				'value' => '04:05 pm',
				'raw' => '04:05 pm',
				'expected' => '4:05..pm',
			),
		);

		// Fake it as it's used for default filters
		$field = new GF_Field_Text();

		foreach ( $tests as $test ) {
			$value = isset( $test['value'] ) ? $test['value'] : 'value should not be used';
			$merge_tag = isset( $test['merge_tag'] ) ? $test['merge_tag'] : 'merge tag not used';
			if( isset( $test['merge_tag'] ) && $test['merge_tag'] === 'Time'){
				$field = new GF_Field_Time();
			}

			if( isset( $test['merge_tag'] ) && $test['merge_tag'] === 'Date'){
				$field = new GF_Field_Date();
			}

			$value = GravityView_Merge_Tags::process_modifiers( $value, $merge_tag, $test['modifier'], $field, $test['raw'] );
			$this->assertEquals( $test['expected'], $value, print_r( $test, true ) );
		}

	}

	/**
	 * @covers GravityView_Field_Date_Created::replace_merge_tag
	 * @group date_created
	 */
	function test_replace_date_created_and_updated( $date_field = 'date_created' ) {
		$form = $this->factory->form->create_and_get();

		$entry = $this->factory->entry->create_and_get( array(
			'form_id' => $form['id'],
			'date_created' => '2019-11-16 10:00:00',
			'date_updated' => '2019-11-16 10:00:00',
		) );

		$date_value = \GV\Utils::get( $entry, $date_field );

		/**
		 * adjusting date to local configured Time Zone
		 * @see GFCommon::format_date()
		 */
		$entry_gmt_time   = mysql2date( 'G', $date_value );
		$entry_local_time = GFCommon::get_local_timestamp( $entry_gmt_time );

		$time_now = $entry_gmt_time + 1; // 1 second difference for human_time_diff calls

		add_filter( 'human_time_diff', $callback = function( $since, $diff, $from, $to ) use ( $time_now ) {
			if ( $to !== $time_now ) {
				return human_time_diff( $from, $time_now );
			}
			return $since;
		}, 10, 4 );

		$tests = array(

			"{{$date_field}:raw}" => $date_value,
			"{{$date_field}:raw:timestamp}" => $date_value, // Raw logic is first, it wins
			"{{$date_field}:raw:time}" => $date_value,
			"{{$date_field}:raw:human}" => $date_value,
			"{{$date_field}:raw:format:example}" => $date_value,

			"{{$date_field}:timestamp:raw}" => $date_value, // Raw logic is first, it wins
			"{{$date_field}:timestamp}" => $entry_local_time,
			"{{$date_field}:timestamp:time}" => $entry_local_time,
			"{{$date_field}:timestamp:human}" => $entry_local_time,
			"{{$date_field}:timestamp:format:example}" => $entry_local_time,

			// Blog date format
			"{{$date_field}}" => GFCommon::format_date( $date_value, false, '', false ),

			// Blog date format
			"{{$date_field}:human}" => GFCommon::format_date( $date_value, true, '', false ),

			// Blog "date at time" format ("%s at %s")
			"{{$date_field}:time}" => GFCommon::format_date( $date_value, false, '', true ),

			// 1 second ago
			"{{$date_field}:diff}" => sprintf( '%s ago', human_time_diff( $entry_gmt_time ) ),
			"{{$date_field}:diff:format:%s is so long ago}" => sprintf( '%s is so long ago', human_time_diff( $entry_gmt_time, $time_now ) ),

			// Relative should NOT process other modifiers
			"{{$date_field}:diff:time}" => sprintf( '%s ago', human_time_diff( $entry_gmt_time, $time_now ) ),
			"{{$date_field}:diff:human}" => sprintf( '%s ago', human_time_diff( $entry_gmt_time, $time_now ) ),
			"{{$date_field}:human:diff}" => sprintf( '%s ago', human_time_diff( $entry_gmt_time, $time_now ) ),

			"{{$date_field}:format:mdy}" => GFCommon::format_date( $date_value, false, 'mdy', false ),
			"{{$date_field}:human:format:m/d/Y }" => GFCommon::format_date( $date_value, true, 'm/d/Y', false ),

			"{{$date_field}:time:format:d}" => GFCommon::format_date( $date_value, false, 'd', true ),
			"{{$date_field}:human:time:format:mdy}" => GFCommon::format_date( $date_value, true, 'mdy', true ),

			"{{$date_field}:format:m/d/Y}" => date_i18n( 'm/d/Y', $entry_local_time, true ),
			"{{$date_field}:format:m/d/Y\ \w\i\\t\h\ \\t\i\m\\e\ h\:i\:s}" => date_i18n( 'm/d/Y\ \w\i\t\h\ \t\i\m\e\ h:i:s', $entry_local_time, true ),
		);

		foreach ( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
		}

		$this->assertTrue( remove_filter( 'human_time_diff', $callback ) );
	}

	function test_replace_date_updated() {
		return $this->test_replace_date_created_and_updated( 'date_updated' );
	}

	/**
	 * @covers GravityView_Merge_Tags::replace_merge_tags_dates
	 *
	 * @since  2.30.0
	 */
	function test_replace_field_dates_merge_tags() {
		$form = $this->factory->form->create_and_get();

		$entry = $this->factory->entry->create_and_get( [
			'form_id' => $form['id'],
		] );

		$test_data = [
			'{now:raw}'                => date_i18n( 'Y-m-d H:i:s', time(), true ),
			'{now:format:Y-m-d}'       => date_i18n( 'Y-m-d', time(), true ),
			'{now:timestamp}'          => time(),
			'{tomorrow:raw}'           => date_i18n( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS, true ),
			'{tomorrow:format:Y-m-d}'  => date_i18n( 'Y-m-d', time() + DAY_IN_SECONDS, true ),
			'{tomorrow:timestamp}'     => time() + DAY_IN_SECONDS,
			'{yesterday:raw}'          => date_i18n( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS, true ),
			'{yesterday:format:Y-m-d}' => date_i18n( 'Y-m-d', time() - DAY_IN_SECONDS, true ),
			'{yesterday:timestamp}'    => time() - DAY_IN_SECONDS,
		];

		foreach ( $test_data as $merge_tag => $expected ) {
			$this->assertEquals(
				$expected,
				GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ),
				$merge_tag
			);
		}
	}

	/**
	 * @covers GravityView_Field::replace_merge_tag
	 * @covers GravityView_Merge_Tags::replace_is_starred
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
			'is_starred' => 1,
			'transaction_id' => 'apoaejt92983'
		);

		$entry = $this->factory->entry->create_and_get( $entry_array );

		$tests = array(
			'{payment_amount:raw}' => $entry_array['payment_amount'],
			'{payment_status}' => $entry_array['payment_status'],
			'{payment_method}' => $entry_array['payment_method'],
			'{transaction_id}' => $entry_array['transaction_id'],
			'{is_starred}' => $entry_array['is_starred'],
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
		if ( ! function_exists( '__return_example' ) ) {
			function __return_example() { return 'example'; }
		}
		add_filter('gravityview/merge_tags/get/value/string', '__return_example' );
		$this->assertEquals( 'example', GravityView_Merge_Tags::replace_variables( '{get:string}' ) );
		remove_filter('gravityview/merge_tags/get/value/string', '__return_example' );

		### TEST gravityview/merge_tags/do_replace_variables FILTER
		add_filter( 'gravityview/merge_tags/do_replace_variables', '__return_false' );
		$this->assertEquals( '{get:string}', GravityView_Merge_Tags::replace_variables( '{get:string}' ) );
		remove_filter( 'gravityview/merge_tags/do_replace_variables', '__return_false' );
	}

	/**
	 * @covers GravityView_Merge_Tags::modifier_strings()
	 * @covers GravityView_Merge_Tags::modifier_explode()
	 *
	 * @since 2.0
	 */
	function test_merge_tag_data() {

		$form = $this->factory->form->create_and_get();
		$post = $this->factory->post->create_and_get();

		$entry_args = array(
			'form_id' => $form['id'],
			'post_id' => $post->ID,
		);

		$entry = $this->factory->entry->create_and_get( $entry_args );

		// 2.3 checks to make sure the fields exist
		$form['fields'][] = new GF_Field_Text( array( 'id' => 100, 'form_id' => $form['id'] ) );
		$form['fields'][] = new GF_Field_Text( array( 'id' => 101, 'form_id' => $form['id'] ) );
		$form['fields'][] = new GF_Field_Text( array( 'id' => 201, 'form_id' => $form['id'] ) );
		$form['fields'][] = new GF_Field_Text( array( 'id' => 301, 'form_id' => $form['id'] ) );

		$list_field = new GF_Field_List( array( 'id' => 401, 'form_id' => $form['id'] ) );

		$form['fields'][] = $list_field;

		$entry['100'] = 'This is spaces';
		$entry['101'] = 'This,is,commas';
		$entry['201'] = '<tag>';
		$entry['301'] = '["This","is","JSON"]';
		$entry['401'] = 'a:2:{i:0;s:8:"One List";i:1;s:8:"Two List";}';

		$tests = array(
			'{Field:100:sanitize_html_class}' => 'This is spaces',
			'{Field:100:ucwords}' => 'This Is Spaces',
			'{Field:100:ucwords,urlencode}' => 'This+Is+Spaces',
			'{Field:100:urlencode,ucwords}' => 'This+is+spaces',
			'{Field:100:urlencode}' => 'This+is+spaces',
			'{Field:100:sanitize_html_class,urlencode}' => 'This+is+spaces',
			'{Field:100:urlencode,sanitize_html_class}' => 'Thisisspaces',
			'{Field:101:sanitize_html_class}' => 'Thisiscommas',
			'{Field:101:sanitize_html_class,urlencode}' => 'Thisiscommas',
			'{Field:101:explode}' => 'This is commas',
			'{Field:101:explode,strtoupper}' => 'THIS IS COMMAS',
			'{Field:101:explode,strtoupper,strtolower}' => 'this is commas',
			'{Field:101:explode,ucwords}' => 'This Is Commas',
			'{Field:101:urlencode}' => 'This%2Cis%2Ccommas',
			'{Field:101:urlencode,strtoupper}' => 'THIS%2CIS%2CCOMMAS',
			'{Field:101:urlencode,sanitize_html_class}' => 'Thisiscommas',
			'{Field:201:sanitize_html_class}' => 'tag',
			'{Field:201:sanitize_html_class,urlencode}' => 'tag',
			'{Field:201:esc_html}' => '&lt;tag&gt;',
			'{Field:201:esc_html,urlencode}' => '%26lt%3Btag%26gt%3B',
			'{Field:201:urlencode,sanitize_html_class}' => 'tag',
			'{Field:201:strtoupper}' => '<TAG>',
			'{Field:301:explode}' => 'This is JSON',
			'{Field:301:explode,sanitize_title}' => 'this-is-json',
			'{Field:301:explode,sanitize_title,strtoupper,urlencode}' => 'THIS-IS-JSON',
			'{Field:301:explode,strtolower}' => 'this is json',
			'{Field:301:esc_html,explode}' => '[&quot;This&quot; &quot;is&quot; &quot;JSON&quot;]',
			'{List Field:401:url}' => 'One List,Two List',
			'{List Field:401:text}' => 'One List, Two List',
			'{List Field:401:html}' => "<ul class='bulleted'><li>One List</li><li>Two List</li></ul>",
			'{List Field:401:url,urlencode}' => 'One+List%2CTwo+List',
			'{List Field:401:text,urlencode}' => 'One+List%2C+Two+List',
			'{List Field:401:html,esc_html}' => "&lt;ul class=&#039;bulleted&#039;&gt;&lt;li&gt;One List&lt;/li&gt;&lt;li&gt;Two List&lt;/li&gt;&lt;/ul&gt;",
			'{List Field:401:non_gf_non_gv}' => 'One List, Two List',
		);

		$filter_tags = function( $tags ) {
			return array( '<tag>' );
		};

		// Allow GF to process the tag
		add_filter( 'gform_allowable_tags', $filter_tags );

		foreach( $tests as $merge_tag => $expected ) {
			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
		}

		remove_filter( 'gform_allowable_tags', $filter_tags );

		wp_reset_postdata();
	}

	/**
	 * We want to make sure that GravityView doesn't affect core Gravity Forms Merge Tags output
	 * @covers GravityView_Merge_Tags::replace_site_url()
	 * @group gf_merge_tags
	 * @since 2.10.1
	 */
	function test_replace_site_url() {

		$this->assertEquals( 'No merge tag', GravityView_Merge_Tags::replace_variables( 'No merge tag' ) );

		$this->assertEquals( sprintf( 'URL: %s, then content', get_site_url() ), GravityView_Merge_Tags::replace_variables( 'URL: {site_url}, then content' ) );

		$this->assertEquals( sprintf( 'URL: %s, then content', urlencode( get_site_url() ) ), GravityView_Merge_Tags::replace_variables( 'URL: {site_url}, then content', [], [], true, false ) );

		$this->assertEquals( sprintf( 'URL: %s, then content', esc_html( get_site_url() ) ), GravityView_Merge_Tags::replace_variables( 'URL: {site_url}, then content', [], [], false, true ) );
	}

	/**
	 * We want to make sure that GravityView doesn't affect core Gravity Forms Merge Tags output
	 * @covers GravityView_Merge_Tags::replace_variables()
	 * @group gf_merge_tags
	 * @since 1.15.1
	 */
	function test_gf_merge_tags() {
		
		$form = $this->factory->form->create_and_get();
		$post = $this->factory->post->create_and_get();
		$entry = $this->factory->entry->create_and_get( array( 'post_id' => $post->ID, 'form_id' => $form['id'] ) );

		$tests = array(
			'{form_title}' => $form['title'],
			'{form_id}' => $form['id'],
			'{entry_id}' => $entry['id'],
			'{entry_url}' => esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=gf_entries&view=entry&id=' . $form['id'] . '&lid=' . \GV\Utils::get( $entry, 'id' ) ),
			'{admin_email}' => get_bloginfo( 'admin_email' ),
			'{post_id}' => $post->ID,
		);

		foreach( $tests as $merge_tag => $expected ) {

			$this->assertEquals( $expected, GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, false ), $merge_tag );
			$this->assertEquals( urlencode( $expected ), GravityView_Merge_Tags::replace_variables( $merge_tag, $form, $entry, true ), $merge_tag . ' (urlencoded)' );

			remove_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10 );
			$this->assertEquals( $expected, GFCommon::replace_variables( $merge_tag, $form, $entry ), $merge_tag );
			$this->assertEquals( urlencode( $expected ), GFCommon::replace_variables( $merge_tag, $form, $entry, true ), $merge_tag );
			add_filter( 'gform_replace_merge_tags', array( 'GravityView_Merge_Tags', 'replace_gv_merge_tags' ), 10, 7 );
		}

		wp_reset_postdata();
	}
}
