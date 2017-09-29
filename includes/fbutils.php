<?php
/**
 * @package FacebookCommerce
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('WC_Facebookcommerce_Utils')) :

  /**
   * FB Graph API helper functions
   *
   */
  class WC_Facebookcommerce_Utils {

    const FB_RETAILER_ID_PREFIX = 'wc_post_id_';
    const PLUGIN_VERSION = '1.6.2';  // Change it in `facebook-for-*.php` also

    /**
     * WooCommerce 2.1 support for wc_enqueue_js
     *
     * @since 1.2.1
     *
     * @access public
     * @param string $code
     * @return void
     */
    public static function wc_enqueue_js($code) {
      if (function_exists('wc_enqueue_js')) {
        wc_enqueue_js($code);
      } else {
        global $woocommerce;
        $woocommerce->add_inline_js($code);
      }
    }

    /**
     * Validate URLs, make relative URLs absolute
     *
     * @access public
     * @param string $url
     * @return string
     */
    public static function make_url($url) {
      if (
        // The first check incorrectly fails for URLs with special chars.
        !filter_var($url , FILTER_VALIDATE_URL) &&
        substr($url, 0, 4) !== 'http'
      ) {
        return get_site_url() . $url ;
      } else {
        return $url;
      }
    }

    /**
     * Product ID for Dynamic Ads on Facebook can be SKU or wc_post_id_123
     * This function should be used to get retailer_id based on a WC_Product
     * from WooCommerce
     *
     * @access public
     * @param WC_Product $woo_product
     * @return string
     */
    public static function get_fb_retailer_id($woo_product) {
      $woo_id = $woo_product->get_id();

      // Call $woo_product->get_id() instead of ->id to account for Variable
      // products, which have their own variant_ids.
      return $woo_product->get_sku() ? $woo_product->get_sku() . '_' .
         $woo_id : self::FB_RETAILER_ID_PREFIX . $woo_id;
    }

    /**
     * Return categories for products/pixel
     *
     * @access public
     * @param String $id
     * @return Array
     */
    public static function get_product_categories($wpid) {
      $category_path = wp_get_post_terms(
        $wpid,
        'product_cat',
        array('fields' => 'all'));
      $content_category = array_values(
        array_map(
          function($item) {
            return $item->name;
          },
          $category_path));
      $content_category_slice = array_slice($content_category, -1);
      $categories =
        empty($content_category) ? '""' : implode(', ', $content_category);
      return array(
        'name' => array_pop($content_category_slice),
        'categories' => $categories
      );
    }

    /**
     * Compatibility method for legacy retailer IDs prior to 1.1
     * Returns a variety of IDs to match on for Pixel fires.
     *
     * @access public
     * @param WC_Product $woo_product
     * @return array
     */
    public static function get_fb_content_ids($woo_product) {
      return array_values(array_unique(array_filter(array(
        $woo_product->get_sku(),
        self::FB_RETAILER_ID_PREFIX . $woo_product->get_id(),
        self::get_fb_retailer_id($woo_product)
      ))));
    }

    /**
     * Clean up strings for FB Graph POSTing.
     * This function should will:
     * 1. Replace newlines chars/nbsp with a real space
     * 2. strip_tags()
     * 3. trim()
     *
     * @access public
     * @param String string
     * @return string
     */
    public static function clean_string($string) {
      $string = str_replace(array('&amp%3B', '&amp;'), '&', $string);
      $string = str_replace(array("\r", "\n", "&nbsp;", "\t"), ' ', $string);
      // Strip shortcodes via regex but keep inner content
      $string = preg_replace("~(?:\[/?)[^/\]]+/?\]~s", '', $string);
      $string = wp_strip_all_tags($string, true); // true == remove line breaks
      return trim($string);
    }

    /**
     * Returns flat array of woo IDs for variable products, or
     * an array with a single woo ID for simple products.
     *
     * @access public
     * @param WC_Product $woo_product
     * @return array
     */
    public static function get_product_array($woo_product) {
      $result = array();
      if ($woo_product->get_type() === 'variable') {
        foreach ($woo_product->get_children() as $item_id) {
          array_push($result, $item_id);
        }
        return $result;
      } else {
        return array($woo_product->get_id());
      }
    }

    /**
     * Returns true if WooCommerce plugin found.
     *
     * @access public
     * @return bool
     */
    public static function isWoocommerceIntegration() {
      return class_exists('WooCommerce');
    }

    /**
     * Returns integration dependent name.
     *
     * @access public
     * @return string
     */
    public static function getIntegrationName() {
      if (WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
        return 'WooCommerce';
      } else {
        return 'WordPress';
      }
    }

    /**
     * Returns user info for the current WP user.
     *
     * @access public
     * @param boolean $use_pii
     * @return array
     */
    public static function get_user_info($use_pii) {
      $current_user = wp_get_current_user();
      if (0 === $current_user->ID || $use_pii === false) {
        // User not logged in or admin chose not to send PII.
        return array();
      } else {
        return array_filter(
          array(
            // Keys documented in
            // https://developers.facebook.com/docs/facebook-pixel/pixel-with-ads/
            // /conversion-tracking#advanced_match
            'em' => $current_user->user_email,
            'fn' => $current_user->user_firstname,
            'ln' => $current_user->user_lastname
          ),
          function ($value) { return $value !== null && $value !== ''; });
      }
    }
  }
endif;
