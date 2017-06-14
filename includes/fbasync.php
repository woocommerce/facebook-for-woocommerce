<?php
/**
 * @package FacebookCommerce
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('WP_Async_Request', false) ) {
  // Do not attempt to create this class without WP_Async_Request
  return;
}

if (!class_exists('WC_Facebookcommerce_Async_Request')) :

/**
 * FB Graph API async request
 *
 */
class WC_Facebookcommerce_Async_Request extends WP_Async_Request {

  protected $action = 'wc_facebook_async_request';

  /**
   * Handle
   *
   * Override this method to perform any actions required
   * during the async request.
   */
  protected function handle() {
    // Actions to perform
    error_log("Okay doing async HANDLE");

    error_log($_POST['url']);
  }

}

endif;
