<?php 
/*
Plugin Name: CartOn ShopLogistics Devivery
Plugin URI: http://kidberries.com
Description: Это расширение позволит покупателю выбрать доставку с помощью ShopLogistics.

Version: 1.0.0
Author: Kidberries.com team
Author URI: http://kidberries.com/
*/

define('PLUGIN_DIR_PATH_SHOPLOGISTICS_DELIVERY',plugin_dir_path(__FILE__));
define('PLUGIN_DIR_PATH_SHOPLOGISTICS_DELIVERY_CLASSES', PLUGIN_DIR_PATH_SHOPLOGISTICS_DELIVERY . 'carton/classes/');
define('SHOPLOGISTICS_POSTCODES_FILE', PLUGIN_DIR_PATH_SHOPLOGISTICS_DELIVERY . 'carton/data/shoplogistics-delivery/postcodes.csv');


if( !defined( 'PLUGIN_VERSION_SHOPLOGISTICS_DELIVERY' ) )
	define( 'PLUGIN_VERSION_SHOPLOGISTICS_DELIVERY', '1.0.0' );

function append_shoplogistics_delivery_shipping_method() {
	if ( !class_exists('WC_Shipping_Method') )
		return; // if the parent class is not available, do nothing
	include_once (PLUGIN_DIR_PATH_SHOPLOGISTICS_DELIVERY_CLASSES . 'class-carton-shipping-shoplogistics.php');
}
add_action( 'plugins_loaded', 'append_shoplogistics_delivery_shipping_method' );
?>