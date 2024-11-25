<?php
/**
 * Plugin Loader.
 */

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_waafi_Gateway_Loader
{
    
    public function __construct()
    {
        $includes_path = wc_gateway_waafipay()->includes_path;

        require_once($includes_path . 'class-wc-gateway-waafi.php');

        add_filter('woocommerce_payment_gateways', array($this, 'payment_gateways'));
    }

    /**
     
     * @param array Payment methods.
     *
     * @return array Payment methods
     */
    public function payment_gateways($methods)
    {
        $methods[] = 'WC_Gateway_Waafi';
        return $methods;
    }
}
