<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Waafi Gateway.
 */
return array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable service, Payments through WaafiPay Hosted Page',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'btn_text' => array(
					'title'       => 'Button text',
					'type'        => 'text',
					'description' => 'Text to display on the payment button',
					'default'     => 'Pay now',
					'desc_tip'    => true,
				),
				'waafi_payment_types' => array(
					'title'         => __('Payment Methods', 'wcwaafipay'),
					'type'          => 'multiselect',
					'options'       => array(
					  'MWALLET_ACCOUNT'      => __('Mobile Account', 'wcwaafipay'),
					  'MWALLET_BANKACCOUNT'       => __('Bank Account', 'wcwaafipay'),
					  'CREDIT_CARD'         => __('Credit Card', 'wcwaafipay'),					  
					 ),
					
					'description'   => 'Payment Methods.'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'Title that users will see when selecting this means of payment.',
					'default'     => 'WaafiPay',
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'Description that users will see when selecting this payment method',
					'default'     => 'Pay with your preferred wallet or credit card via our super-cool payment gateway.',
					'desc_tip'    => true,
				),
				'testmode' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'test_waafi_store_id' => array(
					'title'       => 'Test Store ID',
					'type'        => 'text'
				),
				'test_waafi_publishable_key' => array(
					'title'       => 'Test HPP Key',
					'type'        => 'text'
				),
				'test_waafi_merchant_id' => array(
					'title'       => 'Test Merchant ID',
					'type'        => 'text',
				),
				'waafi_store_id' => array(
					'title'       => 'Live Store ID',
					'type'        => 'text'
				),
				'waafi_publishable_key' => array(
					'title'       => 'Live HPP Key',
					'type'        => 'text'
				),
				'waafi_merchant_id' => array(
					'title'       => 'Live Merchant ID',
					'type'        => 'text',
				)
			);
