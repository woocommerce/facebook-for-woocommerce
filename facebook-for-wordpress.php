<?php /**
 * Plugin Name: Facebook Pixel
 * Plugin URI: https://github.com/facebookincubator/facebook-for-woocommerce/
 * Description: The Facebook pixel is an analytics tool that helps you measure the effectiveness of your advertising. You can use the Facebook pixel to understand the actions people are taking on your website and reach audiences you care about.
 * Author: Facebook
 * Author URI: https://www.facebook.com/
 * Version: 1.7.7
 * Text Domain: facebook-pixel
 */
/**
 * @package FacebookCommerce
 */

if (!class_exists('WP_FacebookPixel')) :
include_once 'includes/fbutils.php';
include_once 'facebook-config-warmer.php';
include_once 'facebook-wordpress.php';

class WP_FacebookPixel {

  // Change it above as well
  const PLUGIN_VERSION = WC_Facebookcommerce_Utils::PLUGIN_VERSION;

  public function __construct() {
    if (!WC_Facebookcommerce_Utils::isWoocommerceIntegration()) {
      // Initialize PixelID in storage
      $options = get_option(FacebookWordPress_Config::SETTINGS_KEY);
      $pixel_id = $options[FacebookWordPress_Config::PIXEL_ID_KEY];
      $should_update = false;
      if (!WC_Facebookcommerce_Utils::is_valid_id($pixel_id) &&
          class_exists('WC_Facebookcommerce_WarmConfig')) {
        $fb_warm_pixel_id = WC_Facebookcommerce_WarmConfig::$fb_warm_pixel_id;

        if (WC_Facebookcommerce_Utils::is_valid_id($fb_warm_pixel_id) &&
            (int)$fb_warm_pixel_id == $fb_warm_pixel_id) {
          $pixel_id = (string)$fb_warm_pixel_id;
          $should_update = true;
        }
      }

      // Initialize Use PII in storage
      $use_pii = $options[FacebookWordPress_Config::USE_PII_KEY];
      if (!isset($use_pii) || ($use_pii != '0' && $use_pii != '1')) {
        // Opt-In
        $use_pii = '0';
        $should_update = true;
      }

      if ($should_update) {
        update_option(FacebookWordPress_Config::SETTINGS_KEY, array(
          FacebookWordPress_Config::PIXEL_ID_KEY => $pixel_id,
          FacebookWordPress_Config::USE_PII_KEY => $use_pii,
        ));
      }

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
