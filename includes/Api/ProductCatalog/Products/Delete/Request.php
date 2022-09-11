<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\ProductCatalog\Products\Delete;

use WooCommerce\Facebook\Api\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Product Catalog > Products > Delete Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-item/#Deleting
 */
class Request extends ApiRequest {

	/**
	 * @param string $facebook_product_id Facebook Product Group ID.
	 */
	public function __construct( string $facebook_product_id ) {
		parent::__construct( "/{$facebook_product_id}", 'DELETE' );
	}
}
