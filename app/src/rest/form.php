<?php

namespace beagl\rest\form;

use function lolita\chain;
use function beagl\misc\security\is_can_create;
use function beagl\api\form\to_wp;

/**
 * Get form.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function get( $request ) {
	$form_id = intval( $request->get_param( 'id' ) );
	chain( $form_id )
		->thru( 'beagl\api\form\get_form' )
		->iif(
			'beagl\api\form\is_valid_form',
			'beagl\api\form\to_fe',
			function() use ( $form_id ) {
				wp_send_json_error( sprintf( 'Form "%d" not found!', $form_id ), 500 );
				die();
			}
		)
		->thru( 'wp_send_json_success' );
}

/**
 * Save form.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function save( $request ) {
	is_can_create();
	chain( json_decode( $request->get_body(), true ) )
		->thru( to_wp( intval( $request->get_param( 'id' ) ) ) )
		->thru( 'beagl\api\form\update_meta' )
		->thru( 'wp_update_post' )
		->thru( 'wp_send_json_success' );
}
