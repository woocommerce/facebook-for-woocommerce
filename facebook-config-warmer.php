<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

defined( 'ABSPATH' ) || exit;

class WC_Facebookcommerce_WarmConfig {
	/** @var string Pixel ID. */
	public static $fb_warm_pixel_id                     = null;

	/** @var bool Is Advanced matching enabled. */
	public static $fb_warm_is_advanced_matching_enabled = null;

	/** @var bool Uses S2S. */
	public static $fb_warm_use_s2s                      = null;

	/** @var string Access token. */
	public static $fb_warm_access_token                 = null;
}
