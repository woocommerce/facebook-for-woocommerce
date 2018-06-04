<?php
/**
 * @package FacebookCommerce
 */
if (! defined('ABSPATH')) {
  exit;
}

include_once(dirname(__FILE__, 2) . '/fbproductfeed.php');
include_once(dirname(__FILE__, 2) . '/fbutils.php');

if (! class_exists('WC_Facebook_Product_Feed_Test')) :
/**
 * Mock for Facebook feed class
 */
class WC_Facebook_Product_Feed_Test_Mock extends WC_Facebook_Product_Feed {

  public static $product_post_wpid = null;

  public function get_product_wpid() {
    return self::$product_post_wpid;
  }

  // Log progress in local log file for testing.
  // Not to overwhelm DB log to track important signals.
  public function log_feed_progress($msg) {
    WC_Facebookcommerce_Utils::log('Test - ' . $msg);
  }
}

endif;
