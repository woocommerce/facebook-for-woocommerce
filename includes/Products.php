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
 * Products handler.
 */
class Products {


	/** @var string the meta key used to flag whether a product should be synced in Facebook */
	private static $sync_meta_key = '_wc_facebook_sync';


	/**
	 * Enables sync for given products.
	 *
	 * @param \WC_Products[] $products an array of product objects
	 */
	public static function enable_sync_for_products( array $products ) {

		foreach ( $products as $product ) {

			if ( $product instanceof \WC_Product ) {

				$product->update_meta_data( self::$sync_meta_key, 'yes' );
				$product->save_meta_data();
			}
		}
	}


	/**
	 * Disables sync for given products.
	 *
	 * @param \WC_Products[] $products an array of product objects
	 */
	public static function disable_sync_for_products( array $products ) {

		foreach ( $products as $product ) {

			if ( $product instanceof \WC_Product ) {

				$product->update_meta_data( self::$sync_meta_key, 'no' );
				$product->save_meta_data();
			}
		}
	}


}
