<?php

namespace beagl\misc\security;

/**
 * Is can create.
 *
 * @return void
 */
function is_can_create() {
	if ( ! defined( 'BGL_CAN_CREATE_GUARD_OFF' ) && ! current_user_can( 'editor' ) && ! current_user_can( 'administrator' ) ) {
		wp_send_json_error( 'Access denied! You can not create!', 500 );
	}
}
