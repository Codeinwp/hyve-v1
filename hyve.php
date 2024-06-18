<?php
/**
 * Hyve.
 *
 * @package Codeinwp/hyve
 *
 * Plugin Name:       Hyve
 * Plugin URI:        https://themeisle.com/
 * Description:       An AI powered chatbot.
 * Version:           1.0.3
 * Author:            ThemeIsle
 * Author URI:        https://themeisle.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       hyve
 * Domain Path:       /languages
 * Requires License:  yes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'HYVE_BASEFILE', __FILE__ );
define( 'HYVE_URL', plugins_url( '/', __FILE__ ) );
define( 'HYVE_PATH', __DIR__ );
define( 'HYVE_VERSION', '1.0.3' );

$vendor_file = HYVE_PATH . '/vendor/autoload.php';

if ( is_readable( $vendor_file ) ) {
	require_once $vendor_file;
}

add_filter(
	'themeisle_sdk_products',
	function ( $products ) {
		$products[] = HYVE_BASEFILE;

		return $products;
	}
);

add_filter(
	'themesle_sdk_namespace_' . md5( HYVE_BASEFILE ),
	function () {
		return 'hyve';
	}
);

add_filter(
	'hyve_lc_no_valid_string',
	function ( $message ) {
		return str_replace( '<a href="%s">', '<a href="' . add_query_arg( 'page', 'hyve', admin_url( 'admin.php' ) ) . '">', $message );
	}
);

add_filter( 'hyve_hide_license_field', '__return_true' );

new \ThemeIsle\Hyve\Main();
