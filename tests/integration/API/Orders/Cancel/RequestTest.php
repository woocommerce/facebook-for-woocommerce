<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders\Cancel;

use SkyVerge\WooCommerce\Facebook\API\Orders\Cancel\Request;

/**
 * Tests the API\Orders\Cancel\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '368508827392800', Request::REASON_CUSTOMER_REQUESTED, false );

		$this->assertEquals( '/368508827392800/cancellations', $request->get_path() );
		$this->assertEquals( 'POST', $request->get_method() );

		$expected_data = [
			'cancel_reason'   => [
				'reason_code' => Request::REASON_CUSTOMER_REQUESTED,
			],
			'restock_items'   => false,
			'idempotency_key' => $request->get_idempotency_key(),
		];
		$this->assertEquals( $expected_data, $request->get_data() );
	}


	/** @see Request::get_rate_limit_id() */
	public function test_get_rate_limit_id() {

		$this->assertEquals( 'pages', Request::get_rate_limit_id() );
	}


}
