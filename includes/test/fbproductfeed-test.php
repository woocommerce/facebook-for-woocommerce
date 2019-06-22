<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/fbproductfeed.php';
require_once dirname( __DIR__ ) . '/fbutils.php';

if ( ! class_exists( 'WC_Facebook_Product_Feed_Test' ) ) :
	/**
	 * Mock for Facebook feed class
	 */
	class WC_Facebook_Product_Feed_Test_Mock extends WC_Facebook_Product_Feed {

		public static $product_post_wpid = null;

		// Return test product post id.
		// Don't mess up actual products.
		public function get_product_wpid() {
			return self::$product_post_wpid;
		}

		// Log progress in local log file for testing.
		// Not to overwhelm DB log to track important signals.
		public function log_feed_progress( $msg, $object = array() ) {
			$msg = empty( $object ) ? $msg : $msg . json_encode( $object );
			WC_Facebookcommerce_Utils::log( 'Test - ' . $msg );
		}
	}

endif;
