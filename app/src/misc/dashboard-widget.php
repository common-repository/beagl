<?php

namespace beagl\misc\dashboard;

use function \lolita\arr\move;
use function beagl\misc\chore\view;

/**
 * Init dashboard widget.
 *
 * @return void
 */
function widget_init() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! apply_filters( 'beagl_dashboard_widget', '__return_true' ) ) {
		return;
	}

	widget_hooks();
}

/**
 * Dashboard widget hooks.
 *
 * @return void
 */
function widget_hooks() {
	add_action( 'wp_dashboard_setup', 'beagl\misc\dashboard\widget_register' );
}

/**
 * Dashboard widget register.
 *
 * @return void
 */
function widget_register() {
	global $wp_meta_boxes;

	wp_add_dashboard_widget(
		BEAGL_DASHBOARD_WIDGET,
		esc_html__( 'Beagl', 'beagl' ),
		'beagl\misc\dashboard\widget_content'
	);
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$wp_meta_boxes['dashboard']['normal']['core'] = move( $wp_meta_boxes['dashboard']['normal']['core'], BEAGL_DASHBOARD_WIDGET, 0 );
}

/**
 * Dashboard widget register.
 *
 * @return void
 */
function widget_content() {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo view(
		array(
			'logo' => esc_attr( bgl_plugin_url() . '/app/assets/img/beagl.svg' ),
			'url'  => esc_attr( admin_url( 'index.php?page=beagl#/hello' ) ),
		),
		'dashboard_widget'
	);
}
