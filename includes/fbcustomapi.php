<?php
/**
 * @package FacebookCommerce
 */
if (! defined('ABSPATH')) {
  exit;
}

if (! class_exists('WC_Facebookcommerce_REST_Controller')) :

/**
 * Custom API REST path class
 *
 */
class WC_Facebookcommerce_REST_Controller extends WP_REST_Controller {

    /**
   * Init and hook in the integration.
   */
  public function __construct() {
    global $woocommerce;
    add_action('rest_api_init', array($this, 'register_routes'));


    // TODO:  wp_woocommerce_api_keys
    // http://stackoverflow.com/questions/31327994/woocommerce-rest-client-api-
      // programmatically-get-consumer-key-and-secret

  }

  /**
   * Function to define each of the custom path
   */
  public function register_routes() {

      register_rest_route('facebook/v1', '/test' ,
        array(
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => array($this, 'fb_test_function'),
        ));
  }

  public function fb_test_function(WP_REST_Request $request) {
    $parameters = $request->get_params();
    // hardcoded product ID just for demo purposes
    $item = wc_get_product(17);
    // error_log($item);

    // Create the response object
    $res = new WP_REST_Response($item);

    // Add a custom status code
    $res->set_status(200);
    $res->jsonSerialize();

    return $res;
  }


}

endif;
