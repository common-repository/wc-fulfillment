<?php
if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('DS_Fulfillment_Shipping_Method___ID__')) {

  class DS_Fulfillment_Shipping_Method___ID__ extends WC_Shipping_Method {

    public function __construct($instance_id = 0) {
      $this->id = '__ID__';
      $this->instance_id = absint($instance_id);

      $this->method_title = __('__NAME__', 'dropshipping-fulfillment');
      $this->method_description = __('"__NAME__" shipping method.', 'dropshipping-fulfillment');

      $this->type = 'class';

      $this->init_form_fields();
      $this->init_settings();
      $this->init_instance_settings();

      $this->supports = array(
        'shipping-zones',
        'instance-settings',
        'instance-settings-modal'
      );

      $this->enabled = $this->get_option('fulfillment____ID___enabled');
      $this->title = $this->get_option('fulfillment____ID___title');
      $this->description = $this->get_option('fulfillment____ID___description');

      $this->debug_mode = $this->get_option('fulfillment____ID___debug_mode');

      add_action('woocommerce_update_options_shipping_' . $this->id, array(
        $this,
        'admin_options'
      ));
    }

    public function init_form_fields() {

      $this->instance_form_fields = array(
        'fulfillment____ID___enabled' => array(
          'title' => __('Enable/Disable', 'dropshipping-fulfillment') ,
          'type' => 'checkbox',
          'label' => __('Enable this shipping method', 'dropshipping-fulfillment') ,
          'default' => 'yes',
        ) ,
        'fulfillment____ID___title' => array(
          'title' => __('Method Title', 'dropshipping-fulfillment') ,
          'type' => 'text',
          'description' => __('This controls the title which the user sees during checkout.', 'dropshipping-fulfillment') ,
          'default' => __('__NAME__', 'dropshipping-fulfillment') ,
          'desc_tip' => true
        ) ,
        'fulfillment____ID___description' => array(
          'title' => __('Method Description', 'dropshipping-fulfillment') ,
          'type' => 'text',
          'description' => __('This controls the description which the user sees during checkout.', 'dropshipping-fulfillment') ,
          'default' => __('__NAME__', 'dropshipping-fulfillment') ,
          'desc_tip' => true
        ) ,
        'fulfillment____ID___debug_mode' => array(
          'title' => __('Enable Debug Mode', 'dropshipping-fulfillment') ,
          'type' => 'checkbox',
          'label' => __('Enable', 'dropshipping-fulfillment') ,
          'default' => 'no',
          'description' => __('If debug mode is enabled, the shipping method will be activated just for the administrator.') ,
        ) ,
        'fulfillment____ID___base_rate' => array(
          'title' => __('Base rate', 'dropshipping-fulfillment') ,
          'type' => 'price',
          'description' => __('Base delivery rate') ,
          'default' => '__PRICE__',
          'css' => 'width: 100px;',
          'placeholder' => wc_format_localized_price(0)
        ) ,
        'fulfillment____ID___cod_rate' => array(
          'title' => __('COD rate', 'dropshipping-fulfillment') ,
          'type' => 'price',
          'description' => __('Collect on Delivery additional rate ') ,
          'default' => null,
          'css' => 'width: 100px;',
          'placeholder' => wc_format_localized_price(0)
        )
      );
    }

    public function is_available($package) {
      if ($this->debug_mode === 'yes') {
        return current_user_can('administrator');
      }

      return true;
    }

    public function admin_options() {
      parent::admin_options();
    }

    public function calculate_shipping($package = array()) {
      $rate = array(
        'id' => $this->get_rate_id() ,
        'label' => $this->title,
        'cost' => $this->get_option('base_rate') ,
        'package' => $package
      );
      $this->add_rate($rate);
    }
  }

}
