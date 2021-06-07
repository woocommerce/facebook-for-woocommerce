<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

use Exception;

/**
 * Class FeedInactiveException
 *
 * Exception for when the feed is configured correctly but for some reason is not active.
 * In some scenarios when there were errors the feed gets stopped by Facebook.
 * This requires manual triggering the feed in the Facebook Catalog Datasource UI.
 */
class FeedInactiveException extends Exception {}
