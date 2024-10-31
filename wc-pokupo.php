<?php
/**
 * Plugin Name: Pokupo WooCommerce Gateway
 * Plugin URI: https://pokupo.ru
 * Description: Provides a Pokupo Payment Gateway.
 * Version: 1.0.2
 * WC requires at least: 4.0
 * WC tested up to: 4.0
 * Author: Pokupo
 */


/* Add a custom payment class to WC
  ------------------------------------------------------------ */
if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', 'woocommerce_pokupo', 0);
function woocommerce_pokupo()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;
    if (class_exists('WC_Pokupo'))
        return;

    class WC_Pokupo extends WC_Payment_Gateway
    {
        public function __construct()
        {

            $plugin_dir = plugin_dir_url(__FILE__);

            global $woocommerce;

            $this->id = 'pokupo';
            $this->icon = apply_filters('woocommerce_pokupo_icon', '' . $plugin_dir . 'pokupo.svg');
            $this->method_title       =  __( 'Pokupo', 'wс-pokupo' );
            $this->method_description =  $this->t('desc');

            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->ID = $this->get_option('ID');
            $this->NOTIFICATION_PASSWORD = $this->get_option('NOTIFICATION_PASSWORD');
            $this->MerchantLang = $this->get_option('MerchantLang');
            $this->SuccessUrl = urlencode($this->get_option('SuccessUrl'));
            $this->FailUrl = urlencode($this->get_option('FailUrl'));
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
            
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_wc_' . $this->id, array($this, 'check_assistant_response'));

        }


        public function t($str)
        {
            if(get_locale()=='ru_RU'){
                $l['pay']='Оплатить';
                $l['cancel']='Отказаться от оплаты и вернуться в корзину';
                $l['merchant']="https://seller.pokupo.ru/api/ru";
                $l['completed']='Заказ успешно оплачен';
                $l['failed']='Заказ не оплачен';
                $l['thanks']="Спасибо за Ваш заказ, пожалуйста, нажмите кнопку ниже, чтобы заплатить";
                $l['desc'] =  'Pokupo – свыше 30 методов оплаты и онлайн касса бесплатно';
                $l['shopid_desc'] =  'ID (для CMS) вашего магазина в Pokupo';

            }    
            else{
                $l['pay']='Make Payment';
                $l['cancel']='Back to cart';
                $l['merchant']="https://seller.pokupo.io/api/en";
                $l['completed']='Payment completed';
                $l['failed']='Payment failed';
                $l['thanks']="Thanks for your order, push the button below to complete payment";
                $l['desc'] =  'Pokupo – more than 30 payment methods';
                $l['shopid_desc'] =  'Your Pokupo shop id for CMS';
            }

            if($l[$str]){
                return $l[$str];
            }
            else{
                return $str;
            }
        }


        /**
         * Форма настроек платежного шлюза
         *
         * @access public
         * @return void
         */
        function init_form_fields()
        {

            $resulturl = site_url('?wc-api=wc_pokupo&pokupo=callback');
            $successurl = site_url('?wc-api=wc_pokupo&pokupo=success');
            $failurl = site_url('?wc-api=wc_pokupo&pokupo=fail');

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Activate/Deactivate', 'wc-pokupo'),
                    'type' => 'checkbox',
                    'label' => __('Activated', 'wc-pokupo'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Name', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This is the name that the user sees when paying', 'wc-pokupo'),
                    'default' => __('Pokupo', 'wc-pokupo')
                ),
                'ID' => array(
                    'title' => __('Shop Id', 'wc-pokupo'),
                    'type' => 'text',
                    'description' => $this->t('shopid_desc'),
                    'default' => ''
                ),
                'NOTIFICATION_PASSWORD' => array(
                    'title' => __('Notification Password', 'wc-pokupo'),
                    'type' => 'text',
                    'default' => ''
                ),
                'ResultUrl' => array(
                    'title' => __('Notification Url', 'wc-pokupo'),
                    'type' => 'title',
                    'description' => $resulturl,
                    'default' => ''
                ),
                'SuccessUrl' => array(
                    'title' => __('Success Url', 'wc-pokupo'),
                    'type' => 'text',
                    'description' => __('URL to which the user will be redirected in case of successful payment', 'wc-pokupo'),
                    'default' => $successurl
                ),
                'FailUrl' => array(
                    'title' => __('Fail Url', 'wc-pokupo'),
                    'type' => 'text',
                    'description' => __('URL to which the user will be redirected in case of unsuccessful payment', 'wc-pokupo'),
                    'default' => $failurl
                ),
                'MerchantLang' => array(
                    'title' => __('Merchant language', 'wc-pokupo'),
                    'type' => 'select',
                    'options'=>array('ru'=>'ru', 'en'=>'en'),
                    'description' => __('Merchant language', 'wc-pokupo'),
                    'default' => 'ru'
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Payment method description.', 'woocommerce'),
                    'default' => 'Pokupo Payment'
                ),
                'instructions' => array(
                    'title' => __('Instructions', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Instructions', 'woocommerce'),
                    'default' => 'Pokupo Payment'
                ),

            );
        }

        /**
         * There are no payment fields for sprypay, but we want to show the description if set.
         **/
        function payment_fields()
        {
            if ($this->description) {
                echo wpautop(wptexturize($this->description));
            }
        }


        /**
         * Форма отправки заказа
         **/
        public function generate_form($order_id)
        {
            global $woocommerce;

            $order = new WC_Order($order_id);
        
            $action_adr = $this->t('merchant')."/payment/merchant";

            $out_summ = number_format($order->get_total(), 2, '.', '');
            $successUrl = $this->SuccessUrl;
            $failUrl = $this->FailUrl;


            /*Setup product description*/
            $order_desc = '';
            $desc_count=0;
            $items = $order->get_items();
            foreach ( $items as $item )
            {
                $order_desc .= ' ' . $item['name'];
            }    

           /* Count order description length */
           if(function_exists('mb_strlen'))
           {
                $desc_count = mb_strlen($order_desc);
            }
           elseif (function_exists('iconv_strlen'))
           {
                $desc_count = iconv_strlen($order_desc);
            }
            else
            {
                $desc_count = strlen($order_desc);
            }

            if($desc_count > 250)
            {
                $order_desc = 'Order number: ' . $order_id;
            }

            $args = array(
                'LMI_PAYEE_PURSE' => $this->ID,
                'LMI_PAYMENT_AMOUNT' => $out_summ,
                'LMI_PAYMENT_NO' => $order_id,
                'LMI_PAYMENT_DESC' => $order_desc,
                'LMI_SUCCESS_URL' => $successUrl,
                'LMI_FAIL_URL' => $failUrl,
                'CLIENT_MAIL' => $order->get_billing_email(),  
            );

            $args_array = array();

            foreach ($args as $key => $value) {
                $args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }


            return
                '<form action="' . esc_url($action_adr) . '" method="GET" id="pokupo_payment_form">' . "\n" .
                implode("\n", $args_array) .
                '<input type="submit" class="button alt" id="submit_pokupo_payment_form" value="' . $this->t('pay') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . $this->t('cancel') . '</a>' ."\n" .
                '</form>';

        }



        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);

            return array(
                'result' => 'success',
                'redirect' => add_query_arg('order-pay', $order->get_id(), add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
            );
        }

        /**
         * receipt_page
         **/
        function receipt_page($order)
        {
            echo '<p>' . $this->t('thanks') . '</p>';
            echo $this->generate_form($order);
        }



        /**
         * Check pokupo validity
         **/
        public $errorMessage;

        function check_pokupo_is_valid($posted)
        {
            if (!isset($posted['LMI_PAYMENT_NO'])) {
                $this->errorMessage = 'LMI_PAYMENT_NO is missing';
                return false;
            }
            $merch_key = $this->NOTIFICATION_PASSWORD;
            $shop_id = $this->ID;
            $HTTP = $posted;
            $LMI_PAYEE_PURSE = sanitize_key($HTTP['LMI_PAYEE_PURSE']);
            $LMI_PAYMENT_AMOUNT = sanitize_text_field($HTTP['LMI_PAYMENT_AMOUNT']);
            $LMI_PAYMENT_NO = sanitize_key($HTTP['LMI_PAYMENT_NO']);
            $LMI_SYS_TRANS_NO = sanitize_text_field($HTTP['LMI_SYS_TRANS_NO']);
            $LMI_MODE = sanitize_key($HTTP['LMI_MODE']);
            $LMI_SYS_INVS_NO = sanitize_text_field($HTTP['LMI_SYS_INVS_NO']);
            $LMI_SYS_TRANS_DATE = sanitize_text_field($HTTP['LMI_SYS_TRANS_DATE']);
            $LMI_PAYER_PURSE = sanitize_email($HTTP['LMI_PAYER_PURSE']);
            $LMI_PAYER_WM = sanitize_email($HTTP['LMI_PAYER_WM']);
            $LMI_HASH = sanitize_text_field($HTTP['LMI_HASH']);
            $order = new WC_Order($LMI_PAYMENT_NO);
            if (!$order || !$order->get_total()) {
                $this->errorMessage = 'LMI_PAYMENT_NO is incorrect';
                return false;
            }

            if ($order->is_paid()) {
                $this->errorMessage = 'Order is already paid';
                return false;
            }

            $orderSum = number_format($order->get_total(), 2, '.', '');
            if ($orderSum != $LMI_PAYMENT_AMOUNT) {
                $this->errorMessage = 'LMI_PAYMENT_AMOUNT is incorrect';
                return false;
            }
            if ($LMI_PAYEE_PURSE != $shop_id) {
                $this->errorMessage = 'LMI_PAYEE_PURSE is incorrect';
                return false;
            }
            if (isset($HTTP['LMI_SECRET_KEY'])) {
                $LMI_SECRET_KEY = sanitize_text_field($HTTP['LMI_SECRET_KEY']);
                if ($LMI_SECRET_KEY != $merch_key) {
                    $this->errorMessage = 'LMI_SECRET_KEY is incorrect';
                } else {
                    return true;
                }
            } else {
                $CalcHash = md5($LMI_PAYEE_PURSE . $LMI_PAYMENT_AMOUNT . $LMI_PAYMENT_NO . $LMI_MODE . $LMI_SYS_INVS_NO . $LMI_SYS_TRANS_NO . $LMI_SYS_TRANS_DATE . $merch_key . $LMI_PAYER_PURSE . $LMI_PAYER_WM);
                if ($LMI_HASH != strtoupper($CalcHash)) {
                    $this->errorMessage = 'LMI_HASH is incorrect';
                    return false;
                } else {
                    return true;
                }
            }
        }

        /**
         * Check Response
         **/

        function check_assistant_response()
        {

            global $woocommerce;
            $_REQUEST = stripslashes_deep($_REQUEST);

            $LMI_PAYMENT_NO=sanitize_key($_REQUEST['LMI_PAYMENT_NO']);

            foreach ($_REQUEST as $key => $value) {
                if (strstr($key, '&') || strstr($key, 'amp;')) {
                    unset ($_REQUEST[$key]);
                    $key = str_replace("&", '', $key);
                    $key = str_replace("amp;", '', $key);
                    $_REQUEST[$key] = $value;
                }
            }

            if (isset($_REQUEST['pokupo']) AND sanitize_text_field($_REQUEST['pokupo']) == 'callback') {

                if (isset($_REQUEST['LMI_PREREQUEST']) && sanitize_key($_REQUEST['LMI_PREREQUEST']) == 1) {
                    die ('YES');
                } else {
                    if ($this->check_pokupo_is_valid($_REQUEST)) {
                        $order_id = $LMI_PAYMENT_NO;
                        $order = new WC_Order($order_id);
                        $order->update_status('completed', $this->t('completed'));
                        die('YES');
                    } else {
                        wp_die('Error: '.$this->errorMessage);
                    }
                }
            } else if (isset($_REQUEST['pokupo']) AND sanitize_text_field($_REQUEST['pokupo']) == 'success') {
                $order_id = $LMI_PAYMENT_NO;
                $order = new WC_Order($order_id);
                $woocommerce->cart->empty_cart();
                wp_redirect($this->get_return_url($order));
                exit;

            } else if (isset($_REQUEST['pokupo']) AND sanitize_text_field($_REQUEST['pokupo']) == 'fail') {
                $order_id = $LMI_PAYMENT_NO;
                $order = new WC_Order($order_id);
                $order->update_status('failed', $this->t('failed'));
                wp_redirect($order->get_cancel_order_url());
                exit;
            }
        }

    }


    /**
     * Add the gateway to WooCommerce
     **/
    function add_pokupo_gateway($methods)
    {
        $methods[] = 'WC_Pokupo';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_pokupo_gateway');
}

?>
