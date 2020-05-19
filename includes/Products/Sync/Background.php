<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Products\Sync;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The background sync handler.
 *
 * @since 2.0.0-dev.1
 */
class Background extends Framework\SV_WP_Background_Job_Handler {


	/** @var string async request prefix */
	protected $prefix = 'wc_facebook';

	/** @var string async request action */
	protected $action = 'background_product_sync';

	/** @var string data key */
	protected $data_key = 'requests';


	/**
	 * Processes a job.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \stdClass|object $job
	 * @param int $items_per_batch number of items to process in a single request. Defaults to unlimited.
	 * @throws \Exception when job data is incorrect
	 * @return \stdClass $job
	 */
	public function process_job( $job, $items_per_batch = null )
	{
		// TODO
	}


	/**
	 * Processes multiple items.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \stdClass|object $job
	 * @param array $data
	 * @param int $items_per_batch number of items to process in a single request. Defaults to unlimited.
	 */
	public function process_items( $job, $data, $items_per_batch = null ) {
		// TODO
	}


	/**
	 * Processes a single item.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param mixed $item
	 * @param object|\stdClass $job
	 * @return array
	 */
	public function process_item($item, $job) {
		// TODO
	}


	/**
	 * Sends item updates to Facebook.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param $requests
	 */
	public function send_item_updates( $requests ) {
		// TODO
	}


}
