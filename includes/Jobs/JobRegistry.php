<?php

namespace SkyVerge\WooCommerce\Facebook\Jobs;

use Automattic\WooCommerce\ActionSchedulerJobFramework\Proxies\ActionScheduler;
use SkyVerge\WooCommerce\Facebook\Feed\FeedFileHandler;
use SkyVerge\WooCommerce\Facebook\Feed\FeedDataExporter;


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
	 * @var CleanupSkyvergeFrameworkJobOptions
	 */
	public $cleanup_skyverge_job_options;

	/**
	 * Instantiate and init all jobs for the plugin.
	 */
	public function init() {
		$action_scheduler_proxy          = new ActionScheduler();
		$feed_file_handler               = new FeedFileHandler();
		$feed_data_exporter              = new FeedDataExporter();
		$this->generate_product_feed_job = new GenerateProductFeed( $action_scheduler_proxy, $feed_file_handler, $feed_data_exporter );
		$this->generate_product_feed_job->init();

		$this->cleanup_skyverge_job_options = new CleanupSkyvergeFrameworkJobOptions();
		$this->cleanup_skyverge_job_options->init();
	}

}
