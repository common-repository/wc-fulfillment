<?php

define('PEXXI_APIFACE_PROVIDER_DROPSHIPPING_ENDPOINT', 'https://client.api.dropshipping.cz/v1');
define('PEXXI_APIFACE_PROVIDER_DROPSHIPPING_CONFIG', json_encode(array(
  'authenticate' => array()
)));

define('PEXXI_APIFACE_PROVIDER_FULFILLMENT_ENDPOINT', 'https://client.api.dropshipping.cz/v1/fulfillment');
define('PEXXI_APIFACE_PROVIDER_FULFILLMENT_CONFIG', json_encode(array(
  'authenticate' => array()
)));

$wc_ff_upload_dir = wp_upload_dir();
define('DS_FULFILLMENT_UPLOAD_DIR', $wc_ff_upload_dir['basedir']);
define('DS_FULFILLMENT_UPLOAD_BKP_DIR', $wc_ff_upload_dir['basedir'] . '.bkp');
define('DS_FULFILLMENT_UPLOAD_URL', $wc_ff_upload_dir['baseurl']);
define('DS_FULFILLMENT_UPLOAD_CUR_DIR', $wc_ff_upload_dir['path']);
define('DS_FULFILLMENT_UPLOAD_CUR_URL', $wc_ff_upload_dir['url']);

define('DS_FULFILLMENT_VARIATION_ATTRIBUTE_NAME', 'Variant');
define('DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY', wc_attribute_taxonomy_name(DS_FULFILLMENT_VARIATION_ATTRIBUTE_NAME));
define('DS_FULFILLMENT_VARIATION_ATTRIBUTE', 'attribute_' . DS_FULFILLMENT_VARIATION_ATTRIBUTE_TAXONOMY);

define('DS_FULFILLMENT_IMPORT_MAX_TIMEOUT', 8 * 3600);
define('DS_FULFILLMENT_ORDER_MAX_TIMEOUT', 120);

define('DS_FULFILLMENT_ORDER_CREATED_FROM', date('Y-m-d H:i:s', strtotime('-3 day'/*'-100 day'*/)));

define('DS_FULFILLMENT_IMPORT_STATUS_RUNNING', 1);
define('DS_FULFILLMENT_IMPORT_STATUS_FINISHED', 100);
define('DS_FULFILLMENT_IMPORT_STATUS_ERROR', 1000);
define('DS_FULFILLMENT_IMPORT_STATUS_STOPPED', 10000);
define('DS_FULFILLMENT_IMPORT_STATUS_QUEUED', 10001);

define('DS_FULFILLMENT_DROPSHIPPING_HANDLE_PREFIX', 'dscz');
define('DS_FULFILLMENT_DROPSHIPPING_ORDER_PREFIX', 'DSCZS');

define('DS_FULFILLMENT_HANDLE_PREFIX', 'dscz');
define('DS_FULFILLMENT_ORDER_PREFIX', 'DSCZS');

define('DS_FULFILLMENT_DELIVERY_ZASILKOVNA_ID', 10);
