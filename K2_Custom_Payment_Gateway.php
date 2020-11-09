<?php
/**
 * Plugin Name: K2 Custom Payment Gateway for WooCommerce
 * @package K2Blocks
 * Plugin URI: https://pookidevs.com
 * Description: Add Custom Payment Gateway on Woocommerce Checkout with custom fields.
 * Author: PookiDevs
 * Author URI: http://pookidevs.com
 * Version: 1.1
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


// Add K2-Woo-Custom Payment Gateway in existing woocommerce gateway methods array
add_filter( 'woocommerce_payment_gateways', 'K2CGP_add_custom_gateway_class' );

function K2CGP_add_custom_gateway_class( $methods ) {
    $methods[] = 'WC_K2_Woo_Custom_Gateway_payment';
    return $methods;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
}

else{
    add_action( 'admin_notices', 'admin_notice_missing_main_plugin' );
    return;    
}

function admin_notice_missing_main_plugin() {
    deactivate_plugins( plugin_basename(__FILE__) );
    unset($_GET['activate']);
            ?>
        <div class="notice notice-error">
            <p><strong>Plugin deactivated. WooCommerce not installed/activated</strong>.</p>
        </div>
        <?php
}

// Define Custom Gateway Class & Implement Login
add_action('plugins_loaded', 'K2CGP_init_custom_gateway_class');
function K2CGP_init_custom_gateway_class(){

    class WC_K2_Woo_Custom_Gateway_payment extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'custom_payment';

            $this->id                 = 'k2woocustompaymentgateway';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'K2-Woo Custom Payment Gateway', $this->domain );
            $this->method_description = __( 'Description: Add Custom Payment Gateway on Woocommerce Checkout with custom fields.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_style_scripts' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			
		


            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			
			
			
			
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable K2-Woo Custom Payment Gateway', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'Enter the Title to show on the checkout page', $this->domain ),
                    'default'     => __( 'K2-Woo Custom Payment Gateway', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Select Order Status after checkout', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Enter the Title to show on the checkout page', $this->domain ),
                    'default'     => __('Checkout with K2-Woo Custom Payment Gateway with additional custom fields', $this->domain),
                    'desc_tip'    => true,
                )
            );
        }


		public function payment_scripts() {

			// we need JavaScript to process a token only on cart/checkout pages, right?
			if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
				return;
			}

			// if our payment gateway is disabled, we do not have to enqueue JS too
			if ( 'no' === $this->enabled ) {
				return;
			}


		}
		

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            $status = 'wc-' === substr( $this->order_status, 0, 3 ) ? substr( $this->order_status, 3 ) : $this->order_status;

            // Set order status
            $order->update_status( $status, __( 'Checkout with K2-Woo Custom Payment Gateway. ', $this->domain ) );

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }
		
					
    }
}



add_action('woocommerce_checkout_process', 'K2CGP_process_custom_payment');
function K2CGP_process_custom_payment(){
	global $wpdb;

    if($_POST['payment_method'] != 'k2woocustompaymentgateway')
        return;

}
