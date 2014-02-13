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


	//if( is_product() || is_cart() || is_checkout() ) { TODO
		wp_register_script( 'bootstrap-js', plugin_dir_url(__FILE__) . 'js/bootstrap/bootstrap.min.js', array( 'jquery' ) );
		wp_register_script( 'shoplogistics-delivery-ymap-js', plugin_dir_url(__FILE__) . 'js/shoplogistics-delivery/shoplogistics-delivery-ymap.js', array( 'jquery', 'bootstrap-js' ) );
		wp_register_script( 'api-maps-yandex', 'http://api-maps.yandex.ru/2.0-stable/?load=package.standard&lang=ru-RU', array( 'jquery', 'bootstrap-js' ) );

		wp_enqueue_style( 'bootstrap-buttons-css', plugin_dir_url(__FILE__) . 'css/bootstrap/bootstrap.buttons.css' );
		wp_enqueue_style( 'bootstrap-modal-css', plugin_dir_url(__FILE__) . 'css/bootstrap/bootstrap.modal.css' );
		wp_enqueue_style( 'bootstrap-glyphicon-css', plugin_dir_url(__FILE__) . 'css/bootstrap/bootstrap.glyphicon.css' );
		wp_enqueue_style( 'shoplogistics-delivery-ymap-css', plugin_dir_url(__FILE__) . 'css/shoplogistics-delivery/shoplogistics-delivery-ymap.css' );

		wp_enqueue_script( 'bootstrap-js' );
		wp_enqueue_script( 'shoplogistics-delivery-ymap-js' );
		wp_enqueue_script( 'api-maps-yandex' );
	//}
}

add_action( 'plugins_loaded', 'append_shoplogistics_delivery_shipping_method' );
?>