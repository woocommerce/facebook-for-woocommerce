<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Orders\Cancel;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Orders API cancel request object.
 *
 * @since 2.1.0-dev.1
 */
class Request extends API\Request  {


	use API\Traits\Idempotent_Request;


	/** @var string customer requested cancellation */
	const REASON_CUSTOMER_REQUESTED = 'CUSTOMER_REQUESTED';

	/** @var string out of stock cancellation */
	const REASON_OUT_OF_STOCK = 'OUT_OF_STOCK';

	/** @var string invalid address cancellation */
	const REASON_INVALID_ADDRESS = 'INVALID_ADDRESS';

	/** @var string suspicious order cancellation */
	const REASON_SUSPICIOUS_ORDER = 'SUSPICIOUS_ORDER';

	/** @var string other reason cancellation */
	const REASON_OTHER = 'CANCEL_REASON_OTHER';


}
