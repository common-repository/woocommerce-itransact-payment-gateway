<?php
/*
Plugin Name: WooCommerce iTransact Payment Gateway
Version: 2.0.3
Plugin URI: https://wordpress.org/plugins/woocommerce-itransact-payment-gateway/
Description: iTransact Payment Gateway Plugin for WooCommerce.
Author: Outsource WordPress
Author URI: https://www.outsource-wordpress.com/
*/

add_action( 'plugins_loaded', 'iTransact_init', 0 );

include(plugin_dir_path( __FILE__ )."lib/iTransactSDK.php");
	
use iTransact\iTransactSDK\CardPayload;
use iTransact\iTransactSDK\AddressPayload;
use iTransact\iTransactSDK\TransactionPayload;
use iTransact\iTransactSDK\iTTransaction;

function iTransact_init()
{
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	};
	
	class WC_itransact extends WC_Payment_Gateway 
	{	
		var $notify_url;
		var $gateway_url;
		
		/**
		 * Constructor for the gateway.
		 *
		 * @access public
		 * @return void
		 */
		 public function __construct()
		 {
			global $woocommerce;
	
			$this->id			= 'wc_itransact';
			$this->method_title = __( 'iTransact', 'woocommerce' );
			$this->has_fields	 = false;
			$this->icon		 = apply_filters( 'woocommerce_techprocess_icon', $woocommerce->plugin_url() . '/assets/images/icons/itransact-logo.png' );
			$this->notify_url        = WC()->api_request_url( 'WC_itransact' );
			$this->gateway_url        = 'https://secure.itransact.com/customers/split';
			
			// Load the form fields.
			$this->init_form_fields();
				
			// Load the settings.
			$this->init_settings();
				
			// Define user set variables
			$this->title				 = $this->get_option('title');
			$this->description			 = $this->get_option('description');			
			$this->payment_method 		 = $this->get_option('payment_method');
			$this->vendor_id			 = $this->get_option('vendor_id');
			$this->merchant_name 		 = $this->get_option('merchant_name');
			$this->api_username			 = $this->get_option('api_username');
			$this->api_key 		 		 = $this->get_option('api_key');
			
			$this->card_types	 		 = array( 'mastercard', 'visa', 'discover', 'amex', 'dinersclub' );
	
			add_action('woocommerce_api_wc_techprocess', array($this, 'check_response' ) );
			
			if($this->payment_method!="api")
				add_action('woocommerce_receipt_techprocess', array(&$this, 'receipt_page'));
				
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			
			// Payment listener/API hook
			add_action( 'woocommerce_api_wc_itransact', array( $this, 'check_response' ) );
			
			if(is_admin())
				wp_enqueue_script('gateway-itransact_admin',plugins_url( 'js/gateway-itransact_admin.js' , __FILE__ ) );
			
			if ( !$this->is_valid_for_use() ) $this->enabled = false;
		}
		
		function check_response()
		{			
			@ob_clean();
	
			$_POST = stripslashes_deep($_POST);
			$this->successful_request($_POST);
		}
		
		function successful_request( $posted )
		{
			global $woocommerce;
			
			$valid="";
			$order_id_key=$posted['customerReference'];
			$order_id_key=explode("-",$order_id_key);
			$order_id=$order_id_key[0];
			$order_key=$order_id_key[1];
			$err=$posted['err'];
			$die=$posted['die'];
			$xid=$posted['xid'];
			$paidtotal=$posted['total'];
			if(!$paidtotal)
			{
				$totaltemp=$posted['wc-api'];
				$totaltemp1=explode('?total=',$totaltemp);
				$paidtotal=$totaltemp1[1];
			}
			
			$order = new WC_Order( $order_id );
			
			if ( $order->order_key !== $order_key ) :
				echo 'Error: Order Key does not match Invoice.';
				$valid="no";
				exit;
			endif;
			
			if ( $err ) {
				echo $err;
				echo '<br><br><a href="javascript:window.history.back();">Please try again.</a>';
				exit;
			}
			
			if ( !$paidtotal ) {
				echo 'Payment unsuccessful.';
				echo '<br><br><a href="javascript:window.history.back();">Please try again.</a>';
				exit;
			}
			
			if ( $order->get_total() != $paidtotal ) {
				echo 'Error: Amount not match.';
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: Amounts do not match (%s).', 'woocommerce' ), $paidtotal ) );
				$valid="no";
				exit;
			}
	
			// if TXN is approved
			if($err=="" && $die=="" && $valid=="")
			{
				// Payment completed
				$order->add_order_note( __('Payment completed', 'woocommerce') );
	
				// Mark order complete
				$order->payment_complete();
	
				  // Empty cart and clear session
				$woocommerce->cart->empty_cart();
	
				// Redirect to thank you URL
				wp_redirect( $this->get_return_url( $order ) );
				exit;
			}
			else // TXN has declined
			{	   
				// Change the status to pending / unpaid
				$order->update_status('pending', __('Payment declined', 'woothemes'));
			   
				// Add a note with the IPG details on it
				$order->add_order_note(__('iTransact payment failed - Transaction Reference: ' . $xid . " - Response Code: " .$err, 'woocommerce')); // FAILURE NOTE
			   
				// Add error for the customer when we return back to the cart
				$woocommerce->add_error(__('Transaction declined: ', 'woothemes') . $err);
			   
				// Redirect back to the last step in the checkout process
				wp_redirect( $woocommerce->cart->get_checkout_url());
				exit;
			}
	
		}
	
		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use()
		{
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_itransact_supported_currencies', array( 'USD', 'EUR', 'GBP' ) ) ) ) {
				return false;
			}
		
			return true;
		}
	
		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		public function admin_options()
		{
			?>
			<h3><?php _e('iTransact', 'woocommerce'); ?></h3>	
			<?php  
			if( $this->payment_method == "api" )
			{
				if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
					echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> API Payment Method is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
				}
				if(!function_exists('curl_exec'))
				{
					echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> API Payment Method is enabled and PHP cURL library needs to be installed and enabled.</a>" ), $this->method_title ) ."</p></div>";
				}
			}
			?>
			<table class="form-table">
			<?php
				if ( $this->is_valid_for_use() ) :
					// Generate the HTML For the settings form
					$this->generate_settings_html();
				else :
					?>
						<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'iTransact does not support your store currency.', 'woocommerce' ); ?></p></div>
					<?php
				endif;
			?>
			</table><!--/.form-table-->
			<?php
		}
	
		/**
		 * Initialise Gateway Settings Form Fields
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array
							(
								'title' => __( 'Enable/Disable', 'woocommerce' ),
								'type' => 'checkbox',
								'label' => __( 'Enable iTransact', 'woocommerce' ),
								'default' => 'yes'
							),
				'title' => array
							(
								'title' => __( 'Title', 'woocommerce' ),
								'type' => 'text',
								'description' => __( 'This is the title the customer can see when checking out', 'woocommerce' ),
								'default' => __( 'iTransact', 'woocommerce' )
							),
				'description' => array
							(
								'title' => __( 'Description', 'woocommerce' ),
								'type' => 'text',
								'description' => __( 'This is the description the customer can see when checking out', 'woocommerce' ),
								'default' => __("Pay with Credit Card via iTransact", 'woocommerce')
							),
				'payment_method' => array
							(
								'title' => __( 'Payment Method', 'woocommerce' ),
								'type' => 'select',
								'default' => 'redirect',
								'options' => array(
									  'redirect' => 'Redirect (Redirect to iTransact site for payment)',
									  'api' => 'API (Get credit card information within this site)',
								 )
							),	
				'merchant_name' => array
							(
								'title' => __( 'Merchant Name', 'woocommerce' ),
								'type' => 'text',
								'class' => 'redirect_options',
								'description' => __( 'Merchant name that is shown on payment page', 'woocommerce' ),
								'default' => ''
							),				
				'vendor_id' => array
							(
								'title' => __( 'Vendor ID', 'woocommerce' ),
								'type' => 'text',
								'class' => 'redirect_options',
								'description' => __( '<div style="text-align:left;">To get this,<br><ol><li>Login into iTransact Gateway</li><li>Navigate to Control Panel > Merchant Settings</li><li>Copy \'Order Form UID\' from Integration tab</li></ol></div>', 'woocommerce' ),
								'default' => '',
								'desc_tip'	=>  true
							)	,	
				'api_username' => array
							(
								'title' => __( 'API Username', 'woocommerce' ),
								'type' => 'text',
								'class' => 'api_options',
								'description' => __( '<div style="text-align:left;">To get this,<br><ol><li>Login into iTransact Gateway</li><li>Navigate to Control Panel > Merchant Settings</li><li>Copy \'API Username\' from Integration tab</li></ol></div>', 'woocommerce' ),
								'default' => '',
								'desc_tip'	=>  true
							),				
				'api_key' => array
							(
								'title' => __( 'API Key', 'woocommerce' ),
								'type' => 'text',
								'class' => 'api_options',
								'description' => __( '<div style="text-align:left;">To get this,<br><ol><li>Login into iTransact Gateway</li><li>Navigate to Control Panel > Merchant Settings</li><li>Copy \'API Key\' from Integration tab</li></ol></div>', 'woocommerce' ),
								'default' => '',
								'desc_tip'	=>  true
							)   
				);
		}
		
		/*get card images*/
		public function get_icon()
		{
			$icon = '';
			foreach ($this->card_types as $card_type )
			{
				$icon .= '<img width="45" src="' . esc_url( WC_HTTPS::force_https_url( plugins_url( 'images/' . $card_type . '.png' , __FILE__ ) ) ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />&nbsp;&nbsp;';
			}
	
			return apply_filters( 'woocommerce_itransact_icon', $icon, $this->id );
		}
      
		/*get card types*/
		function get_card_type($number)
		{
			if($number!="")
			{
				$number=preg_replace('/[^\d]/','',$number);
				if (preg_match('/^3[47][0-9]{13}$/',$number))
				{
					return 'amex';
				}
				elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
				{
					return 'dinersclub';
				}
				elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
				{
					return 'discover';
				}
				elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
				{
					return 'jcb';
				}
				elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
				{
					return 'mastercard';
				}
				elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
				{
					return 'visa';
				}
				else
				{
					return 'invalid';
				}
			}
			else
				return 'blank';
		}
	
		/**
		 * Get techprocess Args
		 *
		 * @access public
		 * @param mixed $order
		 * @return array
		 */
		function get_itransact_args( $order )
		{
			global $woocommerce;
	
			$order_id = $order->id;
			$data = array();			
			
			$bill_add=$order->billing_address_1;
			if($order->billing_address_2!="") $bill_add=$bill_add." ".$order->billing_address_2;
			$ship_add=$order->shipping_address_1;
			if($order->shipping_address_2!="") $ship_add=$ship_add." ".$order->shipping_address_2;
			
			$data['passback'] = "customerReference";
			$data['customerReference'] = $order_id.'-'.$order->order_key;
			
			$data['email'] = $order->billing_email;
			$data['first_name'] = $order->billing_first_name;
			$data['last_name'] = $order->billing_last_name;
			$data['address'] = $bill_add;
			$data['city'] = $order->billing_city;
			$data['state'] = $order->billing_state;
			$data['zip'] = $order->billing_postcode;
			$data['country'] = $order->billing_country;
			$data['phone'] = $order->billing_phone;
			
			$data['sfname'] = $order->shipping_first_name;
			$data['slname'] = $order->shipping_last_name;
			$data['saddr'] = $ship_add;
			$data['scity'] = $order->shipping_city;
			$data['sstate'] = $order->shipping_state;
			$data['szip'] = $order->shipping_postcode;
			$data['sctry'] = $order->shipping_country;
			
			//$data['amount'] = number_format($order->get_total(), 2, '.', '');	
			
			$cnt=0;
			
			$orderitems = $order->get_items();
			$items = array();
			if (count($orderitems)) {
				foreach ($orderitems as $item) {
					// get SKU
					if ($item['variation_id']) { 

						if(function_exists("wc_get_product")) {
					    	$product = wc_get_product($item['variation_id']);
					    }
					    else {
					    	$product = new WC_Product($item['variation_id']);
					    }
				  	} 
				  	else {

				  		if(function_exists("wc_get_product")) {
				    		$product = wc_get_product($item['product_id']);
					    }
					    else {
					    	$product = new WC_Product($item['product_id']);
					    }
				  	}
					
					$cnt++;
					$pdtname=str_replace("<sup>&reg;</sup>","",$item['name']);
					
					$data['item_'.$cnt.'_desc'] = $pdtname;						
					$data['item_'.$cnt.'_qty'] = $item['qty'];
					$data['item_'.$cnt.'_cost'] = number_format(($item['line_subtotal'] / $item['qty']),2,'.','');
				}
			}							
			
			// Check whether to add shipping
			if ($order->get_total_shipping() > 0)
			{
				$cnt++;
				
				$data['item_'.$cnt.'_desc'] = 'Shipping';						
				$data['item_'.$cnt.'_qty'] = 1;
				$data['item_'.$cnt.'_cost'] = number_format($order->get_total_shipping(),2,'.','');
			}
			
			// Check whether to add tax
			if ( $order->get_total_tax() > 0 )
			{
				$cnt++;
				
				$data['item_'.$cnt.'_desc'] = 'Tax';						
				$data['item_'.$cnt.'_qty'] = 1;
				$data['item_'.$cnt.'_cost'] = number_format($order->get_total_tax(),2,'.','');
			}

			// Check whether to add discount
			if ( $order->get_total_discount() > 0 )
			{
				$cnt++;
				
				$data['item_'.$cnt.'_desc'] = 'Discount';						
				$data['item_'.$cnt.'_qty'] = 1;
				$data['item_'.$cnt.'_cost'] = "-".number_format($order->get_total_discount(),2,'.','');
			}
			
			$data['vendor_id'] = $this->vendor_id;
			$data['home_page'] = site_url();
			$data['ret_addr'] = $this->notify_url;
			
			$data['showaddr'] = "1";
			$data['showcvv'] = "1";
			$data['show_items'] = "1";
			$data['mername'] = $this->merchant_name;
			$data['acceptcards'] = "1";
			$data['acceptchecks'] = "0";
			$data['accepteft'] = "0";
			$data['altaddr'] = "0";
			$data['nonum'] = "1";
			$data['ret_mode'] = "post";
			
			$data['post_back_on_error'] = "1";
			$data['lookup'] = "xid";
			$data['lookup'] = "total";
			
			return $data;
		}
	
		/**
		 * Process the payment and return the result
		 *
		 * @access public
		 * @param int $order_id
		 * @return array
		 */
		function process_payment( $order_id )
		{
			$order = new WC_Order( $order_id );					
			
			if($this->payment_method=="api")
			{
				$cardtype = $this->get_card_type( sanitize_text_field(str_replace(" ", "",$_POST['wc_itransact-card-number']) ) );
			
         		if(!in_array($cardtype ,$this->card_types ))
         		{
					if($cardtype=="blank")
						wc_add_notice('Please enter Card Number.' ,  $notice_type = 'error' );
					else
         				wc_add_notice('Merchant do not accept '.$cardtype.' card.' ,  $notice_type = 'error' );
         			return false;
					die();
         		}
         
				$exp_date         = explode( "/", sanitize_text_field($_POST['wc_itransact-card-expiry']));
				$exp_month        = str_replace( ' ', '', $exp_date[0]);
				$exp_year         = str_replace( ' ', '',$exp_date[1]);								
	
				if (strlen($exp_year) == 2) {
					$exp_year += 2000;
				}
				
				if(strlen($exp_month)!=2 || strlen($exp_year)!=4)
         		{
         			wc_add_notice('Expiry Date invalid.' ,  $notice_type = 'error' );
         			return false;
					die();
         		}
				if($_POST['wc_itransact-card-cvc']=="")
         		{
         			wc_add_notice('Plese enter Card CVC Code.' ,  $notice_type = 'error' );
         			return false;
					die();
         		}
				
				$exp_month = ltrim($exp_month, '0');
				$pay_amount = (number_format($order->order_total,2,".",""))*100;
				
				$order_name = $order->billing_first_name;
				if($order->billing_last_name!="") $order_name.=" ".$order->billing_last_name;
								
				$card = new CardPayload(
					sanitize_text_field($order_name),
					sanitize_text_field(str_replace(" ", "",$_POST['wc_itransact-card-number']) ),
					sanitize_text_field(str_replace(" ", "",$_POST['wc_itransact-card-cvc']) ),
					$exp_month,
					$exp_year
				);
				
				$address = new AddressPayload(
					$order->billing_address_1,
					$order->billing_address_2,
					$order->billing_city,
					$order->billing_state,
					$order->billing_postcode
				);
				
				$payload = new TransactionPayload(
					$pay_amount,
					$card,
					$address
				);
				
				$sdk = new iTTransaction();
				
				// Use the following to get payload signature, and submit the transaction.
				$postResult = $sdk->postCardTransaction($this->api_username, $this->api_key, $payload);
				
				if ( count($postResult) > 1 )
				{
				 	if( 'captured' == $postResult['status'] )
					{
						$order->add_order_note( __( $postResult['status'].' on '.date("d-m-Y h:i:s e"). ' with Transaction ID = '.$postResult['id'].' AVS Response: '.$postResult['avs_response'].' CVV Response: '.$postResult['cvv_response'].' Authorization Code: '.$postResult['authorization_code']  , 'woocommerce' ) );
					
						$order->payment_complete($postResult['id']);
						WC()->cart->empty_cart();
						return array (
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						   );
					}
					else 
					{
						$order->add_order_note( __( $postResult['status']  , 'woocommerce' ) );	 
						wc_add_notice($postResult['status'] , $notice_type = 'error' );
					}
				}
				else 
				{
					$order->add_order_note( __( $postResult['status']  , 'woocommerce' ) );	 
					wc_add_notice($postResult['status'] , $notice_type = 'error' );
				}
			}
			else
			{
				$itransact_args = $this->get_itransact_args( $order );
				$itransact_args = http_build_query( $itransact_args, '', '&' );
		
				$gateway_adr = $this->gateway_url . '?';
		
				return array(
					'result' 	=> 'success',
					'redirect'	=> $gateway_adr . $itransact_args
				);
			}
		}
		
		function receipt_page()
		{
			echo '<p>'.__('Thank you for your order, click on submit to process iTransact payment.', 'woocommerce').'</p>';
			echo $this->generate_itransact_form();
		}
		
		public function field_name( $name ) {
			return $this->supports( 'tokenization' ) ? '' : ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
		}
		
		public function payment_fields()
		{
			echo apply_filters( 'description', wpautop(wp_kses_post( wptexturize(trim($this->description) ) ) ) );
			$this->generate_itransact_form();
		}
		
		function generate_itransact_form()
		{
			if($this->payment_method=="api")
			{
				wp_enqueue_script( 'wc-credit-card-form' );
				
				$fields = array();
				$cvc_field = '<p class="form-row form-row-last">
					<label for="' . esc_attr( $this->id ) . '-card-cvc">' . __( 'Card CVC Code', 'woocommerce' ) . ' <span class="required">*</span></label>
					<input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . '/>
				</p>';
				$default_fields = array(
					'card-number-field' => '<p class="form-row form-row-wide">
						<label for="' . esc_attr( $this->id ) . '-card-number">' . __( 'Card Number', 'woocommerce' ) . ' <span class="required">*</span></label>
						<input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
					</p>',
					'card-expiry-field' => '<p class="form-row form-row-first">
						<label for="' . esc_attr( $this->id ) . '-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
						<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' />
					</p>',
					'card-cvc-field'  => $cvc_field
				);
				
				 $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
				?>
		
				<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
					<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
					<?php
						foreach ( $fields as $field ) {
							echo $field;
						}
					?>
					<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
					<div class="clear"></div>
				</fieldset>
			<?php
			}
			else
			{
				$return='<form action="'.esc_url( $this->gateway_url ).'" method="post" id="itransact_payment_form" target="_top">';
				$return .='<input type="submit" id="submit_itransact_payment_form" value="submit"/></form>';
				return $return;
			}
		}
	}
	
	function woocommerce_itransact_add_gateway( $methods )
	{
		$methods[] = 'WC_itransact';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'woocommerce_itransact_add_gateway' );
}

/*plugin settings link*/
function itransact_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_itransact">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'itransact_settings_link' );
?>