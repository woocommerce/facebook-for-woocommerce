<?php

namespace SkyVerge\WooCommerce\Facebook\Feed;

use Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class FeedFileHandler
 *
 * @since 2.5.0
 */
class FeedFileHandler {

	const OPTION_FEED_URL_SECRET = 'wc_facebook_feed_url_secret';
	const REQUEST_FEED_ACTION    = 'facebook_for_woocommerce_get_feed';

	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	public $filename = 'product_catalog.csv';

	/**
	 * Temporary export file.
	 *
	 * @var string
	 */
	public $temp_filename = 'temp_product_catalog.csv';

	/**
	 * Location of feed CSV files.
	 *
	 * @since 2.5.0
	 */
	public function get_feed_directory() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'facebook_for_woocommerce/';
	}

	/**
	 * Return the CSV feed filename.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( $this->filename );
	}

	/**
	 * Return a filename.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_temp_filename() {
		return sanitize_file_name( $this->temp_filename );
	}

	/**
	 * Get file path to export to.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_file_path() {
		// Parent class will write to the temp file first so this is why we are using temp.
		return $this->get_feed_directory() . $this->get_filename();
	}

	/**
	 * Get file path to export to.
	 *
	 * @since 2.5.0
	 * @return string
	 */
	public function get_temporary_file_path() {
		// Parent class will write to the temp file first so this is why we are using temp.
		return $this->get_feed_directory() . $this->get_temp_filename();
	}

	/**
	 * Setup feed location folder.
	 * Prevent unauthorized access.
	 *
	 * @since 2.5.0
	 * @throws Error Folder creation not possible.
	 */
	public function prepare_feed_folder() {
		$catalog_feed_directory = trailingslashit( $this->get_feed_directory() );

		if ( ! wp_mkdir_p( $catalog_feed_directory ) ) {
			throw new Error( __( 'Could not create product catalog feed directory', 'facebook-for-woocommerce' ), 500 );
		}

		$files = array(
			array(
				'base'    => $catalog_feed_directory,
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base'    => $catalog_feed_directory,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	/**
	 * Setup empty CSV file for temporary output.
	 * Remove old file, create a new one, set appropriate permissions.
	 *
	 * @since 2.5.0
	 */
	public function create_fresh_feed_temporary_file() {
		if ( file_exists( ( $this->get_temporary_file_path() ) ) ) {
			unlink( $this->get_temporary_file_path() );
		}
		file_put_contents( $this->get_temporary_file_path(), '' );
		chmod( $this->get_temporary_file_path(), 0664 );
	}

	/**
	 * Write data to temporary CSV file.
	 *
	 * @since 2.5.0
	 * @param string $data Data to write to the file.
	 */
	public function write_to_feed_temporary_file( $data ) {
		file_put_contents( $this->get_temporary_file_path(), $data, FILE_APPEND );
	}

	/**
	 * Last step of the feed generation procedure.
	 * We have been writing to the temporary file until now.
	 * We can safely delate old feed file and replace it with
	 * the content of temporary file.
	 *
	 * @since 2.5.0
	 */
	public function replace_feed_file_with_temp_file() {
		rename( // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_rename, Generic.PHP.NoSilencedErrors.Discouraged,
			$this->get_temporary_file_path(),
			$this->get_file_path()
		);
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

		if ( ! $secret ) {

			$secret = wp_hash( 'products-feed-' . time() );

			update_option( self::OPTION_FEED_URL_SECRET, $secret );
		}

		return $secret;
	}

	/**
	 * Gets the URL for retrieving the product feed data.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public static function get_feed_data_url() {

		$query_args = array(
			'wc-api' => self::REQUEST_FEED_ACTION,
			'secret' => self::get_feed_secret(),
		);

		return add_query_arg( $query_args, home_url( '/' ) );
	}
}
