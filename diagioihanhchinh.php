<?php
/**
 * Plugin Name: Địa Giới Hành Chính Việt Nam
 * Plugin URI: https://github.com/puleeno/diagioihanhchinh
 * Author: Puleeno Nguyen
 * Author URI: https://puleeno.com
 * Version: 1.0.0
 * Description: Triển khai địa giới hành chính cho WordPress hỗ trợ WP-JSON (Rest), Boundaries data cho bản đồ
 */

if ( ! defined( 'DIAGIOIHANHCHINH_PLUGIN_FILE' ) ) {
	define( 'DIAGIOIHANHCHINH_PLUGIN_FILE', __FILE__ );
}

$composer_autoloader = sprintf( '%s/vendor/autoload.php', dirname( __FILE__ ) );
if ( ! file_exists( $composer_autoloader ) ) {
	return;
}
require_once $composer_autoloader;

if ( ! class_exists( Diagioihanhchinh::class ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-diagioihanhchinh.php';
}

if ( ! function_exists( 'diagioihanhchinh' ) ) {
	function diagioihanhchinh() {
		return Diagioihanhchinh::get_instance();
	}
}

$GLOBALS['diagioihanhchinh'] = diagioihanhchinh();
