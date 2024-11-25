<?php
/**
 * WaafiPay Payment gateway .
 */

//directory access forbidden
if (!defined('ABSPATH')) {
    exit;
}

class WC_Waafi_Payment_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->timeout = 45;
        $this->has_fields = false;  // No additional fields in checkout page
        $this->method_title = 'WaafiPay';
        $this->method_description = 'Waafipay payment gateway';
        if (empty(wc_gateway_waafipay()->settings->__get('btn_text'))) {
            $this->order_button_text = 'Proceed to Pay With WAAFI';
        } else {
            $this->order_button_text = wc_gateway_waafipay()->settings->__get('btn_text');
        }
        $this->supports = array(
            'products'
        );

        // Load the settings.
        $this->init_form_fields();
        
        // Configure page fields
        $this->init_settings();
        
        $this->testmode = 'yes' === wc_gateway_waafipay()->settings->__get('testmode');
        $this->enabled = wc_gateway_waafipay()->settings->__get('enabled');
        $this->title = wc_gateway_waafipay()->settings->__get('title');
        $this->description = wc_gateway_waafipay()->settings->__get('description');
        $this->waafi_payment_types = wc_gateway_waafipay()->settings->__get('waafi_payment_types');
                
        $this->apiurl = $this->testmode ? 'https://sandbox.waafipay.net/asm' : 'https://api.waafipay.net/asm';
        
        $this->store_id = $this->testmode ? wc_gateway_waafipay()->settings->__get( 'test_waafi_store_id' ) : wc_gateway_waafipay()->settings->__get( 'waafi_store_id' );
        
        $this->publishable_key = $this->testmode ? wc_gateway_waafipay()->settings->__get( 'test_waafi_publishable_key' ) : wc_gateway_waafipay()->settings->__get( 'waafi_publishable_key' );
        
        $this->merchant_id = $this->testmode ? wc_gateway_waafipay()->settings->__get( 'test_waafi_merchant_id' ) : wc_gateway_waafipay()->settings->__get( 'waafi_merchant_id' );
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options'));
        
        add_action( 'woocommerce_api_waafisuccess', array( $this, 'waafisuccess' ) );
        add_action( 'woocommerce_api_waafifail', array( $this, 'waafifail' ) );
        add_action('wp_footer', array( $this, 'addscript_function'));
        
        //add_filter ('woocommerce_gateway_icon', array( $this, 'njengah_custom_woocommerce_icons'), 10, 2);
        
    }
    
    public function njengah_custom_woocommerce_icons( $icon, $gateway_id) {
        if ('wc-waafipay' === $gateway_id) {
            $icon_url = WAAFIPAY_PATH . 'assets/waafipay_logo.png';
            $icon = '<img src="' . esc_url( $icon_url ) . '" alt="waafipay" />';
        }
         return $icon;
    }


    /*
    * Show gateway settings in woocommerce checkout settings
    */
    public function admin_options() {
        if (wc_gateway_waafipay()->admin->is_valid_for_use()) {
            $this->show_admin_options();
            return true;
        }
        
            wc_gateway_waafipay()->settings->__set('enabled', 'no');
            wc_gateway_waafipay()->settings->save();
        ?>
        <div class="inline error"><p><strong><?php 'Gateway disabled'; ?></strong>: <?php 'WaafiPay Payments does not support your store currency.'; ?></p></div>
        <?php
    }

    public function show_admin_options() {
        $plugin_data = get_plugin_data(WAAFIPAY_PATH . 'wc_waafipay.php');
        $plugin_version = $plugin_data['Version'];

        ?>
        <h3><?php echo esc_html('WaafiPay Payment Gateway'); ?><span><?php echo esc_html('Version ' . $plugin_version); ?></span> </h3>
        <div id="wc_get_started">
            <span class="main"><?php 'WaafiPay Hosted Payment Page'; ?></span>
            <span><br><b>NOTE: </b> You must enter your Store ID , Store Key and Merchant ID</span>
        </div>

        <table class="form-table">
        <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }
    
    /**
    * Process the payment and return the result.
    *
    * @param (int)order id
    * @return array
    */
    public function process_payment ( $order_id ) {
        return wc_gateway_waafipay()->checkout->process_payment($order_id);
    }
    
    public function payment_fields() {
         global $woocommerce;
        if ( $this->description ) {
            echo '<p>' . esc_html($this->description) . '</p>';
        }
        echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
        
        $inputhtml = '';
        
        if (1 < count($this->waafi_payment_types)) {
            foreach ($this->waafi_payment_types as $waafi_payment_types) {
                
                if ( 'CREDIT_CARD' === $waafi_payment_types) {
                    $txt_waafi = 'Credit Card';
                } elseif ( 'MWALLET_ACCOUNT' === $waafi_payment_types) {
                    $txt_waafi = 'Mobile Account';
                } elseif ( 'MWALLET_BANKACCOUNT' === $waafi_payment_types) {
                    $txt_waafi = 'Bank Account';
                }
                echo '<div class="form-row form-row-wide">
                                    <input id="waafi_pay_from_macc" style="height:15px;" name="waafi_pay_from" value="' . esc_html($waafi_payment_types) . '" class="input-radio"  type="radio" />
                                    <span style="font-size: 16px;margin-left: 12px;">' . esc_html($txt_waafi) . '</span>
                                </div>';
            }
        } elseif (1 === count($this->waafi_payment_types)) {
            foreach ($this->waafi_payment_types as $waafi_payment_types) {
                echo '<div class="form-row form-row-wide">
                                    <input id="waafi_pay_from_macc" name="waafi_pay_from" value="' . esc_html($waafi_payment_types) . '"  type="hidden" />
                                </div>';
            }
        } elseif (empty($this->waafi_payment_types)) {
            echo '<div class="form-row form-row-wide">
                                    <input id="waafi_pay_from_macc" name="waafi_pay_from" value="CREDIT_CARD"   type="hidden" />
                                </div>';
        }

        // Add this action hook if you want your custom payment gateway to support it
        do_action( 'woocommerce_credit_card_form_start', $this->id );
        /*** Echo $inputhtml; ***/
        do_action( 'woocommerce_credit_card_form_end', $this->id );
        echo '<div class="clear"></div></fieldset>';
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = wc_gateway_waafipay()->admin->init_form_fields();
    }
    
    public function addscript_function() {
        global $woocommerce, $post;
        if ( is_checkout() ) {
            // phpcs:ignore
            if ( isset( $_REQUEST['cancelled'] ) ) {
                wc_clear_notices();
                wc_add_notice( __( 'Error processing checkout. Please try again.', 'woothemes' ), 'error' );
                // phpcs:ignore
                if (!empty($_GET['msg'])) {
                    wc_add_notice( sanitize_text_field($_GET['msg']), 'error' );
                } else {
                    wc_add_notice( __( 'FAILED_PROCESS_ORDER', 'woothemes' ), 'error' );
                }
            }
        }
    }
    
    public function waafifail() {
        global $woocommerce;
        $storeId = $this->store_id;
        $hppKey = $this->publishable_key;
        $merchantUid = $this->merchant_id;
        // phpcs:ignore
        if (isset($_GET['id'])) {
            // phpcs:ignore
            $explodedid = explode('?' , sanitize_text_field($_GET['id']));
        } else {
            exit;
        }
            /*** $explodedid = explode('?' , $_REQUEST['id']); ***/
            $order_id = $explodedid[0];
            $explodedresponse = explode('=' , $explodedid[1]);
        if ('hppResultToken' === $explodedresponse[0]) {
            $hppResultToken = $explodedresponse[1];
        }
            
        if (!empty($hppResultToken)) {
                $timestamp = get_post_meta($order_id, 'wc_waafipay_timestamp', true);
                $requestId = get_post_meta($order_id, 'wc_waafipay_requestId', true);
                
                $body_array = array (
                    'schemaVersion' => '1.0',
                    'requestId' => $requestId,
                    'timestamp' => $timestamp,
                    'channelName' => 'WEB',
                    'serviceName' => 'HPP_GETRESULTINFO',
                    'serviceParams' => array(
                        'storeId' => $storeId,
                        'hppKey' => $hppKey,
                        'merchantUid' => $merchantUid,
                        'hppResultToken' => $hppResultToken,
                    )
                );
                $response = wp_remote_post( $this->apiurl , array(
                    'method'      => 'POST',
                    'timeout'     => $this->timeout,
                    'redirection' => 10,
                    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                    'httpversion' => '1.0',
                    'body' => wp_json_encode($body_array),
                    'data_format' => 'body'
                    )
                );
                
                $responsarr = json_decode($response['body'], true);
                                
                
            if ('2001' === $responsarr['responseCode']) {
                update_post_meta($order_id, 'waafierror', $responsarr['params']['procDescription']);
                wp_safe_redirect( add_query_arg( array('cancelled' => 'true', 'msg' => $responsarr['params']['procDescription']), wc_get_checkout_url() ) );
                exit;
            }
            
                
        }

            
    }
        
    public function waafisuccess() {
        global $woocommerce;
        $storeId = $this->store_id;
        $hppKey = $this->publishable_key;
        $merchantUid = $this->merchant_id;
        // phpcs:ignore
        if (isset($_GET['id'])) {
            // phpcs:ignore
            $explodedid = explode('?' , sanitize_text_field($_GET['id']));
        } else {
            exit;
        }
        $order_id = $explodedid[0];
        $explodedresponse = explode('=' , $explodedid[1]);
        if ('hppResultToken' === $explodedresponse[0]) {
            $hppResultToken = $explodedresponse[1];
        }
            
        if (!empty($hppResultToken)) {
                $timestamp = get_post_meta($order_id, 'wc_waafipay_timestamp', true);
                $requestId = get_post_meta($order_id, 'wc_waafipay_requestId', true);
                
                $body_array = array (
                    'schemaVersion' => '1.0',
                    'requestId' => $requestId,
                    'timestamp' => $timestamp,
                    'channelName' => 'WEB',
                    'serviceName' => 'HPP_GETRESULTINFO',
                    'serviceParams' => array(
                        'storeId' => $storeId,
                        'hppKey' => $hppKey,
                        'merchantUid' => $merchantUid,
                        'hppResultToken' => $hppResultToken,
                    )
                );
                $response = wp_remote_post( $this->apiurl , array(
                    'method'      => 'POST',
                    'timeout'     => $this->timeout,
                    'redirection' => 10,
                    'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
                    'httpversion' => '1.0',
                    'body' => wp_json_encode($body_array),
                    'data_format' => 'body'
                    )
                );
                
                
                
                $responsarr = json_decode($response['body'], true);
                
                
            if ('2001' === $responsarr['responseCode']) {
            
                $payment_method_title = get_post_meta($order_id, '_payment_method_title', true);
                $waafi_pay_from = get_post_meta($order_id, 'waafi_pay_from', true);
                if ('CREDIT_CARD' === $waafi_pay_from ) {
                        $order_paymnttyp_name = $payment_method_title . ' ( Credit Card )';
                } elseif ('MWALLET_ACCOUNT' === $waafi_pay_from) {
                    $order_paymnttyp_name = $payment_method_title . ' ( MWALLET ACCOUNT )';
                } elseif ( 'MWALLET_BANKACCOUNT' === $waafi_pay_from ) {
                    $order_paymnttyp_name = $payment_method_title . ' ( MWALLET BANKACCOUNT )';
                }
                
                    update_post_meta($order_id, 'hppResultToken', $hppResultToken);
                    update_post_meta($order_id, 'procCode', $responsarr['params']['procCode']);
                    update_post_meta($order_id, 'procDescription', $responsarr['params']['procDescription']);
                    update_post_meta($order_id, 'transactionId', $responsarr['params']['transactionId']);
                    update_post_meta($order_id, 'issuerTransactionId', $responsarr['params']['issuerTransactionId']);
                    update_post_meta($order_id, 'txAmount', $responsarr['params']['txAmount']);
                    update_post_meta($order_id, 'state', $responsarr['params']['state']);
                    
                    
                    
                    $order = wc_get_order( $order_id );
                    $order->payment_complete();
                    $order->reduce_order_stock();
                    
                    // some notes to customer (replace true with false to make it private)
                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );

                    // Empty cart
                    $woocommerce->cart->empty_cart();
                    delete_post_meta($order_id, 'hppwaafiretrnurl');
                    update_post_meta($order_id, '_payment_method_title', $order_paymnttyp_name);
                    $redirecturl = $this->get_return_url( $order );
                    wp_safe_redirect( $redirecturl );
                    exit;
            }
                
                
        }
            
            
    }
}

