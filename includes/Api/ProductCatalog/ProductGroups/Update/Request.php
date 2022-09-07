<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\ProductCatalog\ProductGroups\Update;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Product Catalog > Product Groups > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_groups/
 */
class Request extends ApiRequest {

	/**
	 * @param string $product_group_id Facebook Product Group ID.
	 * @param array  $data Facebook Product Group Data.
	 */
	public function __construct( string $product_group_id, array $data ) {
		parent::__construct( "/{$product_group_id}", 'POST' );
		parent::set_data( $data );
	}
}
