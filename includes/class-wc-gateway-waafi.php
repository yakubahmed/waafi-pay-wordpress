<?php
/*
 * WaafiPay Gateway for WooCommercee
*/

//directory access forbidden
if (!defined('ABSPATH')) {
	exit;
}

class WC_Gateway_Waafi extends WC_Waafi_Payment_Gateway {
	public function __construct() {
		$this->id = 'wc-waafipay';

		parent::__construct();
	}
}
