<?php
/**
 * plugin admin handler
 */

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Waafi_Admin_Handler
{
    public function __construct()
    {
        $this->allowed_currencies = include(dirname(__FILE__) . '/settings/supportd-currencies-waafi.php');
    }
    public function is_valid_for_use()
    {
        $currency_list = apply_filters('woocommerce_waafi_supported_currencies', $this->allowed_currencies);
        return in_array(get_woocommerce_currency(), $currency_list);
    }
    
    public function init_form_fields()
    {
        return include(dirname(__FILE__) . '/settings/settings-waafi.php');
    }
}
