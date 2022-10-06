<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductGroups\Read;

use WooCommerce\Facebook\Api\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Product Catalog > Product Groups > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-group/#Updating
 * @property-read array data A list of ProductGroup nodes.
 * @property-read array paging Paging cursors and next data link.
 * @property-read array summary Aggregated information about the edge, such as counts. Specify the fields to fetch in the summary param (like summary=total_count).
 */
class Response extends ApiResponse {

	/**
	 * Gets the Product Item IDs indexed by the retailer ID.
	 *
	 * @return string[]
	 */
	public function get_ids(): array {
		$product_item_ids = [];
		foreach ( $this->data as $entry ) {
			$product_item_ids[ $entry['retailer_id'] ] = $entry['id'];
		}
		return $product_item_ids;
	}
}
