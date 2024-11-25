<?php
/*
 * Plugin Name: WaafiPay Payment Gateway for WooCommerce
 * Author: Safarifone Inc, WaafiPay.net
 * Description: Accept Mobile Money Wallets and Credit Card payments securely within your online store.
 * Version: 1.1.0
  */

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

define( 'WAAFIPAY_PATH', plugin_dir_url( __FILE__ ) );

function wc_gateway_waafipay()
{
    static $plugin;

    if (!isset($plugin)) {
        require_once('includes/class-wc-gateway-waafi-plugin.php');
 
        $plugin = new WC_Gateway_Waafi_Plugin(__FILE__);
    }

    return $plugin;
}
 
wc_gateway_waafipay()->maybe_run();
