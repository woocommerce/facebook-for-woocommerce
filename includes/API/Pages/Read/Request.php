<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Pages\Read;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API;

/**
 * Page API request object.
 *
 * @since 2.0.0
 */
class Request extends API\Request {
	/**
	 * API request constructor.
	 *
	 * @param string $page_id Facebook Page ID.
	 */
	public function __construct( $page_id ) {
		parent::__construct( "/{$page_id}/?fields=name,link", 'GET' );
	}
}
