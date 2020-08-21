<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

/**
 * Base handler for Commerce-specific functionality.
 *
 * @since 2.3.0-dev.1
 */
class Commerce {


	/** @var string option that stores the plugin-level fallback Google product category ID */
	const OPTION_GOOGLE_PRODUCT_CATEGORY_ID = 'wc_facebook_google_product_category_id';


	/** @var Commerce\Orders the orders handler */
	protected $orders;


}
