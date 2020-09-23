<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\Utilities;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;


/**
 * Background job handler to remove duplicate fb_visibility entries from the postmeta table.
 *
 * The background job handler to hide virtual products from the catalog had a bug that allowed it to create many entries for each product.
 *
 * @since 2.0.2-dev.1
 */
class Background_Remove_Duplicate_Visibility_Meta extends Framework\SV_WP_Background_Job_Handler {


	/**
	 * Background job constructor.
	 *
	 * @since 2.0.2-dev.1
	 */
	public function __construct() {

		$this->prefix = 'wc_facebook';
		$this->action = 'background_remove_duplicate_visibility_meta';

		parent::__construct();
	}


	/**
	 * No-op
	 *
	 * @since 2.0.2-dev.1
	 */
	protected function process_item( $item, $job ) {
		// void
	}


}
