<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

use SkyVerge\WooCommerce\PluginFramework\v5_10_0 as Framework;


defined( 'ABSPATH' ) || exit;
/**
 * Class FeedFileUploadHandler
 *
 * @since 2.5.0
 */
class FeedFileUploadHandler {

	/**
	 * Feed file handling and manipulation class.
	 *
	 * @var FeedFileHandler
	 */
	protected $feed_file_handler;


	/**
	 * Singleton instance.
	 *
	 * @var FeedFileUploadHandler
	 */
	protected static $instance;

	/**
	 * Constructor.
	 *
	 * @param FeedFileHandler $feed_file_handler Feed file handling.
	 */
	public function __construct( $feed_file_handler ) {
		$this->feed_file_handler = $feed_file_handler;
		add_action( 'woocommerce_api_' . $feed_file_handler::REQUEST_FEED_ACTION, array( $this, 'handle_feed_request' ) );
	}

	/**
	 * Initialize the class.
	 *
	 * @since 2.6.0
	 * @param FeedFileHandler $feed_file_handler Feed file handling.
	 * @return void
	 */
	public static function init( $feed_file_handler ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $feed_file_handler );
		}
	}

	/**
	 * Handles the feed data request.
	 */
	public function handle_feed_request() {

		$file_path = $this->feed_file_handler->get_file_path();

		try {

			// Bail early if the feed secret is not included or is not valid.
			if ( $this->feed_file_handler->get_feed_secret() !== Framework\SV_WC_Helper::get_requested_value( 'secret' ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Invalid feed secret provided.', 401 );
			}

			// Bail early if the file can't be read.
			if ( ! is_readable( $file_path ) ) {
				throw new Framework\SV_WC_Plugin_Exception( 'File is not readable.', 404 );
			}

			// Set the download headers.
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length:' . filesize( $file_path ) );

			$file = @fopen( $file_path, 'rb' );

			if ( ! $file ) {
				throw new Framework\SV_WC_Plugin_Exception( 'Could not open feed file.', 500 );
			}

			// fpassthru might be disabled in some hosts (like Flywheel).
			if ( $this->is_fpassthru_disabled() || ! @fpassthru( $file ) ) {

				\WC_Facebookcommerce_Utils::log( 'fpassthru is disabled: getting file contents' );

				$contents = @stream_get_contents( $file );

				if ( ! $contents ) {
					throw new Framework\SV_WC_Plugin_Exception( 'Could not get feed file contents.', 500 );
				}

				echo $contents; // phpcs::ignore WordPress.Security.EscapeOutput.OutputNotEscaped .
			}

		} catch ( \Exception $exception ) {

			\WC_Facebookcommerce_Utils::log( 'Could not serve product feed. ' . $exception->getMessage() . ' (' . $exception->getCode() . ')' );

			status_header( $exception->getCode() );
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

}
