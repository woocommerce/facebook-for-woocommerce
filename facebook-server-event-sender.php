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

  private static $instance = null;
  private $tracked_events = [];

  /**
   * Returns an instance of this class
  */
  public static function get_instance() {
    if (self::$instance == null) {
      self::$instance = new WC_Facebookcommerce_ServerEventSender();
    }

    return self::$instance;
  }

  /**
   * Adds an event to the list of tracked events
  */
  public function track($event) {
    $this->tracked_events[] = $event;
  }

  /**
   * Adds an event to the list of tracked events
   * @return array
  */
  public function get_tracked_events() {
    return $this->tracked_events;
  }

  /**
   * Returns the amount of tracked events
   * @return int
  */
  public function get_num_tracked_events(){
    return count( $this->tracked_events );
  }

  /**
   * Sends events to Facebook and returns the response
   * @param array Array of events to send
   * @return EventResponse Response of the request
   */
  public static function send( $events ) {
    try{
      $agent =  WC_Facebookcommerce_Pixel::get_agent();
      $pixel_id =  WC_Facebookcommerce_Pixel::get_pixel_id();
      $access_token =  WC_Facebookcommerce_Pixel::get_access_token();
      $api = Api::init(null, null, $access_token);
      $request = (new EventRequest($pixel_id))
                    ->setEvents( $events )
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
