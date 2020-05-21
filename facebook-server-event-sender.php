<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

defined( 'ABSPATH' ) or exit;

use FacebookAds\Api;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Exception\Exception;

if ( ! class_exists( 'WC_Facebookcommerce_ServerEventSender' ) ) :

if ( ! class_exists( 'WC_Facebookcommerce_Pixel' ) ) {
	include_once 'facebook-commerce-pixel-event.php';
}

final class WC_Facebookcommerce_ServerEventSender {
  /*
   * Sends an Event to Facebook and returns the response
   * @param Event event object to send
   * @param string pixel_id associated pixel
   * @param string access_token required to send the request
   * @param string agent plugin identifier
   */
  public static function send_event($event) {
    try{
      $pixel_id =  WC_Facebookcommerce_Pixel::get_pixel_id();
      $agent =  WC_Facebookcommerce_Pixel::get_agent();
      $access_token =  WC_Facebookcommerce_Pixel::get_access_token();
      $api = Api::init(null, null, $access_token);
      $request = (new EventRequest($pixel_id))
                    ->setEvents(array($event))
                    ->setPartnerAgent($agent);
      $response = $request->execute();
      return $response;
    }
    catch( Exception $e ){
      return null;
    }
  }
}

endif;
