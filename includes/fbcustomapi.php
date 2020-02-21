<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

if (! defined('ABSPATH')) {
  exit;
}

require_once 'fbproductfeed.php';

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
  }

  /**
   * Function to define custom routes
   */
  public function register_routes() {
    register_rest_route('facebook/v1', '/genfeed' ,
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array($this, 'gen_feed'),
      ));
    register_rest_route('facebook/v1', '/genfeed/feedping' ,
      array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => array($this, 'gen_feed_ping'),
      ));
  }

  public function gen_feed( WP_REST_Request $request ) {
    $feed = new WC_Facebook_Product_Feed();
    $url = $feed->gen_feed( false );
    $res = new WP_REST_Response( $url );
    $res->set_status( 200 );
    $res->jsonSerialize();
    return $res;
  }

  public function gen_feed_ping( WP_REST_Request $request ) {
    $feed = new WC_Facebook_Product_Feed();
    $time = $feed->estimateFeedGenerationTime();
    $res = new WP_REST_Response( $time );
    $res->set_status( 200 );
    $res->jsonSerialize();
    return $res;
  }

}

endif;
