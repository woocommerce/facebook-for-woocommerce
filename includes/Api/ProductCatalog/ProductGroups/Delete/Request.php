<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductGroups\Delete;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Product Catalog > Product Groups > Delete Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_groups/
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_group_id Facebook Product Group ID.
	 */
	public function __construct( string $product_group_id ) {
		parent::__construct( "/{$product_group_id}?deletion_method=delete_items", 'DELETE' );
	}
}
