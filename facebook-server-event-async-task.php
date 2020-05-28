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

require_once( plugin_dir_path( __FILE__ ) . '/wp-content/plugins/wp-async-task/wp-async-task.php' );

if ( ! class_exists( 'WC_Facebookcommerce_ServerEventSender' ) ) {
	include_once 'facebook-server-event-sender.php';
}

class WC_Facebook_ServerEventAsyncTask extends WP_Async_Task {
  protected $action = 'wc_facebook_send_server_event';

  /**
   * Creates an array from data, throws an exception if it is empty
   * @param array data Contains the arguments passed to the do_action function
   * @return array Contains the data that run_action will use
   */
  protected function prepare_data($data) {
    try {
      if (!empty($data)) {
        return array('event_data' => base64_encode(serialize($data[0])), 'num_events'=>$data[1]);
      }
    } catch (\Exception $ex) {
      error_log($ex);
    }

    return array();
  }

  /**
   * Runs a function that can take a long time to execute.
   * In this case, it sends the server side events
   */
  protected function run_action() {
    try {
      $num_events = $_POST['num_events'];
      if( $num_events == 0 ){
        return;
      }
      $events = unserialize(base64_decode($_POST['event_data']));
      //When an array has just one object, the deserialization process returns just the object
      //and we want an array
      if( $num_events == 1 ){
        $events = array( $events );
      }
      WC_Facebookcommerce_ServerEventSender::send($events);
    }
    catch (\Exception $ex) {
      error_log($ex);
    }
  }

}
