<?php

use SkyVerge\WooCommerce\Facebook\Products\Feed;

/**
 * Tests the Feed class.
 */
class FeedTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Products/Feed.php';
	}


	/** Test methods **************************************************************************************************/


	/** @see Feed::get_feed_data_url() */
	public function test_get_feed_data_url() {

		$feed_data_url = Feed::get_feed_data_url();

		$this->assertStringContainsString( sprintf( 'wc-api=%s', Feed::REQUEST_FEED_ACTION ), $feed_data_url );

		$secret = get_option( Feed::OPTION_FEED_URL_SECRET );

		$this->assertStringContainsString( "secret=$secret", $feed_data_url );
	}


	/** @see Feed::get_feed_data_url() */
	public function test_get_feed_data_url_contains_stored_secret() {

		update_option( Feed::OPTION_FEED_URL_SECRET, '123456' );

		$feed_data_url = Feed::get_feed_data_url();

		$this->assertStringContainsString( sprintf( 'wc-api=%s', Feed::REQUEST_FEED_ACTION ), $feed_data_url );
		$this->assertStringContainsString( "secret=123456", $feed_data_url );
	}


}

