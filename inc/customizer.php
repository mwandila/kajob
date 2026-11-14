<?php
/**
 * Customizer settings: the theme options panel buyers will use.
 *
 * @package Kajob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Customizer panels, sections and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function kajob_customize_register( $wp_customize ) {

	/* ── Panel: Theme Options ─────────────────────────────── */
	$wp_customize->add_panel( 'kajob_options', array(
		'title'       => __( 'Kajob Theme Options', 'kajob' ),
		'priority'    => 10,
		'description' => __( 'Customize colors, texts and layout for the Kajob theme.', 'kajob' ),
	) );

	/* ── Section: Colors ──────────────────────────────────── */
	$wp_customize->add_section( 'kajob_colors', array(
		'title' => __( 'Colors', 'kajob' ),
		'panel' => 'kajob_options',
	) );

	$wp_customize->add_setting( 'kajob_primary_color', array(
		'default'           => '#ffb020',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'kajob_primary_color', array(
		'label'   => __( 'Primary Color', 'kajob' ),
		'section' => 'kajob_colors',
	) ) );

	$wp_customize->add_setting( 'kajob_secondary_color', array(
		'default'           => '#0a0e1f',
		'sanitize_callback' => 'sanitize_hex_color',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'kajob_secondary_color', array(
		'label'   => __( 'Secondary Color (headings, footer)', 'kajob' ),
		'section' => 'kajob_colors',
	) ) );

	$wp_customize->add_setting( 'kajob_enable_dark_mode', array(
		'default'           => true,
		'sanitize_callback' => 'kajob_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'kajob_enable_dark_mode', array(
		'label'   => __( 'Show dark / light toggle', 'kajob' ),
		'section' => 'kajob_colors',
		'type'    => 'checkbox',
	) );

	$wp_customize->add_setting( 'kajob_default_scheme', array(
		'default'           => 'dark',
		'sanitize_callback' => 'kajob_sanitize_scheme',
	) );
	$wp_customize->add_control( 'kajob_default_scheme', array(
		'label'   => __( 'Default color scheme', 'kajob' ),
		'section' => 'kajob_colors',
		'type'    => 'select',
		'choices' => array(
			'light' => __( 'Light', 'kajob' ),
			'dark'  => __( 'Dark', 'kajob' ),
		),
	) );

	/* ── Section: Hero ────────────────────────────────────── */
	$wp_customize->add_section( 'kajob_hero', array(
		'title'       => __( 'Front Page: Hero', 'kajob' ),
		'panel'       => 'kajob_options',
		'description' => __( 'The large banner at the top of the job board landing page.', 'kajob' ),
	) );

	kajob_add_text_control( $wp_customize, 'kajob_hero_title', __( 'Hero Title', 'kajob' ), 'kajob_hero', 'Find Your Dream Job Today' );
	kajob_add_text_control( $wp_customize, 'kajob_hero_subtitle', __( 'Hero Subtitle', 'kajob' ), 'kajob_hero', 'Browse thousands of full-time, part-time and remote job listings from companies hiring near you.' );

	/* ── Section: Sections & Content ──────────────────────── */
	$wp_customize->add_section( 'kajob_sections', array(
		'title' => __( 'Front Page: Sections', 'kajob' ),
		'panel' => 'kajob_options',
	) );

	kajob_add_text_control( $wp_customize, 'kajob_categories_title', __( 'Categories Section Title', 'kajob' ), 'kajob_sections', 'Browse by Category' );
	kajob_add_text_control( $wp_customize, 'kajob_latest_jobs_title', __( 'Latest Jobs Section Title', 'kajob' ), 'kajob_sections', 'Latest Jobs' );

	$wp_customize->add_setting( 'kajob_latest_jobs_count', array(
		'default'           => 6,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'kajob_latest_jobs_count', array(
		'label'   => __( 'Jobs per front page section', 'kajob' ),
		'section' => 'kajob_sections',
		'type'    => 'number',
	) );

	/* ── Section: Employer CTA ────────────────────────────── */
	$wp_customize->add_section( 'kajob_cta', array(
		'title' => __( 'Front Page: Employer CTA', 'kajob' ),
		'panel' => 'kajob_options',
	) );

	kajob_add_text_control( $wp_customize, 'kajob_cta_title', __( 'CTA Title', 'kajob' ), 'kajob_cta', 'Are you an employer?' );
	kajob_add_text_control( $wp_customize, 'kajob_cta_text', __( 'CTA Text', 'kajob' ), 'kajob_cta', 'Reach thousands of qualified candidates. Post your job listing in minutes.' );
	kajob_add_text_control( $wp_customize, 'kajob_cta_button', __( 'CTA Button Label', 'kajob' ), 'kajob_cta', 'Post a Job' );
	kajob_add_url_control( $wp_customize, 'kajob_cta_url', __( 'CTA Button URL', 'kajob' ), 'kajob_cta', '#post-job' );

	/* ── Section: Header & Footer ─────────────────────────── */
	$wp_customize->add_section( 'kajob_header_footer', array(
		'title' => __( 'Header & Footer', 'kajob' ),
		'panel' => 'kajob_options',
	) );

	$wp_customize->add_setting( 'kajob_header_cta', array(
		'default'           => true,
		'sanitize_callback' => 'kajob_sanitize_checkbox',
	) );
	$wp_customize->add_control( 'kajob_header_cta', array(
		'label'   => __( 'Show header button', 'kajob' ),
		'section' => 'kajob_header_footer',
		'type'    => 'checkbox',
	) );

	kajob_add_text_control( $wp_customize, 'kajob_header_cta_text', __( 'Header Button Label', 'kajob' ), 'kajob_header_footer', 'Post a Job' );
	kajob_add_url_control( $wp_customize, 'kajob_header_cta_url', __( 'Header Button URL', 'kajob' ), 'kajob_header_footer', '#post-job' );

	$wp_customize->add_setting( 'kajob_footer_copyright', array(
		'default'           => sprintf( __( '© %1$s %2$s. All rights reserved.', 'kajob' ), date_i18n( 'Y' ), get_bloginfo( 'name' ) ),
		'sanitize_callback' => 'sanitize_text_field',
	) );
	$wp_customize->add_control( 'kajob_footer_copyright', array(
		'label'   => __( 'Copyright Text', 'kajob' ),
		'section' => 'kajob_header_footer',
		'type'    => 'textarea',
	) );

	/* ── Section: Layout ──────────────────────────────────── */
	$wp_customize->add_section( 'kajob_layout', array(
		'title' => __( 'Layout', 'kajob' ),
		'panel' => 'kajob_options',
	) );

	$wp_customize->add_setting( 'kajob_container_width', array(
		'default'           => 1200,
		'sanitize_callback' => 'absint',
	) );
	$wp_customize->add_control( 'kajob_container_width', array(
		'label'       => __( 'Container Width (px)', 'kajob' ),
		'section'     => 'kajob_layout',
		'type'        => 'number',
		'input_attrs' => array(
			'min'  => 960,
			'max'  => 1600,
			'step' => 20,
		),
	) );

	/* Selective refresh for performance. */
	$wp_customize->selective_refresh->add_partial( 'kajob_hero_title', array(
		'selector' => '.hero__title',
	) );
	$wp_customize->selective_refresh->add_partial( 'kajob_footer_copyright', array(
		'selector' => '.site-footer__copyright',
	) );
}
add_action( 'customize_register', 'kajob_customize_register' );

/**
 * Helper: add a sanitized text control.
 */
function kajob_add_text_control( $wp_customize, $id, $label, $section, $default ) {
	$wp_customize->add_setting( $id, array(
		'default'           => $default,
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'postMessage',
	) );
	$wp_customize->add_control( $id, array(
		'label'   => $label,
		'section' => $section,
		'type'    => 'text',
	) );
}

/**
 * Helper: add a sanitized URL control.
 */
function kajob_add_url_control( $wp_customize, $id, $label, $section, $default ) {
	$wp_customize->add_setting( $id, array(
		'default'           => $default,
		'sanitize_callback' => 'esc_url_raw',
	) );
	$wp_customize->add_control( $id, array(
		'label'   => $label,
		'section' => $section,
		'type'    => 'url',
	) );
}

/**
 * Checkbox sanitizer.
 */
function kajob_sanitize_checkbox( $checked ) {
	return ( isset( $checked ) && true === (bool) $checked );
}

/**
 * Color scheme sanitizer: only light or dark exist.
 */
function kajob_sanitize_scheme( $value ) {
	return in_array( $value, array( 'light', 'dark' ), true ) ? $value : 'dark';
}
