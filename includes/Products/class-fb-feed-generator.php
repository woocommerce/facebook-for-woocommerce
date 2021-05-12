<?php
/**
 * Handles Facebook Product feed CSV file generation.
 *
 * @version 2.5.0
 */

namespace SkyVerge\WooCommerce\Facebook\Products;

use SkyVerge\WooCommerce\Facebook\Feed\FeedDataExporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FB_Feed_Generator Class.
 */
class FB_Feed_Generator {

	const FEED_SCHEDULE_ACTION    = 'wc_facebook_for_woocommerce_feed_schedule_action';
	const FEED_AJAX_GENERATE_FEED = 'facebook_for_woocommerce_do_ajax_feed';
	const FEED_GENERATION_NONCE   = 'wc_facebook_for_woocommerce_feed_generation_nonce';
	const FEED_FILE_INFO          = 'wc_facebook_for_woocommerce_feed_file_info';
	const REQUEST_FEED_ACTION     = 'facebook_for_woocommerce_get_feed';
	const OPTION_FEED_URL_SECRET  = 'wc_facebook_feed_url_secret';
	const FEED_NAME               = 'Facebook For WooCommerce Feed.';

	/**
	 * Should meta be exported?
	 *
	 * @var boolean
	 */
	protected $enable_meta_export = false;

	/**
	 * Products belonging to what category should be exported.
	 *
	 * @var string
	 */
	protected $product_category_to_export = array();

	// Refactor feed handler into this class;
	protected $feed_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->feed_handler = new \WC_Facebook_Product_Feed();
		add_action( 'admin_init', array( $this, 'maybe_schedule_feed_generation' ) );
		add_action( self::FEED_SCHEDULE_ACTION, array( $this, 'prepare_feed_generation' ) );
		add_action( 'wp_ajax_' . self::FEED_AJAX_GENERATE_FEED, array( $this, 'ajax_feed_handle' ) );

		//Just copied and not integrated yet.
		add_action( 'woocommerce_api_' . self::REQUEST_FEED_ACTION, array( $this, 'handle_feed_request' ) );
	}

	public function maybe_schedule_feed_generation() {
		if ( false !== as_next_scheduled_action( self::FEED_SCHEDULE_ACTION ) ) {
			return;
		}
		$timestamp = strtotime( 'today midnight +1 day' );
		as_schedule_single_action( $timestamp, self::FEED_SCHEDULE_ACTION );
	}

	public function prepare_feed_generation() {
		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$generate_feed_job->queue_start();
	}

	public function ajax_feed_handle() {
		if ( 'true' === $_POST['generate'] ) {
			$this->prepare_feed_generation();
		}

		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$processing_count  = FeedDataExporter::get_number_of_items_for_processing();
		$generate_feed_job = facebook_for_woocommerce()->job_registry->generate_product_feed_job;
		$processed         = $generate_feed_job->get_number_of_items_processed();
		$progress          = $processing_count ? intval( ( $processed / $processing_count ) * 100 ) : 0;

		$response = array(
			'done'       => ! $generate_feed_job->is_running(),
			'percentage' => $progress,
			'total'      => $processing_count,
			'processed'  => $processed,
			'file'       => get_option( self::FEED_FILE_INFO, null ),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Handles the feed data request.
	 */
	public function handle_feed_request() {

		$feed_file = get_option( self::FEED_FILE_INFO );

		$file_path = $feed_file['location'];

		try {

			// bail early if the feed secret is not included or is not valid
			// if ( Feed::get_feed_secret() !== Framework\SV_WC_Helper::get_requested_value( 'secret' ) ) {
			// 	throw new Framework\SV_WC_Plugin_Exception( 'Invalid feed secret provided.', 401 );
			// }

			// bail early if the file can't be read
			if ( ! is_readable( $file_path ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'File is not readable.', 404 );
			}

			// set the download headers
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length:'. filesize( $file_path ) );

			$file = @fopen( $file_path, 'rb' );

			if ( ! $file ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Could not open feed file.', 500 );
			}

			// fpassthru might be disabled in some hosts (like Flywheel)
			if ( $this->is_fpassthru_disabled() || ! @fpassthru( $file ) ) {

				//\WC_Facebookcommerce_Utils::log( 'fpassthru is disabled: getting file contents' );

				$contents = @stream_get_contents( $file );

				// if ( ! $contents ) {
				// 	throw new Framework\SV_WC_Plugin_Exception( 'Could not get feed file contents.', 500 );
				// }

				echo $contents;
			}

		} catch ( \Exception $exception ) {

			// \WC_Facebookcommerce_Utils::log( 'Could not serve product feed. ' . $exception->getMessage() . ' (' . $exception->getCode() . ')' );

			// status_header( $exception->getCode() );
		}

		exit;
	}

	/**
	 * Checks whether fpassthru has been disabled in PHP.
	 *
	 * Helper method, do not open to public.
	 *
	 * @since 1.11.0
	 *
	 * @return bool
	 */
	private function is_fpassthru_disabled() {

		$disabled = false;

		if ( function_exists( 'ini_get' ) ) {

			$disabled_functions = @ini_get( 'disable_functions' );

			$disabled = is_string( $disabled_functions ) && in_array( 'fpassthru', explode( ',', $disabled_functions ), false );
		}

		return $disabled;
	}

	/**
	 * Gets the URL for retrieving the product feed data.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_data_url() {

		$query_args = [
			'wc-api' => self::REQUEST_FEED_ACTION,
			'secret' => self::get_feed_secret(),
		];

		return add_query_arg( $query_args, home_url( '/' ) );
	}

	/**
	 * Gets the secret value that should be included in the Feed URL.
	 *
	 * Generates a new secret and stores it in the database if no value is set.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_secret() {

		$secret = get_option( self::OPTION_FEED_URL_SECRET, '' );

		if  ( ! $secret ) {

			$secret = wp_hash( 'products-feed-' . time() );

			update_option( self::OPTION_FEED_URL_SECRET, $secret );
		}

		return $secret;
	}


}
