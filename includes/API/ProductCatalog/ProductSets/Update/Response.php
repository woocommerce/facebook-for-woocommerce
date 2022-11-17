<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\ProductCatalog\ProductSets\Update;

use WooCommerce\Facebook\API\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Product Catalog > Product Sets > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/product_sets/
 * @property-read bool $success Either request was successful or not.
 */
class Response extends ApiResponse {}
