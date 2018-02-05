<?php /**
 * Plugin Name: Facebook Pixel
 * Plugin URI: https://github.com/facebookincubator/facebook-for-woocommerce/
 * Description: The Facebook pixel is an analytics tool that helps you measure the effectiveness of your advertising. You can use the Facebook pixel to understand the actions people are taking on your website and reach audiences you care about.
 * Author: Facebook
 * Author URI: https://www.facebook.com/
 * Version: 1.7.11
 * Text Domain: facebook-pixel
 */
/**
 * @package FacebookCommerce
 */

if (!class_exists('WP_FacebookPixel')) :
include_once 'includes/fbutils.php';
include_once 'facebook-config-warmer.php';
include_once 'facebook-wordpress.php';
include_once 'facebook-commerce-pixel-event.php';

class WP_FacebookPixel {

  // Change it above as well
  const PLUGIN_VERSION = WC_Facebookcommerce_Utils::PLUGIN_VERSION;

  public function __construct() {
    if (!WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
      WC_Facebookcommerce_Pixel::initialize();

      // Register WordPress integration.
      add_action('init', array($this, 'register_integration'), 0);
      $this->register_settings_page();
    }
  }

  /**
   * Helper function for registering this integration.
   */
  public function register_integration() {
    return new WP_Facebook_Integration();
  }

  /**
   * Helper function for registering the settings page.
   */
  public function register_settings_page() {
    if (is_admin()) {
      $plugin_name = plugin_basename(__FILE__);
      new FacebookWordPress_Config($plugin_name);
    }
  }
}

$WP_FacebookPixel = new WP_FacebookPixel(__FILE__);

endif;
