<?php 
/*
Plugin Name: CartOn ipGeobase
Plugin URI: http://kidberries.com
Description: Это расширение позволит определить местонахождение клиента (только для России и Украины) в php коде. Вколючите и используйте массив (array) $geoip.

Version: 1.0.0
Author: Kidberries.com team
Author URI: https://github.com/rossvs/ipgeobase.php
*/

define('PLUGIN_DIR_PATH_CARTON_IPGEOBASE',plugin_dir_path(__FILE__));
define('CARTON_IPGEOBASE_CLASSES', PLUGIN_DIR_PATH_CARTON_IPGEOBASE . 'carton/classes/');
define('CARTON_IPGEOBASE_DATA', PLUGIN_DIR_PATH_CARTON_IPGEOBASE . 'carton/data/ipgeobase/');

if( !defined( 'PLUGIN_VERSION_CARTON_IPGEOBASE' ) )
	define( 'PLUGIN_VERSION_CARTON_IPGEOBASE', '1.0.0' );

function append_carton_ipgeobase() {
	global $_geoip_db;
	include_once (CARTON_IPGEOBASE_CLASSES . 'ipgeobase.php');

	$_geoip_db = new IPGeoBase( CARTON_IPGEOBASE_DATA . 'cidr_optim.txt', CARTON_IPGEOBASE_DATA . 'cities.txt' );
}

add_action('plugins_loaded', 'append_carton_ipgeobase', 0);


if( !function_exists( 'get_geoip_location' ) ) {
    function get_geoip_location() {
	global $_geoip_db;

        header( 'Content-Type: application/json; charset=UTF-8' );
	$geoip = $_geoip_db->getRecord( $_SERVER["HTTP_X_FORWARDED_FOR"] ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["HTTP_X_REAL_IP"] );
	foreach ( $geoip as $key => $value ) {
		$geoip[ $key ] = iconv( "CP1251", "UTF-8", $geoip[ $key ] );
	}

	echo json_encode( $geoip );
	die();
    }
}

add_action( 'wp_ajax_nopriv_get_geoip_location', 'get_geoip_location' );
add_action( 'wp_ajax_get_geoip_location', 'get_geoip_location' );

?>