<?php

namespace beagl\api\upload;

use \Exception;
use function lolita\chain;
use function lolita\fl\move_file;
use function lolita\fl\human_size;
use function lolita\str\guid;
use function lolita\functions\iif;
use function lolita\arr\get;

/**
 * Validate basics.
 *
 * @throws Exception File upload error. %s - error text.
 * @param  array $file File object.
 *
 * @return array File object.
 */
function basic_validation( $file ) {
	if ( 0 === $file['error'] || 4 === $file['error'] ) {
		return $file;
	}
	$errors = array(
		false,
		esc_html__( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'beagl' ),
		esc_html__( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'beagl' ),
		esc_html__( 'The uploaded file was only partially uploaded.', 'beagl' ),
		esc_html__( 'No file was uploaded.', 'beagl' ),
		'',
		esc_html__( 'Missing a temporary folder.', 'beagl' ),
		esc_html__( 'Failed to write file to disk.', 'beagl' ),
		esc_html__( 'File upload stopped by extension.', 'beagl' ),
	);

	if ( array_key_exists( $file['error'], $errors ) ) {
		/* translators: %s - error text. */
		throw new Exception( sprintf( esc_html__( 'File upload error. %s', 'beagl' ), $errors[ $file['error'] ] ) );
	}
	return $file;
}

/**
 * Validate file size.
 *
 * @throws Exception File exceeds max size allowed (%s).
 * @param  array $file File object.
 *
 * @return array File object.
 */
function size_validation( $file ) {
	$max_size = wp_max_upload_size();
	if ( $file['size'] > $max_size ) {
		throw new Exception(
			sprintf( /* translators: $s - allowed file size in Mb. */
				esc_html__( 'File exceeds max size allowed (%s).', 'beagl' ),
				human_size( $max_size )
			)
		);
	}
	return $file;
}

/**
 * Validate extension against blacklist and admin-provided list.
 * There are certain extensions we do not allow under any circumstances,
 * with no exceptions, for security purposes.
 *
 * @throws Exception File must have an extension. | File type is not allowed.
 * @param  array $file File object.
 *
 * @return array File object.
 */
function ext_validation( $file ) {
	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	// Make sure file has an extension first.
	if ( empty( $ext ) ) {
		throw new Exception( esc_html__( 'File must have an extension.', 'beagl' ) );
	}

	// Validate extension against all allowed values.
	if ( ! in_array( $ext, allowed_extensions(), true ) ) {
		throw new Exception( esc_html__( 'File type is not allowed.', 'beagl' ) );
	}

	return $file;
}

/**
 * Get allowed extensions supported by WordPress
 * without those that we manually blacklist.
 *
 * @return array
 */
function allowed_extensions() {
	return chain( get_allowed_mime_types() )
		->array_keys()
		->implode( '|' )
		->explode( '|' )
		->array_diff( black_list_ext() )
		->value();
}

/**
 * File extensions that are now allowed.
 *
 * @return array
 */
function black_list_ext() {
	return array( 'ade', 'adp', 'app', 'asp', 'bas', 'bat', 'cer', 'cgi', 'chm', 'cmd', 'com', 'cpl', 'crt', 'csh', 'csr', 'dll', 'drv', 'exe', 'fxp', 'flv', 'hlp', 'hta', 'htaccess', 'htm', 'html', 'htpasswd', 'inf', 'ins', 'isp', 'jar', 'js', 'jse', 'jsp', 'ksh', 'lnk', 'mdb', 'mde', 'mdt', 'mdw', 'msc', 'msi', 'msp', 'mst', 'ops', 'pcd', 'php', 'pif', 'pl', 'prg', 'ps1', 'ps2', 'py', 'rb', 'reg', 'scr', 'sct', 'sh', 'shb', 'shs', 'sys', 'swf', 'tmp', 'torrent', 'url', 'vb', 'vbe', 'vbs', 'vbscript', 'wsc', 'wsf', 'wsf', 'wsh', 'dfxp', 'onetmp' );
}


/**
 * Get upload dir.
 *
 * @return string
 */
function dir() {
	return chain( wp_upload_dir() )
		->get( 'basedir' )
		->concat( '', '/beagl' )
		->value();
}

/**
 * Get upload url for files.
 *
 * @return string
 */
function url() {
	return chain( wp_upload_dir() )
		->get( 'baseurl' )
		->concat( '', '/beagl' )
		->value();
}

/**
 * Get tmp dir for files.
 */
function tmp_dir() {
	return trailingslashit( dir() ) . 'tmp';
}

/**
 * Get tmp url for files.
 *
 * @return string
 */
function tmp_url() {
	return trailingslashit( url() ) . 'tmp';
}

/**
 * Is file is outdated.
 *
 * @param  mixed $file Path.
 * @param  mixed $lifespan Seconds.
 *
 * @return boolean
 */
function is_outdated( $file, $lifespan = DAY_IN_SECONDS ) {
	// In some cases filemtime() can return false, in that case - pretend this is a new file and do nothing.
	$modified = (int) filemtime( $file );
	if ( empty( $modified ) ) {
		$modified = time();
	}
	return ( time() - $modified ) >= $lifespan;
}

/**
 * Is not index.html file?.
 *
 * @param  string $file Input file.
 *
 * @return boolean
 */
function is_not_index( $file ) {
	return strpos( $file, 'index.html' ) === false;
}


/**
 * Clean up the tmp folder - remove all old files every day (filterable interval).
 *
 * @param  string $tmp_dir Temporary directory.
 *
 * @return array
 */
function clean_tmp_files( $tmp_dir ) {
	return chain( trailingslashit( $tmp_dir ) )
		->concat( '', '*' )
		->thru( 'glob' )
		->array_filter( 'beagl\api\upload\is_not_index' )
		->array_filter( 'is_file' )
		->array_filter( 'beagl\api\upload\is_outdated' )
		->map( 'lolita\fl\unlink' )
		->value();
}

/**
 * Move file to tmp dir.
 *
 * @param  string $tmp_dir Temporary directory path.
 *
 * @return string New file path.
 */
function move( $tmp_dir ) {
	return function( $file ) use ( $tmp_dir ) {
		$ext      = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$tmp_path = $file['tmp_name'];
		$new_name = guid() . '.' . $ext;
		$new_path = trailingslashit( $tmp_dir ) . $new_name;

		$file['new_name'] = $new_name;
		$file['new_path'] = move_file( $tmp_path, $new_path );
		$file['url']      = trailingslashit( tmp_url() ) . $new_name;
		return $file;
	};
}


/**
 * Prepare content before save.
 *
 * @param  array $form Form object.
 *
 * @return array Prepared form object.
 */
function prepare_content( $form ) {
	$form['content'] = chain( $form )
		->get( 'content', array() )
		->map(
			iif(
				function( $el ) {
					return 'uploader' === get( $el, 'control', '' );
				},
				'\beagl\api\upload\prepare_uploader',
				function( $el ) {
					return $el;
				}
			)
		)
		->value();
	return $form;
}

/**
 * Move file to upload dir.
 *
 * @param  string $file File object.
 *
 * @return string New file path.
 */
function move_file_to_prod( $file ) {
	$tmp_path      = trailingslashit( tmp_dir() ) . $file['new_name'];
	$file['name']  = wp_unique_filename( dir(), $file['name'] );
	$file['path']  = trailingslashit( dir() ) . $file['name'];
	$file['url']   = trailingslashit( url() ) . $file['name'];
	$file['moved'] = rename( $tmp_path, $file['path'] );
	return $file;
}

/**
 * Prepare uploader control.
 *
 * @param  array $el Uploader control object.
 *
 * @return array Prepared uploader control object.
 */
function prepare_uploader( $el ) {
	$input          = get( $el, 'value.input', array() );
	$store_in_media = get( $el, 'value.storeInMedia', false );

	$el['value']['input'] = chain( $el )
		->get( 'value.input', array() )
		->map( '\beagl\api\upload\move_file_to_prod' )
		->map(
			function( $file ) use ( $store_in_media ) {
				if ( true === $store_in_media ) {
					return insert_attachment( $file );
				}
				return $file;
			}
		)
		->value();

	$el['value']['final'] = chain( $el['value']['input'] )
		->map(
			function( $el ) {
				return $el['url'];
			}
		)
		->implode( ', ' )
		->value();
	return $el;
}

/**
 * Insert attachment to media library.
 *
 * @throws Exception Something went wrong with the attachment creating.
 * @param  array $file File object.
 *
 * @return array File object.
 */
function insert_attachment( $file ) {
	include_once ABSPATH . 'wp-admin/includes/image.php';
	$attachment_id = wp_insert_attachment(
		array(
			'post_title'     => wp_basename( $file['url'] ),
			'post_status'    => 'publish',
			'post_mime_type' => $file['type'],
		),
		$file['path']
	);

	if ( empty( $attachment_id ) ) {
		throw new Exception( esc_html__( 'Something went wrong with the attachment creating.', 'beagl' ) );
	}

	// Generate attachment meta.
	wp_update_attachment_metadata(
		$attachment_id,
		array_merge(
			array( 'beagl' => true ),
			wp_generate_attachment_metadata( $attachment_id, $file['path'] )
		)
	);

	// Update file url/name.
	$file['url']           = wp_get_attachment_url( $attachment_id );
	$file['attachment_id'] = $attachment_id;
	return $file;
}
