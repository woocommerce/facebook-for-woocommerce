<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\Api\Pages\Read;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\Api;

/**
 * Page API response object.
 *
 * @since 2.0.0
 * @property-read string $name Facebook Page Name.
 * @property-read string $link Facebook Page URL.
 */
class Response extends Api\Response {}
