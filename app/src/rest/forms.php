<?php

namespace beagl\rest\forms;

use function lolita\chain;
use function beagl\api\form\to_wp;
use function beagl\api\form\update_count;
use function beagl\misc\security\is_can_create;

/**
 * Add form.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function post( $request ) {
	is_can_create();

	chain( json_decode( $request->get_body(), true ) )
		->thru( to_wp( 0 ) )
		->thru( 'beagl\api\form\add' )
		->thru( 'beagl\api\form\get_form' )
		->thru( 'beagl\api\form\to_fe' )
		->thru( 'wp_send_json_success' );
}

/**
 * Get forms.
 *
 * @return void
 */
function get() {
	chain(
		get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'beagl-form',
				'fields'      => 'ids',
			)
		)
	)
	->map( 'beagl\api\form\get_form' )
	->map( 'beagl\api\form\to_fe' )
	->thru( 'wp_send_json_success' );
}

/**
 * Delete forms.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function delete( $request ) {
	is_can_create();
	chain( json_decode( $request->get_body() ) )
		->map( 'beagl\api\form\delete' )
		->thru( 'wp_send_json_success' );
}

/**
 * Update entries counter per each.
 *
 * @return void
 */
function update_entries_count() {
	is_can_create();
	chain(
		get_posts(
			array(
				'numberposts' => -1,
				'post_type'   => 'beagl-form',
			)
		)
	)
	->map( 'lolita\arr\to' )
	->map(
		function( $post ) {
			return update_count(
				intval( $post['ID'] ),
				count(
					get_posts(
						array(
							'post_parent' => intval( $post['ID'] ),
							'numberposts' => -1,
							'post_type'   => 'beagl-entry',
						)
					)
				)
			);
		}
	)
	->thru( 'wp_send_json_success' );
}
