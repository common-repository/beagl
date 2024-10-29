<?php

namespace beagl\api\form;

use function lolita\arr\get;
use function lolita\chain;
use function lolita\str\len;
use function lolita\loc\wpdb as db;

/**
 * Add one form.
 *
 * @param  array $data Form data.
 *
 * @return int
 */
function add( $data ) {
	$data = array_merge(
		array(
			'post_type'   => 'beagl-form',
			'post_status' => 'publish',
		),
		$data
	);

	return wp_insert_post( $data );
}

/**
 * Delete form with all entries.
 *
 * @param  mixed $id Form ID.
 *
 * @return array
 */
function delete( $id ) {
	return chain(
		get_posts(
			array(
				'post_parent' => intval( $id ),
				'numberposts' => -1,
				'fields'      => 'ids',
				'post_type'   => 'beagl-entry',
			)
		)
	)
	->unshift( $id )
	->map( 'wp_delete_post' )
	->value();
}


/**
 * Get one form.
 *
 * @param  int $id Form ID.
 *
 * @return WP_Post
 */
function get_form( $id ) {
	$id   = intval( $id );
	$form = get_post( $id, ARRAY_A );
	if ( null !== $form ) {
		return array_merge(
			$form,
			array( 'submit' => get_post_meta( $id, 'submit' ) ),
			array( 'settings' => get_post_meta( $id, 'settings' ) )
		);
	}
	return false;
}

/**
 * Is valid form?
 *
 * @param  array $form WP_Post.
 *
 * @return boolean
 */
function is_valid_form( $form ) {
	if ( ! is_array( $form ) ) {
		return false;
	}
	return true;
}

/**
 * Prepare post for frontend.
 *
 * @param  array $form WP_Post.
 *
 * @return array
 */
function to_fe( $form ) {
	return array(
		'id'       => intval( $form['ID'] ),
		'title'    => $form['post_title'],
		'content'  => json_decode( $form['post_content'], true ),
		'date'     => $form['post_date'],
		'modified' => $form['post_modified'],
		'name'     => $form['post_name'],
		'submit'   => get( $form, 'submit.0', array() ),
		'settings' => get( $form, 'settings.0', array() ),
		'count'    => intval( $form['comment_count'] ),
	);
}

/**
 * Conver from request to WP.
 *
 * @param  int $form_id Form ID.
 *
 * @return Function
 */
function to_wp( $form_id ) {
	return function( $data ) use ( $form_id ) {
		$default_submit = array(
			'title'       => 'Submit button',
			'description' => 'The form submit button.',
			'icon'        => array(
				'path'    => 'M400 32H48C21.49 32 0 53.49 0 80v352c0 26.51 21.49 48 48 48h352c26.51 0 48-21.49 48-48V80c0-26.51-21.49-48-48-48zm0 32c8.823 0 16 7.178 16 16v352c0 8.822-7.177 16-16 16H48c-8.822 0-16-7.178-16-16V80c0-8.822 7.178-16 16-16h352m-34.301 98.293l-8.451-8.52c-4.667-4.705-12.265-4.736-16.97-.068l-163.441 162.13-68.976-69.533c-4.667-4.705-12.265-4.736-16.97-.068l-8.52 8.451c-4.705 4.667-4.736 12.265-.068 16.97l85.878 86.572c4.667 4.705 12.265 4.736 16.97.068l180.48-179.032c4.704-4.667 4.735-12.265.068-16.97z',
				'viewBox' => '0 0 448 512',
			),
			'category'    => 'System fields',
			'name'        => 'submit-button',
			'control'     => 'submit-button',
			'settings'    => 'submit-button-settings',
			'value'       => array(
				'input'     => 'Submit',
				'className' => '',
			),
		);
		return array(
			'ID'            => intval( $form_id ),
			'post_title'    => wp_strip_all_tags( $data['title'] ),
			'post_type'     => 'beagl-form',
			'post_status'   => 'publish',
			'post_content'  => wp_json_encode( get( $data, 'content', array() ) ),
			'post_modified' => current_time( 'mysql' ),
			'meta'          => array(
				'submit'   => get( $data, 'submit', $default_submit ),
				'settings' => get( $data, 'settings', array() ),
			),
		);
	};
}

/**
 * Update entries count.
 *
 * @param  int $id Form id.
 * @param  int $count New entries count.
 *
 * @return int|false The number of rows updated, or false on error.
 */
function update_count( $id, $count ) {
	return db()->update(
		db()->posts,
		array( 'comment_count' => intval( $count ) ),
		array( 'ID' => intval( $id ) )
	);
}

/**
 * Update meat by post.
 *
 * @param  mixed $data Post data.
 *
 * @return array
 */
function update_meta( $data ) {
	if ( array_key_exists( 'meta', $data ) && is_array( $data['meta'] ) ) {
		foreach ( $data['meta'] as $meta_key => $meta_value ) {
			update_post_meta( $data['ID'], $meta_key, $meta_value );
		}
	}
	return $data;
}

/**
 * Get form by slug
 *
 * @param  string $slug Form slug.
 *
 * @return mixed
 */
function get_by_slug( $slug ) {
	if ( ! is_string( $slug ) ) {
		return false;
	}
	$cache_key = "bgl_chache_key_$slug";
	$db_result = wp_cache_get( $cache_key );
	if ( false === $db_result ) {
		$db_result = db()->get_var(
			sprintf(
				'SELECT post_id
				FROM %s
				WHERE meta_key = \'settings\'
				AND meta_value LIKE \'%%s:4:"slug";s:%d:"%s"%%\'',
				db()->postmeta,
				len( $slug ),
				$slug
			)
		);
		wp_cache_set( $cache_key, $db_result );
	}

	return $db_result;
}
