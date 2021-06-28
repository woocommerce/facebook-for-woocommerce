<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Request object for the Send Item Updates API request.
 *
 * @since 2.0.0
 */
class Request extends API\Request {


	/** @var array an array of item update requests */
	protected $requests = array();

	/** @var bool determines whether updates for products that are not currently in the catalog should create new items */
	protected $allow_upsert = true;


	/**
	 * Gets the rate limit ID.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public static function get_rate_limit_id() {

		return 'ads_management';
	}


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $catalog_id catalog ID
	 */
	public function __construct( $catalog_id ) {

		// Switching this out to make sure everything continues to work
		// parent::__construct( "/{$catalog_id}/batch", 'POST' );
		parent::__construct( "/{$catalog_id}/items_batch", 'POST' );
	}


	/**
	 * Sets the array of item update requests.
	 *
	 * @since 2.0.0
	 *
	 * @param array $requests item update requests
	 */
	public function set_requests( array $requests ) {

		$this->requests = $requests;
	}


	/**
	 * Gets the array of item update requests.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_requests() {

		return $this->requests;
	}


	/**
	 * Sets whether updates for products that are not currently in the catalog should create new items.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $allow_upsert whether to allow updates to insert new items
	 */
	public function set_allow_upsert( $allow_upsert ) {

		$this->allow_upsert = (bool) $allow_upsert;
	}


	/**
	 * Gets whether updates for products that are not currently in the catalog should create new items.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function get_allow_upsert() {

		return $this->allow_upsert;
	}


	/**
	 * Gets the request data.
	 *
	 * @since 2.0.0
	 */
	public function get_data() {
		// TODO: Make it so the item type is based on the actual item type

		return array(
			'allow_upsert' => $this->get_allow_upsert(),
			'requests'     => $this->get_requests(),
			'item_type'    => 'PRODUCT_ITEM',
		);
	}


}
