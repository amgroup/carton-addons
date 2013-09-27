<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Delivery via Shoplogistics Shipping Method
 *
 * A simple shipping method allowing local delivery as a shipping method
 *
 * @class 		WC_Shipping_Shoplogistics
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		Kidberries
 */
class Carton_Shipping_Shoplogistics extends WC_Shipping_Method {
	/** @var float Stores the cost of shipping */
	var $shipping_total 			= 0;
	var $shipping_total_real		= 0;

	/**  @var array Stores an array of shipping taxes. */
	var $shipping_taxes				= array();

	/**  @var string Stores the label for the chosen method. */
	var $shipping_label				= null;

    var $api_url        = 'http://client-shop-logistics.ru/index.php?route=deliveries/api';
    var $curl           = null;
    var $cities         = array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->id           = 'shoplogistics_delivery';
		$this->method_title = __( 'Delivery via Shoplogistics', 'woocommerce' );
		$this->init();
		$this->init_curl();

		add_action( 'complete_order_' . $this->id , array( $this, 'make_shoplogistics_delivery' ) );
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
		$this->login 		= $this->get_option( 'login' );
		$this->password		= $this->get_option( 'password' );
        $this->customer_key	= $this->get_option( 'customer_key' );
        $this->api_key      = $this->get_option( 'api_key' );
        $this->api_key      = $this->api_key ? $this->api_key : '02d345ff7272162957ac614a1d1a83b5';
		$this->max_days		= $this->get_option( 'max_days' );
        $this->delivery_correction = $this->get_option( 'delivery_correction' );

		$this->cost_correction	= $this->get_option( 'cost_correction' );
		$this->cost_correction_subject = $this->get_option( 'cost_correction_subject' );
		$this->fee		        = $this->get_option( 'fee' );

		$this->pickup_places    = array();

        if( isset( $_POST['shipping_method'] ) )
            $woocommerce->session->shipping_last_selection_id = $_POST['shipping_method'];
        if( isset( $_POST['shipping_method_variant'] ) )
            $woocommerce->session->shipping_last_selection_variant_id = $_POST['shipping_method_variant'];
        if( isset( $_POST['shipping_method_sub_variant'] ) )
            $woocommerce->session->shipping_last_selection_sub_variant_id = $_POST['shipping_method_sub_variant'];
        
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}

    /**
     * init function for Curl.
     *
     * @access public
     * @return void
     */
    function init_curl () {
        $this->curl = curl_init();
        curl_setopt( $this->curl, CURLOPT_URL, $this->api_url );
        curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $this->curl, CURLOPT_USERAGENT, 'Customer_' . $this->customer_key );

        curl_setopt( $this->curl, CURLOPT_CONNECTTIMEOUT, 1 );
//          curl_setopt( $this->curl, CURLOPT_CONNECTTIMEOUT_MS, 1000 );
        curl_setopt( $this->curl, CURLOPT_TIMEOUT, 2 );
//          curl_setopt( $this->curl, CURLOPT_TIMEOUT_MS, 2000 );
    }
     /**
     * api_get function.
     *
     * @access public
     * @param request
     * @return string
     */
    function api_get ( $request ) {
        if( empty( $this->curl ) )
            init_cutrl();

        curl_setopt( $this->curl, CURLOPT_POSTFIELDS, 'xml='.urlencode(base64_encode($request)) );
        $response = curl_exec( $this->curl );
        //curl_close( $this->curl );

        if( $response )
            return $response;
    }

    function get_cities() {
        $cities = array();

        $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><request/>');
        $request->api_id = $this->api_key;
        $request->function = 'get_dictionary';
        $request->dictionary_type = 'city';

        $_cities = new SimpleXMLElement( $this->api_get( $request->asXML() ) );

        $this->cities = array();
        
        foreach( $_cities->cities->city as $key => $value )
            $cities[ (string) $value->name ] = (string) $value->code_id ;
        return $cities;
    }

    function get_pickup_pindex() {
        $pickup_pindex = array();
        
        $idx = plugin_dir_path(__FILE__) . '../../shoplogistics-delivery-postcodes.csv';
        if( file_exists($idx) ) {
            $lines = file($idx);
            foreach( $lines as $line_num => $line ) {
                list($sid, $pidx, $city, $state) = split(";", $line);
                if( $sid != $pidx )
                    $pickup_pindex[ $sid ] = $pidx;
            }
        }
        return $pickup_pindex;
    }
    
    function get_pickup_cities() {
        $pickup_cities = array();

        $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><request/>');
        $request->api_id = $this->api_key;
        $request->function = 'get_dictionary';
        $request->dictionary_type = 'pickup';

        $_pickup = new SimpleXMLElement( $this->api_get( $request->asXML() ) );
        
        foreach( $_pickup->pickups->pickup as $key => $value )
            $pickup_cities[ (string) $value->city_name ] = (string) $value->city_code_id;

        return $pickup_cities;
    }

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param array $package (default: array())
	 * @return void
	 */
	function calculate_shipping( $package = array() ) {
		global $woocommerce;

		$shipping_total      = 0;
		$shipping_total_real = 0;
		$selected_city       = null;
		$errors              = array();

		$rate = array(
			'id'          => $this->id,
			'label'       => $this->title,
			'label_extra' => null,
			'cost'        => null,
			'cost_real'   => null,
			'info'		  => null,
		);

		$shipping_fee    = 0;
		$discount        = $this->get_shipping_discout( $package );
		
		$fee = ( trim( $this->fee ) == '' ) ? 0 : $this->fee;
		if ( $fee )  $shipping_fee = $this->fee;
		
        $cities = $this->get_pickup_cities();
        $index  = $this->get_pickup_pindex();

        if( ! empty( $cities ) ) {
            $this->pickup_places[] = '<option value=""></option>';
            foreach( $cities as $key => $value ) {
                $name = $key;
                $idx  = $value;
                $code = $value;

                if( isset( $index[ $value ] ) )
                    $code = $index[ $value ];
                
                if( preg_match('/^$/', $name, $matches ) )
                    continue;

                $selected = '';
                if( $woocommerce->session->shipping_last_selection_variant_id == $code ) {

                    $selected = ' selected="selected"';
                    $selected_city = array(
                        'name' => $name,
                        'to_city_id' => $value,
                        'central_postindex' => $code
                    );
                }
                $this->pickup_places[] = '<option data-city_id="' . $idx . '" value="' . $code . '"' . $selected . '>' . $name . '</option>';
            }

            $rate[ 'label_extra' ] .=  '<select style="width: 100%" data-placeholder="Ваш город..." id="shipping_city_chzn" name="shipping_method_sub_variant" class="s_city chzn-select shipping_method_variant">' . 
                                    implode('',$this->pickup_places ) .
                                    '</select>';
        }

        if( $woocommerce->session->shipping_last_selection_variant_id ) {
            if( is_array($selected_city) ) {
		if( ! $woocommerce->cart->cart_contents_weight )
			$woocommerce->cart->cart_contents_weight = 0.5;
                $subvariants = (array) $this->get_rates( $selected_city[ 'name' ], $package['contents_cost'], $woocommerce->cart->cart_contents_weight );
                if( sizeof( $subvariants ) ) {

                    $pickup_place = array();
                    $only_one_variant = 1;

                    foreach( $subvariants['resuts'] as $_subvariants ) {
                        $subvariant = (array) $_subvariants;

                        if ( $subvariant['is_terminal'] )
                            continue;

                        // Delivery Time
                        preg_match('/(?P<from>[0-9]+)[^0-9]?(?P<to>[0-9]+)?/', $subvariant['srok_dostavki'], $days );
                        if ( isset( $days['to'] ) ) {
                            if( $this->delivery_correction > 0 ) {
                                $days['from'] += $this->delivery_correction;
                                $days['to']   += $this->delivery_correction;
                            }
                            $delivery = 'от ' . $days['from'] .' до ' . $days['to'] . ' ' . _n( 'business day', 'business days', $days['to'], 'woocommerce');
                        } else {
                            if( $this->delivery_correction > 0 ) {
                                $days['from'] += $this->delivery_correction;
                            }
                            $delivery = $days['from'] . ' ' ._n( 'business day', 'business days', $days['from'], 'woocommerce');
                            $days['to'] = $days['from'];
                        }
                        
                        // Delivery Time Checking
                        if( $this->max_days <= 0 || $days['to'] > $this->max_days )
                            continue;
                            
                        // Devlivery cost
                        if( ! $subvariant['comission_percent'] )
                            $subvariant['comission_percent'] = 1.5;
                        $price = (($subvariant['comission_percent']/100) * $package['contents_cost']) + $subvariant['price'];
                        
                        // Delivery cost correction
                        if( $this->cost_correction ) {
                            $value = preg_replace('([^0-9.,]+)', '', $this->cost_correction);
                            $value = preg_replace('(,)', '.', $value);

                            if( preg_match('/^([-])/', $this->cost_correction, $octothorpe ) )
                                $value *= -1;
                            if( preg_match('/(%)$/', $this->cost_correction, $percent ) ) {
                                $value *= $price/100;
                            }
                            $price = $price + $value;
                        }
                        
                        // Fee
                        if ( $this->fee )
                            $price += $this->fee;

                        $discounted_price = $price;

                        // Apply Shipping Discounts
		                if( $discounted_price > $discount )
		                    $discounted_price -= $discount;
		                elseif( $discounted_price <= $discount )
		                    $discounted_price = 0;
		                else
		                    $discounted_price = null;

                        // Shipping Discounts Cost Correction
                        if( $discounted_price > 0 && $discounted_price < 51 )
                            $discounted_price = 50;
                        if( $discounted_price > 51 && $discounted_price < 101 )
                            $discounted_price = 100;


                        // Adress correction
                        $type = ($subvariant['pickup_places_type_id'] ? 'pickup' : 'courier');

                        if( ! $subvariant['pickup_places_type_id'] ) {
                            $subvariant['street_address'] = '';
							$subvariant['type_ru'] = 'Курьером до дома';
                            $subvariant['address'] = '<span class="type ' . $type . '">' . $subvariant['type_ru'] . '</span>';
                            $subvariant['pickup_place_code'] = '000000';
                        } else {
                            $subvariant['address'] = preg_replace('(\(.*$)', '', $subvariant['address'] );
                            $subvariant['street_address'] = mb_initcap( $subvariant['address'] );
							$subvariant['type_ru'] = 'Пункт вывоза заказа';
                            $subvariant['address'] = '<span class="type ' . $type . '">' . $subvariant['type_ru'] . '</span> - ' . $subvariant['street_address'];
                        }
                        
                        $checked = '';
                        if( $only_one_variant || $subvariant['pickup_place_code'] == $woocommerce->session->shipping_last_selection_sub_variant_id ) {
                            $checked = ' checked="checked"';

                            $woocommerce->session->shipping_type = $type;

                            $label = array();
                            if( isset( $subvariant['type_ru'] ) )
                                $label[] = $subvariant['type_ru'];
                            else
                                $label[] = $this->title;

                            if( isset( $selected_city[ 'name' ] ) )
                                $label[] = $selected_city[ 'name' ];
                                
                            if( isset( $delivery ) )
                                $label[] = '(' . $delivery . ')';

                            $rate[ 'label' ]     = implode(' - ', $label );
                            $rate[ 'cost' ]      = $discounted_price;
                            $rate[ 'cost_real' ] = $price;
                            
                            $description = array();
                            $description[] = "Контрольный срок доставки: " . $delivery;
                            if( $subvariant['street_address'] )
                                $description[] = "Адрес пункта вывоза заказа: " . $subvariant['street_address'];
                            if( $subvariant['worktime'] )
                                $description[] = "Часы работы: " . $subvariant['worktime'];
                            if( $subvariant['phone'] )
                                $description[] = "Контактный телефон: " . $subvariant['phone'];
                            if( $subvariant['comments'] )
                                $description[] = "Дополнительная информация: " . $subvariant['comments'];

                            $rate[ 'info' ]      = array(
                                'instruction' => $description,
                                'shoplogistics' => array( 
                                    'pickup_place'      => $subvariant['pickup_place'],
                                    'pickup_place_code' => $subvariant['pickup_place_code'],
                                    'to_city_id'        => $subvariant['to_city_id'],
                                    'to_city'           => $selected_city[ 'name' ],
                                    'pickup_places_type_id' => $subvariant['pickup_places_type_id'],
                                ),
                                'tracking_number' => '',
                                'tracking_number_label' => $subvariant['pickup_place'],
                                'tracking_number_status' => '',
                            );
                        }

                        $pickup_place[] = '<li class="subvariant">' .
                            '<label class="option"><input class="shipping_method_sub_variant ' . $type . '" type="radio" name="shipping_method_sub_variant" ' .
                                'value="' . $subvariant['pickup_place_code'] . '"' . $checked . '>' .
                                (($discounted_price >0) ? woocommerce_price( $discounted_price ) : 'Бесплатно!') .
                                '<span class="delivery"> (' . $delivery . ')</span> - '.
                                $subvariant['address'] .
                            '</input></label></li>';

                        $only_one_variant = null;
                    }

                    if( sizeof($pickup_place) )
                        $rate[ 'label_extra' ] .= '<br/><ol>' . implode('', $pickup_place ) . '</ol>';

                } else {
                    $errors[] = '';
                    $errors[] = '<div class="error" style="color: red;">Сожалеем, но мы не сможем доставить ваш заказ из-за превышения максимально допустимого веса заказа в этом направлении.</div>';
                    $errors[] = '<div class="help">Если это возможно, разделите этот заказ на несколько частей';
                    $errors[] = '<strong style="color: green;">или воспользуйтесь другим способом доставки.</strong></div>';
                    
                    $rate[ 'label_extra' ] .= implode( '<br/>', $errors );
                }
            }
        }
        $this->add_rate($rate);
	}

	/**
	 * get_rates function.
	 *
	 * @access public
     * @param string $s_city
     * @param float  $contents_cost
	 * @return void
	 */
    function get_rates( $s_city, $contents_cost, $contents_weight ) {

        if(function_exists('curl_init')) {
            $data = array(
                'jsoncallback' => 'jQuery1102021348485886119306_1373380838491',
                'route' => 'deliveries/tarifs/get_result_cross',
                'customer_key' => $this->customer_key,
                'form_city_id' => '1',
                'to_city_id' => $s_city,
                'weight' => $contents_weight,
                'num' => '1',
                'max_price'=>$contents_cost,
                '_' => '1373380838507',
            );
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "http://client-shop-logistics.ru/index.php?" . http_build_query($data) );
            curl_setopt($curl, CURLOPT_USERAGENT, 'Customer_' . $data['customer_key'] );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
//            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, 1000);
            curl_setopt($curl, CURLOPT_TIMEOUT, 2);
//            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 2000);

	    $response = curl_exec($curl);
	    curl_close($curl);
            
	if($response) {
                $response = preg_replace( '/(' . $data['jsoncallback'] . '\()/', '', $response );
                $response = preg_replace( '/(\);)$/', '', $response );
                
                $rates = json_decode( $response );

                if( sizeof( $rates ) ) {
                    return $rates;
                } else {
                    return;
                }

			} else {
//				$this->debug(3, "Error fetching using curl: " . curl_error($curl));
//				$this->error("Error reading the URL you specified from remote host." . curl_error($curl));
				return false;
            }

        } else {
	    return false;
//            $this->debug(3, "Error trying to fetch delivery rates: $err");
        }
        return true;
    }
    
	/**
	 * init_form_fields function.
	 *fputs ( $file, $str);
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable delivery via ShopLogistics', 'woocommerce' ),
				'default' 		=> 'no'
			),
			'title' => array(
				'title' 		=> __( 'Title', 'woocommerce' ),
				'type' 			=> 'text',
				'description'		=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'		=> __( 'Delivery via Shoplogistics', 'woocommerce' ),
				'desc_tip'		=> true,
			),
			'login' => array(
				'title' 		=> __( 'Login', 'woocommerce' ),
				'type' 			=> 'text',
				'description'		=> __( 'Your ShopLogistics Login', 'woocommerce' ),
				'desc_tip'		=> true,
			),
			'password' => array(
				'title' 		=> __( 'Password', 'woocommerce' ),
				'type' 			=> 'password',
				'description'		=> __( 'Your ShopLogistics Password', 'woocommerce' ),
				'desc_tip'		=> true,
			),
			'customer_key' => array(
				'title' 		=> __( 'Customer key', 'woocommerce' ),
				'type' 			=> 'text',
				'description'		=> __( 'Your ShopLogistics Customer Key', 'woocommerce' ),
				'desc_tip'		=> true,
			),            
/* TODO			'file' => array(
				'title' 		=> __( 'File pickup.txt', 'woocommerce' ),
				'type' 			=> 'file',
				'description'		=> __( 'File pickup.txt', 'woocommerce' ),
				'desc_tip'		=> true,
			),
*/
			'delivery_correction' => array(
				'title' 		=> __( 'Delivery Time Correction', 'woocommerce' ),
				'type' 			=> 'number',
				'description'		=> __( 'Delivery Time Correction in Business Days. Leave blank or set to 0 if it does not matter.', 'woocommerce' ),
				'placeholder'		=> '0',
				'custom_attributes'	=> array(
					'min'		=> '0',
                    'step'      => '1',
				),
				'desc_tip'		=> true,
			),
			'max_days' => array(
				'title' 		=> __( 'Max Delivery Time', 'woocommerce' ),
				'type' 			=> 'number',
				'description'		=> __( 'Max Delivery Time in Business Days. Leave blank or set to 0 if it does not matter.', 'woocommerce' ),
				'placeholder'		=> '0',
				'custom_attributes'	=> array(
					'min'		=> '0',
                    'step'      => '1',
				),
				'desc_tip'		=> true,
			),
			'cost_correction' => array(
				'title' 		=> __( 'Delivery Cost Correction', 'woocommerce' ),
				'type' 			=> 'text',
				'description'		=> __( '+200, -200, +2% -3%', 'woocommerce' ),
				'custom_attributes'	=> array(
					'pattern'		=> '(\+|\-)?([0-9]+)(\.[0-9]{1,6})?(\%)?',
				),
				'placeholder'		=> '0.00',
				'desc_tip'		=> true,
			),
			'cost_correction_subject' => array(
				'title' 		=> __( 'Delivery Cost Correction Subject', 'woocommerce' ),
				'type' 			=> 'select',
				'description'		=> __( 'Please Choose subtotal or delivery cost', 'woocommerce' ),
				'default' 		=> 'subtotal',
				'options'		=> array(
					'subtotal'		=> __('Cart Subtotal', 'woocommerce'),
					'delivery'		=> __('Delivery Cost', 'woocommerce'),
					'total'		=> __('Cart Subtotal and Delivery Cost', 'woocommerce'),
				),
				'desc_tip'		=> true,
			),
			'fee' => array(
				'title' 		=> __( 'Packaging Fee', 'woocommerce' ),
				'type' 			=> 'text',
				'custom_attributes'	=> array(
					'pattern'		=> '([0-9]+)(\.[0-9]{1,2})?',
				),
				'description'		=> __( 'What fee do you want to charge for parcel packaging, disregard if you choose free. Leave blank to disable.', 'woocommerce' ),
				'default'		=> '',
				'desc_tip'      => true,
				'placeholder'		=> '0.00'
			),
		);
	}

	/**
	 * admin_options function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
	?>
		<h3><?php echo $this->method_title; ?></h3>
		<p><?php _e( 'ShopLogistics delivery is a simple shipping method for delivering orders.', 'woocommerce' ); ?></p>
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
/*
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
*/
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

function shoplogistics_shipping_method( $methods ) {
	$methods[] = 'Carton_Shipping_Shoplogistics';
	return $methods;
}
add_filter('woocommerce_shipping_methods', 'shoplogistics_shipping_method' );

function shoplogistics_override_shipping_fields( $fields ) {
	global $woocommerce;
	if( 'shoplogistics_delivery' === $woocommerce->session->chosen_shipping_method )
		return shoplogistics_override_fields( $fields, 'shipping' );
	return $fields;
}

function shoplogistics_override_billing_fields( $fields ) {
	global $woocommerce;
	if( 'shoplogistics_delivery' === $woocommerce->session->chosen_shipping_method )
		return shoplogistics_override_fields( $fields, 'billing' );
	return $fields;
}

function shoplogistics_override_fields( $fields, $block_name = 'shipping' ) {
    global $woocommerce;

    foreach( array('country', 'last_name', 'postcode', 'state', 'company', 'address_2', 'city' ) as $field ) {
        //$fields[ $block_name . '_' . $field ]['required']   = false;
        unset( $fields[ $block_name . '_' . $field ] );
    }
//echo "<p>" . $woocommerce->session->shipping_type . "</p>";
    if( $woocommerce->session->shipping_type == 'pickup' )
        unset( $fields[ $block_name . '_address_1' ] );
    return $fields;
}

add_filter( 'woocommerce_billing_fields', 'shoplogistics_override_billing_fields', 10, 1 );
add_filter( 'woocommerce_shipping_fields', 'shoplogistics_override_shipping_fields', 10, 1 );
