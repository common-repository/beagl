<?php

namespace beagl\rest\uploads;

use \Exception;
use function lolita\chain;
use function beagl\api\upload\tmp_dir;
use function beagl\api\upload\clean_tmp_files;
use function beagl\api\upload\move;
use function beagl\api\upload\url;

/**
 * Add entry.
 *
 * @param  WP_REST_Request $request Request object.
 *
 * @return void
 */
function post( $request ) {
	try {
		$tmp_dir = chain( tmp_dir() )
			->thru( 'lolita\fl\mkdir' )
			->value();

		clean_tmp_files( $tmp_dir );

		chain( $request->get_file_params() )
			->get( 'file' )
			->thru( 'beagl\api\upload\basic_validation' )
			->thru( 'beagl\api\upload\size_validation' )
			->thru( 'beagl\api\upload\ext_validation' )
			->thru( move( $tmp_dir ) )
			->forget( array( 'new_path', 'error', 'tmp_name' ) )
			->thru(
				function( $file ) {
					$file['candidate'] = trailingslashit( url() ) . $file['name'];
					return $file;
				}
			)
			->thru( 'wp_send_json_success' )
			->value();
	} catch ( Exception $e ) {
		wp_send_json_error( $e->getMessage(), 500 );
	}
}
