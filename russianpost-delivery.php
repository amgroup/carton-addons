<?php 
/*
Plugin Name: CartOn RussianPost Devivery
Plugin URI: http://kidberries.com
Description: Это расширение позволит покупателю выбрать доставку с помощью Почты России и наложенный платеж как вариант оплаты покупки.

Version: 1.0.0
Author: Kidberries.com team
Author URI: http://kidberries.com/
*/

define('PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY',plugin_dir_path(__FILE__));
define('PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY_CLASSES', PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY . 'carton/classes/');
define('RUSSIANPOST_DELIVERY_POSTCODES_FILE', PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY . 'carton/data/russianpost-delivery/pindx.csv' );


if( !defined( 'PLUGIN_VERSION_RUSSIANPOST_DELIVERY' ) )
	define( 'PLUGIN_VERSION_RUSSIANPOST_DELIVERY', '1.0.0' );

function append_russianpost_delivery_shipping_method() {
	if ( !class_exists('WC_Shipping_Method') )
		return; // if the parent class is not available, do nothing
	include_once (PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY_CLASSES . 'class-carton-shipping-russianpost.php');
}


function append_russianpost_cod_payment_method() {
	if ( !class_exists('WC_Payment_Gateway') )
		return; // if the woocommerce payment gateway class is not available, do nothing
	include_once (PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY_CLASSES . 'class-carton-gateway-russianpost-cod.php');
}
/*
function append_russianpost_cod_payment_method_extra_charges() {
	if ( !class_exists('WC_Gateway_Russianpost_COD_Charges') )
		return; // if the woocommerce payment gateway class is not available, do nothing
	include_once (PLUGIN_DIR_PATH_RUSSIANPOST_DELIVERY_CLASSES . 'class-carton-gateway-russianpost-cod-extra-charges.php');
	new WC_Gateway_Russianpost_COD_Charges();
}
*/
add_action('plugins_loaded', 'append_russianpost_delivery_shipping_method', 0);
add_action('plugins_loaded', 'append_russianpost_cod_payment_method', 0);
//add_action('plugins_loaded', 'append_russianpost_cod_payment_method_extra_charges', 0);



/*
 *
 * AJAX dinamic load russian post delivery places
*/
if( !function_exists( 'get_russianpost_places' ) ) {
    function get_russianpost_places(){
        global $woocommerce;
        
        $pattern = '/^(' . mb_strtolower( ($_POST['place'] ? $_POST['place'] : $_GET['place'] ), 'utf-8' ) . ')/';
        $file  = RUSSIANPOST_DELIVERY_POSTCODES_FILE;
        
        if( file_exists( $file ) ) {
            header( 'Content-Type: application/javascript; charset=UTF-8' );

        
            $places = file( $file );
            $count  = sizeof( $places );
            
            $result = array();

			for( $i=0; $i <= $count; $i++ ){
				if( isset( $places[$i] ) ) {
                    if( preg_match ( $pattern, mb_strtolower( $places[$i], 'utf-8' ), $matches ) ) {
                        $column = split(';',  trim ($places[$i]) );
                        $part   = split(', ',  $column[0] );
                        $result[] = array( 'value' => $column[1], 'place'=>$part[0], 'text' => $column[0] );
                    }
				}
			}
            echo json_encode( $result );
        }
        die();
    }
    add_action( 'wp_ajax_nopriv_get_russianpost_places', 'get_russianpost_places' );
    add_action( 'wp_ajax_get_russianpost_places', 'get_russianpost_places' );
}


?>