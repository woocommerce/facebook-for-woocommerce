<?php

namespace WooCommerce\Facebook\ProductSync;

use Exception;

/**
 * Class ProductInvalidException
 *
 * Exception for when a product configuration is not correct in terms of Facebook product sync.
 * There are limitations that will exclude product from Facebook catalog. We want to inform
 * user as early as possible.
 */
class ProductInvalidException extends Exception {}
