<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders;

defined( 'ABSPATH' ) or exit;

/**
 * Orders API order handler.
 *
 * @since 2.1.0-dev.1
 */
class Order {


	/** @var string API state meaning Facebook is still processing the order and no action is possible */
	const STATUS_PROCESSING = 'FB_PROCESSING';

	/** @var string API state meaning Facebook has processed the orders and the seller needs to acknowledge it */
	const STATUS_CREATED = 'CREATED';

	/** @var string API state meaning the order was acknowledged and is now being processed in WC */
	const STATUS_IN_PROGRESS = 'IN_PROGRESS';

	/** @var string API state meaning all items in the order are shipped and/or cancelled */
	const STATUS_COMPLETED = 'COMPLETED';


}
