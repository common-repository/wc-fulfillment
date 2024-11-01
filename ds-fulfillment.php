<?php
/*
 * @wordpress-plugin
 * Plugin Name:       DS Fulfillment
 * Plugin URI:        https://module.market
 * Description:       Dropshipping.cz Fulfillment (Fulfillment.cz / Dropshipping.cz integration) for WooCommerce
 * Version:           1.12
 * Author:            Pexxi:solutions [pexxi.eu]
 * Author URI:        https://pexxi.solutions
 * Text Domain:       dropshipping-fulfillment
 * License:           GPLv3
 * Domain Path:       /languages
*/

class DS_Fulfillment {

  public static $instance;

  public static function init() {
    global $ds_fulfillment;

    load_plugin_textdomain('dropshipping-fulfillment', false, plugin_basename(dirname(__FILE__)) . '/../languages');

    $ds_fulfillment = self::$instance = new DS_Fulfillment();
    return $ds_fulfillment;
  }
}

DS_Fulfillment::init();

add_filter('cron_schedules', 'ds_fulfillment_cron_schedules');
function ds_fulfillment_cron_schedules($schedules) {
  $schedules['ds_fulfillment_cron_schedule'] = array(
    'interval' => 120,
    'display' => __('WC Fulfillment CRON', 'dropshipping-fulfillment')
  );

  return $schedules;
}

register_activation_hook(__FILE__, 'ds_fulfillment_activation');
function ds_fulfillment_activation() {
  $args = array($args_1, $args_2);
  if (!wp_next_scheduled('ds_fulfillment_cron')) {
    wp_schedule_event(time() , 'ds_fulfillment_cron_schedule', 'ds_fulfillment_cron');
  }
}

register_deactivation_hook(__FILE__, 'ds_fulfillment_deactivation');
function ds_fulfillment_deactivation() {
  wp_clear_scheduled_hook('ds_fulfillment_cron');
}

add_action('ds_fulfillment_cron', 'ds_fulfillment_cron');

add_action('wp_loaded', 'ds_fulfillment_wp_loaded');
function ds_fulfillment_wp_loaded() {
  require_once (__DIR__ . "/includes/constants.php");
  require_once (__DIR__ . "/includes/functions.php");

  require_once 'includes/class-zasilkovna.php';

  $user = wp_get_current_user();

  if (!is_admin() && (strpos($_SERVER['REQUEST_URI'], 'fulfillment_login') !== FALSE)) {
    wp_redirect('/vws_login');
    die();
  }

  if (!is_admin() && (strpos($_SERVER['REQUEST_URI'], 'fulfillment') !== FALSE)) {
    wp_redirect('/testshop');
    die();
  }

  require_once (__DIR__ . "/lib/apiface/Manager.php");

  if (!is_bool($result = ds_fulfillment_create_product_taxonomy(DS_FULFILLMENT_VARIATION_ATTRIBUTE_NAME))) {
    var_dump($result); die();
  }

  if (!is_bool($result = ds_fulfillment_create_product_taxonomy('Product Brand', false))) {
    var_dump($result); die();
  }

  if (!empty(@$_REQUEST['wc_ff_cron'])) {
    _ds_fulfillment_cron();
    die();
  }
}

function ds_fulfillment_cron() {
  file_put_contents(__DIR__ . '/cron.log', time() . "\n", FILE_APPEND | LOCK_EX);
  //$_REQUEST['wc_ff_test'] = 1;
  _ds_fulfillment_cron();
}

function _ds_fulfillment_cron() {

  //wp_delete_term(23, 'product_cat'); die();

  /*global $wpdb;
  $attachments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE ID>=1712 AND post_type='attachment' AND post_parent = 0");

  foreach($attachments as $att) {
    wp_delete_post($att->ID);
  }

  var_dump(count($attachments)); 

  $drafts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE ID>=1712 AND post_status='auto-draft'");

  foreach($drafts as $drf) {
    wp_delete_post($drf->ID);
  }

  var_dump(count($drafts)); die();*/

  $mgr = \Pexxi\Apiface\Manager::getInstance();
  $dropshipping = $mgr->getProvider('Dropshipping');
  //$dropshipping = $mgr->getProvider('Fulfillment');
 
  $token = get_option('wc_settings_tab_fulfillment_token');

  $shop = array(
    'id' => 0,
    'initialization_time' => get_option('ds_fulfillment_initialization_time', 0)
  );

  $config = array(
    'token' => $token
  );

  if ($token) {
    $dropshipping->apiToken = $token;

    set_time_limit(DS_FULFILLMENT_ORDER_MAX_TIMEOUT);

    if (!empty($_REQUEST['wc_ff_abortable'])) {
      ignore_user_abort(true);
    }

    update_option('ds_fulfillment_shop_' . $shop['id'] . '_order_log', 'Order processing started');

    $dropshipping->apiToken = $config['token'];

    $eshopId = get_option('wc_settings_tab_fulfillment_shop', null);

    if ($eshopId) {

      $dsPayments = get_option('ds_fulfillment_payments', array());
      $dsPaymentsById = $dsPayments;

      $firstPayment = reset($dsPaymentsById);
      if (isset($dsPaymentsById[2])) {
        $firstPayment = $dsPaymentsById[2];
      }

      $dsPaymentsByName = array();
      foreach ($dsPayments as $id => $name) {
        $dsPaymentsByName[$name] = $id;
      }

      $dsDeliveries = get_option('ds_fulfillment_shipments', array());
      $dsDeliveriesId = $dsDeliveries;

      $firstDelivery = reset($dsDeliveriesId);
      if (isset($dsDeliveriesId[12])) {
        $firstDelivery = $dsDeliveriesId[2];
      }

      $dsDeliveriesByName = array();
      foreach ($dsDeliveries as $id => $name) {
        $dsDeliveriesByName[$name] = $id;
      }

      $dsOrders = $dropshipping->executeMethod('Dropshipping.Order', 'getOrders', array(
        'created_from' => new DateTime(DS_FULFILLMENT_ORDER_CREATED_FROM /*'@' . $shop['initialization_time']*/)
      ));

      //var_dump($dsOrders); die();
      $dsOrdersByRemoteId = array();

      if (isset($dsOrders->data)) {
        foreach ($dsOrders->data as $dsOrder) {
          if (!$dsOrder->remote_id) {
            continue;
          }

          $dsOrdersByRemoteId[$dsOrder->remote_id] = $dsOrder;
        }
      }
      else {
        var_dump($dsOrders);
        //die();
      }

      $initial_date = date('Y-m-d', strtotime(DS_FULFILLMENT_ORDER_CREATED_FROM) /*$shop['initialization_time']*/);
      $final_date = date('Y-m-d');

      $sOrders = wc_get_orders(array(
        'limit' => - 1,
        'type' => 'shop_order',
        //'status'=> array( 'wc-pending' ),
        'date_created' => $initial_date . '...' . $final_date
      ));

      //$sOrders = array();
      $sOrderList = $sOrders;

      foreach ($sOrderList as $sOrder) {
        //var_dump($sOrder); die();

        $remote_id = DS_FULFILLMENT_ORDER_PREFIX . $sOrder->id;
        $dsOrder = @$dsOrdersByRemoteId[$remote_id];

        if (!$dsOrder) {
          $remote_id = DS_FULFILLMENT_ORDER_PREFIX . $sOrder->get_order_number();
          $dsOrder = @$dsOrdersByRemoteId[$remote_id];
        }

        //$dsOrder = null;
        //var_dump($remote_id); 
        //var_dump($dsOrder); die();
      
        if ($dsOrder) {
          if ($dsOrder->tracking) {
            update_post_meta($sOrder->id , 'Tracking_ID', $dsOrder->tracking->number);
            update_post_meta($sOrder->id , 'Tracking_Link', $dsOrder->tracking->link);

            update_post_meta($sOrder->id , '_tracking_id', $dsOrder->tracking->number);
            update_post_meta($sOrder->id , '_tracking_link', $dsOrder->tracking->link);

          }
        }
        else {
          $dsOrderProducts = array();
          foreach ($sOrder->get_items() as & $sLineItem) {

            if (!$sLineItem->get_product_id()) {
              // TODO[think]: ?
              continue;
            }

            $sku = get_post_meta($sLineItem['variation_id'] ? $sLineItem['variation_id'] : $sLineItem['product_id'], '_sku', true);

            if (!$sku) {
              continue;
            }

            if (!$sLineItem->get_quantity()) {
              continue;
            }

            //$sProduct = $sLineItem->get_product();

            $dsOrderProducts[] = array(
              'price' => $sLineItem->get_total(),
              'code' => $sku,
              'sku' => $sku,
              'quantity' => $sLineItem->get_quantity()
            );

            $orderNotes = wc_get_order_notes(array(
              'order_id' => $sOrder->get_id() ,
              'type' => 'interal'
            ));

            $hasNote = false;
            $notes = array();
            foreach ($orderNotes as $orderNote) {
              if ($orderNote->content == 'fulfillment') {
                $hasNote = true;
                break;
              }
              else {
                $notes[] = $orderNote->content;
              }
            }

            if (!$hasNote) {
              $sOrder->add_order_note('fulfillment', false, false);
            }
          }
          unset($sLineItem);

          //var_dump($dsOrderProducts); die();

          if (!$dsOrderProducts) {
            continue;
          }

          $dsOrder = $dropshipping->createInstance('Dropshipping.Order');
          unset($dsOrder->id);

          $dsOrder->eshop_id = (int)$eshopId;
          $dsOrder->email = trim($sOrder->get_billing_email());

          if (!$dsOrder->email) {
            //$dsOrder->email = strtolower($remote_id) . '@example.com';
            $dsOrder->email = 'info@dropshipping.cz';
          }

          $billing_address = $sOrder->get_address('billing');
          $shipping_address = $sOrder->get_address('shipping');

          if (@$shipping_address['phone']) {
            $dsOrder->phone = $shipping_address['phone'];
          }

          $dsOrder->remote_id = $remote_id;

          $dsOrder->invoice_firstname = $billing_address['first_name'];
          $dsOrder->invoice_surname = $billing_address['last_name'];
          $dsOrder->invoice_street = trim($billing_address['address_1'] . ' ' . $billing_address['address_2']);
          $dsOrder->invoice_city = $billing_address['city'];
          $dsOrder->invoice_zipcode = $billing_address['postcode'];
          $dsOrder->invoice_company = (string)$billing_address['company'];
          //$dsOrder->invoice_ico =
          //$dsOrder->invoice_dic =
          $dsOrder->contact_firstname = $shipping_address['first_name'];
          $dsOrder->contact_surname = $shipping_address['last_name'];
          $dsOrder->contact_street = trim($shipping_address['address_1'] . ' ' . $billing_address['address_2']);
          $dsOrder->contact_city = $shipping_address['city'];
          $dsOrder->contact_zipcode = $shipping_address['postcode'];
          $dsOrder->contact_company = (string)$shipping_address['company'];
          //$dsOrder->contact_ico =
          //$dsOrder->contact_dic =
          $dsOrder->note = (string)implode("\n", $notes);

          $dsOrder->basket = array();
          $dsOrder->inserts = array();
          foreach ($dsOrderProducts as $idx => $dsOrderProduct) {
            $dsBasket = $dropshipping->createInstance('Dropshipping.Basket');
            $dsInsert = $dropshipping->createInstance('Dropshipping.Insert');

            //$dsInsert->id = $dsBasket->id = $dsOrderProduct['id'];
            unset($dsInsert->id);
            unset($dsBasket->id);
            $dsBasket->price_retail_vat = (float)$dsOrderProduct['price'];
            $dsInsert->code = $dsBasket->code = $dsOrderProduct['code'];
            $dsInsert->quantity = $dsBasket->quantity = $dsOrderProduct['quantity'];

            $dsOrder->basket[] = $dsBasket;
            //$dsOrder->inserts[] = $dsInsert;
            
          }

          $enabled_payments = get_option('wc_settings_tab_fulfillment_payments', array());
          $dsPaymentsById = get_option('ds_fulfillment_payments', array());

          $dsPaymentsByName = array();
          foreach ($dsPaymentsById as $id => $payment) {
            $dsPaymentsByName[$payment->name] = $id;
          }

          $dsOrder->payment_id = (int)reset($enabled_payments);
          $paymentName = $sOrder->get_payment_method();
          $paymentName_split = explode('_', $paymentName);
          if ((count($paymentName_split) == 2) && ($paymentName_split[0] == 'ff') && (isset($dsPaymentsById[$paymentName_split[1]]))) {
            $dsOrder->payment_id = (int)$paymentName_split[1];
          }

          $dsOrder->payment_price_vat = isset($dsPaymentsById[$dsOrder->payment_id]) ? (float)$dsPaymentsById[$dsOrder->payment_id]->price_vat : 0;

          $enabled_shipments = get_option('wc_settings_tab_fulfillment_shipments', array());
          $dsDeliveriesById = get_option('ds_fulfillment_shipments', array());

          $dsDeliveriesByName = array();
          foreach ($dsDeliveriesById as $id => $delivery) {
            $dsDeliveriesByName[$delivery->name] = $id;
          }

          $dsOrder->delivery_id = (int)reset($enabled_shipments);
          $deliveries = $sOrder->get_shipping_methods();
          $delivery = reset($deliveries);
          $deliveryName = $delivery->get_method_id();
          $deliveryName_split = explode('_', $deliveryName);
          if ((count($deliveryName_split) == 2) && ($deliveryName_split[0] == 'ff') && (isset($dsDeliveriesById[$deliveryName_split[1]]))) {
            $dsOrder->delivery_id = (int)$deliveryName_split[1];
          }
          else if ($deliveryName_split[0] == 'zasilkovna') {
            $dsOrder->delivery_id = DS_FULFILLMENT_DELIVERY_ZASILKOVNA_ID;
          }

          $dsOrder->delivery_price_vat = isset($dsDeliveriesById[$dsOrder->delivery_id]) ? (float)$sOrder->get_shipping_total() : 0;

          //$dsOrder->test = "1";

          $branchId = get_post_meta($sOrder->id, 'ds_fulfillment_zasilkovna_branchId', true);
          $branchName = get_post_meta($sOrder->id, 'ds_fulfillment_zasilkovna_branchName', true);

          if ($branchId) {
            $dsOrder->delivery_place_id = (int)$branchId;
          }

          if ($branchName) {
            $dsOrder->delivery_place_ext_id = $branchName;
          }
          
          //var_dump($dsOrder); 
          $dsResult = $dropshipping->executeMethod('Dropshipping.Order', 'updateOrder', array(
            $dsOrder
          ));

          //var_dump($dsResult);
          //die();

          if (empty($dsResult->id) || ($dsResult->code >= 400)) {
            var_dump($dsResult);
            var_dump($dsOrder);
          }
        }
      }

      update_option('ds_fulfillment_shop_' . $shop['id'] . '_order_log', 'Order processing finished');
    }

    //die();

    set_time_limit(DS_FULFILLMENT_IMPORT_MAX_TIMEOUT);
    if (!empty(@$_REQUEST['wc_ff_abortable'])) {
      ignore_user_abort(true);
    }

    $shop['import_status'] = get_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', NULL);
    $shop['import_start'] = get_option('ds_fulfillment_shop_' . $shop['id'] . '_import_start', 0);
    //$shop['import_start'] = 0;

    //var_dump($shop); die();

    if ($shop['import_status'] < DS_FULFILLMENT_IMPORT_STATUS_FINISHED) {
      if (empty($_REQUEST['wc_ff_force']) && ($shop['import_start'] + DS_FULFILLMENT_IMPORT_MAX_TIMEOUT) > time()) {
        trigger_error("Already running: {$shop['import_start']}");
        return;
      }
    }
    else if ($shop['import_status'] < DS_FULFILLMENT_IMPORT_STATUS_STOPPED) {
      if (empty($_REQUEST['wc_ff_force']) && ($shop['import_start'] + DS_FULFILLMENT_IMPORT_MAX_TIMEOUT) > time()) {
        trigger_error("Recently run: {$shop['import_start']}");
        return;
      }
    }

    update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_start', time());
    update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_RUNNING);
    update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', 'Update started');

    $config['feed'] = get_option('wc_settings_tab_fulfillment_feed', null);
    if ($config['feed']) {

      try {
        $dsProducts = $dropshipping->executeMethod('Dropshipping.Product', 'getProducts', array(
          'url' => $config['feed']
        ));
      }
      catch (Exception $e) {
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_ERROR);
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', 'Feed unavailable');
        return;
      }

      //var_dump($dsProducts); die();
      $args = array(
        'post_type' => 'product',
        'posts_per_page' => - 1,
      );

      $loop = new WP_Query($args);

      $allSProductsById = array();

      while ($loop->have_posts()) {
        global $post;

        $loop->the_post();
        $allSProduct = new WC_Product_Variable($post->ID);
        $allSProductsById[$allSProduct->id] = $allSProduct->id;
      }
      unset($post);

      $allSVariantsByProductId = array();
      $allSVariantsBySku = array();

      $sProducts = array();
      $sProductsById = array();

      //$break_id = '337171';
      $break_id = null;
      $break_idx = 987;

      $progress = 0;
      $total = $dsTotal = count($dsProducts->SHOP->SHOPITEM);
      //var_dump($total);die();

      if (!$total) {
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_ERROR);
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', 'Feed empty');
      }

      update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_RUNNING);
      update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', 'Data collection started');

      foreach ($dsProducts->SHOP->SHOPITEM as $idx => $dsProduct) {

        //$product_id = $dsProduct->PRODUCT_ID[0]->{'@'};
        if (isset($dsProduct->ITEMGROUP_ID[0]->{'@'})) {
          $product_id = $dsProduct->ITEMGROUP_ID[0]->{'@'};
        }
        else {
          $product_id = $dsProduct->ITEM_ID[0]->{'@'};
        }

        $handle = DS_FULFILLMENT_HANDLE_PREFIX . $product_id;

        if (isset($break_id)) {
          if ($product_id == $break_id) {
            if (!empty($_REQUEST['wc_ff_test'])) {
              break;
            }
          }
        }

        if (isset($break_idx)) {
          if ($idx >= $break_idx) {
            if (!empty($_REQUEST['wc_ff_test'])) {
              break;
            }
          }
        }

        unset($sProduct);
        if (isset($sProductsById[$product_id])) {
          $sProduct = & $sProductsById[$product_id];
        }
        else {
          $found = false;

          $args = array(
            'post_type' => array(
              'product'
            ) ,
            'meta_query' => array(
              array(
                'key' => 'ds_fulfillment_handle',
                'value' => $handle,
              )
            ) ,
          );

          $query = new WP_Query($args);
          $products = $query->get_posts();
          /*var_dump($products); die();*/
          $product = reset($products);

          if ($product) {

            $foundSProduct = new WC_Product_Variable($product->ID);

            if ($foundSProduct) {
              $found = true;
              unset($allSProductsById[$foundSProduct->id]);
              if (!isset($allSVariantsByProductId[$foundSProduct->id])) {
                $allSVariantsByProductId[$foundSProduct->id] = array();
              }

              foreach ($foundSProduct->get_children() as $sVariantId) {
                $sVariant = new WC_Product_Variation($sVariantId);
                if ($sVariant) {
                  continue;
                }

                $allSVariantsByProductId[$foundSProduct->id][$sVariant->get_sku() ] = & $sVariant;
              }
              unset($sVariant);

              $sProducts[$product_id] = $foundSProduct;
            }
          }
          else {
            $foundSProduct = null;
          }

          if (!$found) {
            $sProducts[$product_id] = new WC_Product_Variable();
          }

          $sProduct = & $sProducts[$product_id];

          $sProductsById[$product_id] = & $sProduct;

          $sProduct->variants = array();
        }

        if (!$config['preserveDescription'] || !$sProduct->get_name()) {
          $sProduct->set_name($dsProduct->PRODUCT_NAME[0]->{'@'});
        }

        if (!$config['preserveDescription'] || !$sProduct->get_description()) {
          $sProduct->set_description((string)@$dsProduct->DESCRIPTION_HTML[0]->{'@'});
          if (!$sProduct->get_description()) {
            // TODO[feature]: default body?
          }
        }

        $vendor = (string)@$dsProduct->MANUFACTURER[0]->{'@'};
        if ($vendor) {
          // TODO[feature]: create taxonomy
          $sProduct->vendor = $vendor;
          if (!term_exists($sProduct->vendor , 'product-brand')) {
            wp_insert_term($sProduct->vendor , 'product-brand');
          }
        }
        else {
          // TODO[feature]: default vendor?
        }

        $sProduct->handle = $handle;
        if (!isset($Product->quantity)) {
          $sProduct->quantity = 0;
        }

        $cats = array();
        if (!empty($dsProduct->CATEGORIES[0]->CATEGORY)) {
          foreach ($dsProduct->CATEGORIES[0]->CATEGORY as $dsCategory) {
            $cats[] = $dsCategory->{'@'};
          }
        }

        $sProduct->cats = $cats;
        $sProduct->tag_slugs = array();
        $sProduct->cat_slugs = array();

        foreach($sProduct->cats as $cat) {
          if (!term_exists($cat, 'product_tag')) {
            wp_insert_term($cat, 'product_tag');
          }

          if (!term_exists($cat, 'product_cat')) {
            wp_insert_term($cat, 'product_cat');
          }

          $cat_term = get_term_by('name', $cat , 'product_cat');
          $sProduct->tag_slugs[] = get_term_by('name', $cat , 'product_tag')->slug;
          $sProduct->cat_slugs[$cat_term->term_id] = $cat_term->slug;
        }

        $sProduct->set_category_ids(array_keys($sProduct->cat_slugs));

        $images = array();
        $images[] = $dsProduct->IMGURL[0];
        $images = array_merge($images, $dsProduct->IMAGES[0]->IMGURL);
        $sProduct->images = array_unique($images, SORT_REGULAR);

        $sProduct->set_stock_quantity(0);
        $sProduct->set_manage_stock(true);
        $sProduct->set_stock_status('instock');

        $sProduct->attributes = array();
        $sProduct->attributes[DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY]['is_visible'] = 1;
        $sProduct->attributes[DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY]['is_taxonomy'] = 1;
        $sProduct->attributes[DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY]['is_variation'] = 1;
        $sProduct->attributes[DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY]['name'] = DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY;
        $sProduct->product_attributes[DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY]['value'] = '';

        //$sProduct->validate_props();
        unset($foundSVariant);
        foreach ($sProduct->get_children() as $sVariantId) {
          //var_dump($sVariantId);
          $sVariant = new WC_Product_Variation($sVariantId);
          if ($sVariant->get_sku() == $dsProduct->CODE[0]->{'@'}) {
            $foundSVariant = & $sVariant;
          }
        }
        unset($sVariant);

        if (isset($foundSVariant)) {
          $sVariant = & $foundSVariant;
        }
        else {
          $sVariant = new WC_Product_Variation();
        }

        try {
          $sVariant->set_sku($dsProduct->CODE[0]->{'@'});
        }
        catch (Exception $e) {
          trigger_error($dsProduct->CODE[0]->{'@'});
          trigger_error($e->getMessage());
          // no code
        }

        $sProduct->quantity += (int)@$dsProduct->WAREHOUSE_QUANTITY[0]->{'@'};
        $sProduct->set_stock_quantity($sProduct->quantity);

        $sVariant->set_stock_quantity((int)@$dsProduct->WAREHOUSE_QUANTITY[0]->{'@'});
        $sVariant->set_manage_stock(true);
        $sVariant->set_stock_status((int)@$dsProduct->WAREHOUSE_QUANTITY[0]->{'@'} ? 'instock' : 'outofstock');

        $sVariant->ean = (string)@$dsProduct->EAN[0]->{'@'};

        if (!$config['preserveDescription'] || !$sVariant->get_name()) {
          $sVariant->set_name(empty($dsProduct->VARIANT_NAME[0]->{'@'}) ? $dsProduct->PRODUCT_NAME[0]->{'@'} : $dsProduct->VARIANT_NAME[0]->{'@'});

          if ($sVariant->get_name()) {
            if (!term_exists($sVariant->get_name() , DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY)) {
              wp_insert_term($sVariant->get_name() , DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY);
            }
          }
        }

        $attr_slug = get_term_by('name', $sVariant->get_name() , DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY)->slug;
        $sVariant->attr_slug = $attr_slug;

        if (!$config['preservePrice'] || !$sVariant->get_price('edit')) {
          //$sVariant->price = $dsProduct->PRICE[0]->{'@'};
          //$sVariant->set_sale_price();
          $sVariant->set_price($price = round((float)$dsProduct->PRICE[0]->{'@'} * (1 + (float)$dsProduct->VAT[0]->{'@'} / 100) , 1)); // TODO[warn]: rounded to 1 decimal

          $sVariant->set_regular_price($price);
        }

        $sVariant->wholesale = $dsProduct->PRICE_WHOLESALE[0]->{'@'};

        $vat = $dsProduct->VAT[0]->{'@'};
        if ($vat) {
          $sProduct->set_tax_status('taxable');
          $sVariant->set_tax_status('taxable');

          $sProduct->set_tax_class($vat);
          $sVariant->set_tax_class($vat);
        }
        else {
          $sProduct->set_tax_status('none');
          $sVariant->set_tax_status('none');

          $sVariant->set_tax_class('');
          $sVariant->set_tax_class('');
        }

        $allSVariantsBySku[$sVariant->get_sku() ] = & $sVariant;
        unset($allSVariantsByProductId[$sProduct->id][$sVariant->get_sku() ]);

        $sProduct->variants[$sVariant->get_variation_id()] = & $sVariant;

        unset($sVariant);

        $percent = round(($idx + 1) / $total * 100);
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_RUNNING);
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', "Data collection running [$percent%]");

        //break;
      }

      unset($sProduct);

      $total = count($allSProductsById);
        
      if (!$config['preserveProducts']) {
        $idx = 0;

        foreach ($allSProductsById as $allSProduct) {
          $idx++;

          $allSProduct = new WC_Product_Variable($allSProduct);

          foreach ($allSProduct->get_children() as $child_id) {
            $child = new WC_Product_Variation($child_id);
            if (!empty($child->get_variation_id())) {
              $child->delete();
            }
          }

          $allSProduct->delete();

          $percent = round($idx / $total * 100);
          update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_RUNNING);
          update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', "Deletion running [$percent%]");
        }
      }

      //var_dump($sProducts); die();
      $total = $dsTotal;

      foreach ($sProducts as $sProduct) {

        $sProduct->variants = array_filter($sProduct->variants);

        if ($sProduct->id) {
          foreach ($allSVariantsByProductId[$sProduct->id] as & $sVariant) {
            $sVariant->delete();
            $sVariant = NULL;
          }
          unset($sVariant);

          $sResult = $sProduct->save();

          if (empty($sResult)) {
            /*var_dump('update product: ' . $sProduct->get_sku());
            var_dump($sResult);
            var_dump($sProduct);*/
          }
        }
        else {
          $sResult = $sProduct->save();

          if (empty($sResult)) {
            /*var_dump('add product: ' . $sProduct->get_sku());
            var_dump($sResult);
            var_dump($sProduct);*/
          }
        }

        $sProduct->set_status("publish");
        $sProduct->set_catalog_visibility('visible');

        update_post_meta($sProduct->id, DS_FULFILLMENT_VARIATION_ATTRIBUTE, '');

        update_post_meta($sProduct->id, '_product_attributes', $sProduct->attributes);
        update_post_meta($sProduct->id, 'ds_fulfillment_handle', $sProduct->handle);

        $image_ids = array();
        foreach ($sProduct->images as $key => $imgUrl) {
          $media = ds_fulfillment_get_or_add_media($imgUrl->{'@'}, $sProduct->id, false);
          if ($key) {
            $image_ids[] = $media['ID'];
          }
          else {
            $sProduct->thumb = $media['ID'];
          }
        }

        $sProduct->image_ids = $image_ids;

        set_post_thumbnail($sProduct->id, $sProduct->thumb);
        update_post_meta($sProduct->id, '_product_image_gallery', implode(',', $sProduct->image_ids));

        wp_set_object_terms($sProduct->id, array(
          $sProduct->vendor
        ) , 'product-brand');

        wp_set_object_terms($sProduct->id, $sProduct->tag_slugs, 'product_tag');
        wp_set_object_terms($sProduct->id, $sProduct->cat_slugs, 'product_cat');

        $var_terms = array();

        if (!empty($sProduct->variants)) {
          //var_dump(array_keys($sProduct->variants));

          foreach ($sProduct->variants as & $sVariant) {
            /*var_dump($sProduct->id);
            var_dump($sVariant->get_variation_id()); die();*/

            $sVariant->set_parent_id($sProduct->id);

            $var_terms[] = get_term_by('name', $sVariant->get_name(), DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY)->slug;

            if ($sVariant->get_variation_id()) {

              $sResult = $sVariant->save();

              if (empty($sResult)) {
                /*var_dump('update variant: ' . $sVariant->get_sku());
                var_dump($sResult);
                var_dump($sVariant);*/
              }
            }
            else {
              $sResult = $sVariant->save();

              if (empty($sResult)) {
                /*var_dump('add variant: ' . $sVariant->get_sku());
                var_dump($sResult);
                var_dump($sVariant);*/
              }
            }

            update_post_meta($sVariant->get_variation_id(), '_barcode', $sVariant->ean);
            update_post_meta($sVariant->get_variation_id(), '_wholesale', $sVariant->wholesale);

            update_post_meta($sVariant->get_variation_id(), DS_FULFILLMENT_VARIATION_ATTRIBUTE, $sVariant->attr_slug);
            update_post_meta($sVariant->get_variation_id(), 'variation_image_gallery', '');
          }
  
          unset($sVariant);

          wp_set_object_terms($sProduct->id, $var_terms, DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY);

          if (count($sProduct->variants) == 1) {
            $default_attrs = array();
            $default_attrs[DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY] = reset($var_terms);
            update_post_meta($sProduct->id, '_default_attributes', $default_attrs);
          }

          $progress += count($sProduct->variants);

          delete_transient('wc_product_children_' . $sProduct->id);
          wc_delete_product_transients($sProduct->id);
        }

        $percent = round($progress / $total * 100);
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_RUNNING);
        update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', "Update running [$percent%]");
      }
    }

    wc_delete_product_transients();
    update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_FINISHED);
    update_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', 'Update finished');

  }
}

add_action("wp_ajax_fulfillment_status", "ds_fulfillment_ajax_status");
//add_action("wp_ajax_nopriv_my_user_like", "ds_fulfillment_ajax_status");
function ds_fulfillment_ajax_status() {
  $shop = array(
    'id' => 0
  );

  $import_start = get_option('ds_fulfillment_shop_' . $shop['id'] . '_import_start', time());
  $import_status = get_option('ds_fulfillment_shop_' . $shop['id'] . '_import_status', DS_FULFILLMENT_IMPORT_STATUS_RUNNING);
  $import_message = get_option('ds_fulfillment_shop_' . $shop['id'] . '_import_message', 'Update running');

  $class = null;
  switch (true) {
    case ($shop['import_status'] < 100):
      $class = 'warn';
    break;

    case ($shop['import_status'] < 1000):
      $class = 'success';
    break;

    case ($shop['import_status'] < 10000):
      $class = 'error';
    break;

    default:
      $class = 'default';
  }

  $status = array(
    'import_start' => $import_start,
    'import_status' => $import_status,
    'import_message' => $import_message,
    'import_class' => $class,
    'order_log' => $order_log,
    'order_state_log' => $order_state_log
  );

  $status['ok'] = true;
  wp_send_json($status);
}

add_action('woocommerce_order_status_pending', 'ds_fulfillment_order_status');
add_action('woocommerce_order_status_failed', 'ds_fulfillment_order_status');
add_action('woocommerce_order_status_on-hold', 'ds_fulfillment_order_status');
add_action('woocommerce_order_status_processing', 'ds_fulfillment_order_status');
add_action('woocommerce_order_status_completed', 'ds_fulfillment_order_status');
add_action('woocommerce_order_status_refunded', 'ds_fulfillment_order_status');
add_action('woocommerce_order_status_cancelled', 'ds_fulfillment_order_status');

function ds_fulfillment_order_status($order_id) {
  $order = new WC_Order($order_id);
  //error_log($order_id);
  //var_dump($order); die();
}

add_action('wp_enqueue_scripts', 'ds_fulfillment_enqueue_scripts');
add_action('admin_enqueue_scripts', 'ds_fulfillment_admin_enqueue_scripts');

function ds_fulfillment_enqueue_scripts() {
  wp_enqueue_script('ds_fulfillment', plugins_url('js/script.js', __FILE__));

  $settings = array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'packetaUrl' => plugins_url('js/packetaWidget.js', __FILE__)
  );

  wp_add_inline_script('ds_fulfillment', 'window.DS_Fulfillment = ' . json_encode($settings) . '; ', 'before');
  wp_enqueue_style('ds_fulfillment', plugins_url('css/style.css', __FILE__));
}

function ds_fulfillment_admin_enqueue_scripts() {
  wp_enqueue_script('ds_fulfillment', plugins_url('js/admin.js', __FILE__));

  $settings = array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'packetaUrl' => plugins_url('js/packetaWidget.js', __FILE__)
  );

  wp_add_inline_script('ds_fulfillment', 'window.DS_Fulfillment = ' . json_encode($settings) . '; ', 'before');
  wp_enqueue_style('ds_fulfillment', plugins_url('css/admin.css', __FILE__));
}

add_filter('woocommerce_settings_tabs_array', 'ds_fulfillment_add_settings_tab', 50);

function ds_fulfillment_add_settings_tab($settings_tabs) {
  $settings_tabs['settings_tab_fulfillment'] = __('Fulfillment', 'dropshipping-fulfillment');
  return $settings_tabs;
}

add_action('woocommerce_settings_tabs_settings_tab_fulfillment', 'ds_fulfillment_settings_tab');
add_action('woocommerce_update_options_settings_tab_fulfillment', 'ds_fulfillment_update_options');

function ds_fulfillment_settings_tab() {
  woocommerce_admin_fields(ds_fulfillment_get_settings());
}

function ds_fulfillment_update_options() {
  woocommerce_update_options(ds_fulfillment_get_settings());
}

function ds_fulfillment_get_settings() {

  $mgr = \Pexxi\Apiface\Manager::getInstance();
  $dropshipping = $mgr->getProvider('Dropshipping');
  //$dropshipping = $mgr->getProvider('Fulfillment');

  $shipments = array();
  $payments = array();
  $shops = array();

  $token = get_option('wc_settings_tab_fulfillment_token');
  if ($token) {
    $dropshipping->apiToken = $token;

    $eshops = $dropshipping->executeMethod('Dropshipping.Eshop', 'getEshops', array());
    foreach ($eshops->data as $eshop) {
      $shops[$eshop->id] = $eshop->name . ' (' . $eshop->id . ')';
    }

    $eshopId = (int)get_option('wc_settings_tab_fulfillment_shop');
    if ($eshopId) {
      $dsPayments = $dropshipping->executeMethod('Dropshipping.Payment', 'getPayments', array(
        'eshop_id' => $eshopId
      ));

      $dsPaymentsById = array();
      foreach ($dsPayments->data as $dsPayment) {
        $dsPaymentsById[$dsPayment->id] = $dsPayment;
      }

      $dsDeliveries = $dropshipping->executeMethod('Dropshipping.Delivery', 'getDeliveries', array(
        'eshop_id' => $eshopId
      ));

      $dsDeliveriesById = array();
      foreach ($dsDeliveries->data as $dsDelivery) {
        $dsDeliveriesById[$dsDelivery->id] = $dsDelivery;
      }

      $payments = $dsPaymentsById;
      $shipments = $dsDeliveriesById;

      //ksort($payments);
      //ksort($shipments);
      
    }
  }

  update_option('ds_fulfillment_shipments', $shipments);
  update_option('ds_fulfillment_payments', $payments);

  $shipmentNames = array();
  foreach ($shipments as $id => $shipment) {
    $shipmentNames[$id] = $shipment->name;
  }

  $paymentNames = array();
  foreach ($payments as $id => $payment) {
    $paymentNames[$id] = $payment->name;
  }

  $init_time = get_option('ds_fulfillment_initialization_time', 0);
  if (!$init_time) {
    update_option('ds_fulfillment_initialization_time', time());
  }

  $settings = array(
    'fulfillment_config_title' => array(
      'name' => __('Fulfillment: API Integration', 'dropshipping-fulfillment') ,
      'type' => 'title',
      'desc' => __('Allows you to configure API settings and other options<br/><br/><i>Products, payment and delivery methods will be automatically updated after saving your settings.</i>', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_config_title'
    ) ,
    'fulfillment_token' => array(
      'name' => __('Token', 'dropshipping-fulfillment') ,
      'type' => 'text',
      'desc' => __('API token', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_token'
    ) ,
    'fulfillment_shop' => array(
      'name' => __('Shop ID', 'dropshipping-fulfillment') ,
      'type' => 'select',
      'desc' => __('Shop ID', 'dropshipping-fulfillment') ,
      'options' => $shops,
      'id' => 'wc_settings_tab_fulfillment_shop'
    ) ,
    'fulfillment_feed' => array(
      'name' => __('XML feed URL', 'dropshipping-fulfillment') ,
      'type' => 'text',
      'desc' => __('https://.../xml/...', 'dropshipping-fulfillment') ,
      'options' => $shops,
      'id' => 'wc_settings_tab_fulfillment_feed'
    ) ,
    'fulfillment_payments' => array(
      'name' => __('Payment methods', 'dropshipping-fulfillment') ,
      'type' => 'multiselect',
      'desc' => __('Allowed payment methods', 'dropshipping-fulfillment') ,
      'options' => $paymentNames,
      'id' => 'wc_settings_tab_fulfillment_payments'
    ) ,
    'fulfillment_shipments' => array(
      'name' => __('Shipping methods', 'dropshipping-fulfillment') ,
      'type' => 'multiselect',
      'desc' => __('Allowed shipping methods', 'dropshipping-fulfillment') ,
      'options' => $shipmentNames,
      'id' => 'wc_settings_tab_fulfillment_shipments'
    ) ,
    'fulfillment_createProducts' => array(
      'name' => __('Create products', 'dropshipping-fulfillment') ,
      'type' => 'checkbox',
      'desc' => __('Create products, if they don\'t exist in feed', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_createProducts'
    ) ,
    'fulfillment_preservePrice' => array(
      'name' => __('Preserve price', 'dropshipping-fulfillment') ,
      'type' => 'checkbox',
      'desc' => __('Preserve (don\'t update) price of existing products', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_preservePrice'
    ) ,
    'preserveDescription' => array(
      'name' => __('Preserve title & description', 'dropshipping-fulfillment') ,
      'type' => 'checkbox',
      'desc' => __('Preserve (don\'t update) title & description of existing products', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_preserveDescription'
    ) ,
    'fulfillment_preserveProducts' => array(
      'name' => __('Preserve unavailable products', 'dropshipping-fulfillment') ,
      'type' => 'checkbox',
      'desc' => __('Preserve (don\'t delete) products unavailable in feed', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_preserveProducts'
    ) ,
    'fulfillment_config_end' => array(
      'type' => 'sectionend',
      'id' => 'wc_settings_tab_fulfillment_config_end'
    ) ,
    'fulfillment_status_title' => array(
      'name' => __('Fulfillment: Status', 'dropshipping-fulfillment') ,
      'type' => 'title',
      'desc' => __('<div id="fulfillment_status"></div>', 'dropshipping-fulfillment') ,
      'id' => 'wc_settings_tab_fulfillment_status_title'
    ) ,
    'fulfillment_status_end' => array(
      'type' => 'sectionend',
      'id' => 'wc_settings_tab_fulfillment_status_end'
    )
  );

  return apply_filters('wc_settings_tab_fulfillment_settings', $settings);
}

add_action('plugins_loaded', 'ds_fulfillment_payment_init');

function ds_fulfillment_payment_init() {
  require_once 'includes/class-payment.php';
  add_filter('woocommerce_payment_gateways', 'ds_fulfillment_payment_methods');
}

function ds_fulfillment_payment_methods($gateways) {
  $enabled_payments = get_option('wc_settings_tab_fulfillment_payments', array());
  $payments = get_option('ds_fulfillment_payments', array());

  $file = file_get_contents(__DIR__ . '/includes/class-payment.php');
  $file = str_replace('<?php', '', $file);

  foreach ($enabled_payments as $id) {
    $name = @$payments[$id]->name;
    if (!$name) {
      continue;
    }

    $price = (int)$payments[$id]->price_vat;
    $id = 'ff_' . $id;

    $eval = str_replace('__ID__', $id, $file);
    $eval = str_replace('__NAME__', $name, $eval);
    $eval = str_replace('__PRICE__', $price, $eval);

    ds_fulfillment_execute($eval);

    $class = 'DS_Fulfillment_Payment_Method_' . $id;
    $gateways[] = $class;
  }

  //var_dump($gateways); die();
  return $gateways;
}

add_filter('woocommerce_shipping_methods', 'ds_fulfillment_shipping_methods');
function ds_fulfillment_shipping_methods($methods) {
  $enabled_shippings = get_option('wc_settings_tab_fulfillment_shipments', array());
  $shipments = get_option('ds_fulfillment_shipments', array());

  $file = file_get_contents(__DIR__ . '/includes/class-shipping.php');
  $file = str_replace('<?php', '', $file);

  foreach ($enabled_shippings as $id) {
    $name = @$shipments[$id]->name;
    if (!$name) {
      continue;
    }

    $price = (int)$shipments[$id]->price_vat;
    $id = 'ff_' . $id;

    $eval = str_replace('__ID__', $id, $file);
    $eval = str_replace('__NAME__', $name, $eval);
    $eval = str_replace('__PRICE__', $price, $eval);

    ds_fulfillment_execute($eval);

    $class = 'DS_Fulfillment_Shipping_Method_' . $id;
    $methods[$id] = $class;
  }

  $methods['zasilkovna'] = 'DS_Zasilkovna_Shipping_Method';

  return $methods;
}

add_action('woocommerce_shipping_init', 'ds_fulfillment_shipping_method_init');
function ds_fulfillment_shipping_method_init() {
  //require_once 'includes/class-zasilkovna.php';
}

add_filter('sidebars_widgets', 'ds_fulfillment_sidebar_widgets');
function ds_fulfillment_sidebar_widgets($widgets) {
  //return array();
  return $widgets;
}
