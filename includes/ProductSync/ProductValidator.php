<?php

namespace SkyVerge\WooCommerce\Facebook\ProductSync;

use WC_Product;

/**
 * Class ProductValidator
 *
 * This class is responsible for validating whether a product should be synced to Facebook.
 *
 * @since 2.5.0
 */
class ProductValidator {

	/**
	 * @param WC_Product $product
	 *
	 * @return bool
	 */
	public function is_valid_for_sync( WC_Product $product ): bool {

	}

}
