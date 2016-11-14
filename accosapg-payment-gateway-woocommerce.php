<?php
/*
Plugin Name: ACCOSA-PG for Woocommerce
Plugin URI: www.siyalude.biz/ipg/accosapg
Description: ACCOSAPG Internet Payment Gateway from Siyaluude Business Solutions. This plug in support any accosa Payment Gateway provided by any bank. Eg: Sampath Bank PLC, Sri Lanka. Seylan Bank Sri Lanka.
Version: 1.0
Author: Siyalude Business Solutions | Indunil Peramuna.
Author URI: www.siyalude.biz
*/
add_action('plugins_loaded', 'woocommerce_siyalude_accosapg_init', 0);
function woocommerce_siyalude_accosapg_init(){
	if(!class_exists('WC_Payment_Gateway')) return;

	class WC_Siyalude_Accosapg extends WC_Payment_Gateway{
		public function __construct(){

			$this->id = 'accosapg';
			$this->medthod_title = 'ACCOSA-PG';
			$this->has_fields = false;

			$this->init_form_fields();
			$this->init_settings();

			$this->icon 				= $this->settings['icon'];
			$this->title 				= $this->settings['title'];
			$this->description 		= $this->settings['description'];
			$this->merchant_id 		= $this->settings['merchant_id'];
			$this->pg_instance_id 	= $this->settings['pg_instance_id'];
			$this->perform 			= $this->settings['perform'];
			$this->currency_code 		= $this->settings['currency_code'];
			$this->hash_key 			= $this->settings['hash_key'];
			$this->liveurl 			= $this->settings['pg_domain'];
			$this->success_responce_code	= $this->settings['success_responce_code'];
			$this->responce_url_success	= $this->settings['responce_url_success'];
			$this->responce_url_fail		= $this->settings['responce_url_fail'];
			$this->checkout_msg			= $this->settings['checkout_msg'];

			$this->msg['message'] = "";
			$this->msg['class'] = "";

			add_action('woocommerce_api_accosapg', array(&$this, 'check_accosapg_response'));

			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
			add_action('woocommerce_receipt_accosapg', array(&$this, 'receipt_page'));
		}
		function init_form_fields(){

			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'accosapg'),
					'type' => 'checkbox',
					'label' => __('Enable ACCOSA-PG Module.', 'siyalude'),
					'default' => 'no'),

				'title' => array(
					'title' => __('Title:', 'siyalude'),
					'type'=> 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'siyalude'),
					'default' => __('Your Banks Name', 'siyalude')),

				'icon' => array(
					'title' => __('Icon:', 'siyalude'),
					'type'=> 'text',
					'description' => __('This is the icon url for payemnt gateway', 'siyalude'),
					'default' => __('ACCOSA-PG', 'siyalude')),

				'description' => array(
					'title' => __('Description:', 'siyalude'),
					'type'=> 'textarea',
					'description' => __('This controls the description which the user sees during checkout.', 'siyalude'),
					'default' => __('ACCOSA-PG', 'siyalude')),

				'pg_domain' => array(
					'title' => __('PG Domain:', 'siyalude'),
					'type'=> 'text',
					'description' => __('IPG data submiting to this URL', 'siyalude'),
					'default' => __('https://www.paystage.com/AccosaPG/verify.jsp', 'siyalude')),

				'merchant_id' => array(
					'title' => __('PG Merchant Id:', 'siyalude'),
					'type'=> 'text',
					'description' => __('Unique ID for the merchant acc, given by bank.', 'siyalude'),
					'default' => __('', 'siyalude')),

				'pg_instance_id' => array(
					'title' => __('PG Instance Id:', 'siyalude'),
					'type'=> 'text',
					'description' => __('collection of intiger numbers, given by bank.', 'siyalude'),
					'default' => __('', 'siyalude')),

				'perform' => array(
					'title' => __('PG perform:', 'siyalude'),
					'type'=> 'text',
					'description' => __('', 'siyalude'),
					'default' => __('initiatePaymentCapture#sale', 'siyalude')),

				'currency_code' => array(
					'title' => __('PG Currency Code LKR:', 'siyalude'),
					'type'=> 'text',
					'description' => __('You\'r currency type of the account. 144 (LKR) 840 (USD) ...', 'siyalude'),
					'default' => __('144', 'siyalude')),

				'hash_key' => array(
					'title' => __('PG Hash Key:', 'siyalude'),
					'type'=> 'text',
					'description' => __('Collection of mix intigers and strings , given by bank.', 'siyalude'),
					'default' => __('', 'siyalude')),

				'success_responce_code' => array(
					'title' => __('Success responce code :', 'siyalude'),
					'type'=> 'text',
					'description' => __('50020 - Transaction Passed | 50097 - Test Transaction Passed', 'siyalude'),
					'default' => __('50097', 'siyalude')),

				'checkout_msg' => array(
					'title' => __('Checkout Message:', 'siyalude'),
					'type'=> 'textarea',
					'description' => __('Message display when checkout'),
					'default' => __('Thank you for your order, please click the button below to pay with the secured Seylan Bank payment gateway.', 'siyalude')),

				'responce_url_success' => array(
					'title' => __('Success redirect URL :', 'siyalude'),
					'type' => 'select',
					'options' => $this -> get_pages('Select Page'),
					'description' => __('After payment is success redirecting to this page.')),

				'responce_url_fail' => array(
					'title' => __('Fail redirect URL :', 'siyalude'),
					'type' => 'select',
					'options' => $this -> get_pages('Select Page'),
					'description' => __('After payment if there is an error redirecting to this page.', 'siyalude'))
			);
		}

		public function admin_options(){
			echo '<style type="text/css">
				.wpimage {
				margin:3px;
				float:left;
				}
				</style>';
			echo '<h3>'.__('ACCOSA-PG (Payment Gateway) By enStage Inc.', 'siyalude').'</h3>';
			echo '<p>'.__('<a target="_blank" href="http://www.siyalude.biz/">Siyalude Business Solutions</a> is a dynamic web design and custom software development firm with offices based in Colombo (Sri Lanka) and Singapore.').'</p>';


			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}

		/**
		 *  There are no payment fields for ACCOSAPG, but we want to show the description if set.
		 **/
		function payment_fields(){
			if($this->description) echo wpautop(wptexturize($this->description));
		}
		/**
		 * Receipt Page
		 **/
		function receipt_page($order){
			global $woocommerce;
			$order_details = new WC_Order($order);

			echo $this->generate_ipg_form($order);
			echo '<br>'.$this->checkout_msg.'</b>';
		}

		/**
		 * Generate button link
		 **/
		public function generate_ipg_form($order_id){

			global $wpdb;
			global $woocommerce;

			$order          = new WC_Order($order_id);
			$productinfo    = "Order $order_id";
			$currency_code  = $this->currency_code;
			$curr_symbole 	= get_woocommerce_currency();

			$messageHash = $this->pg_instance_id."|".$this->merchant_id."|".$this->perform."|".$currency_code."|".(($order->order_total) * 100)."|".$order_id."|".$this	-> hash_key."|";
			$message_hash = "CURRENCY:7:".base64_encode(sha1($messageHash, true));


			$table_name = $wpdb->prefix . 'accosapg';
			$check_oder = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE merchant_reference_no = '".$order_id."'" );

			if($check_oder > 0){
				$wpdb->update(
					$table_name,
					array(
						'transaction_id' => '',
						'transaction_type_code' => '',
						'currency_code' => $this->currency_code,
						'amount' => ($order->order_total),
						'status' => 0000,
						'or_date' => date('Y-m-d'),
						'installments' => '',
						'exponent' => '',
						'3ds_eci' => '',
						'pg_error_code' => '',
						'pg_error_detail' => '',
						'pg_error_msg' => '',
						'message_hash' => ''
					),
					array( 'merchant_reference_no' => $order_id ));
			}else{

				$wpdb->insert($table_name, array( 'transaction_id'=>'', 'merchant_reference_no'=>$order_id, 'transaction_type_code'=>'', 'currency_code'=>$this->currency_code, 'amount'=>$order->order_total, 'status'=>00000,'or_date' => date('Y-m-d'), 'installments'=>'', 'exponent'=>'', '3ds_eci'=>'', 'pg_error_code'=>'', 'pg_error_detail'=>'', 'pg_error_msg'=>'', 'message_hash'=>'' ), array( '%s', '%d' ) );
			}


			$form_args = array(
				'merchant_id' => $this->merchant_id,
				'pg_instance_id' => $this->pg_instance_id,
				'perform' => $this->perform,
				'currency_code' => $currency_code,
				'amount' => (($order->order_total ) * 100 ),
				'merchant_reference_no' => $order_id,
				'order_desc' => $productinfo,
				'message_hash' => $message_hash
			);

			$form_args_array = array();
			foreach($form_args as $key => $value){
				$form_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
			/*'<p>'.$percentage_msg.'</p>*/
			return '<p>Total amount will be <b>'.$curr_symbole.' '.number_format(($order->order_total)).'</b></p>
			<form action="'.$this->liveurl.'" method="post" id="merchantForm">
            ' . implode('', $form_args_array) . '
            <input type="submit" class="button-alt" id="submit_ipg_payment_form" value="'.__('Pay via Credit Card', 'siyalude').'" />
			<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'siyalude').'</a>
            </form>';
		}
		
		/**
		 * Process the payment and return the result
		 **/
		function process_payment($order_id){
			global $woocommerce;
			$order = new WC_Order( $order_id );
			return array('result' => 'success', 'redirect' => add_query_arg('order',
				$order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))
			);
		}

		/**
		 * Check for valid server callback
		 **/
		function check_accosapg_response(){
			global $wpdb;
			global $woocommerce;

			if(isset($_POST['transaction_type_code']) && isset($_POST['status']) && isset($_POST['merchant_reference_no'])){

				$order_id = $_POST['merchant_reference_no'];

				if($order_id != ''){
					$order 	= new WC_Order($order_id);

					$amount = $_POST['amount'];
					$status = $_POST['status'];
					if($this->success_responce_code == $_POST['status']){

						$table_name = $wpdb->prefix . 'accosapg';
						$wpdb->update(
							$table_name,
							array(
								'transaction_id' => $_POST["transaction_id"],
								'transaction_type_code' => $_POST["transaction_type_code"],
								'status' => $_POST["status"],
								'installments' => $_POST["installments"],
								'exponent' => $_POST["exponent"],
								'3ds_eci' => $_POST["3ds_eci"],
								'pg_error_code' => $_POST["pg_error_code"],
								'pg_error_detail' => $_POST["pg_error_detail"],
								'pg_error_msg' => $_POST["pg_error_msg"],
								'message_hash' => $_POST["message_hash"]
							),
							array( 'merchant_reference_no' => $_POST["merchant_reference_no"] ));

						$order->add_order_note('ACCOSA-PG payment successful<br/>Unnique Id from ACCOSA-PG : '.$_POST['transaction_id']);
						$order->add_order_note($this->msg['message']);
						$woocommerce->cart->empty_cart();

						$mailer = $woocommerce->mailer();

						$admin_email = get_option( 'admin_email', '' );

						$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$_POST["transaction_id"].' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));
						$mailer->send( $admin_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );


						$message = $mailer->wrap_message(__( 'Order confirmed','woocommerce'),sprintf(__('Order '.$_POST["transaction_id"].' has been confirmed', 'woocommerce' ), $order->get_order_number(), $posted['reason_code']));
						$mailer->send( $order->billing_email, sprintf( __( 'Payment for order %s confirmed', 'woocommerce' ), $order->get_order_number() ), $message );

						$order->payment_complete();
						wp_redirect( get_permalink($this->responce_url_success), 200 ); exit;

					}else{
						global $wpdb;

						$order->update_status('failed');
						$order->add_order_note('Failed - Code'.$_POST['pgErrorCode']);
						$order->add_order_note($this->msg['message']);

						$table_name = $wpdb->prefix . 'accosapg';
						$wpdb->update(
							$table_name,
							array(
								'transaction_id' => $_POST["transaction_id"],
								'transaction_type_code' => $_POST["transaction_type_code"],
								'status' => $_POST["status"],
								'installments' => $_POST["installments"],
								'exponent' => $_POST["exponent"],
								'3ds_eci' => $_POST["3ds_eci"],
								'pg_error_code' => $_POST["pg_error_code"],
								'pg_error_detail' => $_POST["pg_error_detail"],
								'pg_error_msg' => $_POST["pg_error_msg"],
								'message_hash' => $_POST["message_hash"]
							),
							array( 'merchant_reference_no' => $_POST["merchant_reference_no"] ));

						wp_redirect( get_permalink($this->responce_url_fail), 200 ); exit;
					}
				}

			}

		}
		
		// get all pages
		function get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
					$has_parent = $page->post_parent;
					while($has_parent) {
						$prefix .=  ' - ';
						$next_page = get_page($has_parent);
						$has_parent = $next_page->post_parent;
					}
				}
				// add to page list array array
				$page_list[$page->ID] = $prefix . $page->post_title;
			}
			return $page_list;
		}
	}
	
	/**
	 * Add the Gateway to WooCommerce
	 **/
	function woocommerce_add_siyalude_accosapg_gateway($methods) {
		$methods[] = 'WC_Siyalude_Accosapg';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_siyalude_accosapg_gateway' );
}

global $jal_db_version;
$jal_db_version = '1.0';

function jal_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'accosapg';
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE {$wpdb->collate}";
	}

	$sql = "CREATE TABLE $table_name (
					id int(9) NOT NULL AUTO_INCREMENT,
					transaction_id int(9) NOT NULL,
					merchant_reference_no VARCHAR(20) NOT NULL,
					transaction_type_code VARCHAR(20) NOT NULL,
					currency_code int(6) NOT NULL,
					amount VARCHAR(20) NOT NULL,
					status int(6) NOT NULL,
					or_date DATE NOT NULL,
					installments VARCHAR(20) NOT NULL,
					exponent text NOT NULL,
					3ds_eci text NOT NULL,
					pg_error_code text NOT NULL,
					pg_error_detail text NOT NULL,
					pg_error_msg text NOT NULL,
					message_hash text NOT NULL,
					UNIQUE KEY id (id)
				) $charset_collate;";


	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jal_db_version', $jal_db_version );
}

register_activation_hook( __FILE__, 'jal_install' );