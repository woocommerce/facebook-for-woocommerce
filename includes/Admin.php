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
 * Admin handler.
 */
class Admin {


	/**
	 * Admin constructor.
	 */
	public function __construct() {

		add_action( 'manage_product_posts_columns', [ $this, 'add_product_list_table_column' ] );
	}


	/**
	 * Adds a column for Facebook Sync in the products edit screen.
	 *
	 * @param array $columns array of keys and labels
	 * @return array
	 */
	public function add_product_list_table_column( $columns ) {

		$columns['facebook'] = __( 'FB Sync Status', 'facebook-for-woocommerce' );

		return $columns;
	}


}
