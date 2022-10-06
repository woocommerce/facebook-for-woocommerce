<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\ProductCatalog\ItemsBatch\Create;

use WooCommerce\Facebook\Api\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Product Catalog > Items Batch > Create Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-catalog/items_batch/
 * @property-read string[] handles Either request was successful or not.
 * @property-read array validation_status
 */
class Response extends ApiResponse {}
