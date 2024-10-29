<?php

namespace beagl\api\entry;

/**
 * Conver from request to WP.
 *
 * @param  int $form_id Form ID.
 *
 * @return Function
 */
function to_wp( $form_id ) {
	return function( $data ) use ( $form_id ) {
		return array_merge(
			array(
				'post_title'    => sanitize_text_field( $data['title'] ),
				'post_type'     => 'beagl-entry',
				'post_status'   => 'publish',
				'post_content'  => wp_json_encode( $data ),
				'post_modified' => current_time( 'mysql' ),
				'post_parent'   => intval( $form_id ),
			),
			$data
		);
	};
}

/**
 * Prepare entry for frontend.
 *
 * @param  array $data WP_Post.
 *
 * @return array
 */
function to_fe( $data ) {
	return array(
		'id'       => intval( $data['ID'] ),
		'content'  => json_decode( $data['post_content'], true ),
		'date'     => $data['post_date'],
		'modified' => $data['post_modified'],
		'name'     => $data['post_name'],
	);
}

/**
 * Get one entry.
 *
 * @param  int $id Entry ID.
 *
 * @return WP_Post
 */
function get( $id ) {
	return get_post( intval( $id ), ARRAY_A );
}

/**
 * Delete entry.
 *
 * @param  mixed $id Form ID.
 *
 * @return array
 */
function delete( $id ) {
	return wp_delete_post( $id );
}
