<?php

namespace SkyVerge\WooCommerce\Facebook\ProductSync;

use SkyVerge\WooCommerce\Facebook\Products;
use WC_Product;
use WC_Facebookcommerce_Integration;

/**
 * Class ProductValidator
 *
 * This class is responsible for validating whether a product should be synced to Facebook.
 *
 * @since 2.5.0
 */
class ProductValidator {

	/**
	 * @var string the meta key used to flag whether a product should be synced in Facebook
	 */
	const SYNC_ENABLED_META_KEY = '_wc_facebook_sync_enabled';

	/**
	 * @var WC_Facebookcommerce_Integration
	 */
	protected $integration;

	/**
	 * ProductValidator constructor.
	 *
	 * @param WC_Facebookcommerce_Integration $integration
	 */
	public function __construct( WC_Facebookcommerce_Integration $integration ) {
		$this->integration = $integration;
	}

	/**
	 * Validate whether a given product should be synced to Facebook.
	 *
	 * @param WC_Product $product
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate( WC_Product $product ) {
		if ( $this->integration->is_product_sync_enabled() ) {
			throw new ProductExcludedException( 'Product sync is globally disabled.' );
		}

		$this->validate_product_status( $product );
		$this->validate_product_stock_status( $product );
		$this->validate_product_sync_field( $product );
		$this->validate_product_price( $product );
		$this->validate_product_visibility( $product );
		$this->validate_product_categories_and_tags( $product );
	}

	/**
	 * Check whether the product's status excludes it from sync.
	 *
	 * @param WC_Product $product A product object.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_status( WC_Product $product ) {
		$product = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;

		if ( 'publish' !== $product->get_status() ) {
			throw new ProductExcludedException( 'Product is not published.' );
		}
	}

	/**
	 * Check whether the product should be excluded due to being out of stock.
	 *
	 * @param WC_Product $product A product object.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_stock_status( WC_Product $product ) {
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->is_in_stock() ) {
			throw new ProductExcludedException( 'Product must be in stock.' );
		}
	}

	/**
	 * Check whether the product's visibility excludes it from sync.
	 *
	 * Products are excluded if they are hidden from the store catalog or from search results.
	 *
	 * @param WC_Product $product A product object.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_visibility( WC_Product $product ) {
		$product = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;

		if ( 'visible' !== $product->get_catalog_visibility() ) {
			throw new ProductExcludedException( 'Product is hidden from catalog and search.' );
		}
	}

	/**
	 * Check whether the product's categories or tags exclude it from sync.
	 *
	 * @param WC_Product $product A product object.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate_product_categories_and_tags( WC_Product $product ) {
		$product = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;

		$excluded_categories = $this->integration->get_excluded_product_category_ids();
		if ( $excluded_categories ) {
			if ( ! empty( array_intersect( $product->get_category_ids(), $excluded_categories ) ) ) {
				throw new ProductExcludedException( 'Product excluded because of categories.' );
			}
		}

		$excluded_tags = $this->integration->get_excluded_product_tag_ids();
		if ( $excluded_tags ) {
			if ( ! empty( array_intersect( $product->get_tag_ids(), $excluded_tags ) ) ) {
				throw new ProductExcludedException( 'Product excluded because of tags.' );
			}
		}
	}

	/**
	 * Validate if the product is excluded from at the "product level" (product meta value).
	 *
	 * @param WC_Product $product A product object.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate_product_sync_field( WC_Product $product ) {
		$invalid_exception = new ProductExcludedException( 'Sync disabled in product field.' );

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$child_product = wc_get_product( $child_id );
				if ( $child_product && 'no' !== $child_product->get_meta( self::SYNC_ENABLED_META_KEY ) ) {
					break;
				}
			}

			// Variable product has no variations with sync enabled so it shouldn't be synced.
			throw $invalid_exception;
		} else {
			if ( 'no' === $product->get_meta( self::SYNC_ENABLED_META_KEY ) ) {
				throw $invalid_exception;
			}
		}
	}

	/**
	 * "allow simple or variable products (and their variations) with zero or empty price - exclude other product types with zero or empty price"
	 * unsure why but that's what we're doing
	 *
	 * @param WC_Product $product A product object.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_price( WC_Product $product ) {
		$parent_product = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;

		// Permit simple and variable products to have an empty price
		if ( in_array( $parent_product->get_type(), array( 'simple', 'variable' ), true ) ) {
			return;
		}

		if ( ! Products::get_product_price( $product ) ) {
			throw new ProductExcludedException( 'If product is not simple, variable or variation it must have a price.' );
		}
	}

}
