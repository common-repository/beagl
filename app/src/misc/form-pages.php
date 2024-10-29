<?php

namespace beagl\misc\formpages;

use function lolita\chain;
use function lolita\arr\get;
use function lolita\arr\set;
use function lolita\arr\only;
use function lolita\functions\iif;


/**
 * Override query vars to prevent redirecting.
 *
 * @param  WP    $wp   WP instance.
 * @param  Array $form Form object.
 *
 * @return Function
 */
function override_query_vars( $wp, $form ) {
	$wp->query_vars = array(
		'post_type' => 'beagl-form',
		'page_id'   => $form['ID'],
	);
	return $form;
}

/**
 * Handle the request.
 *
 * @param WP $wp WP instance.
 */
function handle_request( $wp ) {
	chain(
		array(
			get( $wp->query_vars, 'name' ),
			get( $wp->query_vars, 'pagename' ),
		)
	)
		->array_filter()
		->head()
		->thru( 'beagl\api\form\get_by_slug' )
		->thru( 'beagl\api\form\get_form' )
		->thru(
			iif(
				'is_array',
				function( $form ) use ( $wp ) {
					return hooks( override_query_vars( $wp, $form ) );
				}
			)
		)
		->value();
}

/**
 * Form Page specific hooks.
 *
 * @param array $form Form object.
 */
function hooks( $form ) {
	add_filter( 'template_include', 'beagl\misc\formpages\get_form_template', PHP_INT_MAX );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	add_filter( 'document_title_parts', change_page_title( $form ) );
	add_action( 'wp_print_styles', 'beagl\misc\formpages\css_compatibility_mode' );
	add_action( 'wp_enqueue_scripts', 'beagl\misc\formpages\enqueue_scripts' );
}

/**
 * Enqueue scripts and styles.
 */
function enqueue_scripts() {
	wp_enqueue_style(
		'beagl-single-form',
		BEAGL_PLUGIN_URL . 'app/assets/css/single-form.css',
		array(),
		BEAGL_PLUGIN_VERSION
	);
	do_action( 'beagl_single_form_enqueue_styles' );
}

/**
 * The list of path what's should be dequeued.
 *
 * @return array
 */
function whats_should_be_filtered() {
	$theme_uri  = get_stylesheet_directory_uri();
	$upload_uri = wp_get_upload_dir();
	$upload_uri = get( $upload_uri, 'baseurl', $theme_uri );

	return chain(
		array(
			$theme_uri,
			get_template_directory_uri(),
			$upload_uri,
		)
	)
	->map( 'wp_make_link_relative' )
	->array_unique()
	->value();
}

/**
 * Is should be filtered?
 *
 * @param  String $src Url source.
 *
 * @return boolean
 */
function is_should_be_filtered( $src ) {
	return chain( whats_should_be_filtered() )
		->map(
			function( $part_src ) use ( $src ) {
				return strpos( $src, $part_src );
			}
		)
		->array_filter(
			function( $el ) {
				return false !== $el;
			}
		)
		->thru(
			function( $arr ) {
				return ! ! $arr;
			}
		)
		->value();
}


/**
 * Convert queue item to src string;
 *
 * @param  mixed $el Queue item.
 *
 * @return string
 */
function queue_item_to_src( $el ) {
	return wp_make_link_relative( $el->src );
}

/**
 * Unload CSS potentially interfering with Conversational Forms layout.
 *
 * @return void
 */
function css_compatibility_mode() {
	if ( ! apply_filters( 'beagl_css_compatibility_mode', true ) ) {
		return;
	}
	chain( wp_styles()->registered )
		->only( wp_styles()->queue )
		->map( 'beagl\misc\formpages\queue_item_to_src' )
		->array_filter( 'beagl\misc\formpages\is_should_be_filtered' )
		->array_keys()
		->map( 'wp_dequeue_style' );
}

/**
 * Form Page template.
 */
function get_form_template() {
	return bgl_plugin_dir() . '/app/src/templates/single-form.php';
}

/**
 * Change document title to a custom form title.
 *
 * @param array $form Form object.
 *
 * @return mixed
 */
function change_page_title( $form ) {
	return function( $title ) use ( $form ) {
		return set(
			$title,
			'title',
			get(
				$form,
				'post_title',
				$title['title']
			)
		);
	};
}
