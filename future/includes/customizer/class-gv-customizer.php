<?php
/**
 * Adds options to the customizer for GravityView.
 */

namespace GV;

/** If this file is called directly, abort. */
if ( ! defined( 'GRAVITYVIEW_DIR' ) ) {
	die();
}

/**
 * \GV\Customizer class.
 */
class Customizer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'add_sections' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_scripts' ) );
		#add_filter( 'gravityview/template/table/entry/row/attributes', array( $this, 'add_row_attributes' ), 10, 2 );

		/**
		 * @see twentytwenty_get_customizer_css
		 * @see twentytwenty_generate_css
		 * @param \GV\Template_Context $gravityview The $gravityview object available in templates.
		 */
		add_action( 'gravityview/template/before', function( $gravityview = null ) {

			if ( ! $gravityview instanceof \GV\Template_Context ) {
				return;
			}

			$🦓 = get_theme_mod('gravityview_multiple_entries_zebrastripe_' . $gravityview->view->ID );
			$table_border_color = get_theme_mod( 'gravityview_multiple_entries_table_border_' . $gravityview->view->ID );
			$head_bg_color = get_theme_mod( 'gravityview_multiple_entries_table_header_bgcolor_' . $gravityview->view->ID );
			$head_color = get_theme_mod( 'gravityview_multiple_entries_table_header_color_' . $gravityview->view->ID );
			$row_bg_color = get_theme_mod( 'gravityview_multiple_entries_table_row_bgcolor_' . $gravityview->view->ID );
			$row_color = get_theme_mod( 'gravityview_multiple_entries_table_row_color_' . $gravityview->view->ID );
			$alt_bg_color = $🦓 ? get_theme_mod( 'gravityview_multiple_entries_table_row_alt_bgcolor_' . $gravityview->view->ID ) : $row_bg_color;
			$alt_color = $🦓 ? get_theme_mod( 'gravityview_multiple_entries_table_row_alt_color_' . $gravityview->view->ID ) : $row_color;

			$table_padding = get_theme_mod( 'gravityview_multiple_entries_table_padding_'. $gravityview->view->ID, 5 );
			?>
			<style>

				<?php

				if( $table_border_color ) {
				?>
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view,
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view td,
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view th {
					border-color: #<?php echo $table_border_color; ?>;
				}
				<?php
				}
				?>

				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view th,
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view td {
					padding: <?php echo $table_padding; ?>px!important;
				}

				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view thead th,
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view thead a.gv-sort,
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view tfoot th,
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view tfoot a.gv-sort {
					color: #<?php echo sanitize_hex_color_no_hash( $head_color ); ?>;
					background-color: #<?php echo sanitize_hex_color_no_hash( $head_bg_color ); ?>;
				}
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view tr td {
					color: #<?php echo sanitize_hex_color_no_hash( $row_color ); ?>;
					background-color: #<?php echo sanitize_hex_color_no_hash( $row_bg_color ); ?>;
				}
				.gv-container-<?php echo $gravityview->view->ID; ?> .gv-table-view tr.alt td {
					color: #<?php echo sanitize_hex_color_no_hash( $alt_color ); ?>;
					background-color: #<?php echo sanitize_hex_color_no_hash( $alt_bg_color ); ?>;
				}
			</style>
			<?php
		});
	}

	/**
	 *
	 * @param array $attributes The HTML attributes.
	 * @param \GV\Template_Context The context.
	 *
	 */
	public function add_row_attributes( $attributes, $context ) {

		$row_bg_color = get_option( 'gravityview_multiple_entries_table_row_bgcolor' );

		$style = \GV\Utils::get( $attributes, 'style', '' );
		if ( ! empty( $row_bg_color ) ) {
			$style .= ' background-color: #' . sanitize_hex_color_no_hash( $row_bg_color );
		}

		if ( $style ) {
			$attributes['style'] = $style;
		}

		return $attributes;
	}

	/**
	 * Add settings to the customizer.
	 *
	 * @param \WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_sections( $wp_customize ) {

		$wp_customize->add_panel(
			'gravityview',
			array(
				'priority'       => 200,
				'capability'     => 'edit_theme_options',
				'theme_supports' => '',
				'title'          => __( 'GravityView', 'gravityview' ),
				'active_callback' => function() {
					global $post;
					return \GVCommon::has_gravityview_shortcode( $post );
				}
			)
		);

		$views = \GVCommon::get_all_views( array(
				'posts_per_page' => 300,
		) );

		/**
		 * Customizer assumes that you're going to be setting theme-wide settings. It doesn't want per-post customization.
		 * So we need to hack it to add a setting per-View. This may not scale and may be a bad idea.
		 */
		foreach ( $views as $view_post ) {
			$this->add_multiple_entries_section( $wp_customize, $view_post );
		}

		#$this->add_single_entry_section( $wp_customize );
		#$this->add_edit_entry_section( $wp_customize );
	}

	/**
	 * Frontend CSS styles.
	 */
	public function add_frontend_scripts() {
		if ( ! is_customize_preview() ) {
			return;
		}

		$css = '.woocommerce-store-notice, p.demo_store { display: block !important; }';
		wp_add_inline_style( 'customize-preview', $css );
	}

	/**
	 * Store notice section.
	 *
	 * @param \WP_Customize_Manager $wp_customize Theme Customizer object.
	 * @param \WP_Post $view_post
	 */
	private function add_multiple_entries_section( $wp_customize, $view_post ) {

		$wp_customize->add_section(
			'gravityview_multiple_entries_' . $view_post->ID,
			array(
				'title'    => __( 'Multiple Entries View ' . $view_post->ID, 'woocommerce' ),
				'priority' => 10,
				'panel'    => 'gravityview',
				'active_callback' => function() use ( $wp_customize, $view_post ) {
					global $post;
					return $post->ID === $view_post->ID;
				},
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_border_' . $view_post->ID,
			array(
				'default'              => '',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_border_' . $view_post->ID,
				array(
						'label'   => __( 'Table Border Color' ),
						'section' => 'gravityview_multiple_entries_' . $view_post->ID,
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_padding_' . $view_post->ID,
			array(
				'default'              => 5,
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_padding_' . $view_post->ID,
				array(
					'type'    => 'number',
					'label'   => __( 'Cell Padding' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_header_bgcolor_' . $view_post->ID,
			array(
				'default'              => '',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_header_bgcolor_' . $view_post->ID,
				array(
					'label'   => __( 'Header Background Color' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_header_color_' . $view_post->ID,
			array(
				'default'              => '',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);


		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_header_color_' . $view_post->ID,
				array(
					'label'   => __( 'Header Text Color' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_row_bgcolor_' . $view_post->ID,
			array(
				'default'              => '',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_row_bgcolor_' . $view_post->ID,
				array(
					'label'   => __( 'Row Background Color' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_row_color_' . $view_post->ID,
			array(
				'default'              => '',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_row_color_' . $view_post->ID,
				array(
					'label'   => __( 'Row Text Color' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_zebrastripe_' . $view_post->ID,
			array(
				'default'              => false,
				'capability'           => 'gravityview_edit_settings',
			)
		);

		$wp_customize->add_control(
			'gravityview_multiple_entries_zebrastripe_' . $view_post->ID,
			array(
				'label'    => __( 'Alternate Row Colors', 'woocommerce' ),
				'section'  => 'gravityview_multiple_entries_' . $view_post->ID,
				'settings' => 'gravityview_multiple_entries_zebrastripe_' . $view_post->ID,
				'type'     => 'checkbox',
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_row_alt_bgcolor_' . $view_post->ID,
			array(
				'default'              => 'blank',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_row_alt_bgcolor_' . $view_post->ID,
				array(
					'label'   => __( 'Row Alternate Background Color' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
					'active_callback' => function() use ( $wp_customize, $view_post ) {
						return $wp_customize->get_setting( 'gravityview_multiple_entries_zebrastripe_' . $view_post->ID )->value();
					},
				)
			)
		);

		$wp_customize->add_setting(
			'gravityview_multiple_entries_table_row_alt_color_' . $view_post->ID,
			array(
				'default'              => 'blank',
				'sanitize_callback'    => '\sanitize_hex_color_no_hash',
				'sanitize_js_callback' => 'maybe_hash_hex_color',
			)
		);

		$wp_customize->add_control(
			new \WP_Customize_Color_Control(
				$wp_customize,
				'gravityview_multiple_entries_table_row_alt_color_' . $view_post->ID,
				array(
					'label'   => __( 'Row Alternate Text Color' ),
					'section' => 'gravityview_multiple_entries_' . $view_post->ID,
					'active_callback' => function() use ( $wp_customize, $view_post ) {
						return $wp_customize->get_setting( 'gravityview_multiple_entries_zebrastripe_' . $view_post->ID )->value();
					},
				)
			)
		);

		/*if ( isset( $wp_customize->selective_refresh ) ) {
			$wp_customize->selective_refresh->add_partial(
				'woocommerce_demo_store_notice',
				array(
					'selector'            => '.woocommerce-store-notice',
					'container_inclusive' => true,
					'render_callback'     => 'woocommerce_demo_store',
				)
			);
		}*/
	}

	/**
	 * Product catalog section.
	 *
	 * @param \WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_single_entry_section( $wp_customize ) {
		$wp_customize->add_section(
			'gravityview_single_entry',
			array(
				'title'    => __( 'Single Entry', 'woocommerce' ),
				'priority' => 10,
				'panel'    => 'gravityview',
			)
		);

		$wp_customize->add_control(
			'woocommerce_shop_page_display',
			array(
				'label'       => __( 'Shop page display', 'woocommerce' ),
				'description' => __( 'Choose what to display on the main shop page.', 'woocommerce' ),
				'section'     => 'gravityview_single_entry',
				'settings'    => 'woocommerce_shop_page_display',
				'type'        => 'select',
				'choices'     => array(
					''              => __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show categories', 'woocommerce' ),
					'both'          => __( 'Show categories &amp; products', 'woocommerce' ),
				),
			)
		);

		$wp_customize->add_control(
			'woocommerce_category_archive_display',
			array(
				'label'       => __( 'Category display', 'woocommerce' ),
				'description' => __( 'Choose what to display on product category pages.', 'woocommerce' ),
				'section'     => 'gravityview_single_entry',
				'settings'    => 'woocommerce_category_archive_display',
				'type'        => 'select',
				'choices'     => array(
					''              => __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show subcategories', 'woocommerce' ),
					'both'          => __( 'Show subcategories &amp; products', 'woocommerce' ),
				),
			)
		);

		$wp_customize->add_setting(
			'woocommerce_default_catalog_orderby',
			array(
				'default'           => 'menu_order',
				'type'              => 'option',
				'capability'        => 'gravityview_edit_settings',
				'sanitize_callback' => array( $this, 'sanitize_default_catalog_orderby' ),
			)
		);

		$wp_customize->add_control(
			'woocommerce_default_catalog_orderby',
			array(
				'label'       => __( 'Default product sorting', 'woocommerce' ),
				'description' => __( 'How should products be sorted in the catalog by default?', 'woocommerce' ),
				'section'     => 'gravityview_single_entry',
				'settings'    => 'woocommerce_default_catalog_orderby',
				'type'        => 'select',
				'choices'     => apply_filters(
					'woocommerce_default_catalog_orderby_options',
					array(
						'menu_order' => __( 'Default sorting (custom ordering + name)', 'woocommerce' ),
						'popularity' => __( 'Popularity (sales)', 'woocommerce' ),
						'rating'     => __( 'Average rating', 'woocommerce' ),
						'date'       => __( 'Sort by most recent', 'woocommerce' ),
						'price'      => __( 'Sort by price (asc)', 'woocommerce' ),
						'price-desc' => __( 'Sort by price (desc)', 'woocommerce' ),
					)
				),
			)
		);

		// The following settings should be hidden if the theme is declaring the values.
		if ( has_filter( 'loop_shop_columns' ) ) {
			return;
		}

		$wp_customize->add_setting(
			'woocommerce_catalog_columns',
			array(
				'default'              => 4,
				'type'                 => 'option',
				'capability'           => 'gravityview_edit_settings',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			'woocommerce_catalog_columns',
			array(
				'label'       => __( 'Products per row', 'woocommerce' ),
				'description' => __( 'How many products should be shown per row?', 'woocommerce' ),
				'section'     => 'gravityview_single_entry',
				'settings'    => 'woocommerce_catalog_columns',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => '',
					'step' => 1,
				),
			)
		);

		// Only add this setting if something else isn't managing the number of products per page.
		if ( ! has_filter( 'loop_shop_per_page' ) ) {
			$wp_customize->add_setting(
				'woocommerce_catalog_rows',
				array(
					'default'              => 4,
					'type'                 => 'option',
					'capability'           => 'gravityview_edit_settings',
					'sanitize_callback'    => 'absint',
					'sanitize_js_callback' => 'absint',
				)
			);
		}

		$wp_customize->add_control(
			'woocommerce_catalog_rows',
			array(
				'label'       => __( 'Rows per page', 'woocommerce' ),
				'description' => __( 'How many rows of products should be shown per page?', 'woocommerce' ),
				'section'     => 'gravityview_single_entry',
				'settings'    => 'woocommerce_catalog_rows',
				'type'        => 'number',
				'input_attrs' => array(
					'min'  => 1,
					'max'  => '',
					'step' => 1,
				),
			)
		);
	}

	/**
	 * Product catalog section.
	 *
	 * @param \WP_Customize_Manager $wp_customize Theme Customizer object.
	 */
	public function add_edit_entry_section( $wp_customize ) {
		$wp_customize->add_section(
			'gravityview_edit_entry',
			array(
				'title'    => __( 'Edit Entry', 'woocommerce' ),
				'priority' => 10,
				'panel'    => 'gravityview',
			)
		);

		$wp_customize->add_setting(
			'woocommerce_catalog_columns',
			array(
				'default'              => 4,
				'type'                 => 'option',
				'capability'           => 'gravityview_edit_settings',
				'sanitize_callback'    => 'absint',
				'sanitize_js_callback' => 'absint',
			)
		);

		$wp_customize->add_control(
			'some_edit_entry_setting',
			array(
				'label'       => __( 'Shop page display', 'woocommerce' ),
				'description' => __( 'Choose what to display on the main shop page.', 'woocommerce' ),
				'section'     => 'gravityview_edit_entry',
				'settings'    => 'woocommerce_catalog_columns',
				'type'        => 'select',
				'choices'     => array(
					''              => __( 'Show products', 'woocommerce' ),
					'subcategories' => __( 'Show categories', 'woocommerce' ),
					'both'          => __( 'Show categories &amp; products', 'woocommerce' ),
				),
			)
		);
	}

	/**
	 * Sanitize field display.
	 *
	 * @param string $value '', 'subcategories', or 'both'.
	 * @return string
	 */
	public function sanitize_checkout_field_display( $value ) {
		$options = array( 'hidden', 'optional', 'required' );
		return in_array( $value, $options, true ) ? $value : '';
	}
}

new Customizer();
