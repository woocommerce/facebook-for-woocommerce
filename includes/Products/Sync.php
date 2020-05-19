<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Products;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

/**
 * The product sync handler.
 *
 * @since 2.0.0-dev.1
 */
class Sync {


	/** @var array the array of requests to schedule for sync */
	protected $requests = [];


	/**
	 * Sync constructor.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function __construct() {

		$this->add_hooks();
	}


	/**
	 * Adds needed hooks to support product sync.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function add_hooks() {
		// TODO
	}


	/**
	 * Adds the given product IDs to the requests array to be updated.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $product_ids
	 */
	public function create_or_update_products( array $product_ids ) {
		// TODO
	}


	/**
	 * Adds the given product IDs to the requests array to be deleted.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $product_ids
	 */
	public function delete_products( array $product_ids ) {
		// TODO
	}


	/**
	 * Creates a background job to sync the products in the requests array.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function schedule_sync() {
		// TODO
	}
}
