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

class WC_Facebook_ServerEventAsyncTask extends \WP_Async_Task {
  protected $action = 'wc_facebook_send_server_event';

  protected function prepare_data($data) {
    try {
      if (!empty($data)) {
        return array('data' => base64_encode(serialize($data)));
      }
    } catch (\Exception $ex) {
      error_log($ex);
    }

    return array();
  }

  protected function run_action() {
    try {
      $events = unserialize(base64_decode($_POST['data']));
      if (empty($events)) {
        return;
      }

      WC_Facebook_ServerSideEvent::send($events);
    }
    catch (\Exception $ex) {
      error_log($ex);
    }
  }
}
