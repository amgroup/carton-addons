<?php
/*
Plugin Name: WooCommerce Russianpost Cash on Delivery Gateway Extra Charges
Plugin URI: http://kidberries.com
Description: Russianpost Cash on Delivery Gateway Extra Charges 
Version: 1.0.0
Author: Andrey Guryev
Author URI: http://kidberries.com
*/

/**
 * Russianpost Cash on Delivery Gateway Extra Charges
 *
 * Provides a Russianpost Cash on Delivery Payment Gateway.
 *
 * @class 		WC_Gateway_Russianpost_COD_Charges
 * @version		1.0.0
 * @author 		Kidberries team
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Carton_Gateway_Russianpost_COD_Charges {

    public function __construct() {
        $this->friendly_shipping_method = 'russianpost_delivery';
        $this->payment_method           = 'russianpost_cod';

        add_action( 'woocommerce_calculate_totals', array( $this, 'calculate_totals' ), 10, 1 );
    }

    public function calculate_totals( $totals ) {
        global $woocommerce, $wpdb;

        $extras   = 0;
        $shipping_method = $this->friendly_shipping_method;
        $payment_method  = $this->payment_method;

        if( ! ($woocommerce->session->chosen_shipping_method == $shipping_method && $woocommerce->session->chosen_payment_method == $payment_method) ) return;

        $shipping_method_options = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM $wpdb->options WHERE option_name=%s",
                join('_', array('woocommerce', $shipping_method, 'settings') )
            )
        );
        if( $shipping_method_options ) {
            $shipping_method_options =  maybe_unserialize( $shipping_method_options ); 
            if( $shipping_method_options['enabled'] == "yes" && $woocommerce->session->customer['postcode'] ) {

                // TODO add countries check
                $from     = ($shipping_method_options['postcode'] == '') ? '101000' : $shipping_method_options['postcode'];
                $to       = $woocommerce->session->customer['postcode'];
                $total    = $totals->cart_contents_total;
                $weight   = 1000 * $woocommerce->cart->cart_contents_weight;

                $postage  = (array) json_decode ( $wpdb->get_var( $wpdb->prepare('SELECT fn.postcalc_json( %s, %s, %s, %s ) AS postcalc',$from, $to, $weight, $total ) ) );

                if( $postage ['ЦеннаяПосылка'] ) {
                    $po = (array) $postage['ЦеннаяПосылка'];
                    $extras = $po['НаложенныйПлатеж'];
                }
            }
        }

        if($extras){
            $totals->cart_contents_total = $totals->cart_contents_total + $extras;

            $this->current_gateway_title = $current_gateway->title;
            $this->current_gateway_extra_charges = $extras;

            add_action( 'woocommerce_review_order_before_order_total',  array( $this, 'add_payment_gateway_extra_charges_row'));

        }
        return $totals;
    }

    function add_payment_gateway_extra_charges_row(){
        global $woocommerce;

        $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
        $current_gateway    = $available_gateways[ $this->payment_method ];
        $title = $current_gateway->title;
        ?>
        <tr class="payment-extra-charge">
            <th><?php echo 'За '; echo mb_strtolower( __($title, 'woocommerce'), 'UTF-8' ) ?></th>
            <td><?php echo woocommerce_price($this->current_gateway_extra_charges); ?></td>
        </tr>
        <?php
    }
}
