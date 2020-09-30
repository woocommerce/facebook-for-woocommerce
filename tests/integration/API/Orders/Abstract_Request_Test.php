<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders;

use SkyVerge\WooCommerce\Facebook\API\Orders\Abstract_Request;

/**
 * Tests the API\Orders\Order class.
 */
class Abstract_Request_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Before each test.
	 *
	 * @throws \SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Exception
	 */
	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** @see Request::get_retry_codes() */
	public function test_get_retry_codes() {

		$request = new class( null, null ) extends Abstract_Request {};

		$this->assertContains( 2361081, $request->get_retry_codes() );
	}


}
