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
 *
 * @since x.y.z
 */
class Products {


	/** @var string the meta key used to flag whether a product should be synced in Facebook */
	const SYNC_META_KEY = '_wc_facebook_sync';


	/**
	 * Sets the sync handling for products to enabled or disabled.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product[] $products array of product objects
	 * @param string $handling either 'yes' (enable) or 'no' (disable)
	 */
	private static function toggle_sync_for_products( array $products, $handling ) {

		foreach ( $products as $product ) {

			if ( $product instanceof \WC_Product ) {

				$product->update_meta_data( self::SYNC_META_KEY, $handling );
				$product->save_meta_data();
			}
		}
	}


	/**
	 * Enables sync for given products.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product[] $products an array of product objects
	 */
	public static function enable_sync_for_products( array $products ) {

		self::toggle_sync_for_products( $products, 'yes' );
	}


	/**
	 * Disables sync for given products.
	 *
<<<<<<< HEAD
	 * @since x.y.z
	 *
	 * @param \WC_Product[] $products an array of product objects
	 */
	public static function disable_sync_for_products( array $products ) {

		self::toggle_sync_for_products( $products, 'no' );
	}


	/**
	 * Determines whether a product is set to be synced in Facebook.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product $product product object
	 * @return bool
	 */
	public static function is_sync_enabled_for_product( \WC_Product $product ) {

		return 'yes' === $product->get_meta( self::SYNC_META_KEY );
	}


}
