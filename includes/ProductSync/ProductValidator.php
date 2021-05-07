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
	 * @var WC_Product The product object to validate.
	 */
	protected $product;

	/**
	 * @var WC_Product The product parent object if the product has a parent.
	 */
	protected $product_parent;

	/**
	 * ProductValidator constructor.
	 *
	 * @param WC_Facebookcommerce_Integration $integration
	 * @param WC_Product                      $product The product to validate. Accepts both variations and variable products.
	 */
	public function __construct( WC_Facebookcommerce_Integration $integration, WC_Product $product ) {
		$this->product = $product;

		if ( $product->get_parent_id() ) {
			$parent_product = wc_get_product( $product->get_parent_id() );
			if ( $parent_product instanceof WC_Product ) {
				$this->product_parent = $parent_product;
			}
		}

		$this->integration = $integration;
	}

	/**
	 * Validate whether a given product should be synced to Facebook.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate() {
		$this->validate_sync_enabled_globally();
		$this->validate_product_status();
		$this->validate_product_stock_status();
		$this->validate_product_sync_field();
		$this->validate_product_price();
		$this->validate_product_visibility();
		$this->validate_product_categories_and_tags();
	}

	/**
	 * Validate whether a given product should be synced to Facebook but skip the status check for backwards compatibility.
	 *
	 * @internal Do not use this as it will likely be removed.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate_but_skip_status_check() {
		$this->validate_sync_enabled_globally();
		$this->validate_product_stock_status();
		$this->validate_product_sync_field();
		$this->validate_product_price();
		$this->validate_product_visibility();
		$this->validate_product_categories_and_tags();
	}

	/**
	 * Check if a product has excluded categories or tags.
	 *
	 * @return bool True if it should be excluded.
	 */
	public function is_excluded_by_category_or_tag(): bool {
		try {
			$this->validate_product_categories_and_tags();
		} catch ( ProductExcludedException $e ) {
			// Product failed category and tag validation so it's excluded
			return true;
		}

		return false;
	}

	/**
	 * Check if the product is excluded by the product sync field value.
	 *
	 * @return bool True if it should be excluded.
	 */
	public function is_excluded_by_product_sync_field(): bool {
		try {
			$this->validate_product_sync_field();
		} catch ( ProductExcludedException $e ) {
			// Product failed validation so it's excluded
			return true;
		}

		return false;
	}

	/**
	 * Check whether product sync is globally disabled.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_sync_enabled_globally() {
		if ( $this->integration->is_product_sync_enabled() ) {
			throw new ProductExcludedException( 'Product sync is globally disabled.' );
		}
	}

	/**
	 * Check whether the product's status excludes it from sync.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_status() {
		$product = $this->product_parent ?: $this->product;

		if ( 'publish' !== $product->get_status() ) {
			throw new ProductExcludedException( 'Product is not published.' );
		}
	}

	/**
	 * Check whether the product should be excluded due to being out of stock.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_stock_status() {
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $this->product->is_in_stock() ) {
			throw new ProductExcludedException( 'Product must be in stock.' );
		}
	}

	/**
	 * Check whether the product's visibility excludes it from sync.
	 *
	 * Products are excluded if they are hidden from the store catalog or from search results.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_visibility() {
		$product = $this->product_parent ?: $this->product;

		if ( 'visible' !== $product->get_catalog_visibility() ) {
			throw new ProductExcludedException( 'Product is hidden from catalog and search.' );
		}
	}

	/**
	 * Check whether the product's categories or tags exclude it from sync.
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate_product_categories_and_tags() {
		$product = $this->product_parent ?: $this->product;

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
	 * @throws ProductExcludedException If product should not be synced.
	 */
	public function validate_product_sync_field() {
		$invalid_exception = new ProductExcludedException( 'Sync disabled in product field.' );

		if ( $this->product->is_type( 'variable' ) ) {
			foreach ( $this->product->get_children() as $child_id ) {
				$child_product = wc_get_product( $child_id );
				if ( $child_product && 'no' !== $child_product->get_meta( self::SYNC_ENABLED_META_KEY ) ) {
					break;
				}
			}

			// Variable product has no variations with sync enabled so it shouldn't be synced.
			throw $invalid_exception;
		} else {
			if ( 'no' === $this->product->get_meta( self::SYNC_ENABLED_META_KEY ) ) {
				throw $invalid_exception;
			}
		}
	}

	/**
	 * "allow simple or variable products (and their variations) with zero or empty price - exclude other product types with zero or empty price"
	 * unsure why but that's what we're doing
	 *
	 * @throws ProductExcludedException If product should not be synced.
	 */
	protected function validate_product_price() {
		// Permit simple and variable products to have an empty price
		if ( in_array( $this->product_parent->get_type(), array( 'simple', 'variable' ), true ) ) {
			return;
		}

		if ( ! Products::get_product_price( $this->product ) ) {
			throw new ProductExcludedException( 'If product is not simple, variable or variation it must have a price.' );
		}
	}

}
