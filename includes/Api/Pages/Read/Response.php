<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\Api\Pages\Read;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\Api;

/**
 * Page API response object.
 *
 * @since 2.0.0
 * @property-read string $name Facebook Page Name.
 * @property-read string $link Facebook Page URL.
 */
class Response extends Api\Response {}
