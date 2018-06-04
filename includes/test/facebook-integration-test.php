<?php
/**
 * @package FacebookCommerce
 * usage:
 * 1. set WP_DEBUG = true and WP_DEBUG_DISPLAY = false
 * 2. append "&fb_test_product_sync=true" to the url when you are on facebook-for-woocommerce setting pages
 * 3. refresh the page to launch test
 * https://codex.wordpress.org/WP_DEBUG
 */
if (!defined('ABSPATH')) {
  exit;
}

include_once(dirname(__FILE__, 2) . '/fbutils.php');

if (!class_exists('WC_Facebook_Integration_Test')) :

/**
 * Integtration test class
 */
class WC_Facebook_Integration_Test {

  // simple products' id and variable products' parent_id
  public static $wp_post_ids = array();
  // FB product item retailer id.
  public static $retailer_ids = array();

  public static function create_data() {
    $prod_and_variant_wpid = array();
    // Gets term object from Accessories in the database.
    $term = get_term_by('name', 'Accessories', 'product_cat');
    // Accessories should be a default category.
    // If not exist, set categories term first.
    if (!$term) {
      $term = wp_insert_term(
        'Accessories', // the term
        'product_cat', // the taxonomy
        array(
          'slug' => 'accessories'
        ));
    }
    $data = array (
      'post_content' => 'This is to test a simple product.',
      'post_title' => 'a simple product for test',
      'post_status' => 'publish',
      'post_type' => 'product',
      'term' => $term,
      'price' => 20
    );
    $simple_product_result =
      self::create_test_simple_product($data, $prod_and_variant_wpid);

    if (!$simple_product_result) {
      return false;
    }

    $data['post_content'] = 'This is to test a variable product.';
    $data['post_title'] = 'a variable product for test';
    $data['price'] = 30;
    $variable_product_result =
      self::create_test_variable_product($data, $prod_and_variant_wpid);
    if (!$variable_product_result) {
      return false;
    }
    return $prod_and_variant_wpid;
  }

  static function create_test_simple_product($data, &$prod_and_variant_wpid) {
    $post_id = self::fb_insert_post($data, 'Simple');
    if (!$post_id) {
      return false;
    }
    array_push($prod_and_variant_wpid, $post_id);
    array_push(self::$wp_post_ids, $post_id);
    array_push(self::$retailer_ids, 'wc_post_id_' . $post_id);
    update_post_meta($post_id, '_regular_price', $data['price']);
    wp_set_object_terms($post_id, 'simple', 'product_type');
    $product = wc_get_product($post_id);
    $product->set_stock_status('instock');
    wp_set_object_terms($post_id, $data['term']->term_id, 'product_cat');
    return true;
  }

  static function create_test_variable_product($data, &$prod_and_variant_wpid) {
    $post_id = self::fb_insert_post($data, 'Variable');
    if (!$post_id) {
      return false;
    }

    wp_set_object_terms($post_id, 'variable', 'product_type');
    array_push($prod_and_variant_wpid, $post_id);
    array_push(self::$wp_post_ids, $post_id);
    // Gets term object from Accessories in the database.
    $term = get_term_by('name', 'Accessories', 'product_cat');
    wp_set_object_terms($post_id, $term->term_id, 'product_cat');

    // Set up attributes.
    $avail_attributes = array(
      'Red',
      'Blue'
    );
    $thedata = array(
      'pa_color' => array(
        'name' => 'pa_color',
        'value' => '',
        'is_visible' => '1',
        'is_taxonomy' => '1'
      )
    );
    update_post_meta($post_id, '_product_attributes', $thedata);

    // Insert variations.
    $variation_data = array(
      'post_title' => 'a variable product for test - Red',
      'post_status' => 'publish',
      'post_type' => 'product_variation',
      'post_parent'   => $post_id,
    );
    $variation_red = self::fb_insert_post($variation_data, 'Variation');
    if (!$variation_red) {
      return;
    }

    array_push($prod_and_variant_wpid, $variation_red);
    array_push(self::$retailer_ids, 'wc_post_id_' . $variation_red);

    update_post_meta($variation_red, 'attribute_pa_color', 'Red');
    update_post_meta($variation_red, '_regular_price', $data['price']);
    $product = wc_get_product($variation_red);
    $product->set_stock_status('instock');

    $variation_data['post_title'] = 'a variable product for test - Blue';
    $variation_blue = self::fb_insert_post($variation_data, 'Variatoin');
    if (!$variation_blue) {
      return false;
    }
    array_push($prod_and_variant_wpid, $variation_blue);
    array_push(self::$retailer_ids, 'wc_post_id_' . $variation_blue);
    update_post_meta($variation_blue, 'attribute_pa_color', 'Blue');
    update_post_meta($variation_blue, '_regular_price', $data['price']);
    $product = wc_get_product($variation_blue);
    $product->set_stock_status('instock');
    return true;
  }

  public static function fb_insert_post($data, $p_type) {
    $postarr = array_intersect_key(
      $data,
      array_flip(array(
        'post_content',
        'post_title',
        'post_status',
        'post_type',
        'post_parent',
      )));
    $post_id = wp_insert_post($postarr);
    if (is_wp_error($post_id)) {
      WC_Facebookcommerce_Utils::log('Test - ' . $p_type .
      ' product wp_insert_post' . 'failed: ' . json_encode($post_id));
      return false;
    } else {
      return $post_id;
    }
  }

}

endif;
