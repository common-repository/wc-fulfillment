<?php
if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('DS_Fulfillment_Payment_Method___ID__')) {

  class DS_Fulfillment_Payment_Method___ID__ extends WC_Payment_Gateway {

    public static $validated;

    private static $index = 0;

    public function __construct() {

      $this->id = '__ID__';
      $this->has_fields = false;

      $this->method_title = __('__NAME__', 'dropshipping-fulfillment');
      $this->method_description = __('"__NAME__" fulfillment payment method.', 'dropshipping-fulfillment');
      //$this->icon         = X::get_plugin_directory_url() . "/layout/images/icon.png";
      $this->init_form_fields();
      $this->init_settings();

      $this->enabled = $this->settings["fulfillment___ID___enabled"] ? : __('');
      $this->title = $this->settings["fulfillment___ID___title"] ? : __('');
      $this->description = $this->settings["fulfillment___ID___description"] ? : __('');

      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
        $this,
        'process_admin_options'
      ));
    }

    public function init_form_fields() {
      $this->form_fields = array(
        'fulfillment___ID___enabled' => array(
          'title' => __('Enable/Disable', 'dropshipping-fulfillment') ,
          'type' => 'checkbox',
          'default' => 'no'
        ) ,
        'fulfillment___ID___title' => array(
          'title' => __('Title', 'dropshipping-fulfillment') ,
          'type' => 'textarea',
          'default' => __('__NAME__') ,
          'description' => __('Title of the payment', 'dropshipping-fulfillment')
        ) ,
        'fulfillment___ID___description' => array(
          'title' => __('Description', 'dropshipping-fulfillment') ,
          'type' => 'textarea',
          'default' => __('__NAME__') ,
          'description' => __('Description of the payment', 'dropshipping-fulfillment')
        )
      );
    }

    public function init_settings() {
      parent::init_settings();

      $this->settings["available_gateways"] = array();
    }

    public function get_available_gateways() {
      return $this->settings["available_gateways"];
    }

    /**
     * overrides validation function, so it not only check input fields validity but also validates
     * sing generation for given values and contact the gateway server and retrieve
     * list of allowed gateways for given merchant
     * @param  array|boolean $form_fields
     */
    public function validate_settings_fields($form_fields = false) {
      if (!$form_fields) {
        $form_fields = $this->get_form_fields();
      }

      parent::validate_settings_fields($form_fields);

      if (self::$validated) return;

      if (count($this->errors)) {
        // no code
      }

      self::$validated = true;
    }

    public function print_errors() {
      if (self::$validated) return;

      // no code
    }

    /**
     * renders admin options page
     */
    public function admin_options() {
      parent::admin_options();
    }

    /**
     * this function handles the order after the order form is submites, it validates
     * order data respectively and redirect the user to the gateway page where
     * he could choose the payement method
     * @param  int $order_id
     * @return array
     */
    public function process_payment($order_id) {

      global $woocommerce;

      $order = new WC_Order($order_id);

      /*if ( $order->get_total() > 0 ) {
        $order->update_status(apply_filters('woocommerce_fulfillment_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order), __('Payment to be made.', 'dropshipping-fulfillment'));
      } else {
        $order->payment_complete();
      }*/

      // Remove cart.
      //WC()->cart->empty_cart();

      return array(
        'result'   => 'success',
        'redirect' => $this->get_return_url($order)
      );
    }

    /**
     * this function proccess xml notification retrieved from gateway server and process it
     * by checking its validity in first step an then mark the order status respectively of transaction result
     * @param  string $params_xml (without xml header)
     * @return bool
     */
    public function process_notification() {
      // no code
      
    }
  }

}