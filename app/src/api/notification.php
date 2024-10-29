<?php

namespace beagl\api\notification;

use function lolita\chain;

/**
 * Check one element by required.
 *
 * @param array $data Request data.
 *
 * @return Function
 */
function check_required( $data ) {
	return function( $el ) use ( $data ) {
		if ( empty( $data[ $el ] ) ) {
			throw new Exception( '"' . $el . '" cant be empty.' );
		}
		return $el;
	};
}

/**
 * Add one form.
 *
 * @param  array $data Request data.
 * @throws Exception Wrong email address: {email addres}.
 *
 * @return int
 */
function check_data( $data ) {
	if ( ! is_email( $data['to'] ) ) {
		throw new Exception( 'Wrong email address:' . $data['to'] );
	}
	chain( array( 'subject', 'message', 'fromName', 'fromEmail' ) )
		->map( check_required( $data ) );
	return $data;
}



/**
 * Get headers for email.
 *
 * @param array $data Request data.
 *
 * @return array
 */
function headers( $data ) {
	$headers = array(
		'Content-Type: {$this->get_content_type()}; charset=utf-8',
		'From: ' . $data['fromName'] . '<' . $data['fromEmail'] . '>',
	);

	if ( ! empty( $data['reply'] ) ) {
		$headers[] = 'Reply-To: ' . $data['reply'];
	}
	return $headers;
}
