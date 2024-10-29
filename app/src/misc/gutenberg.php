<?php

namespace beagl\misc\gutenberg;

use function lolita\chain;

/**
 * Register beagl gutenberf block.
 *
 * @return void
 */
function register_block() {
	register_block_type(
		'bgl/form-selector',
		array(
			'attributes'      => array(
				'formId'       => array(
					'type'    => 'string',
					'default' => '',
				),
				'displayTitle' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'className'    => array(
					'type' => 'string',
				),
			),
			'editor_style'    => 'bgl-gutenberg-form-selector',
			'render_callback' => 'beagl\misc\gutenberg\web_component',
		)
	);
}

/**
 * Get web component HTML.
 *
 * @param  mixed $atts Component attributes.
 *
 * @return string HTML.
 */
function web_component( $atts ) {
	$id = intval( \lolita\arr\get( $atts, 'formId', 0 ) );
	if ( ! $id ) {
		return '';
	}

	return '<!-- beagl.in --><script>if(null===document.getElementById("beagl-app")){var a=document.createElement("script");a.setAttribute("src","' . BEAGL_PLUGIN_URL . 'fe/dist_render/js/app.js"),a.setAttribute("id","beagl-app"),document.head.appendChild(a)}if(null===document.getElementById("beagl-app-vendors")){var b=document.createElement("script");b.setAttribute("src","' . BEAGL_PLUGIN_URL . 'fe/dist_render/js/chunk-vendors.js"),b.setAttribute("id","beagl-app-vendors"),document.head.appendChild(b)}if(null===document.getElementById("beagl-app-styles")){var s=document.createElement("link");s.setAttribute("href","' . BEAGL_PLUGIN_URL . 'fe/dist_render/css/app.css"),s.setAttribute("rel","stylesheet"),s.setAttribute("type","text/css"),s.setAttribute("media","all"),s.setAttribute("id","beagl-app-styles"),document.head.appendChild(s)}</script><bgl-render url="' . get_rest_url() . 'beagl/form/' . $id . '"></bgl-render><!-- END beagl.in -->';
}

/**
 * Beagl block editor assets.
 *
 * @return void
 */
function enqueue_block_editor_assets() {
	$i18n = array(
		'title'         => esc_html__( 'Beagl', 'beagl' ),
		'description'   => esc_html__( 'Select and display one of your forms.', 'beagl' ),
		'form_keywords' => array(
			esc_html__( 'form', 'beagl' ),
			esc_html__( 'contact', 'beagl' ),
			esc_html__( 'builder', 'beagl' ),
			esc_html__( 'survey', 'beagl' ),
		),
		'form_select'   => esc_html__( 'Select a Form', 'beagl' ),
		'form_settings' => esc_html__( 'Form Settings', 'beagl' ),
		'form_selected' => esc_html__( 'Form', 'beagl' ),
		'show_title'    => esc_html__( 'Show Title', 'beagl' ),
	);

	wp_enqueue_script( 'bgl-gutenberg-form-selector', BEAGL_PLUGIN_URL . 'app/assets/js/form-selector.min.js', array( 'wp-blocks', 'wp-i18n', 'wp-element' ), BEAGL_PLUGIN_VERSION, true );
	wp_enqueue_script( 'bgl-render-app', BEAGL_PLUGIN_URL . 'fe/dist_render/js/app.js', array(), BEAGL_PLUGIN_VERSION, true );
	wp_enqueue_script( 'bgl-render-vendors', BEAGL_PLUGIN_URL . 'fe/dist_render/js/chunk-vendors.js', array(), BEAGL_PLUGIN_VERSION, true );
	wp_enqueue_style( 'bgl-render-app', BEAGL_PLUGIN_URL . 'fe/dist_render/css/app.css', array(), BEAGL_PLUGIN_VERSION );
	$forms = chain(
		get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'beagl-form',
			)
		)
	)
	->map( 'lolita\arr\to' )
	->map(
		function( $f ) {
			return array(
				'id'    => intval( $f['ID'] ),
				'title' => htmlspecialchars_decode( esc_html( $f['post_title'] ), ENT_QUOTES ),
			);
		}
	)
	->value();

	wp_localize_script(
		'bgl-gutenberg-form-selector',
		'bgl_gutenberg_form_selector',
		array(
			'logoUrl' => BEAGL_PLUGIN_URL . 'app/assets/img/beagl.svg',
			'rest'    => get_rest_url(),
			'wpnonce' => wp_create_nonce( 'bgl-gutenberg-form-selector' ),
			'forms'   => $forms,
			'i18n'    => $i18n,
		)
	);
}
