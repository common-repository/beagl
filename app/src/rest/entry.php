<?php

namespace beagl\rest\entry;

use function lolita\chain;

/**
 * Get entry.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function get( $request ) {
	chain( intval( $request->get_param( 'id' ) ) )
		->thru( 'beagl\api\entry\get' )
		->thru( 'beagl\api\entry\to_fe' )
		->thru( 'wp_send_json_success' );
}
