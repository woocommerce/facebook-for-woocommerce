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


if ( ! class_exists( 'WC_Facebookcommerce_EventIdGenerator' ) ) :

final class WC_Facebookcommerce_EventIdGenerator {
  /**
   * Creates a new guid v4 - via https://stackoverflow.com/a/15875555
   * @return string A 36 character string containing dashes.
   */
  public static function guidv4() {
    $data = openssl_random_pseudo_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }
}

endif;
