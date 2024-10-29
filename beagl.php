<?php
/**
 * Plugin Name: Beagl
 * Plugin URI:  http://beagl.in
 * Description: The block form editor. Use our Drag & Drop form builder to create your WordPress forms.
 * Author:      Eugen Guriev
 * Author URI:  http://beagl.in
 * Version:     1.0.4
 * Text Domain: beagl
 * Domain Path: languages
 *
 * @category  Beagl
 * @package   Beagl
 * @author    Eugen Guriev <eg@beagl.in>
 * @copyright 2019 Beagl
 * @license   https://www.gnu.org/licenses/gpl-3.0.en.html GNU public license
 * @link      http://beagl.in
 * @since     1.0.4
 */

if ( ! function_exists( 'lolita' ) ) {
	require_once 'lolita/lolita.php';
}

/**
 * Logo url.
 *
 * @return string
 */
function bgl_logo_url() {
	return BEAGL_PLUGIN_URL . 'app/assets/img/beagl.svg';
}

/**
 * Plugin url.
 *
 * @return string
 */
function bgl_plugin_url() {
	return plugin_dir_url( __FILE__ );
}

/**
 * Plugin dir.
 *
 * @return string
 */
function bgl_plugin_dir() {
	return __DIR__;
}

lolita( __DIR__ . '/app/' );
