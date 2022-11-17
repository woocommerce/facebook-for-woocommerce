<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\ProductCatalog\Products\Create;

use WooCommerce\Facebook\API\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Product Catalog > Product Groups > Products > Create Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-group/products/#Creating
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_group_id Facebook Product Group ID.
	 * @param array  $data Facebook Product Data.
	 */
	public function __construct( string $product_group_id, array $data ) {
		parent::__construct( "/{$product_group_id}/products", 'POST' );
		parent::set_data( $data );
	}
}
