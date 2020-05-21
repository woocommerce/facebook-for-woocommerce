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

use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Content;

if ( ! class_exists( 'WC_Facebookcommerce_ServerEventFactory' ) ) :

if ( ! class_exists( 'WC_Facebookcommerce_EventIdGenerator' ) ) {
	include_once 'facebook-event-id-generator.php';
}

class WC_Facebookcommerce_ServerEventFactory {

  /**
   * Creates an Event with the standard parameters, ipaddress, useragent, fbp, fbc, eventname, eventtime, eventid, eventsourceurl
   * @param The event name
   * @return Event
   */
  public static function new_event($event_name) {
    $user_data = (new UserData())
                  ->setClientIpAddress(self::get_ip_address())
                  ->setClientUserAgent(self::get_http_user_agent())
                  ->setFbp(self::get_fbp())
                  ->setFbc(self::get_fbc());

    $event = (new Event())
              ->setEventName($event_name)
              ->setEventTime(time())
              ->setEventId(WC_Facebookcommerce_EventIdGenerator::guidv4())
              ->setEventSourceUrl(self::get_request_uri())
              ->setUserData($user_data)
              ->setCustomData(new CustomData());

    return $event;
  }

  /**
   * Gets the ip address of the client
   * @return string
   */
  private static function get_ip_address() {
    $HEADERS_TO_SCAN = array(
      'HTTP_CLIENT_IP',
      'HTTP_X_FORWARDED_FOR',
      'HTTP_X_FORWARDED',
      'HTTP_X_CLUSTER_CLIENT_IP',
      'HTTP_FORWARDED_FOR',
      'HTTP_FORWARDED',
      'REMOTE_ADDR'
    );

    foreach ($HEADERS_TO_SCAN as $header) {
      if (array_key_exists($header, $_SERVER)) {
        $ip_list = explode(',', $_SERVER[$header]);
        foreach($ip_list as $ip) {
          $trimmed_ip = trim($ip);
          if (self::is_valid_ip_address($trimmed_ip)) {
            return $trimmed_ip;
          }
        }
      }
    }

    return null;
  }

  /**
   * Checks if the input string is a valid ip address
   * @param string ip_address
   * @return bool
   */
  private static function is_valid_ip_address($ip_address) {
    return filter_var($ip_address,
      FILTER_VALIDATE_IP,
      FILTER_FLAG_IPV4
      | FILTER_FLAG_IPV6
      | FILTER_FLAG_NO_PRIV_RANGE
      | FILTER_FLAG_NO_RES_RANGE);
  }

  /**
   * Gets the htttp user agent
   * @return string
   */
  private static function get_http_user_agent() {
    $user_agent = null;

    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
      $user_agent = $_SERVER['HTTP_USER_AGENT'];
    }

    return $user_agent;
  }

  /**
   * Gets the requested url
   * @return string
   */
  private static function get_request_uri() {
    $url = "http://";
    if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
      $url = "https://";
    }

    if (!empty($_SERVER['HTTP_HOST'])) {
      $url .= $_SERVER['HTTP_HOST'];
    }

    if (!empty($_SERVER['REQUEST_URI'])) {
      $url .= $_SERVER['REQUEST_URI'];
    }

    return $url;
  }

  /**
   * Gets fbp cookie
   * @return string
   */
  private static function get_fbp() {
    $fbp = null;

    if (!empty($_COOKIE['_fbp'])) {
      $fbp = $_COOKIE['_fbp'];
    }

    return $fbp;
  }

  /**
   * Gets fbc cookie
   * @return string
   */
  private static function get_fbc() {
    $fbc = null;

    if (!empty($_COOKIE['_fbc'])) {
      $fbc = $_COOKIE['_fbc'];
    }

    return $fbc;
  }
}

endif;
