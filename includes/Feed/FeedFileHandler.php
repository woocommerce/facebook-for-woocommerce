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


	public function get_feed_directory() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'facebook_for_woocommerce/';
	}

	/**
	 * Return a filename.
	 *
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( $this->filename );
	}

	/**
	 * Return a filename.
	 *
	 * @return string
	 */
	public function get_temp_filename() {
		return sanitize_file_name( $this->temp_filename );
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	public function get_file_path() {
		// Parent class will write to the temp file first so this is why we are using temp.
		return $this->get_feed_directory() . $this->get_filename();
	}

	/**
	 * Get file path to export to.
	 *
	 * @return string
	 */
	public function get_temporary_file_path() {
		// Parent class will write to the temp file first so this is why we are using temp.
		return $this->get_feed_directory() . $this->get_temp_filename();
	}

	public function prepare_feed_folder() {
		$catalog_feed_directory = trailingslashit( $this->get_feed_directory() );

		if ( ! wp_mkdir_p( $catalog_feed_directory ) ) {
			throw new Error( __( 'Could not create product catalog feed directory', 'facebook-for-woocommerce' ), 500 );
		}

		$files = [
			[
				'base'    => $catalog_feed_directory,
				'file'    => 'index.html',
				'content' => '',
			],
			[
				'base'    => $catalog_feed_directory,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			],
		];

		foreach ( $files as $file ) {

			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {

				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {

					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

	public function create_fresh_feed_temporary_file() {
		if ( file_exists( ( $this->get_temporary_file_path() ) ) ) {
			@unlink( $this->get_temporary_file_path() ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink, Generic.PHP.NoSilencedErrors.Discouraged,
		}
		@file_put_contents( $this->get_temporary_file_path(), ''); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_file_put_contents, Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		@chmod( $this->get_temporary_file_path(), 0664 ); // phpcs:ignore W
	}

	public function write_to_feed_temporary_file( $data ) {
		@file_put_contents( $this->get_temporary_file_path(), $data, FILE_APPEND ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_file_put_contents, Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
	}

	/**
	 * Last step of the feed generation procedure.
	 * We have been writing to the temporary file until now.
	 * We can safely delate old feed file and replace it with
	 * the content of temporary file.
	 */
	public function replace_feed_file_with_temp_file() {
		@rename(
			$this->get_temporary_file_path(),
			$this->get_file_path()
		); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_rename, Generic.PHP.NoSilencedErrors.Discouraged,
	}

}
