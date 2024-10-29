<?php

namespace beagl\rest\entries;

use function lolita\chain;
use function beagl\api\entry\to_wp;
use function beagl\api\form\update_count;
use function beagl\misc\security\is_can_create;

/**
 * Add entry.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function post( $request ) {
	$form_id = intval( $request->get_param( 'id' ) );
	chain( json_decode( $request->get_body(), true ) )
		->thru( 'beagl\api\upload\prepare_content' )
		->thru( to_wp( $form_id ) )
		->thru( 'wp_insert_post' )
		->thru(
			function( $entry_id ) use ( $form_id ) {
				update_count(
					$form_id,
					count(
						get_posts(
							array(
								'post_parent' => $form_id,
								'numberposts' => -1,
								'post_type'   => 'beagl-entry',
							)
						)
					)
				);
				return $entry_id;
			}
		)
		->thru( 'wp_send_json_success' );
}

/**
 * Get entries.
 *
 * @param  mixed $request Request object.
 *
 * @return void
 */
function get( $request ) {
	is_can_create();
	$form_id = intval( $request->get_param( 'id' ) );
	chain(
		get_posts(
			array(
				'post_parent' => $form_id,
				'numberposts' => -1,
				'post_type'   => 'beagl-entry',
			)
		)
	)
	->map( 'lolita\arr\to' )
	->map( 'beagl\api\entry\to_fe' )
	->thru( 'wp_send_json_success' );
}

/**
 * Delete entries.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function delete( $request ) {
	is_can_create();
	chain( json_decode( $request->get_body() ) )
		->map( 'beagl\api\entry\delete' )
		->thru( 'wp_send_json_success' );
}
