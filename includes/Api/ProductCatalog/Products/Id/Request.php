<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\ProductCatalog\Products\Id;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Product Catalog > Product Groups > Products > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-group/products/
 */
class Request extends ApiRequest {

	/**
	 * @param string $facebook_product_catalog_id Facebook Product Catalog ID.
	 * @param string $facebook_product_retailer_id Facebook Product Retailer ID.
	 */
	public function __construct( string $facebook_product_catalog_id, string $facebook_product_retailer_id ) {
		$path = "catalog:{$facebook_product_catalog_id}:" . base64_encode( $facebook_product_retailer_id );
		parent::__construct( "/{$path}/?fields=id,product_group{id}", 'GET' );
	}
}
