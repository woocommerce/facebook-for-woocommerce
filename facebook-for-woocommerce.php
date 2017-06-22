<?php /**
* Plugin Name: Facebook for WooCommerce
* Plugin URI: https://www.facebook.com
* Description: Grow your business on Facebook! Use this official plugin to help sell more of your products using Facebook. After completing the setup, you'll be ready to create ads that promote your products and you can also create a shop section on your Page where customers can browse your products on Facebook.
* Author: Facebook
* Author URI: https://www.facebook.com
* Version: 1.4.2
* Woo: 2127297:0ea4fe4c2d7ca6338f8a322fb3e4e187
* Text Domain: facebook-for-woocommerce
*/
/**
* @package FacebookCommerce
*/

/**
 * Required functions
 */
if (! function_exists('woothemes_queue_update')) {
  include_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update(plugin_basename(__FILE__),
  '0ea4fe4c2d7ca6338f8a322fb3e4e187', '2127297');

if (!class_exists('WC_Facebookcommerce')) :

class WC_Facebookcommerce {

  const PLUGIN_VERSION = '1.4.2';  // Change it above as well

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
    // Checks if WooCommerce is installed.
    if (class_exists('WooCommerce')) {
      global $woocommerce;

      if (WP_DEBUG && WP_DEBUG_DISPLAY) {
        add_action('admin_notices', array($this, 'wp_debug_display_error'));
        return;
      }

      if (!defined('WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL')) {
        define('WOOCOMMERCE_FACEBOOK_PLUGIN_SETTINGS_URL', get_admin_url() .
        '/admin.php?page=wc-settings&tab=integration' .
        '&section=facebookcommerce');
      }

      if (!class_exists('WC_Facebookcommerce_Integration')) {
        // Include our integration class.
        include_once 'facebook-commerce.php';
      }

      // Register the integration.
      add_filter('woocommerce_integrations', array($this, 'add_integration'));
    }
    else {
      add_action('admin_notices', array($this, 'plugin_error'));
    }
  }

  public function plugin_error() {
    ?>
    <div class="error below-h3">
      <p>
      <?php
        printf(__('Could not find a WooCommerce Install.
          Facebook for WooCommerce plugin requires WooCommerce
          v2.6 or higher.', 'facebook-for-woocommerce'));
       ?>
      </p>
    </div>
    <?php
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
        $GLOBALS['hide_save_button'] = true;
       ?>
      </p>
    </div>
    <?php
  }

  /**
   * Add a new integration to WooCommerce.
   */
  public function add_integration($integrations) {
    $integrations[] = 'WC_Facebookcommerce_Integration';
    return $integrations;
  }

}

$WC_Facebookcommerce = new WC_Facebookcommerce(__FILE__);

endif;
