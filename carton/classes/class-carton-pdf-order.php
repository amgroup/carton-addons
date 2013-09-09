<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Carton_PDF_Order class
 */
if ( !class_exists( 'Carton_PDF_Order' ) ) {

	class Carton_PDF_Order {

        private $order;
        private $last_order_id;
        private $dom;
        private $fop;
        
        private $xml;
        private $xslt_one_page;
        private $xslt;
        
        private $pdf;
        private $filename;

		/**
		 * Constructor
		 */
		public function __construct() {
            if( $this->is_woocommerce_activated() ) {
				add_action( 'init', array( $this, 'init' ) );
			}
		}
		
		/**
		 * Init the class
		 * @access public
		 * @return void
		 */
		public function init() {
            $lang = (WPLANG ? WPLANG : 'en_EN');
            
            add_filter( 'woocommerce_email_attachments_new_order', array( $this, 'attach' ) );
            add_filter( 'woocommerce_email_remove_attachments_new_order', array( $this, 'remove' ) );

            $this->order = new WC_Order();
            $this->fop   = new Carton_FOP();
			$this->dom   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><document lang="' . $lang . '" />');

            $this->xslt_one_page = file_get_contents( CARTON_PDF_ORDER_TEMPLATES_DIR . 'invoice_' . $lang . '.xsl' );
            $this->xslt          = file_get_contents( CARTON_PDF_ORDER_TEMPLATES_DIR . 'invoice_alt_' .$lang . '.xsl' );
		}
        
        public function attach($order_id) {
            $this->pdf($order_id);

            $this->filename = $this->save();
            return $this->filename;
        }

        public function remove($filename) {
            if( $filename == $this->filename ) {
                if( unlink( $this->filename ) ) {
                    $this->filename = null;
                    return true;
                }
            }
            return false;
        }
        
        public function xml( $order_id = 0 ) {
            $this->last_order_id = $order_id;
            
            if( $order_id == 0 ) {
                $this->dom  = new SimpleXMLElement( $this->test_data() );
                $this->xml  = $this->dom->saveXML();
            } else {
                $this->dom   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><document type="invoice" lang="'.(WPLANG ? WPLANG : 'en_EN').'" />');
                
                $this->order->get_order( $order_id );
                
                if( $this->order ) {
                    $this->dom->info->number = $this->order->get_order_number();
                    $this->dom->info->date   = date_i18n( get_option( 'date_format' ), strtotime( $this->order->order_date ) );
                    $this->dom->info->status = '';

                    $this->dom->info->payment->name  = __( $this->order->get_order()->payment_method_title, 'woocommerce' );
                    $this->dom->info->shipping->type = '';
                    $this->dom->info->shipping->label    = $this->order->order_custom_fields[ '_order_shipping_tracking_label'][0];
                    $this->dom->info->shipping->tracking = $this->order->order_custom_fields[ '_order_shipping_tracking' ][0];

                    $this->dom->company->info->logo = '';
                    $this->dom->company->info->name = get_bloginfo( 'name' );
                    $this->dom->company->info->slogan = get_bloginfo( 'description' );
                    $this->dom->company->info->site = get_bloginfo( 'url' );
                    $this->dom->company->info->address->p = new SimpleXMLElement( '<p>' . get_option('wcdn_company_address') . '</p>' );
                    $this->dom->company->info->phone = new SimpleXMLElement( '<p>' . get_option('wcdn_company_phone') . '</p>' );
                    $this->dom->company->info->email = new SimpleXMLElement( '<p>' . get_option('wcdn_company_email') . '</p>' );;

                    $cname = array();
                    if( $this->order->billing_first_name )
                        $cname[] = $this->order->billing_first_name;
                    if( $this->order->billing_last_name )
                        $cname[] = $this->order->billing_last_name;

                    $this->dom->customer->name = implode( ', ', $cname );
                    $this->dom->customer->email = $this->order->billing_email;
                    $this->dom->customer->phone = $this->order->billing_phone;
                    $this->dom->customer->address = $this->order->get_billing_address();

                    $rname = array();
                    if( $this->order->shipping_first_name )
                        $rname[] = $this->order->shipping_first_name;
                    if( $this->order->shipping_last_name )
                        $rname[] = $this->order->shipping_last_name;

                    $this->dom->recipient->name  = implode( ', ', $rname );
                    $this->dom->recipient->email = ($this->order->shipping_email ? $this->order->shipping_email : $this->order->billing_email);
                    $this->dom->recipient->phone = ($this->order->shipping_phone ? $this->order->shipping_phone : $this->order->billing_phone);
                    $this->dom->recipient->address = $this->order->get_shipping_address();

                    $this->dom->notes->customer = $this->order->customer_note;
                    $this->dom->notes->personal = new SimpleXMLElement( '<p>' . get_option('wcdn_personal_notes') . '</p>' );
                    $this->dom->notes->conditions = new SimpleXMLElement( '<p>' . get_option('wcdn_policies_conditions') . '</p>' );

                    $this->dom->items = null;
                    $n = 0;
                    foreach( $this->order->get_items() as $item ) {
                        $product = $this->order->get_product_from_item( $item );
                        $this->dom->items->item[$n]->name  = $item[ 'name' ];
                        $this->dom->items->item[$n]->sku   = $product->get_sku();
                        $this->dom->items->item[$n]->qty   = $item['qty'];

                        $this->dom->items->item[$n]->price = new SimpleXMLElement( html2xml_charachters( woocommerce_price( $item['line_total'] ) ) );
                        $this->dom->items->item[$n]->single_price = new SimpleXMLElement( html2xml_charachters( woocommerce_price( $item['line_subtotal'] ) ) );
                        
                        $this->dom->items->item[$n]->weight->value = $product->get_weight();
                        $this->dom->items->item[$n]->weight->unit  = __( get_option('woocommerce_weight_unit'), 'woocommerce' );
                        $n++;
                    }
                    
                    $this->dom->totals = null;
                    $n = 0;
                    foreach ( $this->order->get_order_item_totals() as $key => $total ) {
                        $label = strip_tags( $total['label'] );
                        $colon = strrpos( $label, ':' );

                        if( $colon !== false )
                            $label = substr_replace( $label, '', $colon, 1 );

                        $this->dom->totals->total[$n]->name  = $label;
                        $this->dom->totals->total[$n]->value = new SimpleXMLElement( '<value>' . html2xml_charachters(strip_tags($total['value']) ) . '</value>' );
                        $n++;
                    }
                }
                $this->xml  = $this->dom->saveXML();
            }
            return xmlpp( $this->xml, true );
        }
        
        public function pdf( $order_id = 0 ) {

            $this->xml( $order_id );
        
            $this->fop->get( $this->xslt_one_page, $this->xml );
            if( $this->fop->document->pages > 1 ) {
                $this->fop->get( $this->xslt, $this->xml );
            }
            $this->pdf = $this->fop->document;
            return $this->pdf;
        }
        
        public function save( $filename ) {
            if( ! $filename ) {
                $ext = '.err';
                if( preg_match ( '/(pdf)/i', $this->pdf->type ) )
                    $ext = '.pdf';
                $filename = CARTON_PDF_ORDER_TMP_DIR . 'Order ' . $this->last_order_id . $ext;
            }

            $f = fopen( $filename, 'w' );
            fwrite( $f, $this->pdf->content );
            fclose( $f );
            return $filename;
        }
        
		public function is_woocommerce_activated() {
			$blog_plugins = get_option( 'active_plugins', array() );
			$site_plugins = get_site_option( 'active_sitewide_plugins', array() );

			if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
				return true;
			} else {
				return false;
			}
		}

        public function test_data() {
            return '<?xml version="1.0" encoding="UTF-8"?>
            <document type="invoice" lang="ru_RU">
            <info>
            <number>OL-1972</number>
            <date>05.09.2013</date>
            <status></status>
            <payment><name>Наличными</name><value/></payment>
            <shipping>
            <type></type>
            <label></label>
            <tracking></tracking>
            </shipping>
            </info>
            <company>
            <info>
            <logo></logo>
            <name><p>KIDBAGS.RU</p>
            </name>
            <slogan></slogan>
            <site></site>
            <address><p>г,Москва, Харитоньевский тупик, д. 6 строение 3.<br />
            Компания Wheelpak LTD (Russia)</p>
            </address>
            <phone></phone>
            <email></email>
            </info>
            </company>
            <customer>
            <name>vvv </name>
            <email>wheelpak.com@gmail.com</email>
            <phone>007</phone>
            <address>vv</address>
            </customer>
            <recipient>
            <name>vvv </name>
            <email>wheelpak.com@gmail.com</email>
            <phone>007</phone>
            <address>vv</address>
            </recipient>
            <notes>
            <customer></customer>
            <personal><p>Спасибо за покупку! </p>
            </personal>
            <conditions><p>Вещь из нашего магазина вы можете вернуть  в течении 14 дней, если она останется не ношенной, не стиранной, так-же сохранив все приложенные этикетки, элементы упаковки и саму упаковку.</p>
            </conditions>
            </notes>
            <items><item><sku>tsh</sku><name>Футболка</name><qty>1</qty><meta><dl class="variation"></dl></meta><price>100&#160;&#1088;&#1091;&#1073;</price><single_price>200&#160;&#1088;&#1091;&#1073;</single_price><weight><value>3.0</value><unit>кг</unit></weight></item></items>
            <totals><total><name>Стоимость товаров</name><value>200&#160;&#1088;&#1091;&#1073;</value></total><total><name>Доставка</name><value>&#160;Курьером, в границах старой Москвы -  400&#160;&#1088;&#1091;&#1073;</value></total><total><name>Итого</name><value>500&#160;&#1088;&#1091;&#1073;</value></total></totals>
            </document>';
        }
	}
}

if ( !class_exists( 'Carton_FOP' ) ) {
    class Carton_FOP {
        private $curl;

        public $document = null; /* = array ( 'type'    => '', 'content' => '', 'pages'   => null, ); */
        public $result;
        public $content_type;
        public $pages;
        
        public function __construct() {
            $this->init();
        }
        
        public function init() {
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_URL, "http://localhost:8080/foper");
            curl_setopt($this->curl, CURLOPT_USERAGENT, 'carton-pdf-kit-1.0');
            curl_setopt($this->curl, CURLOPT_HEADER, 1);
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            
            
        }
        
        public function get( $xslt, $xml ) {
            curl_setopt( $this->curl, CURLOPT_POSTFIELDS, array( "xml-xsl" => $xslt, "xml" => $xml ) );

            $response    = curl_exec( $this->curl );
            $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

            $headers = html_headers_array( substr($response, 0, $header_size) );

            $this->document->content = substr( $response, $header_size );
            $this->document->type    = $headers['content-type'];
            $this->document->pages   = $headers['x-page-count'] ? $headers['x-page-count'] : 0;
            
            return $this->document->content;
        }
        
        public function save( $filename ) {
            $f = fopen( $filename, 'w' );
            fwrite( $f, $this->document->content );
            fclose( $f );
        }
        
        public function file_content( $filename ) {
            return file_get_contents( $filename );
        }
        
        public function __destroy() {
            curl_close( $this->curl );
        }
    }
}

?>
