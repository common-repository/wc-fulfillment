<?php
if (!defined('ABSPATH')) {
  exit;
}

add_action('woocommerce_after_shipping_rate', array('DS_Zasilkovna_Shipping_Method', 'after_shipping_rate'), 20, 2);

define("DS_ZASILKOVNA_TTL", 24 * 60 * 60);

class DS_Zasilkovna_Pickup_Points_By_Country extends FilterIterator {
  private $country = '';
  private $pickup_points = array();

  public function __construct($iterator, $shipping_country) {
    switch ($shipping_country) {
      case 'CZ':
      case 'cz':
      case 'CZE':
        $this->country = 'cz';
      break;
      case 'sk':
      case 'SK':
      case 'SVK':
        $this->country = 'sk';
      break;
      default:
        throw new Exception(__METHOD__ . ": invalid country " . $shipping_country);
    }
    parent::__construct($iterator);
  }

  public function accept() {
    $current = $this->getInnerIterator()->current();
    if ($current->country === $this->country) {
      return true;
    }
    return false;

  }
}

$file = file_get_contents(__DIR__ . '/class-shipping.php');
$file = str_replace('<?php', '', $file);
$eval = str_replace('__ID__', 'zasilkovna', $file);
$eval = str_replace('__NAME__', 'Zasilkovna', $eval);
$eval = str_replace('__PRICE__', 0, $eval);
ds_fulfillment_execute($eval);

class DS_Zasilkovna_Shipping_Method extends DS_Fulfillment_Shipping_Method_zasilkovna {
  private $pickup_points = array();

  /**
   * Constructor. The instance ID is passed to this.
   */

  public function __construct($instance_id = 0) {
    parent::__construct($instance_id);

    $this->id = 'zasilkovna';
    $this->method_title = __('Zásilkovna', 'dropshipping-fulfillment');
    $this->method_description = __('Zásilkovna shipping method.', 'dropshipping-fulfillment');
  }

  public function init_form_fields() {

    parent::init_form_fields();

    $this->instance_form_fields += array(
      'api_key' => array(
        'title' => __('API key', 'dropshipping-fulfillment') ,
        'type' => 'text',
        'description' => __('Get your API key from Zásilkovna account settings.', 'dropshipping-fulfillment') ,
        'default' => '',
        'css' => 'width: 100px;'
      ) ,
      'logo' => array(
        'title' => 'Logo',
        'type' => 'text',
        'description' => __('Default Zásilkovna logo') ,
        'default' => '//www.zasilkovna.cz/images/page/Zasilkovna_logo_inverzni_WEB.png',
        'css' => 'width: 500px;'
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
?><?php if (!function_exists('curl_version')): ?>
        <div class="error">
          <p><?php _e('CURL module not found, fetching pickup points might fail.', 'dropshipping-fulfillment'); ?></p>
        </div>
<?php
    endif; ?><?php if ($this->enabled && !$this->get_option('api_key')): ?>
        <div class="error">
          <p><?php _e('Zásilkovna is enabled, but the API key has not been set.', 'dropshipping-fulfillment'); ?></p>
        </div>
<?php
    endif; ?><?php if ($this->debug_mode == 'yes'): ?>
        <div class="updated woocommerce-message">
          <p><?php _e('Zásilkovna debug mode is activated, only administrators can use it.', 'dropshipping-fulfillment'); ?></p>
        </div>
<?php
    endif; ?><?php
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

  public function pickup_point($pickup_point_id) {
    if (sizeof($this->pickup_points) == 0) {
      $this->load_pickup_points();
    }
    if (array_key_exists($pickup_point_id, $this->pickup_points)) {
      return $this->pickup_points[$pickup_point_id];
    }
    return null;
  }

  public function pickup_points($country) {
    if (sizeof($this->pickup_points) == 0) {
      $this->load_pickup_points();
    }

    $iterator = new ArrayIterator($this->pickup_points);

    return new DS_Zasilkovna_Pickup_Points_By_Country($iterator, $country);
  }

  function load_pickup_points() {
    $api_key = $this->get_option('api_key');
    if ($api_key) {
      $transient_name = 'woocommerce_zasilkovna_pickup_points_' . $api_key;
      $pickup_points = get_transient($transient_name);

      if (empty($pickup_points)) {
        $pickup_points = array();
        $json = $this->fetch_pickup_points($api_key);
        foreach ($json as $id => $pickup_point) {
          $id = intval($id);
          $pickup_points[$id] = $pickup_point;
        }
        set_transient($transient_name, $pickup_points, DS_ZASILKOVNA_TTL);
      }
      $this->pickup_points = $pickup_points;
    }
  }

  function fetch_pickup_points($api_key) {
    $url = 'https://www.zasilkovna.cz/api/v4/' . $api_key . '/branch.json';
    $result = wp_remote_get($url);
    if (is_wp_error($result)) {
      throw new Exception(__METHOD__ . ": failed to get content from {$url}.");
    }
    $code = $result['response']['code'];
    if ($code != 200) {
      throw new Exception(__METHOD__ . ": invalid response code from {$url}: ${code}.");
    }
    $response_body = wp_remote_retrieve_body($result);
    if ($response_body == '') {
      throw new Exception(__METHOD__ . ": empty response body.");
    }
    $json = json_decode($response_body);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception(__METHOD__ . ": JSON decode error: " . json_last_error());
    }
    if (empty($json->data) <= 0) {
      throw new Exception(__METHOD__ . ": JSON data empty.");
    }

    return $json->data;
  }

  public function after_shipping_rate($method) {
    if (strpos($method->get_id(), 'zasilkovna' . ':') === 0) {
      $branchId = (int)(WC()->session->get('ds_fulfillment_zasilkovna_branchId', 0));
      $branchName = WC()->session->get('ds_fulfillment_zasilkovna_branchName', '');

      echo '<div style="display: none" class="packeta-selector-branch-id">' . ($branchId ? $branchId : '') . '</div><a class="packeta-selector-open">' . __('Select branch') . '</a><div style="display: block" class="packeta-selector-branch-name">' . $branchName . '</div>';
    }
  }
}

add_action('woocommerce_thankyou', 'ds_fulfillment_zasilkovna_thankyou', 10, 1);
function ds_fulfillment_zasilkovna_thankyou($order_id) {
  if ($branchId = (int)(WC()->session->get('ds_fulfillment_zasilkovna_branchId', 0))) {
    $branchName = WC()->session->get('ds_fulfillment_zasilkovna_branchName', '');

    update_post_meta($order_id, 'ds_fulfillment_zasilkovna_branchId', $branchId);
    update_post_meta($order_id, 'ds_fulfillment_zasilkovna_branchName', $branchName);

    WC()->session->set('ds_fulfillment_zasilkovna_branchId', '');
    WC()->session->set('ds_fulfillment_zasilkovna_branchName', '');
  }
}

add_action("wp_ajax_fulfillment_zasilkovna_branch", "ds_fulfillment_zasilkovna_branch");
add_action("wp_ajax_nopriv_fulfillment_zasilkovna_branch", "ds_fulfillment_zasilkovna_branch");
function ds_fulfillment_zasilkovna_branch() {
  if (empty(($branchId = (int)trim($_REQUEST['branchId'])))) {
    die(json_encode(array('error' => 'branchId')));
  }

  if (empty(($branchName = trim($_REQUEST['branchName'])))) {
    die(json_encode(array('error' => 'branchName')));
  }

  WC()->session->set('ds_fulfillment_zasilkovna_branchId', $branchId);
  WC()->session->set('ds_fulfillment_zasilkovna_branchName', $branchName);

  die(json_encode(array('ok' => true)));
}