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

use SkyVerge\WooCommerce\Facebook\Products;

/**
 * The product stock handler.
 *
 * @since 2.0.0
 */
class Stock {


	/**
	 * Schedules a product sync to update its stock status.
	 *
	 * The product is removed from Facebook if it is out of stock and the plugin is configured to remove out of stock products form the catalog.
	 *
	 * @since 2.0.4-dev.1
	 *
	 * @param \WC_Product $product a product object
	 */
	private function maybe_sync_product_stock_status( \WC_Product $product ) {

		if ( Products::product_should_be_deleted( $product ) ) {

			facebook_for_woocommerce()->get_products_sync_handler()->delete_products( [ \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ) ] );
			return;
		}

		facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( [ $product->get_id() ] );
	}


}
