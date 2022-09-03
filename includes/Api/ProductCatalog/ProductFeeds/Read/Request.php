<?php
/**
 *
 */

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductFeeds\Read;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) or exit;

/**
 * Request object for Product Catalog > Product Feeds > Read Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-feed/#Reading
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_feed_id Facebook Product Feed ID.
	 */
	public function __construct( string $product_feed_id ) {
		parent::__construct( "/{$product_feed_id}/?fields=created_time,latest_upload,product_count,schedule,update_schedule", 'GET' );
	}
}
