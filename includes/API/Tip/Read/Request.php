<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Tip\Read;

use WooCommerce\Facebook\API\Request as ApiRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Request object for Tip > Read Graph Api.
 */
class Request extends ApiRequest {

	/**
	 * @param string $external_merchant_settings_id External Merchant e.g. Shopify, etc.
	 */
	public function __construct( string $external_merchant_settings_id ) {
		parent::__construct( "/{$external_merchant_settings_id}/?fields=connect_woo", 'GET' );
	}
}
