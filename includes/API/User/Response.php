<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\User;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API;

/**
 * User API response object
 *
 * @property-read string id Facebook User ID.
 * @property-read string name Facebook User Name.
 */
class Response extends API\Response {}
