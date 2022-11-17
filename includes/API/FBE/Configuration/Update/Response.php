<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\FBE\Configuration\Update;

use WooCommerce\Facebook\Api\Response as ApiResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Response object for Product Catalog > Product Groups > Update Graph Api.
 *
 * @link https://developers.facebook.com/docs/marketing-api/reference/product-group/#Updating
 * @property-read bool $success Either request was successful or not.
 */
class Response extends ApiResponse {}
