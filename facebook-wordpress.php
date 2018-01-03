<?php
/**
 * @package FacebookCommerce
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!class_exists('WP_Facebook_Integration')) :

include_once 'facebook-wordpress-config.php';
include_once 'facebook-config-warmer.php';

class WP_Facebook_Integration {
  private $events_tracker;
  private $use_pii;

  public function __construct() {
    if (!class_exists('WC_Facebookcommerce_EventsTracker')) {
      include_once 'facebook-commerce-events-tracker.php';
    }

    $options = get_option(FacebookWordPress_Config::SETTINGS_KEY);
    $pixel_id = $options[FacebookWordPress_Config::PIXEL_ID_KEY];
    $use_pii = $options[FacebookWordPress_Config::USE_PII_KEY];

    if (WC_Facebookcommerce_Utils::is_valid_id($pixel_id)) {
      $user_info = WC_Facebookcommerce_Utils::get_user_info($use_pii == '1');
      $this->events_tracker = new WC_Facebookcommerce_EventsTracker(
        $pixel_id, $user_info);

      // Pixel Tracking Hooks
      add_action('wp_head',
        array($this->events_tracker, 'inject_base_pixel'));
      add_action('wp_head',
        array($this->events_tracker, 'inject_base_pixel_noscript'));
      add_action('posts_search',
        array($this->events_tracker, 'inject_search_event'));
    }
  }

  /**
   * Helper log function for debugging
   *
   * @since 1.2.2
   */
  public static function log($message) {
    if (WP_DEBUG === true) {
      if (is_array($message) || is_object($message)) {
        error_log(json_encode($message));
      }
      else {
        error_log($message);
      }
    }
  }
}

endif;
