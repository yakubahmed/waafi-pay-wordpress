<?php
/**
* Checkout handler class.
*/

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}
$includes_path = wc_gateway_waafipay()->includes_path;
require_once($includes_path. 'abstracts/abstract-wc-gateway-waafi.php');

class WC_Gateway_Waafi_Checkout_Handler
{
    public function __construct()
    {
        $this->waafi_payment_gateway = new WC_Waafi_Payment_Gateway();
        $this->payment_mode = wc_gateway_waafipay()->settings->__get('payment_mode');
        $this->payment_mode_woocomm = wc_gateway_waafipay()->settings->__get('payment_mode');
    }
    
    /*
    * Process payment for checkout
    *
    * @param order id (int)
    * @access public
    * @return array
    */
   public function process_payment($order_id)
    {
	global $woocommerce;

	if (!isset($_POST['waafi_pay_from']) && empty($_POST['waafi_pay_from'])) {
		$pay_from = "CREDIT_CARD";
	} else {
		$pay_from = sanitize_text_field($_POST['waafi_pay_from']);
	}
	$order = wc_get_order( $order_id );
	$order_amount = $order->get_total();
	$currency_code = $order->get_currency();
	$requestId = $this->generateRandomString();
	$orderdata = get_post($order_id);
	$post_password = $orderdata->post_password;

	$timestamp = time();
	$orderid = $order_id;
		
		

	$order_obj = new WC_Order( $order_id );
	$orderarray = json_decode($order_obj,true);	
	$shipping_address = $orderarray['shipping'];	
	$billing_address = $orderarray['billing'];
	

	$items = $woocommerce->cart->get_cart();
	$cart_item = array();
	foreach($items as $item) {
		$inner_item = array();
		$inner_item['product'] = get_the_title($item['product_id']);
		$inner_item['qty'] = $item['quantity'];
		$inner_item['price'] = get_post_meta($item['product_id'] , '_price', true);
		$cart_item[] = $inner_item;
	}

	//$billing_address = json_encode($billing_address, JSON_FORCE_OBJECT);
	//$shipping_address = json_encode($shipping_address, JSON_FORCE_OBJECT);
	//$enocded_items = json_encode($cart_item, JSON_FORCE_OBJECT);
	//$enocded_items = str_replace('"',"'",$enocded_items);
	$storeId = $this->waafi_payment_gateway->store_id;
	$hppKey = $this->waafi_payment_gateway->publishable_key;
	$merchantUid = $this->waafi_payment_gateway->merchant_id;
		
		

	$referenceId = $orderid;
	// $invoiceId = $timestamp.$order_id;
	$invoiceId = $post_password;

	update_post_meta($order_id, 'wc_waafipay_requestId', $requestId);	
	update_post_meta($order_id, 'wc_waafipay_referenceid', $referenceId);	
	update_post_meta($order_id, 'wc_waafipay_invoice', $invoiceId);	
	update_post_meta($order_id, 'wc_waafipay_timestamp', $timestamp);	

	$cust_redirecturlsuc = get_site_url().'/wc-api/waafisuccess/?id='.$order_id;
	$cust_redirecturlfail = get_site_url().'/wc-api/waafifail/?id='.$order_id;
	$desc_site_url = get_home_url();

	$apiurl = $this->waafi_payment_gateway->apiurl;

	$body_array = array (
		"schemaVersion" => '1.0',
		"requestId" => $requestId,
		"timestamp" => $timestamp,
		"channelName" => "WEB",
		"serviceName" => "HPP_PURCHASE",
		"serviceParams" => array(
			"storeId" => $storeId,
			"hppKey" => $hppKey,
			"merchantUid" => $merchantUid,
			"hppSuccessCallbackUrl" => $cust_redirecturlsuc,
			"hppFailureCallbackUrl" => $cust_redirecturlfail,
			"hppRespDataFormat" => "4",
			"paymentMethod" => $pay_from,
			"transactionInfo" => array(
				"referenceId" => $referenceId,
				"invoiceId" => $invoiceId,
				"amount" => $order_amount,
				"currency" => $currency_code,
				"description" => $desc_site_url,
				"cart" => $cart_item,
			),
			"billingAddress" => $billing_address,
			"shippingAddress" => $shipping_address,
			
		),					
	);
		
	

	$response = wp_remote_post( $apiurl , array(
		'method'      => 'POST',
		'timeout'     => 45,
		'redirection' => 10,
		'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
		'httpversion' => '1.0',
		'body' => json_encode($body_array),
		'data_format' => 'body'
		)
	);

	if( !is_wp_error( $response ) || !empty($response) ) {
		// $arrresp = json_decode($response,TRUE);
		$arrresp = json_decode($response['body'], true);
		if($arrresp['errorCode'] == 0 && $arrresp['responseMsg'] == "RCS_SUCCESS") {
			$returnurl = $arrresp['params']['hppUrl'];
			$hppRequestId = $arrresp['params']['hppRequestId'];
			$referenceId = $arrresp['params']['referenceId'];
			update_post_meta($order_id, 'waafi_pay_from', $pay_from);	
			update_post_meta($order_id, 'wc_waafipay_referenceid', $referenceId);	
			update_post_meta($order_id, 'wc_waafipay_requestId', $hppRequestId);	
			update_post_meta($order_id, 'hppwaafiretrnurl', $returnurl);	
			$redirect_url = $returnurl;
			$red_url = add_query_arg(
				[								
					'hppRequestId'   => $hppRequestId,
					'referenceId'   => $referenceId,
				], $redirect_url
			);	
			// Redirect to the thank you page
			return array(
				'result' => 'success',
				'redirect' =>  $red_url
			);	

		} else {								
			wc_add_notice(  $arrresp['responseMsg'], 'error' );
			return;
		}
	} else{
		$arrresp = json_decode($response['body'], true);	
		wc_add_notice(  $arrresp['responseMsg'], 'error' );
		return;
	}	
    }
	
    public function generateRandomString($length = 15) {
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
   }
            
}
