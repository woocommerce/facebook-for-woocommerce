<?php /**
* Plugin Name: Facebook for WooCommerce
* Plugin URI: https://github.com/facebookincubator/facebook-for-woocommerce/
* Description: Grow your business on Facebook! Use this official plugin to help sell more of your products using Facebook. After completing the setup, you'll be ready to create ads that promote your products and you can also create a shop section on your Page where customers can browse your products on Facebook.
* Author: Facebook
* Author URI: https://www.facebook.com/
* Version: 1.7.7
* Woo: 2127297:0ea4fe4c2d7ca6338f8a322fb3e4e187
* Text Domain: facebook-for-woocommerce
*/
/**
* @package FacebookCommerce
*/

/**
 * Plugin updates
 */
if (function_exists('woothemes_queue_update')) {
  woothemes_queue_update(plugin_basename(__FILE__),
    '0ea4fe4c2d7ca6338f8a322fb3e4e187', '2127297');
}

if (!class_exists('WC_Facebookcommerce')) :
include_once 'includes/fbutils.php';

class WC_Facebookcommerce {

  // Change it above as well
  const PLUGIN_VERSION = WC_Facebookcommerce_Utils::PLUGIN_VERSION;

  /**
   * Construct the plugin.
   */
  public function __construct() {
    add_action('plugins_loaded', array( $this, 'init'));
  }

  /**
   * Initialize the plugin.
   */
  public function init() {
    if (WP_DEBUG && WP_DEBUG_DISPLAY) {
      add_action('admin_notices', array($this, 'wp_debug_display_error'));
      return;
    }

    if (WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
      include_once('woo-includes/woo-functions.php');
      if (!defined('WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL')) {
        define(
                'WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL',
                get_admin_url()
                .'/admin.php?page=wc-settings&tab=integration'
                .'&section=facebookcommerce');
      }
      include_once 'facebook-commerce.php';

      // Register WooCommerce integration.
      add_filter('woocommerce_integrations', array(
        $this,
        'add_woocommerce_integration'
      ));
    }
  }

  public function wp_debug_display_error() {
    ?>
    <div class="error below-h3">
      <p>
      <?php
        printf(__('To use Facebook for WooCommerce,
          please disable WP_DEBUG_DISPLAY in your wp-config.php file.
          Contact your server administrator for more assistance.',
          'facebook-for-woocommerce'));
       ?>
      </p>
    </div>
    <?php
  }

  /**
   * Add a new integration to WooCommerce.
   */
  public function add_woocommerce_integration($integrations) {
    $integrations[] = 'WC_Facebookcommerce_Integration';
    return $integrations;
  }

  public function add_wordpress_integration() {
    new WP_Facebook_Integration();
  }
}

$WC_Facebookcommerce = new WC_Facebookcommerce(__FILE__);

endif;
