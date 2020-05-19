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


	/** @var string the prefix used in the array indexes */
	const PRODUCT_INDEX_PREFIX = 'p-';

	/** @var string the update action */
	const ACTION_UPDATE = 'UPDATE';

	/** @var string the delete action */
	const ACTION_DELETE = 'DELETE';

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

		foreach ( $product_ids as $product_id ) {
			$this->requests[ $this->get_product_index( $product_id ) ] = self::ACTION_UPDATE;
		}
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


	/**
	 * Gets the prefixed product ID used as the array index.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param $product_id
	 * @return string
	 */
	private function get_product_index( $product_id ) {

		return self::PRODUCT_INDEX_PREFIX . $product_id;
	}


}
