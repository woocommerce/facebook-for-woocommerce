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
	const SYNC_ENABLED_META_KEY = '_wc_facebook_sync_enabled';


	/** @var array memoized array of sync enabled status for products */
	private static $products_sync_enabled = [];


	/**
	 * Sets the sync handling for products to enabled or disabled.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product[] $products array of product objects
	 * @param bool $enabled whether sync should be enabled for $products
	 */
	private static function set_sync_for_products( array $products, $enabled ) {

		self::$products_sync_enabled = [];

		$enabled = wc_bool_to_string( $enabled );

		foreach ( $products as $product ) {

			if ( $product instanceof \WC_Product ) {

				if ( $product->is_type( 'variable' ) ) {

					foreach ( $product->get_children() as $variation ) {

						$product_variation = wc_get_product( $variation );

						if ( $product_variation instanceof \WC_Product ) {

							$product_variation->update_meta_data( self::SYNC_ENABLED_META_KEY, $enabled );
							$product_variation->save_meta_data();
						}
					}

				} else {

					$product->update_meta_data( self::SYNC_ENABLED_META_KEY, $enabled );
					$product->save_meta_data();
				}
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

		self::set_sync_for_products( $products, true );
	}


	/**
	 * Disables sync for given products.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product[] $products an array of product objects
	 */
	public static function disable_sync_for_products( array $products ) {

		self::set_sync_for_products( $products, false );
	}


	/**
	 * Determines whether the given product should be synced.
	 *
	 * If a product is enabled for sync, but belongs to an excluded term, it will return as excluded from sync:
	 * @see Products::is_sync_enabled_for_product()
	 * @see Products::is_sync_excluded_for_product_terms()
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product $product
	 * @return bool
	 */
	public static function product_should_be_synced( \WC_Product $product ) {

		// define the product to check terms on
		$terms_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;

		return self::is_sync_enabled_for_product( $product ) && $terms_product && ! self::is_sync_excluded_for_product_terms( $terms_product );
	}


	/**
	 * Determines whether a product is enabled to be synced in Facebook.
	 *
	 * If the product is not explicitly set to disable sync, it'll be considered enabled.
	 * This applies to products that may not have the meta value set.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product $product product object
	 * @return bool
	 */
	public static function is_sync_enabled_for_product( \WC_Product $product ) {

		if ( ! isset( self::$products_sync_enabled[ $product->get_id() ] ) ) {

			if ( $product->is_type( 'variable' ) ) {

				// assume variable products are not synced until a synced child is found
				$enabled = false;

				foreach ( $product->get_children() as $child_id ) {

					if ( $child_product = wc_get_product( $child_id ) ) {

						if ( self::is_sync_enabled_for_product( $child_product ) ) {
							$enabled = true;
							break;
						}
					}
				}

			} else {

				$enabled = 'no' !== $product->get_meta( self::SYNC_ENABLED_META_KEY );
			}

			self::$products_sync_enabled[ $product->get_id() ] = $enabled;
		}

		return self::$products_sync_enabled[ $product->get_id() ];
	}


	/**
	 * Determines whether the product's terms would make it excluded to be synced from Facebook.
	 *
	 * @since x.y.z
	 *
	 * @param \WC_Product $product product object
	 * @return bool if true, product should be excluded from sync, if false, product can be included in sync (unless manually excluded by individual product meta)
	 */
	public static function is_sync_excluded_for_product_terms( \WC_Product $product ) {

		if ( $integration = facebook_for_woocommerce()->get_integration() ) {
			$excluded_categories = $integration->get_excluded_product_category_ids();
			$excluded_tags       = $integration->get_excluded_product_tag_ids();
		} else {
			$excluded_categories = $excluded_tags = [];
		}

		$categories = $product->get_category_ids();
		$tags       = $product->get_tag_ids();

		// returns true if no terms on the product, or no terms excluded, or if the product does not contain any of the excluded terms
		$matches =    ( ! $categories || ! $excluded_categories || ! array_intersect( $categories, $excluded_categories ) )
		           && ( ! $tags       || ! $excluded_tags       || ! array_intersect( $tags, $excluded_tags ) );

		return ! $matches;
	}


}
