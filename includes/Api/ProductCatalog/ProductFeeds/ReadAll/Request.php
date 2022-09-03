<?php
/**
 *
 */

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductFeeds\ReadAll;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) or exit;

/**
 * Request object for Product Catalog > Product Feeds > Read Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_feeds/v13.0#Reading
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_catalog_id Facebook Product Catalog ID.
	 */
	public function __construct( string $product_catalog_id ) {
		parent::__construct( "/{$product_catalog_id}/product_feeds", 'GET' );
	}
}
