<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Carton_PDF_Order Settings class
 */
if ( ! class_exists( 'Carton_PDF_Order_Settings' ) ) {
	class Carton_PDF_Order_Settings {
		public $tab_name;
		public $hidden_submit;

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->tab_name = 'carton-pdf-order';
			$this->hidden_submit = Carton_PDF_Order::$plugin_prefix . 'submit';

			$this->init();
		}

		/**
		 * Load the class
		 */
		public function init() {
			$this->hooks();
		}

		/**
		 * Load the admin hooks
		 */
		public function hooks() {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ) );
			add_action( 'woocommerce_settings_tabs_' . $this->tab_name, array( $this, 'create_settings_page' ) );
			add_action( 'woocommerce_update_options_' . $this->tab_name, array( $this, 'save_settings_page' ) );

			add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_order_listing_actions' ) );
			add_action( 'woocommerce_order_actions_start', array( $this, 'add_order_actions' ) );

			add_action('wp_ajax_carton_order_get', array($this, 'carton_order_get_ajax'));
		}

		/**
		 * Add view and save actions to the order actions box
		 */
		public function add_order_actions( $order_id ) {
			?>
			<li class="wide">
				<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=carton_order_get&view=pdf&order=' . $order_id ), 'carton_order_get' ); ?>" class="button tips" target="_blank" alt="<?php esc_attr_e( 'View as PDF', 'woocommerce-delivery-notes' ); ?>" data-tip="<?php esc_attr_e( 'View as PDF', $this->tab_name ); ?>">
					<span><i class="glyphicon glyphicon-eye-open"></i> <?php _e( 'View as PDF', $this->tab_name ); ?></span>
				</a>
				<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=carton_order_get&view=pdf&save=save&order=' . $order_id ), 'carton_order_get' ); ?>" class="button tips" alt="<?php esc_attr_e( 'Save as PDF', 'woocommerce-delivery-notes' ); ?>" data-tip="<?php esc_attr_e( 'Save as PDF', 'woocommerce-delivery-notes' ); ?>">
					<span><i class="glyphicon glyphicon-save"></i> <?php _e( 'Save as PDF', $this->tab_name ); ?></span>
				</a>
			</li>
			<?php
		}

		/**
		 * Add view and save actions to the orders listing
		 */
		public function add_order_listing_actions( $order ) {
			?>
			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=carton_order_get&view=pdf&order=' . $order->id ), 'carton_order_get' ); ?>" class="button tips print-preview-button" target="_blank" alt="<?php esc_attr_e( 'View as PDF', 'woocommerce-delivery-notes' ); ?>" data-tip="<?php esc_attr_e( 'View as PDF', $this->tab_name ); ?>">
				<span><i class="glyphicon glyphicon-eye-open"></i> <?php _e( 'PDF', $this->tab_name ); ?></span>
			</a>

			<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=carton_order_get&view=pdf&save=save&order=' . $order->id ), 'carton_order_get' ); ?>" class="button tips print-preview-button" alt="<?php esc_attr_e( 'Save as PDF', 'woocommerce-delivery-notes' ); ?>" data-tip="<?php esc_attr_e( 'Save as PDF', 'woocommerce-delivery-notes' ); ?>">
				<span><i class="glyphicon glyphicon-save"></i> <?php _e( 'PDF', $this->tab_name ); ?></span>
			</a>
			<?php
		}

		/**
		 * Add a tab to the settings page
		 */
		public function add_settings_tab($tabs) {
			$tabs[ $this->tab_name ] = __( 'PDF Print', 'carton-pdf-order' );
			
			return $tabs;
		}

		public function carton_order_get_ajax() {
			if( !( is_admin() or current_user_can( 'manage_woocommerce_orders' ) or current_user_can( 'edit_shop_orders' ) ) ) { 
				wp_die( __( 'You do not have sufficient permissions to access this page. 1' ) );
			}

			if( empty( $_GET['action'] ) || !check_admin_referer( $_GET['action'] ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page. 2' ) );
			}

			if( empty( $_GET['view'] ) || empty( $_GET['order'] ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page. 3' ) );
			}

			$_pdf = new Carton_PDF_Order();
			$_pdf->init();
			$pdf = $_pdf->pdf( $_GET['order'] );

			if( $_GET['view'] == 'pdf' ) {
				header( 'Content-Type: ' . $pdf->type );
				if( preg_match ( '/(pdf)/i', $pdf->type ) && !empty( $_GET['save'] ) )
					header( 'Content-Disposition: attachment; filename="Накладная к заказу '. $_pdf->order->get_order_number() .'.pdf";' );
				echo $pdf->content;

			} else if ( $_GET['view'] == 'xml' ) {
				header( 'Content-Type: application/xml' );
				echo $_pdf->xml;
			}
			die();
		}

		/**
		 * Create the settings page content
		 */
		public function create_settings_page() {
			?>
			<h3><?php _e( 'PDF View', 'carton-pdf-order' ); ?></h3>
			<table class="form-table">
				<tbody>
					<tr class="hide-if-no-js">
						<?php
						$attachment_id = get_option( Carton_PDF_Order::$plugin_prefix . 'company_logo_image_id' );
						?>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_logo_image_id"><?php _e( 'Company/Shop Logo', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<input id="company-logo-image-id" type="hidden" name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_logo_image_id" rows="2" class="regular-text" value="<?php echo $attachment_id ?>" />
							<span id="company-logo-placeholder"><?php if( !empty( $attachment_id ) ) : ?><?php $this->create_thumbnail( $attachment_id ); ?><?php endif; ?></span>
							<a href="#" id="company-logo-remove-button" <?php if( empty( $attachment_id ) ) : ?>style="display: none;"<?php endif; ?>><?php _e( 'Remove Logo', 'carton-pdf-order' ); ?></a>
							<a href="#" <?php if( !empty( $attachment_id ) ) : ?>style="display: none;"<?php endif; ?> id="company-logo-add-button"><?php _e( 'Set Logo', 'carton-pdf-order' ); ?></a>
							<span class="description">
								<?php _e( 'A company/shop logo representing your business.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e( 'When the image is printed, its pixel density will automatically be eight times higher than the original. This means, 1 printed inch will correspond to about 288 pixels on the screen. Example: an image with a width of 576 pixels and a height of 288 pixels will have a printed size of about 2 inches to 1 inch.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>custom_company_name"><?php _e( 'Company/Shop Name', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>custom_company_name" rows="1" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'custom_company_name' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'Your company/shop name for the Delivery Note.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e( 'Leave blank to use the default Website/ Blog title defined in WordPress settings.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_site"><?php _e( 'Company/Shop web site', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_site" rows="1" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'company_site' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'The Web Site of the company/shop, which gets printed.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e('Leave blank to not print.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_slogan"><?php _e( 'Company short description/Shop slogan', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_slogan" rows="1" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'company_slogan' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'The very short description of the company/shop, which gets printed.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e('Leave blank to not print.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_phone"><?php _e( 'Company/Shop Phone', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_phone" rows="1" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'company_phone' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'The phone number of the company/shop, which gets printed.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e('Leave blank to not print a phone.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_email"><?php _e( 'Company/Shop Contact Email', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_email" rows="1" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'company_email' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'The contact email of the company/shop, which gets printed.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e('Leave blank to not print contact email.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_address"><?php _e( 'Company/Shop Address', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>company_address" rows="5" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'company_address' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'The postal address of the company/shop, which gets printed right of the company/shop name, above the order listings.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e('Leave blank to not print an address.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>personal_notes"><?php _e( 'Personal Notes', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>personal_notes" rows="5" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'personal_notes' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'Add some personal notes, or season greetings or whatever (e.g. Thank You for Your Order!, Merry Christmas!, etc.).', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong>
								<?php _e('Leave blank to not print any personal notes.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>policies_conditions"><?php _e( 'Returns Policy, Conditions, etc.:', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>policies_conditions" rows="5" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'policies_conditions' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'Here you can add some more policies, conditions etc. For example add a returns policy in case the client would like to send back some goods. In some countries (e.g. in the European Union) this is required so please add any required info in accordance with the statutory regulations.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong> 
								<?php _e('Leave blank to not print any policies or conditions.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="<?php echo Carton_PDF_Order::$plugin_prefix; ?>footer_imprint"><?php _e( 'Footer Imprint', 'carton-pdf-order' ); ?></label>
						</th>
						<td>
							<textarea name="<?php echo Carton_PDF_Order::$plugin_prefix; ?>footer_imprint" rows="5" class="large-text"><?php echo wp_kses_stripslashes( get_option( Carton_PDF_Order::$plugin_prefix . 'footer_imprint' ) ); ?></textarea>
							<span class="description">
								<?php _e( 'Add some further footer imprint, copyright notes etc. to get the printed sheets a bit more branded to your needs.', 'carton-pdf-order' ); ?>
								<strong><?php _e( 'Note:', 'carton-pdf-order' ); ?></strong> 
								<?php _e('Leave blank to not print a footer.', 'carton-pdf-order' ); ?>
							</span>
						</td>
					</tr>
				</tbody>
			</table>

			<input type="hidden" name="<?php echo $this->hidden_submit; ?>" value="submitted">
			<?php
		}
		
		/**
		 * Get the content for an option
		 */
		public function get_setting( $name ) {
			return get_option( Carton_PDF_Order::$plugin_prefix . $name );
		}
		
		/**
		 * Save all settings
		 */
		public function save_settings_page() {
			if ( isset( $_POST[ $this->hidden_submit ] ) && $_POST[ $this->hidden_submit ] == 'submitted' ) {
				foreach ( $_POST as $key => $value ) {
					if ( $key != $this->hidden_submit && strpos( $key, Carton_PDF_Order::$plugin_prefix ) !== false ) {
						if ( empty( $value ) ) {
							delete_option( $key );
						} else {
							if ( get_option( $key ) && get_option( $key ) != $value ) {
								update_option( $key, $value );
							}
							else {
								add_option( $key, $value );
							}
						}
					}
				}
			}
		}

	}
}
?>
