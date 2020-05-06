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
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'WC_Facebookcommerce_WarmConfig' ) ) :

	class WC_Facebookcommerce_WarmConfig {
		static $fb_warm_pixel_id                     = null;
		static $fb_warm_is_advanced_matching_enabled = null;
		static $fb_warm_use_s2s 										 = null;
		static $fb_warm_access_token 								 = null;
	}

endif;
