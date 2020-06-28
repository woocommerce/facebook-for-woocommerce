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

use WC_Facebook_Product;

defined( 'ABSPATH' ) or exit;

/**
 * Products handler.
 *
 * @since 1.10.0
 */
class Products {


	/** @var string the meta key used to flag whether a product should be synced in Facebook */
	const SYNC_ENABLED_META_KEY = '_wc_facebook_sync_enabled';

	// TODO probably we'll want to run some upgrade routine or somehow move meta keys to follow the same patter e.g. _wc_facebook_visibility {FN 2020-01-17}
	/** @var string the meta key used to flag whether a product should be visible in Facebook */
	const VISIBILITY_META_KEY = 'fb_visibility';

	/** @var string the meta key used to the source of the product  in Facebook */
	const PRODUCT_IMAGE_SOURCE_META_KEY = '_wc_facebook_product_image_source';

	/** @var string product image source option to use the product image of simple products or the variation image of variations in Facebook */
	const PRODUCT_IMAGE_SOURCE_PRODUCT = 'product';

	/** @var string product image source option to use the parent product image in Facebook */
	const PRODUCT_IMAGE_SOURCE_PARENT_PRODUCT = 'parent_product';

	/** @var string product image source option to use the parent product image in Facebook */
	const PRODUCT_IMAGE_SOURCE_CUSTOM = 'custom';


	/** @var array memoized array of sync enabled status for products */
	private static $products_sync_enabled = [];

	/** @var array memoized array of visibility status for products */
	private static $products_visibility = [];

	/** @var array memoized array of prices for products */
	private static $products_price = [];

	/**
	 * Sets the sync handling for products to enabled or disabled.
	 *
	 * @since 1.10.0
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
	 * @since 1.10.0
	 *
	 * @param \WC_Product[] $products an array of product objects
	 */
	public static function enable_sync_for_products( array $products ) {

		self::set_sync_for_products( $products, true );
	}


	/**
	 * Disables sync for given products.
	 *
	 * @since 1.10.0
	 *
	 * @param \WC_Product[] $products an array of product objects
	 */
	public static function disable_sync_for_products( array $products ) {

		self::set_sync_for_products( $products, false );
	}


	/**
	 * Disables sync for products that belong to the given category or tag.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args {
	 *     @type string|array $taxonomy product_cat or product_tag
	 *     @type string|array $include array or comma/space-separated string of term IDs to include
	 * }
	 */
	public static function disable_sync_for_products_with_terms( array $args ) {

		$args = wp_parse_args( $args, [
			'taxonomy' => 'product_cat',
			'include'  => [],
		] );

		$products = [];

		// get all products belonging to the given terms
		if ( is_array( $args['include'] ) && ! empty( $args['include'] ) ) {

			$terms = get_terms( [
				'taxonomy'   => $args['taxonomy'],
				'fields'     => 'slugs',
				'include'    => array_map( 'intval', $args['include'] ),
			] );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {

				$taxonomy = $args['taxonomy'] === 'product_tag' ? 'tag' : 'category';

				$products = wc_get_products( [
					$taxonomy => $terms,
					'limit'   => -1,
				] );
			}
		}

		if ( ! empty( $products ) ) {
			Products::disable_sync_for_products( $products );
		}
	}


	/**
	 * Determines whether the given product should be synced.
	 *
	 * @see Products::published_product_should_be_synced()
	 *
	 * @since 1.10.0
	 *
	 * @param \WC_Product $product
	 * @return bool
	 */
	public static function product_should_be_synced( \WC_Product $product ) {

		return 'publish' === $product->get_status() && self::published_product_should_be_synced( $product );
	}


	/**
	 * Determines whether the given product should be synced assuming the product is published.
	 *
	 * If a product is enabled for sync, but belongs to an excluded term, it will return as excluded from sync:
	 * @see Products::is_sync_enabled_for_product()
	 * @see Products::is_sync_excluded_for_product_terms()
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param \WC_Product $product
	 * @return bool
	 */
	public static function published_product_should_be_synced( \WC_Product $product ) {

		$should_sync = self::is_sync_enabled_for_product( $product );


		// define the product to check terms on
		if ( $should_sync ) {
			$terms_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;
		} else {
			$terms_product = null;
		}

		// allow simple or variable products (and their variations) with zero or empty price - exclude other product types with zero or empty price
		if ( $should_sync && ( ! $terms_product || ( ! self::get_product_price( $product ) && ! in_array( $terms_product->get_type(), [ 'simple', 'variable' ] ) ) ) ) {
			$should_sync = false;
		}

		// exclude products that are excluded from the store catalog or from search results
		if ( $should_sync && ( ! $terms_product || has_term( [ 'exclude-from-catalog', 'exclude-from-search' ], 'product_visibility', $terms_product->get_id() ) ) ) {
			$should_sync = false;
		}

		// exclude products that belong to one of the excluded terms
		if ( $should_sync && ( ! $terms_product || self::is_sync_excluded_for_product_terms( $terms_product ) ) ) {
			$should_sync = false;
		}

		return $should_sync;
	}


	/**
	 * Determines whether the given product should be removed from the catalog.
	 *
	 * A product should be removed if it is no longer in stock and the user has opted-in to hide products that are out of stock.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Product $product
	 * @return bool
	 */
	public static function product_should_be_deleted( \WC_Product $product ) {

		return 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->is_in_stock();
	}


	/**
	 * Determines whether a product is enabled to be synced in Facebook.
	 *
	 * If the product is not explicitly set to disable sync, it'll be considered enabled.
	 * This applies to products that may not have the meta value set.
	 *
	 * @since 1.10.0
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

					$child_product = wc_get_product( $child_id );

					if ( $child_product && self::is_sync_enabled_for_product( $child_product ) ) {

						$enabled = true;
						break;
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
	 * @since 1.10.0
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


	/**
	 * Sets a product's visibility in the Facebook shop.
	 *
	 * @since 1.10.0
	 *
	 * @param \WC_Product $product product object
	 * @param bool $visibility true for 'published' or false for 'staging'
	 * @return bool success
	 */
	public static function set_product_visibility( \WC_Product $product, $visibility ) {

		unset( self::$products_visibility[ $product->get_id() ] );

		if ( ! is_bool( $visibility ) ) {
			return false;
		}

		$product->update_meta_data( self::VISIBILITY_META_KEY, wc_bool_to_string( $visibility ) );
		$product->save_meta_data();

		self::$products_visibility[ $product->get_id() ] = $visibility;

		return true;
	}


	/**
	 * Checks whether a product should be visible on Facebook.
	 *
	 * @since 1.10.0
	 *
	 * @param \WC_Product $product
	 * @return bool
	 */
	public static function is_product_visible( \WC_Product $product ) {

		// accounts for a legacy bool value, current should be (string) 'yes' or (string) 'no'
		if ( ! isset( self::$products_visibility[ $product->get_id() ] ) ) {

			if ( $product->is_type( 'variable' ) ) {

				// assume variable products are not visible until a visible child is found
				$is_visible = false;

				foreach ( $product->get_children() as $child_id ) {

					$child_product = wc_get_product( $child_id );

					if ( $child_product && self::is_product_visible( $child_product ) ) {

						$is_visible = true;
						break;
					}
				}

			} elseif ( $meta = $product->get_meta( self::VISIBILITY_META_KEY ) ) {

				$is_visible = wc_string_to_bool( $product->get_meta( self::VISIBILITY_META_KEY ) );

			} else {

				$is_visible = true;
			}

			self::$products_visibility[ $product->get_id() ] = $is_visible;
		}

		return self::$products_visibility[ $product->get_id() ];
	}


	/**
	 * Gets the product price used for Facebook sync.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param int $price product price in cents
	 * @param \WC_Product $product product object
	 * @return int
	 */
	public static function get_product_price( \WC_Product $product ) {

		if ( ! isset( self::$products_price[ $product->get_id() ] ) ) {

			if ( is_numeric( $facebook_price = $product->get_meta( WC_Facebook_Product::FB_PRODUCT_PRICE ) ) ) {

				$price = $facebook_price;

			} elseif ( class_exists( 'WC_Product_Composite' ) && $product instanceof \WC_Product_Composite ) {

				$price = get_option( 'woocommerce_tax_display_shop' ) === 'incl' ? $product->get_composite_price_including_tax() : $product->get_composite_price();

			} elseif ( class_exists( 'WC_Product_Booking' ) && function_exists( 'is_wc_booking_product' ) && is_wc_booking_product( $product ) ) {

				$booking = new \WC_Product_Booking( $product );
				$price   = wc_get_price_to_display( $booking, [ 'price' => $booking->get_display_cost() ] );

			} else {

				$price = wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] );
			}

			self::$products_price[ $product->get_id() ] = (int) ( $price ? round( $price * 100 ) : 0 );
		}

		/**
		 * Filters the product price used for Facebook sync.
		 *
		 * @since 2.0.0-dev.1
		 *
		 * @param int $price product price in cents
		 * @param \WC_Product $product product object
		 */
		return (int) apply_filters( 'wc_facebook_product_price', self::$products_price[ $product->get_id() ], $product );
	}


}
