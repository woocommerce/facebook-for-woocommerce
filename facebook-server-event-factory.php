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
   * Creates an Event with the standard parameters and custom data specified in data
   * custom data includes email, first_name, last_name, currency, value, content_ids, content_type
   * content_name, content_category, num_items, search string, contents
   * @param string event The event name
   * @param array data The custom data
   * @return Event
   */
  public static function create_event($event_name, $data) {
    $event = self::new_event($event_name);
    if (WC_Facebookcommerce_Pixel::get_use_pii_key()) {
      $user_data = $event->getUserData();
      if (!empty($data['email'])) {
        $user_data->setEmail($data['email']);
      }

      if (!empty($data['first_name'])) {
        $user_data->setFirstName($data['first_name']);
      }

      if (!empty($data['last_name'])) {
        $user_data->setLastName($data['last_name']);
      }
    }

    $custom_data = $event->getCustomData();
    if (!empty($data['currency'])) {
      $custom_data->setCurrency($data['currency']);
    }

    if (!empty($data['value'])) {
      $custom_data->setValue($data['value']);
    }

    if (!empty($data['content_ids'])) {
      $custom_data->setContentIds($data['content_ids']);
    }

    if (!empty($data['content_type'])) {
      $custom_data->setContentType($data['content_type']);
    }

    if (!empty($data['content_name'])) {
      $custom_data->setContentName($data['content_name']);
    }

    if (!empty($data['content_category'])) {
      $custom_data->setContentCategory($data['content_category']);
    }

    if (!empty($data['num_items'])) {
      $custom_data->setNumItems($data['num_items']);
    }

    if (!empty($data['search_string'])) {
      $custom_data->setSearchString($data['search_string']);
    }

    if (!empty($data['contents'])) {
      $contents = array();
      foreach(json_decode($data['contents']) as $content) {
        $contents[] = array(
          'id' => $content->id,
          'quantity' => $content->quantity
        );
      }

      $custom_data->setContents(new Content($contents));
    }

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
