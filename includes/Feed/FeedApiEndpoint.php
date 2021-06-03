<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

use SkyVerge\WooCommerce\Facebook\Feed\FeedFileHandler;
use Error;

defined( 'ABSPATH' ) || exit;
/**
 *  A class responsible for responding to Facebook feed file requests.
 *
 * @since 2.6.0
 */
class FeedApiEndpoint {

	/**
	 * Feed file handling and manipulation class.
	 *
	 * @var FeedFileHandler
	 */
	protected $feed_file_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->feed_file_handler = new FeedFileHandler();

		/**
		 * Add action to handle frontend request endpoint.
		 *
		 * @see \WC_API::handle_api_requests
		 */
		add_action( 'woocommerce_api_' . $this->feed_file_handler::REQUEST_FEED_ACTION, array( $this, 'handle_feed_request' ) );
	}

	/**
	 * Handles the feed data request.
	 *
	 * @since 2.6.0
	 * @throws Error Feed request not possible.
	 */
	public function handle_feed_request() {
		facebook_for_woocommerce()->get_tracker()->track_feed_file_requested();

		$file_path = $this->feed_file_handler->get_file_path();

		try {
			// Bail early if the feed secret is not included or is not valid.
			if ( ! isset( $_GET['secret'] ) || $_GET['secret'] !== $this->feed_file_handler->get_feed_secret() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				throw new Error( 'Invalid feed secret provided.', 401 );
			}

			// Bail early if the file can't be read.
			if ( ! is_readable( $file_path ) ) {
				throw new Error( 'File is not readable.', 404 );
			}

			// Set the download headers.
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length:' . filesize( $file_path ) );

			readfile( $file_path );

		} catch ( \Exception $exception ) {

			\WC_Facebookcommerce_Utils::log( 'Could not serve product feed. ' . $exception->getMessage() . ' (' . $exception->getCode() . ')' );

			status_header( $exception->getCode() );
		}

		exit;
	}

}
