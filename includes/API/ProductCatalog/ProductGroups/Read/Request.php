<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\ProductCatalog\ProductGroups\Read;

use WooCommerce\Facebook\API\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Product Catalog > Product Groups > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_groups/#Reading
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_group_id Facebook Product Group ID.
	 * @param int    $limit Limit.
	 */
	public function __construct( string $product_group_id, int $limit ) {
		parent::__construct( "/{$product_group_id}/products?fields=id,retailer_id&limit={$limit}", 'GET' );
	}
}
