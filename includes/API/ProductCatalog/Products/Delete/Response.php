<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\ProductCatalog\Products\Delete;

use WooCommerce\Facebook\API\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Product Catalog > Product Groups > Delete Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-group/#Deleting
 * @property-read bool $success
 */
class Response extends ApiResponse {}
