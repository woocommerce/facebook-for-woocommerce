<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Utilities;

defined( 'ABSPATH' ) or exit;

/**
 * Utility class for shipment functionality.
 *
 * @since 2.1.0-dev.1
 */
class Shipment {


	/** @var mixed key-value array of valid carriers Facebook codes and labels */
	protected $valid_carriers;

	/** @var mixed mapping of carriers from the Shipment Tracking plugin to their Facebook code */
	protected $shipment_tracking_carriers;


}
