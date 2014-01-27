<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Delivery via Russianpost Shipping Method
 *
 * A simple shipping method allowing local delivery as a shipping method
 *
 * @class 		WC_Shipping_Russianpost
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		Kidberries
 */
class Carton_Shipping_Russianpost extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->id           = 'russianpost_delivery';
		$this->method_title = __( 'Delivery via Russianpost', 'woocommerce' );
		$this->init();
	}

    /**
     * init function.
     *
     * @access public
     * @return void
     */
    function init() {
        global $woocommerce;

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title		= $this->get_option( 'title' );
		$this->type 		= $this->get_option( 'type' );
		$this->fee	    	= $this->get_option( 'fee' );
		$this->postcode		= $this->get_option( 'postcode' );
		$this->availability	= $this->get_option( 'availability' );
		$this->countries	= $this->get_option( 'countries' );
		$this->places		= array();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param array $package (default: array())
	 * @return void
	 */
	function calculate_shipping( $package = array() ) {
		global $woocommerce, $wpdb;

		$shipping_total  = null;
		$shipping_fee    = 0;
		$to              = null;
        $salt            = '_' . rand(100000,999999);


		$fee = ( trim( $this->fee ) == '' ) ? 0 : $this->fee;
		if ( $fee )  $shipping_fee = $this->fee;
		
		// Get the cost & Save last user selection
		if( ( $woocommerce->session->chosen_shipping_method == $this->id && $_POST['shipping_method_variant']) || ($woocommerce->session->russianpost_shipping_method_variant != $woocommerce->session->chosen_shipping_method_variant ) ) {

            $file  = RUSSIANPOST_DELIVERY_POSTCODES_FILE;
            if( file_exists( $file ) ) {
                $places = file( $file );
                
                for( $i=0; $i <= sizeof( $places ); $i++ ){
                    if( isset( $places[$i] ) ) {
                        $column = split(';',  trim( $places[$i] ) );
                        if( $column[1] == $woocommerce->session->chosen_shipping_method_variant ) {
                            $woocommerce->session->russianpost_shipping_method_variant  = $column[1];
                            $woocommerce->session->chosen_shipping_method_variant_label = $column[0];
                        }
                    }
                }
            }
            
            if( $woocommerce->session->chosen_shipping_method_variant_label )  {
                // Found the postoffice pllacement. Get the cost
                $from = ($this->postcode == '') ? '101000' : $this->postcode;
                $to   = $_POST['shipping_method_variant'];

                $postage  = (array) json_decode ( $wpdb->get_var( $wpdb->prepare('SELECT fn.postcalc_json( %s, %s, %s, %s ) AS postcalc', $from, $to, 1000 * $woocommerce->cart->cart_contents_weight, $package['contents_cost'] ) ) );

                if( 'OK' === $postage['Status'] ) {
                    $po = (array) $postage['Куда'];
                    $rate['destination'] = array(
                        'postoffice' => array(
                            'postcode' => $po['Индекс'],
                            'address'  => $po['Адрес'],
                            'phone'    => $po['Телефон'],
                            'city'     => $po['Название'],
                        ),
                        'country'    => $package['destination']['country'],
                        'postcode'   => $package['destination']['postcode'],
                    );
                    if( $po['Название'] != $po['МестоположениеEMS'] ) {
                        $rate['destination']['postoffice']['region'] = $po['МестоположениеEMS'];
                    }
                    if( $postage['ЦеннаяПосылка'] ) {
                        $po = (array) $postage['ЦеннаяПосылка'];
                        $shipping_total = $po['Доставка'];
                    }
                } else {
                    $rate['destination'] = array(
                        'error'    => $postage['Status'],
                        'message'  => $postage['Message'],
                        'country'  => $package['destination']['country'],
                        'postcode' => $package['destination']['postcode'],
                    );
                }


                if( $shipping_total > 0 )
                    $woocommerce->session->chosen_shipping_method_variant_cost = $shipping_total + $shipping_fee;
            }
        }
        
        $holder = "Ваш город...";
        if( $woocommerce->session->chosen_shipping_method_variant_label )
            $holder = $woocommerce->session->chosen_shipping_method_variant_label;


        $extra =
            '<select style="width: 100%" id="' . $this->id . $salt . '" class="' . $this->id . 'chosen ajax-chzn-select" data-placeholder="' . $holder . '" data-postcode="' . $woocommerce->session->chosen_shipping_method_variant . '">'.
                '<option selected="selected" value=""></option>'.
            '</select>';

        $label = array();
        $label[] = $this->title;
        
        if( $woocommerce->session->chosen_shipping_method_variant_label )
            $label[] = $woocommerce->session->chosen_shipping_method_variant_label;

        $shipping_label = mb_initcap( implode(' - ', $label ) );

		if( isset( $woocommerce->session->chosen_shipping_method_variant_cost ) )
			$shipping_total = $woocommerce->session->chosen_shipping_method_variant_cost;
		else
			$shipping_total = null;
	    $shipping_total_real = $shipping_total;

        // Apply Shipping Discounts
		$discount = $this->get_shipping_discout( $package );
		
		if( isset( $shipping_total ) ) {
			if( $shipping_total > $discount )
				$shipping_total -= $discount;
			elseif( $shipping_total <= $discount )
				$shipping_total = 0;
			else
				$shipping_total = null;
		} else {
			$shipping_total = null;
		}

		$rate = array(
			'id'          => $this->id,
			'label'       => $shipping_label,
			'label_extra' => $extra,
			'cost'        => $shipping_total,
			'cost_real'   => $shipping_total_real,
		);

		$script = '<script type="text/javascript">
    jQuery(document).ready(function($){ 
        $("#' . $this->id . $salt . '")
            .chosen( {no_results_text: "Ничего не найдено!"} )
            .change(function(e){
                var $this = $(this);
                
                if( ! $("#billing_city").size() )
                    $this.closest("form").append("<input type=\"hidden\" name=\"billing_city\" id=\"billing_city\" />");

                if( ! $("#billing_postcode").size() )
                    $this.closest("form").append("<input type=\"hidden\" name=\"billing_postcode\" id=\"billing_postcode\" />");

                if( ! $("#billing_country").size() )
                    $this.closest("form").append("<input type=\"hidden\" name=\"billing_country\" id=\"billing_country\" />");
                    
                $("#billing_city").val( $this.find("option[value=\"" + $this.val() + "\"]").html() );
                $("#billing_postcode").val( $this.val() );
                $("#billing_country").val( "RU" );
                
                var $shipping_method_variant = $("input[name=\'shipping_method_variant\']:first" );
                $shipping_method_variant.val( $this.val() );
                $shipping_method_variant.trigger( "change" );
            })
            .ajaxChosen({
                minTermLength: 2,
                afterTypeDelay: 500,
                keepTypingMsg: "Продолжайте набирать...",
                lookingForMsg: "Ищем в базе",
                type: "POST",
                url: woocommerce_params.ajax_url,
                data: { action:"get_russianpost_places" },
                jsonTermKey: "place",
                dataType: "json"
            }, function (data) {
                var results = [];
                $.each(data, function (i, item) { results.push({ value: item.value, text: item.text }); });
                return results;
            });
    });
</script>';
		$rate['label_extra'] .= $script;

		$this->add_rate($rate);
	}

	/**
	 * init_form_fields function.
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		global $woocommerce;
		$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable delivery via Russinapost', 'woocommerce' ),
				'default' 		=> 'no'
			),
			'title' => array(
				'title' 		=> __( 'Title', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'		=> __( 'Delivery via Russianpost', 'woocommerce' ),
				'desc_tip'      => true,
			),
			'type' => array(
				'title' 		=> __( 'Fee Type', 'woocommerce' ),
				'type' 			=> 'select',
				'description' 	=> __( 'How to calculate delivery charges', 'woocommerce' ),
				'default' 		=> 'fixed',
				'options' 		=> array(
					'fixed' 	=> __( 'Fixed amount', 'woocommerce' ),
					'percent'	=> __( 'Percentage of cart total', 'woocommerce' ),
					'product'	=> __( 'Fixed amount per product', 'woocommerce' ),
				),
				'desc_tip'      => true,
			),
			'fee' => array(
				'title' 		=> __( 'Packaging Fee', 'woocommerce' ),
				'type' 			=> 'number',
				'custom_attributes' => array(
					'step'	=> 'any',
					'min'	=> '0'
				),
				'description' 	=> __( 'What fee do you want to charge for parcel packaging, disregard if you choose free. Leave blank to disable.', 'woocommerce' ),
				'default'		=> '',
				'desc_tip'      => true,
				'placeholder'	=> '0.00'
			),
			'postcode' => array(
				'title' 		=> __( 'Your Post Code', 'woocommerce' ),
				'type' 			=> 'input',
				'description' 	=> __( 'Your post code would you like to offer delivery from.', 'woocommerce' ),
				'default'		=> '',
				'desc_tip'      => true,
				'placeholder'	=> '101000 etc'
			),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woocommerce' ),
							'type' 			=> 'select',
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __( 'All allowed countries', 'woocommerce' ),
								'specific' 	=> __( 'Specific Countries', 'woocommerce' )
							)
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woocommerce' ),
							'type' 			=> 'multiselect',
							'class'			=> 'chosen_select',
							'css'			=> 'width: 450px;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries
						)
		);
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		global $woocommerce; ?>
		<h3><?php echo $this->method_title; ?></h3>
		<p><?php _e( 'Russian Post delivery is a simple shipping method for delivering orders.', 'woocommerce' ); ?></p>
		<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
    	</table> <?php
	}


    /**
     * is_available function.
     *
     * @access public
     * @param array $package
     * @return bool
     */
    function is_available( $package ) {
		global $woocommerce;

		if ($this->enabled=="no") return false;
		if ($package['contents_cost']>=90000) return false; // Лимит наложенного платежа - 100000 (90000 + 10%)
		if ($woocommerce->cart->cart_contents_weight>=20) return false; // Лимит веса посылки 20 кг.

		// Either post codes not setup, or post codes are in array... so lefts check countries for backwards compatibility.
		$ship_to_countries = '';
		if ($this->availability == 'specific') :
			$ship_to_countries = $this->countries;
		else :
			if (get_option('woocommerce_allowed_countries')=='specific') :
				$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
			endif;
		endif;

		if (is_array($ship_to_countries))
			if (!in_array( $package['destination']['country'] , $ship_to_countries))
				return false;

		// Yay! We passed!
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true );
    }


    /**
     * clean function.
     *
     * @access public
     * @param mixed $code
     * @return string
     */
    function clean( $code ) {
    	return str_replace( '-', '', sanitize_title( $code ) ) . ( strstr( $code, '*' ) ? '*' : '' );
    }
}

function russianpost_shipping_method( $methods ) {
	$methods[] = 'Carton_Shipping_Russianpost';
	return $methods;
}
add_filter('woocommerce_shipping_methods', 'russianpost_shipping_method' );
