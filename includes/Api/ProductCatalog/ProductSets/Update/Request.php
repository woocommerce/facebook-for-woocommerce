<?php
/**
 *
 */

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductSets\Update;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) or exit;

/**
 * Request object for Product Catalog > Product Sets > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_sets/
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_set_id Facebook Product Set ID.
	 * @param array  $data Facebook Product Set Data.
	 */
	public function __construct( string $product_set_id, array $data ) {
		parent::__construct( "/{$product_set_id}", 'POST' );
		parent::set_data( $data );
	}
}
