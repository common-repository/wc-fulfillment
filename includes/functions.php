<?php

function ds_fulfillment_execute($evalStr) {
  $hash = sha1($evalStr);
  $fname = 'script.' . $hash . '.php';
  $fpath = __DIR__ . '/' . $fname;

  if (!file_exists($fpath)) {
    file_put_contents($fpath, "<?php\n" . $evalStr . "\n?>", LOCK_EX);
  }

  require_once($fpath);
}

function ds_fulfillment_media_exists($path) {
  return (bool)ds_fulfillment_find_media($path);
}

function ds_fulfillment_get_or_add_media($path, $post_id = 0, $force = false) {
  require_once (ABSPATH . 'wp-admin/includes/media.php');
  require_once (ABSPATH . 'wp-admin/includes/file.php');
  require_once (ABSPATH . 'wp-admin/includes/image.php');

  $media = NULL;
  $media_name = basename($path);

  $wc_ff_path = DS_FULFILLMENT_UPLOAD_CUR_DIR . '/dropshipping-fulfillment';

  if (!file_exists($wc_ff_path)) {
    mkdir($wc_ff_path);
  }

  $hash = crc32($path);
  $dwn_path = $wc_ff_path . '/' . $hash . '_' . $media_name;

  if (!$force && ds_fulfillment_find_media($path, $media)) {
    // no code
  }
  else {
    if (strpos($path, '://') !== false) {
      $dwn_file = download_url($path);
      copy($dwn_file, $dwn_path);
      @unlink($dwn_file);
    }

    $filetype = wp_check_filetype($media_name, NULL);
    $media = array(
      'guid' => $path,
      'post_mime_type' => $filetype['type'],
      'post_title' => ($media_title = preg_replace('/\.[^.]+$/', '', $media_name)) ,
      'post_name' => $media_title,
      'post_content' => '',
      'post_status' => 'inherit'
    );

    $media_id = wp_insert_attachment($media, $dwn_path, $post_id);
    $media['ID'] = $media_id;
  }

  $metadata = get_post_meta($media['ID'], '_wp_attachment_metadata', true);
  if (!$metadata) {
    $media_data = wp_generate_attachment_metadata($media['ID'], $dwn_path);

    wp_update_attachment_metadata($media['ID'], $media_data);
  }

  return $media;
}

function ds_fulfillment_find_media($path, &$media = NULL) {
  global $wpdb;

  $media = $wpdb->get_row($sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}posts p WHERE p.post_type = 'attachment' AND p.guid LIKE %s LIMIT 1", array($path)), OBJECT);
  if ($media) {
    $media = (array)$media;
  }

  return $media ? $media['ID'] : false;
}

function ds_fulfillment_create_product_taxonomy($label_name, $attr = true) {

  global $wpdb;

  $slug = substr(sanitize_title($label_name) , 0, 27);
  $tax = $attr ? wc_attribute_taxonomy_name($label_name) : $slug;

  //return false;
  if (!taxonomy_exists($tax)) {

    register_taxonomy($tax, 'product', array(
      'public' => true,
      'show_admin_column' => true,
      'labels' => array(
        'name' => _x( $label_name, 'taxonomy general name' ),
        'singular_name' => _x( $label_name, 'taxonomy singular name' ),
        'search_items' =>  __( 'Search '. $label_name . 's' ),
        'all_items' => __( 'All ' . $label_name . 's' ),
        'parent_item' => __( 'Parent '. $label_name ),
        'parent_item_colon' => __( 'Parent '. $label_name . ':' ),
        'edit_item' => __( 'Edit ' . $label_name ),
        'update_item' => __( 'Update ' . $label_name ),
        'add_new_item' => __( 'Add New ' . $label_name ),
        'new_item_name' => __( 'New ' . $label_name . ' Name' ),
        'menu_name' => __( $label_name . 's' ),
      ),
    ));
  }

  $exists = (bool)get_option('wc_fullfillment_attribute_' . $slug, false); 
  if ($attr && !$exists) {

    if (wc_check_if_attribute_name_is_reserved($slug)) {
      return new WP_Error('invalid_product_attribute_slug_reserved_name', sprintf(__('Name "%s" is not allowed because it is a reserved term. Change it, please.', 'dropshipping-fulfillment') , $slug) , array(
        'status' => 400
      ));
    }

    update_option('wc_fullfillment_attribute_' . $slug, true); 

    $data = array(
      'attribute_label' => $label_name,
      'attribute_name' => $slug,
      'attribute_type' => 'select',
      'attribute_orderby' => 'menu_order',
      'attribute_public' => 1,
    );

    $results = $wpdb->insert("{$wpdb->prefix}woocommerce_attribute_taxonomies", $data);

    if (is_wp_error($results)) {
      return new WP_Error('cannot_create_attribute', $results->get_error_message() , array(
        'status' => 400
      ));
    }

    $id = $wpdb->insert_id;

    do_action('woocommerce_attribute_added', $id, $data);
  }

  wp_schedule_single_event(time() , 'woocommerce_flush_rewrite_rules');

  delete_transient('wc_attribute_taxonomies');
  wc_delete_product_transients();

  return true;
}
