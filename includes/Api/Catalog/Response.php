<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\Catalog;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\Api\Response as ApiResponse;

/**
 * Catalog API response object
 *
 * @property-read string id   Facebook Catalog ID.
 * @property-read string name Facebook Catalog Name.
 */
class Response extends ApiResponse {}
