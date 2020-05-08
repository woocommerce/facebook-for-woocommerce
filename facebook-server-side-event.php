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
use FacebookAds\Object\ServerSide\UserData;

class WC_Facebook_ServerSideEvent {
  private static $instance = null;
  private $tracked_events = [];

  public static function get_instance() {
    if (self::$instance == null) {
      self::$instance = new WC_Facebook_ServerSideEvent();
    }

    return self::$instance;
  }

  public function track($event) {
    $this->tracked_events[] = $event;
    do_action('wc_facebook_send_server_event', $event);
  }

  public function get_tracked_events() {
    return $this->tracked_events;
  }

  public static function send($events) {
    if (empty($events)) {
      return;
    }

    $pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();
    $access_token = "";
    $agent = WC_Facebookcommerce_Pixel::get_agent();

    $api = Api::init(null, null, $access_token);

    $request = (new EventRequest($pixel_id))
                   ->setEvents($events)
                   ->setPartnerAgent($agent);

    $response = $request->execute();
  }
}
