<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\Catalog;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API\Response as ApiResponse;

/**
 * Catalog API response object
 *
 * @property-read string id   Facebook Catalog ID.
 * @property-read string name Facebook Catalog Name.
 */
class Response extends ApiResponse {}
