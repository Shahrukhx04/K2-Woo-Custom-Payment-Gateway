<?php
/*
 * Plugin Name: WooCommerce Telenor Payment Gateway
 * Plugin URI: https://pookidevs.com
 * Description: Set up billing through Telenor Mobile Carrier.
 * Author: PookiDevs Technologies
 * Author URI: http://pookidevs.com
 * Version: 2.0.0
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

 
 
 add_filter( 'woocommerce_payment_gateways', 'add_custom_gateway_class' );
function add_custom_gateway_class( $methods ) {
    $methods[] = 'WC_Telenor_Gateway';
    return $methods;
}


add_action('plugins_loaded', 'init_custom_gateway_class');
function init_custom_gateway_class(){

    class WC_Telenor_Gateway extends WC_Payment_Gateway {

        public $domain;

        /**
         * Constructor for the gateway.
         */
        public function __construct() {

            $this->domain = 'custom_payment';

            $this->id                 = 'telenor';
            $this->icon               = apply_filters('woocommerce_custom_gateway_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'Telenor Payment Gateway', $this->domain );
            $this->method_description = __( 'Gateway for Telenor Payment Integeration to set up billing through the mobile carrier.', $this->domain );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->order_status = $this->get_option( 'order_status', 'completed' );

            // Actions
            // 			
            
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_style_scripts' ) );

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			
		


            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			
			
			
			
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

			// You can also register a webhook here

			//add_filter( 'woocommerce_after_checkout_validation' , 'Validate_Telenor_Fields' );

			// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
			//ajax for get code
			add_action('wp_ajax_sf_telenor_getcode', array( $this, 'sf_telenor_getcode' )); // wp_ajax_{action}
			add_action('wp_ajax_nopriv_sf_telenor_getcode', array( $this, 'sf_telenor_getcode' )); // wp_ajax_nopriv_{action}
			
			//get code for verify code
			add_action('wp_ajax_sf_telenor_verifycode', array( $this, 'sf_telenor_verifycode' )); // wp_ajax_{action}
			add_action('wp_ajax_nopriv_sf_telenor_verifycode', array( $this, 'sf_telenor_verifycode' )); // wp_ajax_nopriv_{action}
        }

        /**
         * Initialise Gateway Settings Form Fields.
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', $this->domain ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Custom Payment', $this->domain ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', $this->domain ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', $this->domain ),
                    'default'     => __( 'Custom Payment', $this->domain ),
                    'desc_tip'    => true,
                ),
                'order_status' => array(
                    'title'       => __( 'Order Status', $this->domain ),
                    'type'        => 'select',
                    'class'       => 'wc-enhanced-select',
                    'description' => __( 'Choose whether status you wish after checkout.', $this->domain ),
                    'default'     => 'wc-completed',
                    'desc_tip'    => true,
                    'options'     => wc_get_order_statuses()
                ),
                'description' => array(
                    'title'       => __( 'Description', $this->domain ),
                    'type'        => 'textarea',
                    'description' => __( 'Payment method description that the customer will see on your checkout.', $this->domain ),
                    'default'     => __('Payment Information', $this->domain),
                    'desc_tip'    => true,
                )
            );
        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
          
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
            
        }

        public function payment_fields(){

            if ( $description = $this->get_description() ) {
                echo wpautop( wptexturize( $description ) );
            }

			$nonce = wp_create_nonce("telenor_user_nonce");

            ?>
            <div id="custom_input">
                 <div style="padding: 0em; display: flex; flex-wrap: wrap;">
						 <input style = "flex: 1 1 65%; box-sizing: border-box !important; margin-right: 0.5em;  margin-top: 0.3em; background-color: #F5F5F5;" type="text" class="" name="mobile" id="mobile" placeholder="03XXXXXXXXX" value="" >
						<div id="telenor_get_code_skillsfirst" data-nonce="' . $nonce . '" style="flex: 1 1 25%; color:white; background-color: #0143A3; margin-right: 0.5em; margin-top: 0.3em; padding: 14px; font-family: Roboto; align-items: center; cursor: pointer; justify-content: center;"><span>Receive Code</span></div>
                </div>
				<p id="VerficationSentMessage" style="font-family: Roboto; padding-top: 0.2em; display: none; color: red;">
					Please check your phone for verification code.
				</p>
				<div style = "margin-top: 0.5em; width: 100%; display: inline-flex; height: 50px;">
                <p class="form-row form-row-wide">
                    <input style="width: 99% ; box-sizing: border-box !important; margin-right: auto; background-color: #F5F5F5; " type="text" class="" name="transaction" id="transaction" placeholder="Pin Code" value="">
                </p>
				</div>
            </div>
            <?php
			
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

			// no reason to enqueue JavaScript if API keys are not set
			/*if ( empty( $this->private_key ) || empty( $this->publishable_key ) ) {
				return;
			}
			// do not work with card detailes without SSL unless your website is in a test mode
			if ( ! $this->testmode && ! is_ssl() ) {
				return;
			}*/

			// and this is our custom JS in your plugin directory
			wp_register_script( 'woocommerce_sf_telenor', plugins_url( 'RecieveButton.js', __FILE__ ), array( 'jquery'), null, false );
			wp_localize_script( 'woocommerce_sf_telenor', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
			// in most payment processors you have to use PUBLIC KEY to obtain a token
			//wp_localize_script( 'woocommerce_misha', 'misha_params', array(
			//	'publishableKey' => $this->publishable_key
			//) );

			wp_enqueue_script( 'woocommerce_sf_telenor' );


		}
		
		public function payment_style_scripts() {

			// we need JavaScript to process a token only on cart/checkout pages, right?
			if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
				return;
			}
			wp_register_style( 'telenor_css', plugins_url( 'telenor_payment_style.css', __FILE__ ) );
    		wp_enqueue_style( 'telenor_css' );
			
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
            $order->update_status( $status, __( 'Checkout with Telenor payment Gateway. ', $this->domain ) );

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



add_action('woocommerce_checkout_process', 'process_custom_payment');
function process_custom_payment(){
	global $wpdb;

    if($_POST['payment_method'] != 'telenor')
        return;
	

    if( !isset($_POST['mobile']) || empty($_POST['mobile']) ) {
        wc_add_notice( __( 'Please add your Mobile Number' ), 'error' );
		return;
	}
	
    if( !isset($_POST['transaction']) || empty($_POST['transaction']) ) {
        wc_add_notice( __( 'Please add your Pin Code' ), 'error' );
		return;
	}
	
	
	
	$Mobile_Number = $_POST['mobile'];
	$Mobile_code = $_POST['transaction'];
	
	//verify mobile number
	if  (strlen($Mobile_Number) < 11 || !is_numeric($Mobile_Number) ) {
		wc_add_notice( __( 'Invalid Number' ), 'error' );
		return;
	}
	$code_from_db_result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_codes WHERE msisdn = '{$Mobile_Number}'");
	if(count($code_from_db_result) < 1){
		wc_add_notice( __( 'Please generate a code first' ), 'error' );
		return;
	}
	$code_from_db = $code_from_db_result[0]->code;
	if((string)$code_from_db != (string)$Mobile_code){
		wc_add_notice( __( "Pin Code incorrect"), 'error' );
		return;
	}
	if(!($code_from_db_result[0]->valid)){
		wc_add_notice( __( "Invalid code. Please generate a new one."), 'error' );
		return;
	}
	//all was good so mark code as false so it is not used again
	$wpdb->update(
		$wpdb->prefix . 'sftelenor_codes', 
		array( 	
			'valid' => 0
		), 
		array( 'msisdn' => $Mobile_Number ), 
		array( 
			'%d'	
		), 
		array( '%s' ) 
	);
	
	
	
	$cart_amount = (string) WC()->cart->get_cart_total();
	$cart_amount = substr(preg_replace("/[^0-9]/","", $cart_amount), -4); //This is dangerous!!!
	$cart_amount = "0.1"; //comment for actual price
	
	//get token from database
	$token_result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'token'");
	$token = $token_result[0]->value;
	$token_time_stamp = strtotime($token_result[0]->time_stamp);
	$time_difference = time() - $token_time_stamp;
	if ($time_difference > 1800 || empty($token)){ //more than 30 mins or empty
		//get new token
		$args = array(
			'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password )
			)
		);
		$response =  wp_remote_post( $url, $args);
		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$token = $response->access_token;
		//store new token in db
		if(!empty($token)){
			$wpdb->update(
				$wpdb->prefix . 'sftelenor_constants', 
				array( 
					'value' => $token,	
					'time_stamp' => date("Y-m-d H:i:s")
				), 
				array( 'name' => 'token' ), 
				array( 
					'%s',	// value1
					'%s'	// value2
				), 
				array( '%s' ) 
			);
		}
		else{
			wc_add_notice( __( "Sorry we are facing a technical problem. Try again later."), 'error' );
			return;
		}
	}
	//for transactionID
	$code_from_db_time_stamp = strtotime($code_from_db_result[0]->time_stamp);
	$code_from_db_time_stamp = date('YmdHis', $code_from_db_time_stamp); 

	$charge_endpoint = 'https://apis.telenor.com.pk/payment/v1/charge';
 
	$charge_body = [
	 		"msisdn" => $Mobile_Number,
	 		"chargableAmount" => $cart_amount, //change to $cart_amount
	 		"correlationId" => "{$Mobile_Number}{$Mobile_code}{$code_from_db_time_stamp}", //same as transactionid
	 		"PartnerID" => "TP-SKILLSFIRST",
	 		"ProductID" => "SFOneTimeSM-Charge",
			"TransactionID" => "{$Mobile_Number}{$Mobile_code}{$code_from_db_time_stamp}" //Mobilenumber+code+timestamp(of code)
	 	];

	$charge_body = wp_json_encode( $charge_body );

	$charge_options = [
		'body'        => $charge_body,
		'headers'     => [ 
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		],
		'timeout'     => 60,
		'data_format' => 'body',
	];

	$ChargeApiResponse = wp_remote_post( $charge_endpoint, $charge_options );
	
	$Error = json_decode( wp_remote_retrieve_body( $ChargeApiResponse ) );

	
	if ($Error->errorMessage != null){
			wc_add_notice( __($Error->errorMessage), 'error' );
			return;
	} 
	//Send text message to user----------------------------------------------------------------
	$username = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'username'");
	$username = $username[0]->value;

	//get password from database
	$password = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'password'");
	$password = $password[0]->value;



	//get token from database
	$url = "https://apis.telenor.com.pk/oauthtoken/v1/generate?grant_type=client_credentials";
	$token_result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'token'");
	$token = $token_result[0]->value;
	$token_time_stamp = strtotime($token_result[0]->time_stamp);
	$time_difference = time() - $token_time_stamp;
	if ($time_difference > 1800 || empty($token)){ //more than 30 mins or empty
		//get new token
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password )
			)
		);
		$response =  wp_remote_post( $url, $args);
		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$token = $response->access_token;
		//store new token in db
		if(!empty($token)){
			$wpdb->update(
				$wpdb->prefix . 'sftelenor_constants', 
				array( 
					'value' => $token,	
					'time_stamp' => date("Y-m-d H:i:s")
				), 
				array( 'name' => 'token' ), 
				array( 
					'%s',	// value1
					'%s'	// value2
				), 
				array( '%s' ) 
			);
		}
	}


	$endpoint = 'https://apis.telenor.com.pk/sms/v1/send';
	$body = [
		"messageBody" => "Payment has been successful and Rs.{$cart_amount} has been deducted from your account. Thank you for buying this course. Happy Learning!!!",
		"recipientMsisdn" => $Mobile_Number
	];

	$body = wp_json_encode( $body );

	$options = [
		'body'        => $body,
		'headers'     => [ 
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		],
		'timeout'     => 60,
		'data_format' => 'body',
	];

	$MessageApiResponse = wp_remote_post( $endpoint, $options );
	
	

}

/**
 * Update the order meta with field value
 */
add_action( 'woocommerce_checkout_update_order_meta', 'custom_payment_update_order_meta' );
function custom_payment_update_order_meta( $order_id ) {

	global $wpdb;
    //if($_POST['payment_method'] != 'custom')
     //   return;

	$customer_mobile_number = $_POST['mobile'];
    if ( ! empty( $customer_mobile_number ) )
        update_post_meta( $order_id, 'telenor_mobile_number', sanitize_text_field( $customer_mobile_number ) );

    $customer_mobile_code = $_POST['transaction'];
    if ( ! empty( $customer_mobile_code ) )
        update_post_meta($order_id, 'code', sanitize_text_field( $customer_mobile_code ) );
	
	//getting time_Stamp for this code
	$code_from_db_result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_codes WHERE msisdn = '{$customer_mobile_number}'");

	$code_from_db_time_stamp = strtotime($code_from_db_result[0]->time_stamp);
	$code_from_db_time_stamp = date('YmdHis', $code_from_db_time_stamp); 
    update_post_meta($order_id, 'telenor_transaction_id', "{$customer_mobile_number}{$customer_mobile_code}{$code_from_db_time_stamp}" );
	
}

/**
 * Display field value on the order edit page
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'custom_checkout_field_display_admin_order_meta', 10, 1 );
function custom_checkout_field_display_admin_order_meta($order){
    $method = get_post_meta( $order->id, '_payment_method', true );
    if($method != 'custom')
        return;

    $mobile = get_post_meta( $order->id, 'mobile', true );
    $transaction = get_post_meta( $order->id, 'transaction', true );

    echo '<p><strong>'.__( 'Mobile Number' ).':</strong> ' . $mobile . '</p>';
    echo '<p><strong>'.__( 'Transaction ID').':</strong> ' . $transaction . '</p>';
}


// register the ajax action for authenticated users
add_action('wp_ajax_sf_get_code', 'sf_get_code');

// register the ajax action for unauthenticated users
add_action('wp_ajax_nopriv_sf_get_code', 'sf_get_code');

// handle the ajax request
function sf_get_code() {
 
	global $wpdb;
	
	$Mobile_Number = $_REQUEST['mobile_number'];
	
	//verify mobile number
	if  (strlen($Mobile_Number) < 11 || !is_numeric($Mobile_Number) ) {
		wc_add_notice( __( 'Invalid Number' ), 'error' ); //TODO: only works when page is reloaded
		return;
	}
	
	$url = "https://apis.telenor.com.pk/oauthtoken/v1/generate?grant_type=client_credentials";
	
	//get username form database
	$username = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'username'");
	$username = $username[0]->value;
	
	//get password from database
	$password = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'password'");
	$password = $password[0]->value;
	
	
	
	//get token from database
	$token_result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sftelenor_constants WHERE name = 'token'");
	$token = $token_result[0]->value;
	$token_time_stamp = strtotime($token_result[0]->time_stamp);
	$time_difference = time() - $token_time_stamp;
	if ($time_difference > 1800 || empty($token)){ //more than 30 mins or empty
		//get new token
		$args = array(
			'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password )
			)
		);
		$response =  wp_remote_post( $url, $args);
		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$token = $response->access_token;
		//store new token in db
		if(!empty($token)){
			$wpdb->update(
				$wpdb->prefix . 'sftelenor_constants', 
				array( 
					'value' => $token,	
					'time_stamp' => date("Y-m-d H:i:s")
				), 
				array( 'name' => 'token' ), 
				array( 
					'%s',	// value1
					'%s'	// value2
				), 
				array( '%s' ) 
			);
		}
	}

	$Mobile_Code = (string)rand(1000,9999);
	//store code against number in db
	$wpdb->query( $wpdb->prepare( 
		"
			REPLACE INTO {$wpdb->prefix}sftelenor_codes
			( msisdn, code, valid, time_stamp )
			VALUES ( %s, %s, %d, %s )
		", 
			$Mobile_Number, 
			$Mobile_Code, 
			1,
			date("Y-m-d H:i:s")
	) );
	
	
	$endpoint = 'https://apis.telenor.com.pk/sms/v1/send';
	$customer_name = $_REQUEST['customer_name'];
 
	$body = [
		"messageBody" => "Hi {$customer_name}, This is the message from SkillsFirst Platform. Your code is {$Mobile_Code}",
		"recipientMsisdn" => $Mobile_Number
	];

	$body = wp_json_encode( $body );

	$options = [
		'body'        => $body,
		'headers'     => [ 
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $token,
		],
		'timeout'     => 60,
		'data_format' => 'body',
	];

	$MessageApiResponse = wp_remote_post( $endpoint, $options );
	
	
	wp_send_json_success([$MessageApiResponse]);

}
