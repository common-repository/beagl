<?php

namespace beagl\rest\notification;

use function lolita\chain;
use function beagl\api\notification\headers;

/**
 * Send notification.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function post( $request ) {
	try {
		chain( json_decode( $request->get_body(), true ) )
			->thru( 'beagl\api\notification\check_data' )
			->thru(
				function( $data ) {
					return wp_mail( $data['to'], $data['subject'], $data['message'], headers( $data ) );
				}
			)
			->thru( 'wp_send_json_success' );
	} catch ( Exception $e ) {
		wp_send_json_error( $e->getMessage() );
	}
}
