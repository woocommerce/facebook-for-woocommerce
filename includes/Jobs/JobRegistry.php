<?php

namespace SkyVerge\WooCommerce\Facebook\Jobs;

use Automattic\WooCommerce\ActionSchedulerJobFramework\Proxies\ActionScheduler;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobRegistry
 *
 * @since 2.5.0
 */
class JobRegistry {

	/**
	 * @var GenerateProductFeed
	 */
	public $generate_product_feed_job;

	/**
	 * Instantiate and init all jobs for the plugin.
	 */
	public function init() {
		$action_scheduler_proxy = new ActionScheduler();

		$this->generate_product_feed_job = new GenerateProductFeed( $action_scheduler_proxy );
		$this->generate_product_feed_job->init();
	}

}
