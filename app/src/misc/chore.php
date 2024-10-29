<?php

namespace beagl\misc\chore;

use function lolita\view\render;
use function lolita\arr\get;

/**
 * Activation redirect.
 *
 * @param string $plugin Plugin basename.
 *
 * @return void
 */
function activation_redirect( $plugin ) {
	if ( plugin_basename( __FILE__ ) === $plugin ) {
		wp_safe_redirect( admin_url( 'index.php?page=beagl#/hello' ) );
		exit;
	}
}

/**
 * Allow cors.
 */
function cors() {
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
	header( 'Access-Control-Allow-Credentials: true' );
}

/**
 * Add custom links to menu.
 *
 * @return void
 */
function admin_custom_links() {
	global $submenu;
	// phpcs:ignore
	$submenu['beagl'][] = array(
		__( 'All forms', 'beagl' ),
		'manage_options',
		admin_url( 'admin.php?page=beagl' ),
	);
}

/**
 * Builder page scripts.
 *
 * @return void
 */
function builder_page_scripts() {
	wp_enqueue_script( 'chunk-vendors', BEAGL_PLUGIN_URL . 'fe/dist_builder/js/chunk-vendors.js', array(), BEAGL_PLUGIN_VERSION, true );
	wp_enqueue_script( 'beagl-app', BEAGL_PLUGIN_URL . 'fe/dist_builder/js/app.js', array(), BEAGL_PLUGIN_VERSION, true );
	wp_localize_script(
		'beagl-app',
		'beaglApp',
		array(
			'ajaxUrl'         => get_rest_url() . 'beagl/',
			'pluginUrl'       => bgl_plugin_url(),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'adminEmail'      => get_bloginfo( 'admin_email' ),
			'textDirection'   => 'ltr',
			'backgroundColor' => '#e9eaec',
			'blogName'        => wp_specialchars_decode( get_bloginfo( 'name' ) ),
			'homeUrl'         => esc_url( home_url() ),
		)
	);
}

/**
 * Builder styles.
 *
 * @return void
 */
function builder_page_styles() {
	wp_enqueue_style( 'beagl-app', BEAGL_PLUGIN_URL . 'fe/dist_builder/css/app.css', array(), BEAGL_PLUGIN_VERSION );
	wp_enqueue_style( 'stabilize', BEAGL_PLUGIN_URL . 'app/assets/css/stabilize.css', array(), BEAGL_PLUGIN_VERSION );

	// Remove native wp forms styles. Forms styles hack :-).
	wp_deregister_style( 'forms' );
	wp_enqueue_style( 'forms', BEAGL_PLUGIN_URL . 'app/assets/css/forms.css', array(), BEAGL_PLUGIN_VERSION );
}

/**
 * Admin page styles.
 *
 * @return void
 */
function admin_styles() {
	wp_enqueue_style( 'beagl-admin', BEAGL_PLUGIN_URL . 'app/assets/css/admin.css', array(), BEAGL_PLUGIN_VERSION );

	$screen = get_current_screen();
	if ( isset( $screen->id ) && 'dashboard' === $screen->id ) {
		wp_enqueue_style(
			'beagl-admin-dashboard-widget',
			BEAGL_PLUGIN_URL . 'app/assets/css/dashboard-widget.css',
			array(),
			BEAGL_PLUGIN_VERSION
		);
	}
}

/**
 * Render view.
 *
 * @param array $data Include data.
 * @param type  $path View path.
 *
 * @return rendered html
 */
function view( $data = array(), $path ) {
	return render( $data, bgl_plugin_dir() . '/app/src/views/' . $path );
}

/**
 * Builder page HTML.
 *
 * @return void
 */
function builder_page() {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo view( array(), 'builder-page' );
}

/**
 * Beagl shortcode.
 *
 * @param  mixed $atts Attributes.
 *
 * @return string HTML.
 */
function shortcode( $atts ) {
	$id = get( $atts, 'id', 0 );
	return '<!-- beagl.in --><script>if(null===document.getElementById("beagl-app")){var a=document.createElement("script");a.setAttribute("src","' . BEAGL_PLUGIN_URL . 'fe/dist_render/js/app.js"),a.setAttribute("id","beagl-app"),document.head.appendChild(a)}if(null===document.getElementById("beagl-app-vendors")){var b=document.createElement("script");b.setAttribute("src","' . BEAGL_PLUGIN_URL . 'fe/dist_render/js/chunk-vendors.js"),b.setAttribute("id","beagl-app-vendors"),document.head.appendChild(b)}if(null===document.getElementById("beagl-app-styles")){var s=document.createElement("link");s.setAttribute("href","' . BEAGL_PLUGIN_URL . 'fe/dist_render/css/app.css"),s.setAttribute("rel","stylesheet"),s.setAttribute("type","text/css"),s.setAttribute("media","all"),s.setAttribute("id","beagl-app-styles"),document.head.appendChild(s)}</script><bgl-render url="' . get_rest_url() . 'beagl/form/' . $id . '"></bgl-render><!-- END beagl.in -->';
}
add_shortcode( 'beagl', 'beagl\misc\chore\shortcode' );
