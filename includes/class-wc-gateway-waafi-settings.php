<?php
/*
 * Waafi Payment Gateway setting calss
*/

//directory access forbidden
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class WC_Gateway_Waafi_Settings {

    /**
     * Setting values from get_option.
     *
     * @var array
     */
    protected $_settings = array();
    
    /**
     * Flag to indicate setting has been loaded from DB.
     *
     * @var bool
     */
    private $_is_setting_loaded = false;
    
    public function __construct()
    {
		
        $this->load();
    }
    
    public function __set($key, $value)
    {
        if (array_key_exists($key, $this->_settings)) {
            $this->_settings[$key] = $value;
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->_settings)) {
            return $this->_settings[$key];
        }
        return null;
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->_settings);
    }
    
    /**
     * Load settings from DB.
     *
     */
    public function load()
    {
        if ($this->_is_setting_loaded) {
            return $this;
        }
        $this->_settings          = (array) get_option('woocommerce_wc-waafipay_settings', array());
        $this->_is_setting_loaded = true;
        return $this;
    }
    
    public function save()
    {
        update_option('woocommerce_wc-waafipay_settings', $this->_settings);
    }
    
    /**
     * Is enabled.
     *
     * @return bool
     */
    public function is_enabled()
    {
        return true === $this->enabled;
    }

    /**
     * Is logging enabled.
     *
     * @return bool
     */
    public function is_logging_enabled()
    {
        return true === $this->debug;
    }
}
