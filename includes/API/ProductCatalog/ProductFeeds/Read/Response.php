<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\ProductCatalog\ProductFeeds\Read;

use WooCommerce\Facebook\API\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Product Catalog > Product Feeds > Read Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-feed/#Reading
 * @property-read array $data Facebook Product Feeds.
 */
class Response extends ApiResponse {}
