<?php
/*
 * WaafiPay Plugin for WooCommerce
 * 
*/

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Waafi_Plugin
{
    const DEPENDENCIES_UNSATISFIED  = 1;

    public function __construct($file)
    {
        $this->file = $file;
        $this->plugin_path   = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url    = trailingslashit(plugin_dir_url($this->file));
        $this->plugin_url    = trailingslashit(plugin_dir_url($this->file));
        $this->includes_path = $this->plugin_path . trailingslashit('includes');
    }

    /**
     * run the plugin.
    */
    public function maybe_run()
    {
        register_activation_hook($this->file, array($this, 'activate'));
        register_deactivation_hook($this->file, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'bootstrap'));
        add_filter('plugin_action_links_' . plugin_basename($this->file), array($this, 'plugin_action_links'));
        
        $waafipaySettings = (array) get_option('woocommerce_hpp-waafipay_settings', array());
		#add_action('admin_enqueue_scripts', array($this, 'admin_load_js'));
        
    }

    public function bootstrap()
    {
        try {
            $this->_check_dependencies();
            $this->_run();
            delete_option('wc_gateway_waafi_bootstrap_warning_message');
        } catch (Exception $e) {
            if (in_array($e->getCode(), array(self::DEPENDENCIES_UNSATISFIED))) {

                update_option('wc_gateway_waafi_bootstrap_warning_message', $e->getMessage());
            }
            add_action('admin_notices', array($this, 'show_bootstrap_warning'));
        }
    }

    protected function _check_dependencies()
    {
        if (!function_exists('WC')) {
            throw new Exception(__('HPP Waafipay payments for WooCommerce requires WooCommerce to be activated', 'waafi-woocommerce'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (version_compare(WC()->version, '3.0', '<')) {
            throw new Exception(__('HPP Waafipay payments for WooCommerce requires WooCommerce version 3.0 or greater', 'waafi-woocommerce'), self::DEPENDENCIES_UNSATISFIED);
        }

        if (!function_exists('curl_init')) {
            throw new Exception(__('HPP Waafipay payments for WooCommerce requires cURL to be installed on your server', 'waafi-woocommerce'), self::DEPENDENCIES_UNSATISFIED);
        }
    }


    function admin_load_js(){
        wp_enqueue_script( 'custom_js', plugins_url( '../assets/js/custom.js', __FILE__ ), array('jquery') );
    }

    

    public function show_bootstrap_warning()
    {
        $dependencies_message = get_option('wc_gateway_waafi_bootstrap_warning_message', '');
        if (!empty($dependencies_message)) {
            ?>
            <div class="error fade">
                <p>
                    <strong><?php echo esc_html($dependencies_message); ?></strong>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Run the plugin.
     */
    protected function _run()
    {
        $this->_load_handlers();
    }

    protected function _load_handlers()
    {

        // Load handlers.
        require_once($this->includes_path . 'class-wc-gateway-waafi-settings.php');
        require_once($this->includes_path . 'class-wc-gateway-waafi-gateway-loader.php');
        require_once($this->includes_path . 'class-wc-gateway-waafi-admin-handler.php');
        require_once($this->includes_path . 'class-wc-gateway-waafi-checkout-handler.php');

        $this->settings       = new WC_Gateway_Waafi_Settings();
        $this->gateway_loader = new WC_Gateway_waafi_Gateway_Loader();
        $this->admin          = new WC_Gateway_Waafi_Admin_Handler();
        $this->checkout       = new WC_Gateway_Waafi_Checkout_Handler();
    }

    /**
     * Callback for activation hook.
     */
    public function activate()
    {
        if (!isset($this->setings)) {
            require_once($this->includes_path . 'class-wc-gateway-waafi-settings.php');
            $this->settings = new WC_Gateway_Waafi_Settings();
        }
        return true;
    }

    public function deactivate()
    {
        return true;
    }

    public function plugin_action_links($links)
    {
        $plugin_links = array();

        $setting_url = $this->get_admin_setting_link();
        $plugin_links[] = '<a href="' . esc_url($setting_url) . '">' . esc_html__('Settings', 'wc-waafipay') . '</a>';


        return array_merge($plugin_links, $links);
    }

    /**
     * Link to settings screen.
     */
    public function get_admin_setting_link()
    {
        return admin_url('admin.php?page=wc-settings&tab=checkout&section=wc-waafipay');
    }

    
}
