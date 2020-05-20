<?php
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
 * Base API request object.
 *
 * @since 2.0.0-dev.1
 */
class Request extends API\Request  {


	/** @var array an arary of item update requests */
	protected $requests = [];

	/** @var bool determines whether updates for products that are not currently in the catalog should create new items */
	protected $allow_upsert = true;


	/**
	 * Gets the ID of this request for rate limiting purposes.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public static function get_rate_limit_id() {

		return 'ads_management_api_request';
	}


	/**
	 * API request constructor.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $catalog_id catalog ID
	 */
	public function __construct( $catalog_id ) {

		parent::__construct( $catalog_id, '/batch', 'POST' );
	}


	/**
	 * Sets the array of item update requests.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $requests item update requests
	 */
	public function set_requests( array $requests ) {

		$this->requests = $requests;
	}


	/**
	 * Sets whether updates for products that are not currently in the catalog should create new items.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $data
	 */
	public function set_allow_upsert( $allow_upsert ) {

		$this->allow_upsert = (bool) $allow_upsert;
	}


}
